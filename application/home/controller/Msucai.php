<?php
namespace app\home\controller;
use app\home\controller\Member;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use think\Image;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;
use app\model\materialuprecordModel;
use app\model\materialuprecordpicModel;

/**
 * 会员中心-素材管理
 */
class Msucai extends Member
{
	public function _initialize() {
		parent::_initialize ();
		$this->sucaiList = Session::get('sucaiList');
		$this->assign('url_sucaiList', $this->sucaiList);
		//素材分类
		$catelist = readCacheFile("materialcate");
		$this->assign('catelist', $catelist);
		//判断是否认证
    	$renzobj = $this->is_member_auth();
		if(empty($renzobj)){
			$this->error('未认证，前往会员中心认证！',Url('home/Member/memberauth'));
		}
		//判断会员认证身份
		if($this->member['auth_cate']!=4){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
	}
	
	public function index(){
		$this->redirect(Url('/home/msucai/material'));
	}
	
	/**
	 * 素材列表
	 */
	public function material($is_chk='',$find=''){
		session('sucaiList',Request::instance()->url());
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//数量统计
		$tongji = array();
		$all = materialModel::getOneByWhere(array('is_lock'=>1,'uid'=>$this->uid),array('count(1) num')); //全部
		$chka = materialModel::getOneByWhere(array('is_lock'=>1,'uid'=>$this->uid,'is_chk'=>1),array('count(1) num')); //is_chk=1
		$chkb = materialModel::getOneByWhere(array('is_lock'=>1,'uid'=>$this->uid,'is_chk'=>2),array('count(1) num')); //is_chk=2
		$chkc = materialModel::getOneByWhere(array('is_lock'=>1,'uid'=>$this->uid,'is_chk'=>3),array('count(1) num')); //is_chk=3
		$tongji['allnum'] = $all['num'];
		$tongji['chkanum'] = $chka['num'];
		$tongji['chkbnum'] = $chkb['num'];
		$tongji['chkcnum'] = $chkc['num'];
		$this->assign('tongji', $tongji);
		//列表
		$where = array('is_lock'=>1,'uid'=>$this->uid);
// 		$is_chk = "";
// 		if(!empty($find)){
// 			$arr = explode("_",$find);
// 			$is_chk = $arr[0];
// 		}
		if(in_array($is_chk,array('1','2','3'))){
			$where['is_chk']=$is_chk;
		}else{
			$is_chk = "";
		}
		if(!empty($find)) $where['title'] = array('like','%'.htmlspecialchars($find).'%');
		$list = materialModel::getList($where,[],'10');
		$this->assign('list', $list);
		$this->assign('is_chk', $is_chk); //显示标签
		$this->assign('find', htmlspecialchars($find));
		return $this->fetch();
	}
	
	/**
	 * 素材上传
	 */
	public function materialadd(){
		//素材专题
		$topiclist = materialtopicModel::getListByWhere(array('is_lock'=>1),array('id','title'));
		$this->assign('topiclist', $topiclist);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['cate'] = $post['cate'];
			$rule = $this->getsucaifileinfo($data['uid'],$data['cate']);
			if($rule['is_error']==1){ //如果出错，重新请求一次
				$rule = $this->getsucaifileinfo($data['uid'],$data['cate']);
			}
			$data['metadata'] = $rule['metadata'];
			$data['cno'] = $rule['cno'];
			$data['batch'] = $rule['batch'];
			$data['filename'] = $rule['filename'];
			$data['title'] = $post['title'];
			$data['topic_str'] = $post['topic_str'];
			$data['supply_tit'] = $post['supply_tit'];
			$data['author'] = $post['author'];
			$data['keywords'] = $post['keywords'];
			$data['themewords'] = $post['themewords'];
			$data['source'] = $post['source'];
			$data['version'] = $post['version'];
			$data['originaltime'] = $post['originaltime'];
			$data['makingtime'] = $post['makingtime'];
			$data['roomarea'] = $post['roomarea'];
			$data['desc'] = $post['desc'];
			$data['input_uid'] = $data['uid'];
			$id = materialModel::add_data($data);
			if($id>0){
				$this->success('添加成功',Url('home/Msucai/materialinfo','id='.$id));
			}else{
				$this->error('添加失败！','home/Msucai/materialadd');
			}
		}
		return $this->fetch();
	}
	//添加素材地址路径相关信息生成
	public function getsucaifileinfo($uid,$cid){
		$data = array();
		$m_auth = memberauthModel::getOneByWhere(array('uid'=>$uid));
		$data['leaguer_no'] = $m_auth['leaguer_no'];
		$mc_list = readCacheFile('materialcate');
		$catelist = $mc_list[$cid];
		if(empty($catelist)){
			$catelist = materialcateModel::getOneByWhere(array('id'=>$cid));
		}
		$data['cno'] = $catelist['cno'];
		$data['batch'] = '000';
		$matwhere = array('uid'=>$uid,'batch'=>$data['batch']);
		$matlist = materialModel::getOneByWhere($matwhere,array('count(1) num'));
		if($matlist['num'] < 1){
			$matlist['num'] = 1;
		}else{
			$matlist['num'] = $matlist['num']+1;
		}
		$data['filename'] = substr("00000".$matlist['num'],-5);
		$data['metadata'] = $data['leaguer_no'].$data['cno'].date("ym",time()).$data['batch'].$data['filename'];
		$data['is_error'] = 0;
		if(empty($m_auth) || empty($catelist) || empty($matlist)){
			$data['is_error'] = 1;
		}
		return $data;
	}
	
	/**
	 * 素材编辑
	 */
	public function materialupd($id){
		$detail = materialModel::getByid($id);
		$this->assign('detail',$detail);
		//素材专题
		$topiclist = materialtopicModel::getListByWhere(array('is_lock'=>1),array('id','title'));
		$topiclist = convert_arr_key($topiclist,'id');
		$topic_str = array();
		$topic_arr = array();
		if(!empty($detail['topic_str'])){
			$topic_arr = explode(",",$detail['topic_str']);
			foreach ($topic_arr as $key=>$val){
				$topic_str[$val] = $topiclist[$val]['title'];
			}
		}
		$this->assign('topic_str', implode(",",$topic_str));
		$this->assign('topic_arr', $topic_arr);
		$this->assign('topiclist', $topiclist);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['title'] = $post['title'];
			$data['topic_str'] = $post['topic_str'];
			$data['supply_tit'] = $post['supply_tit'];
			$data['author'] = $post['author'];
			$data['keywords'] = $post['keywords'];
			$data['themewords'] = $post['themewords'];
			$data['source'] = $post['source'];
			$data['version'] = $post['version'];
			$data['originaltime'] = $post['originaltime'];
			$data['makingtime'] = $post['makingtime'];
			$data['roomarea'] = $post['roomarea'];
			$data['desc'] = $post['desc'];
			$data['cover'] = basename($post['cover']);
			$rs = materialModel::upd_data(array('id'=>$id),$data);
			if($rs>0){
				$this->success('编辑成功',Url('home/Msucai/material'));
			}else{
				$this->error('编辑失败！',Url('home/Msucai/materialupd','id='.$id));
			}
		}
		return $this->fetch();
	}
	
	/**
	 * 下载积分设置
	 */
	public function scoreset(){
		$id = Request::instance()->post("id");
		$score = Request::instance()->post("score");
		$rs = 0;
		if($id>0 && $score>=0){
			$rs = materialModel::upd_data(['id'=>$id],['score'=>$score]);
		}
		echo json_encode($rs);
	}
	
	/**
	 * 删除
	 */
	public function materialdel($id){
		$rs = materialModel::upd_data(array('id'=>$id),array('is_del'=>2));
		if($rs){
			$this->success('删除成功',$this->sucaiList);
		}else{
			$this->error('删除失败！',$this->sucaiList);
		}
	}
	
	/**
	 * 素材详情
	 */
	public function materialinfo($id){
		$detail = materialModel::getByid($id);
		//素材专题
		$topiclist = materialtopicModel::getListByWhere(array('is_lock'=>1),array('id','title'));
		$topiclist = convert_arr_key($topiclist,'id');
		$topic_str = array();
		if(!empty($detail['topic_str'])){
			$topic_arr = explode(",",$detail['topic_str']);
			foreach ($topic_arr as $key=>$val){
				$topic_str[$val] = $topiclist[$val]['title'];
			}
		}
		$this->assign('topic_str', implode(",",$topic_str));
		//
		$detail['is_filepath'] = 0;
		if(!empty($detail['filepath'])){
			$filepath = ROOT_PATH.$detail['filepath'];
			if(file_exists($filepath)) $detail['is_filepath']=1;
		}
		$this->assign('detail',$detail);
		return $this->fetch();
	}
	
	/**
	 * 确认发布-原文件转jpg生成水印图缩略图
	 */
	public function matimgmaking($id){
		$detail = materialModel::getByid($id);
		if(!empty($detail['filepath']) && file_exists(ROOT_PATH.$detail['filepath'])){
			//原图片文件转jpg
			$tojpgpath = "";
			$is_pic = false;
			if(empty($detail['pic'])){
				$tojpgpath = helper::createDirOfNewImg(ROOT_PATH.UPLOADS_PATH, "jpg"); //生成jpg图片路径
			}else{
				$pic = helper::getPicPath($detail['pic']);
				$tojpgpath = ROOT_PATH.$pic;
				if(file_exists(!empty($detail['pic']) && $tojpgpath)){
					$is_pic = true;
				}else{
					$tojpgpath = helper::createDirOfNewImg(ROOT_PATH.UPLOADS_PATH, "jpg");
				}
			}
			if(!$is_pic){
				$rs = helper::setimgformat(ROOT_PATH.$detail['filepath'],$tojpgpath); //原图片文件转jpg
			}
			//生成缩略图（300*300）
			$suoluetu = "";
			$is_cover = false;
			if(empty($detail['cover'])){
				$suoluetu = helper::createDirOfNewImg(ROOT_PATH.UPLOADS_PATH, "jpg"); //缩略图物理地址
			}else{
				$pic = helper::getPicPath($detail['cover']);
				$suoluetu = ROOT_PATH.$pic;
				if(file_exists(!empty($detail['cover']) && $suoluetu)){
					$is_cover = true;
				}else{
					$suoluetu = helper::createDirOfNewImg(ROOT_PATH.UPLOADS_PATH, "jpg");
				}
			}
			if(!$is_cover){
				$image = Image::open($tojpgpath);
				$image->thumb(300, 300)->save($suoluetu);
			}
			//生成水印图
			$shuiyin = "";
			$is_pic_water = false;
			if(empty($detail['pic_water'])){
				$shuiyin = helper::createDirOfNewImg(ROOT_PATH.UPLOADS_PATH, "jpg");
			}else{
				$pic = helper::getPicPath($detail['pic_water']);
				$shuiyin = ROOT_PATH.$pic;
				if(file_exists(!empty($detail['pic_water']) && $shuiyin)){
					$is_pic_water = true;
				}else{
					$shuiyin = helper::createDirOfNewImg(ROOT_PATH.UPLOADS_PATH, "jpg");
				}
			}
			if(!$is_pic_water){
				$syssettings = readCacheFile("syssettings");
				if($syssettings['iswater']!=2) $syssettings['watertext']='';
				$image = Image::open($tojpgpath);
				$image->text($syssettings['watertext'],'fzhzgb.ttf',24,'#383838')->save($shuiyin); //生成水印图
			}
			//获取图片文件信息
			$imginfo = helper::getimginfo(ROOT_PATH.$detail['filepath']);
			$data = array();
			$data['pic'] = basename($tojpgpath);
			$data['pic_water'] = basename($shuiyin);
			$data['cover'] = basename($suoluetu);
			$data['ext'] = empty($detail['ext'])?$imginfo['ext']:$detail['ext'];
			$data['width'] = $imginfo['width'];
			$data['height'] = $imginfo['height'];
			$data['size'] = $imginfo['size'];
			$data['colorspace'] = $imginfo['colorspace'];
			$data['pixel'] = $imginfo['pixel']['x'];
			$rs = materialModel::upd_data(array('id'=>$id),$data);
		}else{
			$rs = false;
		}
		echo json_encode($rs);
	}
	
	/**
	 * 批量导入记录
	 */
	public function upmatallrecords(){
		$list = materialuprecordModel::getList(['uid'=>$this->uid]);
		$this->assign('list', $list);
		return $this->fetch();
	}
	
	/**
	 * 批量上传-excel导入
	 */
	public function upmatall(){
		vendor("PHPExcelClass.PHPExcel");
		$objExcel = new \PHPExcel();
		//获取表单上传文件
		$file = request()->file("excel");
		if(!empty($file)){
			$excelpath = UPLOADS_PATH.'excel';
			if (!file_exists($excelpath)) { //如果没有这个文件夹
				@mkdir($excelpath."/", 0777); //创建这个文件夹
			}
			$excelpath.= "/".$this->uid;
			if (!file_exists($excelpath)) { //如果没有这个文件夹
				@mkdir($excelpath."/", 0777); //创建这个文件夹
			}
			$excelpath.= "/".date("Ym",time());
			if (!file_exists($excelpath)) { //如果没有这个文件夹
				@mkdir($excelpath."/", 0777); //创建这个文件夹
			}
			$excelpath.= "/";
			$oldname = $file->getInfo('name');
			$ext = pathinfo($file->getInfo('name'))['extension'];
			$filename = date("Y").date("m").date("d").uniqid().".".$ext;
			$info = $file->validate(['ext'=>'xlsx,xls,csv'])->move(ROOT_PATH.$excelpath,$filename);
			if($info){
				$filename = $info->getSaveName();
				$filepath = $excelpath.$filename; //excel地址
				$data['uid'] = $this->uid;
				$data['filename'] = $oldname;
				$data['filepath'] = $filepath;
				$data['ext'] = $ext;
				$data['input_uid'] = $data['uid'];
				$id = materialuprecordModel::add_data($data);
				if($id>0){
					$this->redirect(Url('home/Msucai/upmatallinfo','id='.$id));
				}else{
					$this->error('添加失败！','home/Msucai/upmatall');
				}
			}else{
				// 上传失败获取错误信息
				echo $file->getError();
			}
		}
		return $this->fetch();
	}
	
	/**
	 * 批量导入详细
	 */
	public function upmatallinfo($id){
		$info = materialuprecordModel::getByid($id);
		$info['is_file'] = 0;
		$filepath = ROOT_PATH.$info['filepath'];
		if(!empty($info['filepath']) && file_exists($filepath)){
			$info['is_file'] = 1;
		}
		$this->assign("info", $info);
		$list = materialModel::getListByWhere(array('mu_id'=>$id),[],'filename asc,id desc');
		if(!empty($list)){
			$fnamearr = convert_arr_key($list,'id','filename');
			$fnnum_arr = array_count_values($fnamearr); //统计元素个数['001'=>9,'002'=>3]
			$this->assign('fnnum_arr', $fnnum_arr);
		}
		$this->assign('list', $list);
		$cygpath = ""; //成员馆目录名称，如：NLC
		$m_auth = memberauthModel::getOneByWhere(array('uid'=>$this->uid));
		if(!empty($m_auth['leaguer_no'])){
			$cygpath = $m_auth['leaguer_no'];
		}
		if($info['is_daoru']!=2){
		  $cnoarr = readCacheFile("cnoarr"); //cno-id
		  $topicarr = readCacheFile("topicarr"); //id-title
		  //读取excel返回数组
// 		  $excel_array = helper::readExcel($filepath);
		  $excel_array = helper::readspreadSheet($filepath);
		  if(!empty($excel_array)){
			array_shift($excel_array);  //删除第一个数组(文件标题);
			array_shift($excel_array);  //删除第二个数组(标题);
			array_shift($excel_array);  //删除第二个数组(标题说明);
			$data = [];
			$i=0;
			$batcharr = [];
			foreach($excel_array as $k=>$v) {
				$v = array_filter($v);
				if(!empty($v)){
					$data[$k]['mu_id'] = $id;
					$data[$k]['eno'] = isset($v[0])?$v[0]:'';
					$data[$k]['metadata'] = isset($v[1])?$v[1]:'';
					$data[$k]['title'] = isset($v[2])?$v[2]:'';
					$data[$k]['supply_tit'] = isset($v[3])?$v[3]:'';
					$data[$k]['topic_str'] = isset($v[4])?$v[4]:'';
					$data[$k]['author'] = isset($v[6])?$v[6]:'';
					$data[$k]['author_info'] = isset($v[7])?$v[7]:'';
					$data[$k]['desc'] = isset($v[8])?$v[8]:'';
					$data[$k]['themewords'] = isset($v[9])?$v[9]:'';
					$data[$k]['keywords'] = isset($v[10])?$v[10]:'';
					$data[$k]['source'] = isset($v[11])?$v[11]:'';
					$data[$k]['version'] = isset($v[12])?$v[12]:'';
					$data[$k]['originaltime'] = isset($v[13])?$v[13]:'';
					$data[$k]['makingtime'] = isset($v[14])?$v[14]:'';
					$data[$k]['resourcecate'] = isset($v[15])?$v[15]:'';
					$data[$k]['filename'] = isset($v[16])?$v[16]:'';
					$data[$k]['ext'] = isset($v[17])?$v[17]:'';
					
					$cate = substr($v[1],-16,4);
					$data[$k]['cate'] = isset($cnoarr[$cate])?$cnoarr[$cate]:0;
					$data[$k]['cno'] = $cate;
// 					$data[$k]['roomarea'] = isset($v[14])?$v[14]:'';
// 					$data[$k]['contain'] = isset($v[16])?$v[16]:'';
					
					$data[$k]['uid'] = $this->uid;
					$data[$k]['batch'] = substr($v[1],-8,3);
					$data[$k]['filename'] = '00'.substr($v[1],-5,5);
					$data[$k]['input_uid'] = $data[$k]['uid'];
					$data[$k]['input_time'] = time();
					
					$imgpath = "/".UPLOADS_PATH.'sucai/'.$cygpath.'/'.$data[$k]['batch'].'/'
							.$data[$k]['filename'].'.'.$data[$k]['ext'];
					$data[$k]['filepath'] = $imgpath;
					$batcharr[$i] = $data[$k]['batch'];
					$i++;
				}
			}
			$suc_num = Db::name('material')->insertAll($data); //批量插入数据
			$err_num = $i-$suc_num;
			if($i>0){
				$batch_arr = array_count_values($batcharr); //统计元素个数['001'=>9,'002'=>3]
				$key_batch = current(array_flip($batch_arr)); //获得数组第一个元素的值(数组键值互换)
				$info_str = '';
				foreach ($batch_arr as $key=>$val){
					$info_str.= "批次".$key."出现".$val."行；";
				}
				$record_arr = [];
				$record_arr['batch'] = $key_batch;
				$record_arr['filenum'] = $i;
				$record_arr['filenum_suc'] = $suc_num;
				$record_arr['filenum_err'] = $err_num;
				$record_arr['is_daoru'] = 2;
				$record_arr['info'] = $info_str;
				$rs = materialuprecordModel::upd_data(array('id'=>$id),$record_arr);
				$this->success('Excel导入数据成功。',Url('home/Msucai/upmatallinfo','id='.$id));
			}
		  }
		}
		return $this->fetch();
	}
	
	/**
	 * 批量生成缩略图水印图
	 */
	public function upmatallimgmaking(){
    	$id = Request::instance()->post("id");
		$matlist = materialModel::getListByWhere(['mu_id'=>$id]);
		$matidarr = convert_arr_key($matlist,'id');
		$matidarr = array_column($matidarr,'id');
		echo json_encode($matidarr);
	}
	
}

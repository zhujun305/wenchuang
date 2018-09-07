<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Cache;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialuprecordModel;
use app\model\materialuprecordpicModel;

/**
 * ajax异步
 */
class Aajax extends Homebase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
	}
	
    /**
     * 上传头像logo
     */
    public function ajaxupimages(){
    	$imgbase64 = Request::instance()->post("imgsource");
    	$type = Request::instance()->post("type");
    	$img_wjz = "";
    	if($type == 'logo'){
    		$img_wjz = "memberlogo";
    	}elseif($type == 'auth'){
    		$img_wjz = "memberauth";
    	}else{
    		
    	}
    	$path = ROOT_PATH.UPLOADS_PATH.$img_wjz."/";
    	$rst = helper::base64_image_content($imgbase64, $path, "jpg");
    	$rst = basename($rst);
    	$imgpath = helper::getImgURL($rst, $img_wjz, '');
    	$data = array("imgurl"=>$imgpath);
		echo json_encode($data);
    }
    
    /**
     * 上传文件（单文件上传）
     */
	public function ajaxupfiles(Request $request){
		$files = $request->file('file');
    	$type = $request->post("type");
    	$filemenu = "";
    	if($type == 'zipfile'){
    		$filemenu = "zipfile";
    	}elseif($type == 'docfile'){
    		$filemenu = "docfile";
    	}else{
    		
    	}
		$path = ROOT_PATH.UPLOADS_PATH.$filemenu.'/';
    	$max_size = 50*1024*1024; //允许上传文件最大大小限制50M
		$ext_arr = Config::get('config.ext_arr'); //允许上传的文件扩展名
		$ext_str = implode(",", $ext_arr['file']);
		$src_arr = array();
		$src_arr['ext_str'] = $ext_str;
		if($files){
			//创建新文件名称路径
			$newFilePath = helper::createDirOfMenu($path);
			$newFileName = date('Y').date('m').date('d').uniqid();
			//获取网站根目录文件地址
			$webpath = substr($newFilePath, strlen(ROOT_PATH));
			//移动到网站根目录/uploads/zipfile/ 目录下
			$filename = false;
			if(!empty($newFileName)){
				$filename = array('filename'=>$newFileName);
			}
			$info = $files->validate(['size'=>$max_size,'ext'=>$ext_str])->move($newFilePath,$filename);
			if($info){
				//成功上传后 获取上传信息
				$src_arr['src'] = $info->getFilename(); // 输出 1001.jpg 文件名
				$src_arr['filepath'] = $webpath.$info->getFilename();
			}else{
				// 上传失败获取错误信息
				$src_arr['error'] = $info->getError();
			}
		}else{
		}
    	$data = array('code'=>0,'msg'=>'','data'=>$src_arr);
    	echo json_encode($data);
	}
    
	/**
	 * kindeditor编辑器上传图片
	 */
	public function kindimage(){
		//无上传文件时
		if (empty($_FILES) === true) $this->alert("没有上传文件。");
		//PHP上传失败
		if (!empty($_FILES['imgFile']['error'])) {
			$error = '';
			switch($_FILES['imgFile']['error']){
				case '1':
					$error = '超过php.ini允许的大小。';
					break;
				case '2':
					$error = '超过表单允许的大小。';
					break;
				case '3':
					$error = '图片只有部分被上传。';
					break;
				case '4':
					$error = '请选择图片。';
					break;
				case '6':
					$error = '找不到临时文件夹。';
					break;
				case '7':
					$error = '写文件到硬盘出错。';
					break;
				case '8':
					$error = 'File upload stopped by extension。';
					break;
				default:
					$error = '未知错误。';
			}
			$this->alert($error);
		}
		//服务器保存文件路径（物理地址）/uploads
		$uploadPath = ROOT_PATH.UPLOADS_PATH;
		//临时文件路径
		$tmpFilePath = $_FILES ['imgFile'] ['tmp_name'];
		//本地文件路径
		$oldFileName = $_FILES ['imgFile'] ['name'];
		//文件后缀（字母转小写）
		$oldFileExt = strtolower( pathinfo ( $oldFileName, PATHINFO_EXTENSION ) );
		//文件大小
		$file_size = $_FILES['imgFile']['size'];
		//获取配置文件定义的文件大小、扩展名限制
		$max_size = Config::get('config.file_size'); //最大文件大小100M
		$ext_arr = Config::get('config.ext_arr'); //允许上传的文件扩展名
		//检查文件名
		if (!$oldFileName) $this->alert("请选择文件。");
		//检查目录
		if (@is_dir($uploadPath) === false) $this->alert("上传目录不存在。");
		//检查目录写权限
		if (@is_writable($uploadPath) === false) $this->alert("上传目录没有写权限。");
		//检查是否已上传（临时文件）
		if (@is_uploaded_file($tmpFilePath) === false) $this->alert("上传失败。");
		//检查文件大小
		if ($file_size > $max_size){
			$file_size_str = round($file_size/1024/1024,2)."MB";
			$max_size_str = round($max_size/1024/1024,2)."MB";
			$this->alert("上传文件大小".$file_size_str."，超过".$max_size_str."限制。");
		}
		//检查目录名
		$dir_name = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
		if (empty($ext_arr[$dir_name])) $this->alert("目录名不正确。");
		//检查上传文件扩展名是否合法
		if(!in_array($oldFileExt,$ext_arr['image'])) $this->alert('只能上传（'.implode(",", $ext_arr[$dir_name]).'格式）图片！');
		//创建新文件名称路径
		$newFileName = helper::createDirOfNewImg($uploadPath, $oldFileExt);
		//获取网站根目录文件地址
		$webNewFileName = substr($newFileName, strlen(ROOT_PATH));
		if (move_uploaded_file($tmpFilePath, $newFileName)) {
			header('Content-type:text/html;charset=UTF-8');
			$result = array('error'=>0, 'url'=>'/'.$webNewFileName);
			echo json_encode($result);
		}else{
			$this->alert("上传文件失败。");
		}
	}
	function alert($msg) {
		header('Content-type:text/html;charset=UTF-8');
		echo json_encode(array('error' => 1, 'message' => $msg));
		exit;
	}
    
    /**
     * 上传素材文件（单文件上传）
     */
	public function ajaxupscfile(Request $request){
		$files = $request->file('file');
		$uid = $request->param('uid');
		$mid = $request->param('mid');
		$matlist = materialModel::getOneByWhere(array('id'=>$mid));
		//判断当前会员的素材上传目录
		$path = ROOT_PATH.UPLOADS_PATH;
		$cygpath = ""; //成员馆目录名称，如：NCL
		$m_auth = memberauthModel::getOneByWhere(array('uid'=>$uid));
		if(!empty($m_auth['leaguer_no'])){
			$cygpath.= $m_auth['leaguer_no'];
		}
		$batch = "000"; //批次文件夹，默认：000（单素材上传）
		if(!empty($matlist['batch'])){
			$batch = $matlist['batch'];
		}
		if(!empty($cygpath) && !empty($batch) && ($uid==$matlist['uid'])){
// 			$path.= $cygpath.'/'.$batch.'/';
			$flujig = 'sucai/'.$cygpath.'/'.$batch;
			$fp_arr = explode("/",$flujig);
			foreach ( $fp_arr as $key => $value ) { //循环输出数组，
				if (! file_exists ( "" . $path . $value . "" )) { //如果没有这个文件夹
					@mkdir ( "" . $path . $value . "/" ); //创建这个文件夹
				}
				$path = $path . $value . "/"; //给$root重新赋值
			}
		}else{
			$msg = ""; //输出错误信息
			if(!($uid>0)) $msg.= "uid不存在。";
			if(!($mid>0)) $msg.= "mid不存在。";
			if(empty($matlist)) $msg.= "素材信息未创建。";
			if($uid != $matlist['uid']) $msg.= "s_uid和m_uid不相同。";
			if(empty($cygpath)) $msg.= "成员馆文件夹不存在。";
			if(empty($batch)) $msg.= "批次文件夹不存在。";
			$data = array('code'=>1,'msg'=>$msg,'data'=>'');
			echo json_encode($data);
			exit(0);
		}
		$imgpath = "/".UPLOADS_PATH.'sucai/'.$cygpath.'/'.$batch.'/';
		$src_arr = array();
		if(count($files)==1){
			//移动到网站根目录/uploads/sucai/ 目录下
			$filename = false;
			if(!empty($matlist['filename'])){
				$filename = array('filename'=>$matlist['filename']);
			}
			$info = $files->move($path,$filename); //参数2为空false使用原文件名称
			if($info){
				//成功上传后 获取上传信息
				$src_arr['src'] = $info->getFilename(); // 输出 1001.jpg 文件名
				$src_arr['filepath'] = $imgpath.$info->getFilename();
				$rst = materialModel::upd_data(array('id'=>$mid),array('filepath'=>$src_arr['filepath'],'ext'=>$info->getExtension()));
				$src_arr['is_upfile'] = $rst;
			}else{
				// 上传失败获取错误信息
				$src_arr['error'] = $info->getError();
			}
		}else{
// 			foreach($files as $key=>$item){
// 				$info = $item->move($path);
// 				if($info){
// 					//成功上传后 获取上传信息
// 					$src_arr[$key]['src'] = $info->getFilename();
// 					$src_arr[$key]['filepath'] = $path.$info->getFilename();
// 				}else{
// 					// 上传失败获取错误信息
// 					$src_arr[$key]['error'] = $info->getError();
// 				}
// 			}
		}
    	$data = array('code'=>0,'msg'=>'','data'=>$src_arr);
    	echo json_encode($data);
	}
    
    /**
     * 上传素材文件（excel批量上传）
     */
	public function ajaxupscfileexcel(Request $request){
		$files = $request->file('file');
		$uid = $request->param('uid');
		$objid = $request->param('objid');
		$objlist = materialuprecordModel::getOneByWhere(array('id'=>$objid));
		//判断当前会员的素材上传目录
		$path = ROOT_PATH.UPLOADS_PATH;
		$cygpath = ""; //成员馆目录名称，如：NCL
		$m_auth = memberauthModel::getOneByWhere(array('uid'=>$uid));
		if(!empty($m_auth['leaguer_no'])){
			$cygpath.= $m_auth['leaguer_no'];
		}
		$batch = "000"; //批次文件夹，默认：000（单素材上传）
		if(!empty($objlist['batch'])){
			$batch = $objlist['batch'];
		}
		//时效图生肖图套图批量上传
		$zhe_filename = "";
		if($objlist['batch'] == '000'){
			$matlist = materialModel::getOneByWhere(array('uid'=>$uid,'batch'=>$objlist['batch']),array('count(1) num'));
			if($matlist['num'] < 1){
				$matlist['num'] = 1;
			}else{
				$matlist['num'] = $matlist['num']+1;
			}
			$zhe_filename = substr("00000".$matlist['num'],-5);
		}
		if(!empty($cygpath) && !empty($batch) && ($uid==$objlist['uid'])){
			$flujig = 'sucai/'.$cygpath.'/'.$batch;
			$fp_arr = explode("/",$flujig);
			foreach ( $fp_arr as $key => $value ) { //循环输出数组，
				if (! file_exists ( "" . $path . $value . "" )) { //如果没有这个文件夹
					@mkdir ( "" . $path . $value . "/" ); //创建这个文件夹
				}
				$path = $path . $value . "/"; //给$root重新赋值
			}
		}else{
			$msg = ""; //输出错误信息
			if(!($uid>0)) $msg.= "uid不存在。";
			if(!($objid>0)) $msg.= "objid不存在。";
			if(empty($objlist)) $msg.= "excel表上传信息未创建。";
			if($uid != $objlist['uid']) $msg.= "s_uid和m_uid不相同。";
			if(empty($cygpath)) $msg.= "成员馆文件夹不存在。";
			if(empty($batch)) $msg.= "批次文件夹不存在。";
			$data = array('code'=>1,'msg'=>$msg,'data'=>'');
			echo json_encode($data);
			exit(0);
		}
		$imgpath = "/".UPLOADS_PATH.'sucai/'.$cygpath.'/'.$batch.'/';
		$src_arr = array();
		if(count($files)==1){
			//移动到网站根目录/uploads/sucai/ 目录下
			$filename = false;
			if(!empty($zhe_filename)){
				$filename = array('filename'=>$zhe_filename);
			}
			$info = $files->move($path,$filename); //参数2为空false使用原文件名称
			if($info){
				//成功上传后 获取上传信息
				$src_arr['src'] = $info->getFilename(); // 输出 1001.jpg 文件名
				$src_arr['filepath'] = $imgpath.$info->getFilename();
				//更新批量上传表图片上传次数
				materialuprecordModel::set_incdec(array('id'=>$objid),'picnum');
				//查询匹配素材表信息
				$fnamearr = explode(".",$src_arr['src']);
				$fname = $fnamearr[0];
				$matitem = materialModel::getOneByWhere(array('mu_id'=>$objid,'filename'=>$fname));
				//生成图片上传记录
				$mu_pic = [];
				$mu_pic['input_uid'] = $objlist['uid'];
				$mu_pic['uprid'] = $objlist['id'];
				$mu_pic['matid'] = $matitem['id'];
				$mu_pic['filename'] = $info->getFilename();
				$mu_pic['filepath'] = $imgpath.$info->getFilename();
				$mupid = materialuprecordpicModel::add_data($mu_pic);
				//更新素材信息，扩展名大小写格式不一致，需要同步
				$matarr = [];
				$matarr['filepath'] = $mu_pic['filepath'];
				$matarr['ext'] = $info->getExtension();
				$rst = materialModel::upd_data(array('id'=>$matitem['id']),$matarr);
				$src_arr['is_upfile'] = $rst;
				$src_arr['is_picnum'] = $objlist['picnum']+1;
			}else{
				// 上传失败获取错误信息
				$src_arr['error'] = $info->getError();
			}
		}else{
// 			foreach($files as $key=>$item){
// 				$info = $item->move($path);
// 				if($info){
// 					//成功上传后 获取上传信息
// 					$src_arr[$key]['src'] = $info->getFilename();
// 					$src_arr[$key]['filepath'] = $path.$info->getFilename();
// 				}else{
// 					// 上传失败获取错误信息
// 					$src_arr[$key]['error'] = $info->getError();
// 				}
// 			}
		}
    	$data = array('code'=>0,'msg'=>'','data'=>$src_arr);
    	echo json_encode($data);
	}
    
    /**
     * 检查成员馆编号是否重复(重复true，不重复false)
     */
    public function chk_leaguer_no(){
    	$leaguer_no = Request::instance()->post("leaguer_no");
		$data = memberauthModel::getOneByWhere(array('leaguer_no'=>$leaguer_no));
		$rst = true;
		if(!empty($data)){
			$rst = false;
		}
		echo json_encode($rst);
    }
	
	/**
	 * 返回素材类型，二级联动
	 */
	public function getmaterialcate(){
		$cate = Request::instance()->post("cate");
		$where = array('is_lock'=>1,'pid'=>$cate);
		$field = array('id','pid','cname','cno');
		$data = materialcateModel::getListByWhere($where,$field);
		echo json_encode($data);
	}
	
}


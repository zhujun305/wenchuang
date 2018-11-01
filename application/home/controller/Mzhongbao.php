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
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\crowdsourcingModel;
use app\model\crowdsourccateModel;
use app\model\crowdsourcwtbModel;
use app\model\csworksModel;
use app\model\msgrecordsModel;

/**
 * 会员中心-众包管理
 */
class Mzhongbao extends Member
{
	public function _initialize() {
		parent::_initialize ();
		$this->mzhongbaoList = Session::get('mzhongbaoList');
		$this->assign('mzhongbaoList', $this->mzhongbaoList);
		$this->mzbworksList = Session::get('mzbworksList');
		$this->assign('mzbworksList', $this->mzbworksList);
		//判断是否认证
    	$renzobj = $this->is_member_auth();
		if(empty($renzobj)){
			$this->error('未认证，前往会员中心认证！',Url('home/Member/memberauth'));
		}
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/home/Mzhongbao/mzhongbao'));
	}
	
	/**
	 * 众包列表：角色（cate=4）
	 */
	public function mzhongbao($find=''){
		session('mzhongbaoList',Request::instance()->url());
		//判断会员认证身份
		if($this->member['auth_cate']!=4){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//列表
		$where = array('is_lock'=>1,'uid'=>$this->uid);
		if(!empty($find)) $where['title'] = $find;
		$list = crowdsourcingModel::getList($where,[],'20');
		$this->assign('list', $list);
		$this->assign('find', htmlspecialchars($find));
		return $this->fetch();
	}
	
	/**
	 * 众包发布：角色（cate=4）
	 */
	public function mzbadd(){
		//判断会员认证身份
		if($this->member['auth_cate']!=4){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $this->uid;
			$data['title'] = $post['title'];
			$data['cate'] = $post['cate'];
			$data['price'] = $post['price'];
			$data['price_max'] = $post['price_max'];
			$data['end_time'] = strtotime($post['end_time']);
			$data['cover'] = basename($post['cover']);
			$data['filename'] = pathinfo($post['filename'],PATHINFO_BASENAME);
			$data['ext'] = pathinfo($post['filename'],PATHINFO_EXTENSION);
			$data['summary'] = $post['summary'];
			$content = $post['content'];
			$data['content'] = $content;
			$rs = crowdsourcingModel::add_data($data);
			$this->success('发布成功。',$this->mzhongbaoList);
		}
		return $this->fetch();
	}
	
	/**
	 * 众包编辑：角色（cate=4）
	 */
	public function mzbupd($id){
		//判断会员认证身份
		if($this->member['auth_cate']!=4){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		$obj = crowdsourcingModel::getByid($id);
		$obj['cover_path'] = getImgURL($obj['cover']);
		$obj['file_path'] = getFileURL($obj['filename'],'zipfile');
		$this->assign('obj',$obj);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $this->uid;
			$data['title'] = $post['title'];
			$data['cate'] = $post['cate'];
			$data['price'] = $post['price'];
			$data['price_max'] = $post['price_max'];
			$data['end_time'] = strtotime($post['end_time']);
			$data['cover'] = basename($post['cover']);
			$data['filename'] = pathinfo($post['filename'],PATHINFO_BASENAME);
			$data['ext'] = pathinfo($post['filename'],PATHINFO_EXTENSION);
			$data['summary'] = $post['summary'];
			$data['content'] = $post['content'];
			$rs = crowdsourcingModel::upd_data(['id'=>$id],$data);
			$this->success('编辑成功。',$this->mzhongbaoList);
		}
		return $this->fetch();
	}
	
	/**
	 * 删除
	 */
	public function mzbdel($id){
		$rs = crowdsourcingModel::upd_data(['id'=>$id],['is_del'=>2]);
		if($rs){
			$this->success('删除成功',$this->mzhongbaoList);
		}else{
			$this->error('删除失败！',$this->mzhongbaoList);
		}
	}
	
	/**
	 * 投稿管理列表
	 */
	public function mzbwtb($id,$type=''){
		//判断会员认证身份
		if($this->member['auth_cate']!=4){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		if(!($id>0)){
			$this->error('请选择项目查看！',$this->mzhongbaoList);
		}
		$this->assign('id', $id);
		$this->assign('type', $type);
		$obj = crowdsourcingModel::getByid($id);
		$this->assign('obj',$obj);
		$fbmember = memberModel::getByid($obj['uid']);
		$this->assign('fbmember',$fbmember);
		//列表
		$where = array('is_lock'=>1);
		if($id>0) $where['cs_id'] = $id;
		$list = crowdsourcwtbModel::getList($where,[],'20');
		$this->assign('list', $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		//稿件状态
		$status_arr = Config::get('config.wtb_status');
		$this->assign('status_arr', $status_arr);
		return $this->fetch();
	}
	
	/**
	 * 投稿详细
	 */
	public function mzbwtbdetail($id){
		//判断会员认证身份
		if($this->member['auth_cate']!=4){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		$obj = crowdsourcwtbModel::getByid($id);
		$this->assign('obj',$obj);
		$fbmember = memberModel::getByid($obj['uid']);
		$this->assign('fbmember',$fbmember);
		//项目信息
		$zbobj = crowdsourcingModel::getByid($obj['cs_id']);
		$this->assign('zbobj',$zbobj);
		//稿件状态
		$status_arr = Config::get('config.wtb_status');
		$this->assign('status_arr', $status_arr);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['comment'] = $post['comment'];
			$rs = crowdsourcwtbModel::upd_data(['id'=>$id],$data);
			$this->success('评论成功。',Url('home/mzhongbao/mzbwtb','id='.$obj['cs_id']));
		}
		return $this->fetch();
	}
	
	/**
	 * 投稿状态设置
	 */
	public function mzbwtbstatus($id,$type='1'){
		$obj = crowdsourcwtbModel::getByid($id);
		if($type==2 || $type==3 || $type==4){
			$rs = crowdsourcwtbModel::upd_data(['id'=>$id],['status'=>$type]);
			if($type==2) $msgstr = "备选";
			if($type==4) $msgstr = "淘汰";
			if($type==3){
				$msgstr = "中标";
				$rs = crowdsourcingModel::upd_data(['id'=>$obj['cs_id']],['wtb_id'=>$id,'wtb_uid'=>$obj['uid']]);
			}
			$mzbcs = crowdsourcingModel::getByid($obj['cs_id']);
			if($obj['uid']>0){
				$type = 1; //类型（1系统、2私信）
				$fuid = 0; //发件人
				$suid = $obj['uid']; //收件人
				$url = Url('home/crowdsourcing/detail','id='.$mzbcs['id']);
				$content = "您投稿的项目【<a href='".$url."'>".$mzbcs['title']."</a>】发布用户已将您的稿件设为".$msgstr;
				$rs = $this->send_msg($type,$fuid,$suid,$content); //type类型，fuid发件人，suid收件人,content内容
			}
		}
		$this->success('设置成功',Url('home/mzhongbao/mzbwtb','id='.$obj['cs_id']));
	}
	
	/**
	 * 给投稿人发私信
	 */
	public function mzbsendmsg(){
		$type = 2; //类型（1系统、2私信）
    	$fuid = Request::instance()->post("fuid");
    	$suid = Request::instance()->post("suid");
    	$csid = Request::instance()->post("csid");
		if($csid>0){
			$obj = crowdsourcingModel::getByid($csid);
			$url = Url('home/crowdsourcing/detail','id='.$obj['id']);
			$content = "您投稿的项目【<a href='".$url."'>".$obj['title']."</a>】发布人给您来发私信：";
		}
    	$content.= Request::instance()->post("content");
		$rs = $this->send_msg($type,$fuid,$suid,$content); //type类型，fuid发件人，suid收件人,content内容
		echo json_encode($rs);
	}
	
	/**
	 * 投稿列表：角色（cate=2、3）
	 */
	public function mzbpartin(){
		//判断会员认证身份
		if($this->member['auth_cate']<2 || $this->member['auth_cate']>3){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		$where = array('is_lock'=>1,'uid'=>$this->uid);
		$list = crowdsourcwtbModel::getList($where,[],'20');
		$this->assign('list', $list);
		//稿件状态
		$status_arr = Config::get('config.wtb_status');
		$this->assign('status_arr', $status_arr);
		return $this->fetch();
	}
	
	/**
	 * 编辑投稿
	 */
	public function mzbwtbupd($id){
		$obj = crowdsourcwtbModel::getByid($id);
		$obj['cover_path'] = getImgURL($obj['cover']);
		$obj['file_path'] = getFileURL($obj['filename'],'zipfile');
		$this->assign('obj',$obj);
		$crowdsc = crowdsourcingModel::getByid($obj['cs_id']);
		$this->assign('crowdsc',$crowdsc);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $this->uid;
			$data['cs_id'] = $id;
			$data['price'] = $post['price'];
			$data['cycle'] = $post['cycle'];
			$data['cover'] = basename($post['cover']);
			$data['filename'] = pathinfo($post['filename'],PATHINFO_BASENAME);
			$content = $post['content'];
			$data['content'] = $content;
			$rs = crowdsourcwtbModel::add_data($data);
			$rs = crowdsourcingModel::upd_data(['id'=>$id],['wtb_num'=>($obj['wtb_num']+1)]);
			$this->success('编辑成功。',Url('home/mzhongbao/mzbpartin'));
		}
		return $this->fetch();
	}
	
	/**
	 * 作品列表：角色（cate=2、3）
	 */
	public function mzbworks($cate=''){
		//判断会员认证身份
		if($this->member['auth_cate']<2 || $this->member['auth_cate']>3){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		session('mzbworksList',Request::instance()->url());
		$this->assign('cate', $cate);
		//列表
		$where = array('is_lock'=>1,'is_chk'=>2,'uid'=>$this->uid);
		if($cate>0) $where['cate'] = $cate;
		$list = csworksModel::getList($where,[],'16','is_settop desc,id desc');
		$this->assign('list', $list);
		return $this->fetch();
	}
	
	/**
	 * 作品上传：角色（cate=2、3）
	 */
	public function mzbworksadd(){
		//判断会员认证身份
		if($this->member['auth_cate']<2 || $this->member['auth_cate']>3){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $this->uid;
			$data['title'] = $post['title'];
			$data['cate'] = $post['cate'];
			$data['desc'] = $post['desc'];
			$data['cover'] = basename($post['cover']);
			$id = csworksModel::add_data($data);
			$picarr = isset($post['picarr'])?$post['picarr']:[];
			$csws = [];
			foreach($picarr as $key=>$val){
				$csws[$key]['cswsid'] = $id;
				$csws[$key]['pic'] = $val;
			}
			if(!empty($csws)){
				$num = Db::name('cs_works_img')->insertAll($csws); //返回插入数量
				$rs = csworksModel::upd_data(['id'=>$id],['pic_um'=>$num]);
			}
			$this->success('上传成功',$this->mzbworksList);
		}
		return $this->fetch();
	}
	
	/**
	 * 作品编辑：角色（cate=2、3）
	 */
	public function mzbworksupd($id){
		//判断会员认证身份
		if($this->member['auth_cate']<2 || $this->member['auth_cate']>3){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		$obj = csworksModel::getByid($id);
		$this->assign('obj',$obj);
		$imglist = Db::table('cs_works_img')->where(['cswsid'=>$id])->select();
		$this->assign('imglist',$imglist);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['title'] = $post['title'];
			$data['cate'] = $post['cate'];
			$data['desc'] = $post['desc'];
			$data['cover'] = basename($post['cover']);
			$rs = csworksModel::upd_data(['id'=>$id],$data);
			$ids = isset($post['ids'])?$post['ids']:[];
			if(!empty($ids)){
				Db::table('cs_works_img')->delete($ids);
			}
			$picarr = isset($post['picarr'])?$post['picarr']:[];
			$csws = [];
			if(!empty($picarr)){
				foreach($picarr as $key=>$val){
					$csws[$key]['cswsid'] = $id;
					$csws[$key]['pic'] = $val;
				}
			}
			if(!empty($csws)){
				$num = Db::name('cs_works_img')->insertAll($csws); //返回插入数量
				$rs = csworksModel::upd_data(['id'=>$id],['pic_um'=>$num]);
			}
			$this->success('编辑成功',$this->mzbworksList);
		}
		return $this->fetch();
	}
	
	/**
	 * 作品推荐
	 */
	public function mzbworkssettop($id,$val){
		if($val==2){
			$val = 2;
		}else{
			$val = 1;
		}
		$rs = csworksModel::upd_data(['id'=>$id],['is_settop'=>$val]);
		if($rs){
			$this->success('推荐成功',$this->mzbworksList);
		}else{
			$this->error('推荐失败！',$this->mzbworksList);
		}
	}
	
	/**
	 * 作品删除
	 */
	public function mzbworksdel($id){
		$rs = csworksModel::upd_data(['id'=>$id],['is_del'=>2]);
		Db::table('cs_works_img')->delete($id);
		if($rs){
			$this->success('删除成功',$this->mzbworksList);
		}else{
			$this->error('删除失败！',$this->mzbworksList);
		}
	}
	
	/**
	 * 图片删除
	 */
	public function mzbworkspicdel($id){
		$rs = Db::table('cs_works_img')->delete($id);
		if($rs){
			$this->success('删除成功',$this->mzbworksList);
		}else{
			$this->error('删除失败！',$this->mzbworksList);
		}
	}
	
}

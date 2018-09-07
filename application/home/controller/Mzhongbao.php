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

/**
 * 会员中心-众包管理
 */
class Mzhongbao extends Member
{
	public function _initialize() {
		parent::_initialize ();
		$this->mzhongbaoList = Session::get('mzhongbaoList');
		$this->assign('mzhongbaoList', $this->mzhongbaoList);
		//判断是否认证
    	$renzobj = $this->is_member_auth();
		if(empty($renzobj)){
			$this->error('未认证，前往会员中心认证！',Url('home/Member/memberauth'));
		}
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
		$where = array('is_lock'=>1,);
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
		//众包分类
		$catelist = crowdsourccateModel::getListByWhere(['is_lock'=>1],[],'sort desc,id asc');
		$this->assign('catelist', $catelist);
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
		//众包分类
		$catelist = crowdsourccateModel::getListByWhere(['is_lock'=>1],[],'sort desc,id asc');
		$this->assign('catelist', $catelist);
		$obj = crowdsourcingModel::getByid($id);
		$obj['cover_path'] = getImgURL($obj['cover']);
		$obj['file_path'] = getFileURL($obj['filename']);
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
	 * 众包参与：角色（cate=2、3）
	 */
	public function mzbpartin(){
		//判断会员认证身份
		if($this->member['auth_cate']<2 || $this->member['auth_cate']>3){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		
		return $this->fetch();
	}
	
	/**
	 * 作品列表：角色（cate=2、3）
	 */
	public function mzbworks(){
		//判断会员认证身份
		if($this->member['auth_cate']<2 || $this->member['auth_cate']>3){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		
		return $this->fetch();
	}
	
	/**
	 * 作品上传：角色（cate=2、3）
	 */
	public function mzbworksupd(){
		//判断会员认证身份
		if($this->member['auth_cate']<2 || $this->member['auth_cate']>3){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		
		return $this->fetch();
	}
	
}

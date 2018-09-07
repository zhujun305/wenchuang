<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\memberauthModel;

/**
 * 会员管理
 */
class Member extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->memberList = Session::get('memberList');
		$this->assign('memberList', $this->memberList);
		$sexarr = ['0'=>'不确定','1'=>'男','2'=>'女',];
		$this->assign('sexarr', $sexarr);
	}

	public function index(){
		$this->redirect(Url('/Admin/Member/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('memberList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'user_name,nick_name,mobile,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$list = memberModel::getList($findObj);
		$this->assign("list", $list);
		//空判断
		$findObj['user_name'] = isset($findObj['user_name'])?$findObj['user_name']:'';
		$findObj['nick_name'] = isset($findObj['nick_name'])?$findObj['nick_name']:'';
		$findObj['mobile'] = isset($findObj['mobile'])?$findObj['mobile']:'';
		$findObj['is_lock'] = isset($findObj['is_lock'])?$findObj['is_lock']:'';
		$this->assign("findObj", $findObj);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id){
		$detail = memberModel::getByid($id);
		$this->assign("detail", $detail);
		return $this->fetch();
	}
	
	/**
	 * 锁定
	 */
	public function lock($id){
		$rs = memberModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->memberList);
	}
	
	/**
	 * 解锁
	 */
	public function unlock($id){
		$rs = memberModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->memberList);
	}
	
	/**
	 * 删除
	 */
	public function del($id) {
		$rs = memberModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->memberList);
	}
	
}
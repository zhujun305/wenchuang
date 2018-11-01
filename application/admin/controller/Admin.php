<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use app\model\adminModel;
use app\model\adminuserModel;
use app\model\syspowerModel;
use app\model\sysroseModel;

/**
 * 后台管理
 */
class Admin extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
	}
	
	/**
	 * 后台首页
	 */
	public function index(){
		if($this->isLogin()){
			return $this->fetch();
		}else{
			$this->error('请登录','Admin/admin/login');
		}
	}
	
	/**
	 * 后台顶部
	 */
	public function top() {
		$menulist = syspowerModel::getListByWhere(array('pid'=>0,'isShow'=>1,'status'=>1),[],'sort desc,id asc');
		$this->assign('menulist', $menulist);
		$user = Session::get('user');
		$user['rosename'] = '';
		if($user['roseid'] == '-1'){
			$user['rosename'] = '超级管理员';
		}elseif($user['roseid']>0){
			$rosearr = sysroseModel::getOneByWhere(['id'=>$user['roseid']],['name']);
			$user['rosename'] = $rosearr['name'];
		}
		$this->assign('user', $user);
		$domain = Config::get('domain_host');
		$this->assign('domain', $domain);
		return $this->fetch();
	}
	
	/**
	 * 后台左部
	 */
	public function left($mid='0'){
		$erlist = syspowerModel::getListByWhere(array('pid'=>$mid),[],'sort desc,id asc');
		$erarr = convert_arr_key($erlist,'id','id');
		$menulist = [];
		if($mid>0 && !empty($erarr)){
			$menulist = syspowerModel::getListByWhere(array('pid'=>['in',$erarr],'status'=>1,'isShow'=>1),[],'sort desc,id asc');
		}
		$this->assign('menulist', $menulist);
		$this->assign('mid', $mid);
		return $this->fetch();
	}
	
	/**
	 * 后台主体
	 */
	public function main() {
		return $this->fetch();
	}
	
	/**
	 * 登录
	 */
	public function login() {
		if($this->isLogin()){
			$this->success('登录成功、前往文创后台首页','Admin/admin/index');
		}
		if(Request::instance()->isPost()){
			$post = input('post.');
			$maparr['name'] = $post['username'];
			$maparr['pwd'] = md5($post['password']);
			if($this->checkimgcode($post['imgcode'])){
				$user = adminuserModel::getOneByWhere($maparr);
				if (empty($user)) {
					$this->error('账号或密码错误','Admin/admin/login');
				}else{
					$rs = adminuserModel::upd_data(array('id'=>$user['id']), array('logintime'=>time(),'loginip'=>request()->ip()));
					/* 得到权限存储到session中. */
					$rights = adminModel::authorize($user);
					$user['rights'] = $rights;
					session('user', $user);
					session('user_id', $user['id']);
					$this->success('登录成功、前往后台管理系统','Admin/admin/index');
				}
			}else{
				$this->error('验证码错误','Admin/admin/login');
			}
		}
		return $this->fetch('admin/login');
	}
	
	/**
	 * 退出
	 */
	public function logout(){
		session('user',null);
		$this->success('退出成功、前往登录页面','Admin/admin/login');
	}
	
}

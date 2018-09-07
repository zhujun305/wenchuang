<?php
namespace app\home\controller;
use app\home\controller\Member;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use app\model\memberModel;
use app\model\memberauthModel;

/**
 * 会员中心-产品管理
 */
class Mchanpin extends Member
{
	public function _initialize() {
		parent::_initialize ();
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
		$this->redirect(Url('/home/Mchanpin/mchanpin'));
	}
	
	/**
	 * 产品列表
	 */
	public function mchanpin(){
		
		return $this->fetch();
	}
	
	/**
	 * 产品发布
	 */
	public function mcpadd(){
		
		return $this->fetch();
	}
	
}

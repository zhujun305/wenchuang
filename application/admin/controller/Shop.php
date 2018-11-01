<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\shopModel;

/**
 * 店铺管理
 */
class Shop extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->shopList = Session::get('shopList');
		$this->assign('shopList', $this->shopList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/shop/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('shopList', Request::instance()->url()); //当前页url
		return $this->fetch();
	}
	
}

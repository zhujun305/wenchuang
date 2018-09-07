<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;

/**
 * 关于-帮助-联系我们
 */
class About extends Homebase
{
	public function _initialize() {
		parent::_initialize ();
		$this->uid = Session::get('portalUserUid');
		if(Request::instance()->action() == 'index'){
			$this->member = $this->setportalUser();
		}else{
			$this->member = $this->getportalUser();
		}
		$this->assign('controller', Request::instance()->controller());
	}
	
	/**
	 * 首页
	 */
	public function index(){
		$this->assign('member', $this->member);
// 		$sql = Db::getLastSql();
        return $this->fetch('index/about');
	}
	
}

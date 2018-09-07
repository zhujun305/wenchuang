<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Url;

/**
 * 后台管理
 */
class Index extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
	}

	public function index(){
		return $this->fetch();
	}
}
<?php
namespace app\common\controller;
use think\Controller;
use think\Db;

/**
 * Base基类控制器
 */
class Base extends Controller
{
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		
	}
	
	/**
	 * 输出sql
	 */
	public function getsql(){
		echo Db::getlastsql();
		echo "<br/>";
	}
	
}

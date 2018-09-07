<?php
namespace app\model;
use think\Db;

class commonModel extends \think\Model
{
	/**
	 * 公共model构造方法
	 */
	public function __construct() {
		
	}

	/**
	 * 采集编辑器内容中的图片
	 * @param string $content 内容
	 * @return 内容中的所有图片
	 */
	public function chuli_content(&$content){
// 		set_time_limit(0); //设置脚本最大执行时间。设置为0不限制
		$imgs = [];
		return $imgs;
	}
	
} 

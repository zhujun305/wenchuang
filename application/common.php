<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Cache;
use think\Config;
use think\Request;
use think\Route;
use think\Loader;
use lib\helper;

// 应用公共文件

// 引入 extend/helper.php
Loader::import('helper', EXTEND_PATH);
// 添加命名空间
// Loader::addNamespace('model','model/');
// 助手函数：全局调用（框架的哪个文件中都可以直接使用），都不用new（因为有自动加载）
// import('helper', EXTEND_PATH);

// manage子域名绑定到admin模块
// Route::domain('admin','admin');
// manage子域名绑定到类
// Route::domain('manage','@\app\admin\controller\Index');

//获取当前域名
function getdomain(){
	$request = Request::instance();
	//获取当前域名  携带https 或 http
	$url_https_wshx=$request->domain();
	return $url_https_wshx;
}
function getimgdomain($www='cdn'){
	$imgurl = "http://".$www.".".Config::get('domain_host');
	return $imgurl;
}

function getImgURL($img, $type='', $path=''){
	return helper::getImgURL($img, $type, $path);
}

function getFileURL($file, $type='', $path=''){
	return helper::getFileURL($file, $type, $path);
}

/**
 * 将数据库中查出的列表以指定的 id 作为数组的键名
 * @param $arr 二维数组
 * @param $key_name 返回的键名（键=>数组）
 * @param $val_name 返回键名对应的字段值（键=>值）
 * @return array
 */
function convert_arr_key($arr, $key_name, $val_name='')
{
	$result = array();
	foreach($arr as $key => $val){
		if(!empty($val_name)){
			$result[$val[$key_name]] = $val[$val_name];
		}else{
			$result[$val[$key_name]] = $val;
		}
	}
	return $result;
}

//文件缓存-写入
function writeCacheFile($filename, $list){
	$list_str = serialize($list);
	$options = [
	// 缓存类型为File
	'type'  =>  'File',
	// 缓存有效期为永久有效
	'expire'=>  0,
	//缓存前缀
	'prefix'=>  '',
	// 指定缓存目录
	'path'  =>  ROOT_PATH.'runtime/cache/',
	];
	Cache::connect($options);
	Cache::set($filename, $list_str);
}
//文件缓存-取出
function readCacheFile($name){
	$getlist = unserialize(Cache::get($name));
	return $getlist;
}

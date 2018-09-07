<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_NAME','Home');
define('APP_PATH', __DIR__ . '/application/');
//自定义常量-网站根目录
define('WEBROOT_PATH', '/');
//自定义常量-静态资源目录
define('STATIC_PATH', 'static/');
//自定义常量-上传资源目录
define('UPLOADS_PATH', 'uploads/');
// 定义配置文件目录和应用目录同级
define('CONF_PATH', __DIR__.'/config/');
//自定义常量-model文件夹
define('Model_PATH', __DIR__ . '/model/');
// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';

<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Cache;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialuprecordModel;
use app\model\materialuprecordpicModel;
use lib\helper;

/**
 * ajax异步
 */
class Aajax extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
	}
	
    /**
     * 上传头像logo
     */
    public function ajaxupimages(){
    	$imgbase64 = Request::instance()->post("imgsource");
    	$type = Request::instance()->post("type");
    	$img_wjz = "";
    	if($type == 'logo'){
    		$img_wjz = "memberlogo";
    	}elseif($type == 'auth'){
    		$img_wjz = "memberauth";
    	}else{
    		
    	}
    	$path = ROOT_PATH.UPLOADS_PATH.$img_wjz."/";
    	$rst = helper::base64_image_content($imgbase64, $path, "jpg");
    	$rst = basename($rst);
    	$imgpath = helper::getImgURL($rst, $img_wjz, '');
    	$data = array("imgurl"=>$imgpath);
		echo json_encode($data);
    }
	
	/**
	 * 返回素材类型，二级联动
	 */
	public function getmaterialcate(){
		$cate = Request::instance()->post("cate");
		
		echo json_encode($data);
	}
	
	
	
}


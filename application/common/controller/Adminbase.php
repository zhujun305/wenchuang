<?php
namespace app\common\controller;
use app\common\controller\Base;
use think\Cache;
use think\Cookie;
use think\Request;
use think\Session;
use think\Url;
use think\captcha\Captcha;
use app\model\materialcateModel;
use app\model\materialtopicModel;
use app\model\adminuserModel;

/**
 * admin 基类控制器
 */
class Adminbase extends Base
{
	protected $search_separ = '_';
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->user = Session::get('user');
		$this->assign("user", $this->user);
		$request = Request::instance();
		$this->m = $request->module();
		$this->c = $request->controller();
		$this->a = $request->action();
		$this->r_url = '/manage.php/'.$this->m.'/'.$this->c.'/'.$this->a;
		$this->assign("search_separ", $this->search_separ);
	}
	
	/**
	 * 是否登录
	 */
	public function isLogin(){
		$user = Session::get('user');
		return !empty($user)?true:false;
	}
	
	/**
	 * 清空缓存
	 */
	public function clear_web_cache(){
		Cache::clear();
	}
	
	//获取sessionid
	public static function sid(){
		if (PHP_SESSION_ACTIVE != session_status()) {
			session_start();
		}
		return session_id();
	}
	//生成验证码保存到session（）
	public function verify(){
		$config = config("captcha");
		$captcha = new Captcha($config);
		return $captcha->entry($this->sid()); #传入session标识
	}
	//验证码检查
	public function checkimgcode($imgcode){
		$captcha = new Captcha();
		return $captcha->check($imgcode,$this->sid());
	}
	
	/**
	 * 解析查询条件
	 * @param object $findObj
	 * @param string $find
	 * @param string $fields
	 * @return object
	 */
	public function getFindObj(&$findObj, $find='', $fields) {
		$fields = explode(',', $fields);
		if ($find!=""){
			$conAry = explode($this->search_separ, $find);
			if (is_array($conAry)){
				foreach ($fields as $key => $val) {
					$findObj[$val]=isset($conAry[$key])?$conAry[$key]:'';
				}
			}
		}
		return $findObj;
	}
	
	/**
	 * 刷新缓存
	 */
	public function refreshCache() {
		$this->cacheAdminuser(); //后台管理员列表
		$this->cacheMaterialcate(); //素材分类
		$this->cacheMaterialtopic(); //素材专题
	}
	/**
	 * 管理员列表
	 */
	public function cacheAdminuser(){
		$list = adminuserModel::getListByWhere(array('is_lock'=>1),array('id','name','truename','roseid','mobile','qq','weixin','email'));
		$list = convert_arr_key($list,'id');
		writeCacheFile("adminuserlist",$list);
	}
	/**
	 * 刷新缓存-素材分类
	 */
	public function cacheMaterialcate(){
		$list = materialcateModel::getListByWhere(array('is_lock'=>1),array('id','pid','cname','cno'));
		$list = convert_arr_key($list,'id');
		writeCacheFile("materialcate",$list);
		//素材分类编号
		$cnoarr = [];
		$cnoarr = convert_arr_key($list,'cno','id');
		writeCacheFile("cnoarr", $cnoarr);
	}
	/**
	 * 刷新缓存-素材专题
	 */
	public function cacheMaterialtopic(){
		$topic = materialtopicModel::getListByWhere(array('is_lock'=>1),array('id','title'));
		$topicarr = convert_arr_key($topic,'id','title');
		writeCacheFile("topicarr",$topicarr);
	}
	
	
}

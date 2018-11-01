<?php
namespace app\common\controller;
use app\common\controller\Base;
use think\Cache;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Url;
use think\captcha\Captcha;
use app\model\adminuserModel;
use app\model\memberintegralModel;
use app\model\memberskilltagsModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;
use app\model\crowdfundcateModel;
use app\model\crowdsourccateModel;
use app\model\friendsModel;
use app\model\resareaModel;
use app\model\shopgoodscateModel;
use app\model\shopnormsModel;
use app\model\shopnormsvalModel;
use app\model\syssettingsModel;

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
		}else{
			foreach ($fields as $key => $val) {
				$findObj[$val]='';
			}
		}
		return $findObj;
	}
	
	/**
	 * 刷新缓存
	 */
	public function refreshCache() {
		$this->cacheSyssettings(); //系统设置
		$this->cacheAdminuser(); //后台管理员列表
		$this->cacheArea(); //地区
		$this->cacheMaterialcate(); //素材分类
		$this->cacheMaterialtopic(); //素材专题
		$this->cacheCrowdsourcingcate(); //众包分类
		$this->cacheMemberskilltags(); //设计师技能标签
		$this->cacheCrowdfundcate(); //众筹分类
		$this->cacheShopgoodscate(); //商品分类
		$this->cacheShopnorms(); //商品规格
		$this->cacheFriends(); //友情链接
	}
	/**
	 * 系统设置
	 */
	public function cacheSyssettings(){
		$obj = syssettingsModel::getObjone();
		writeCacheFile("syssettings",$obj);
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
	 * 刷新缓存-地区
	 */
	public function cacheArea(){
		$list = resareaModel::getListByWhere();
		$list = convert_arr_key($list,'id');
		writeCacheFile("arealist",$list);
		//省级地区
		$areatoparr = [];
		foreach ($list as $key=>$val){
			if($val['pid']==0){
				$areatoparr[$val['id']] = $val;
			}
		}
		writeCacheFile("areatop", $areatoparr);
	}
	/**
	 * 刷新缓存-素材分类
	 */
	public function cacheMaterialcate(){
		$list = materialcateModel::getListByWhere(array('is_lock'=>1),[],'pid asc,sort desc,id asc');
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
		$topic = materialtopicModel::getListByWhere(array('is_lock'=>1),[],'sort desc,id asc');
		$topicarr = convert_arr_key($topic,'id','title');
		writeCacheFile("topicarr",$topicarr);
	}
	/**
	 * 刷新缓存-众包分类
	 */
	public function cacheCrowdsourcingcate(){
		$list = crowdsourccateModel::getListByWhere(array('is_lock'=>1),[],'sort desc,id asc');
		$catearr = convert_arr_key($list,'id');
		writeCacheFile("crowdsourcingcate",$catearr);
	}
	/**
	 * 刷新缓存-设计师技能标签
	 */
	public function cacheMemberskilltags(){
		$list = memberskilltagsModel::getListByWhere(array('is_lock'=>1),[],'pid asc,sort desc,id asc');
		$catearr = convert_arr_key($list,'id');
		writeCacheFile("memberskilltags",$catearr);
		$toparr = [];
		foreach ($list as $key=>$val){
			if($val['pid']==0){
				$toparr[$val['id']] = $val;
			}
		}
		writeCacheFile("memberskilltags",$catearr);
	}
	/**
	 * 刷新缓存-众筹分类
	 */
	public function cacheCrowdfundcate(){
		$list = crowdfundcateModel::getListByWhere(array('is_lock'=>1),[],'sort desc,id asc');
		$catearr = convert_arr_key($list,'id');
		writeCacheFile("crowdfundcate",$catearr);
	}
	/**
	 * 刷新缓存-商品分类
	 */
	public function cacheShopgoodscate(){
		$list = shopgoodscateModel::getListByWhere(array('is_lock'=>1),[],'pid asc,sort desc,id asc');
		$catearr = convert_arr_key($list,'id');
		foreach ($catearr as $key=>$val){
			if($val['pid']==0){
				$catearr[$key]['sonidstr'] = '';
				$idstr = [];
				foreach ($catearr as $kkk=>$vvv){
					if($vvv['pid'] == $val['id']) $idstr[] = $vvv['id'];
				}
				if(!empty($idstr)) $catearr[$key]['sonidstr'] = implode(",",$idstr);
			}
		}
		writeCacheFile("shopgoodscate",$catearr);
	}
	/**
	 * 刷新缓存-商品规格
	 */
	public function cacheShopnorms(){
		$list = shopnormsModel::getListByWhere(array('is_lock'=>1),[],'sort desc,id asc');
		$catearr = convert_arr_key($list,'id');
		writeCacheFile("shopnorms",$catearr);
		//商品规格值
		$snvlist = shopnormsvalModel::getListByWhere(array('is_lock'=>1),[],'sort desc,id asc');
		$nvarr = convert_arr_key($snvlist,'id');
		writeCacheFile("shopnormsval",$nvarr);
		//商品（规格-值）
		$keyval = [];
		foreach($snvlist as $key => $val){
			$keyval[$val['pid']][] = $val['id'];
		}
		writeCacheFile("shopnormskeyval",$keyval);
	}
	/**
	 * 刷新缓存-友情链接
	 */
	public function cacheFriends(){
		$list = friendsModel::getListByWhere(array('is_lock'=>1),[],'sort desc,id asc');
		$friarr = convert_arr_key($list,'id');
		writeCacheFile("friends",$friarr);
	}
	
	/**
	 * 积分收支记录
	 * $uid 会员
	 * $cate 收支类型1收入2支出
	 * $s_score 应该交易积分int
	 * $score 实际交易积分int
	 * $income 收入方式1充值2登录3素材4众包5作品6动态新闻7首次注册8首次登陆
	 * $payjf 支出方式1下载素材
	 */
	public function addintegral($uid,$cate,$income='',$payjf=''){
		if(!($uid>0)){
			return 0;
		}
		if($cate==1){
			//赠送积分方式
			$income_arr = Config::get('config.income_arr');
			if($income_arr[$income]['onoff']==false){
				return 0;
			}
			$s_score = $income_arr[$income]['jifen'];
			$score = $income_arr[$income]['jifen'];
			$desc = $income_arr[$income]['desc'];
		}else{
			return 0;
		}
		$data = [];
		$data['uid'] = $uid;
		$data['input_uid'] = $uid;
		$data['cate'] = $cate;
		$data['income'] = $income;
		$data['payjf'] = $payjf;
		$data['s_score'] = $s_score;
		$data['score'] = $score;
		$data['desc'] = $desc;
		$id = 0;
		if(!empty($data)){
			$id = memberintegralModel::add_data($data);
		}
		return $id;
	}
	
}

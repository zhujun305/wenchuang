<?php
namespace app\common\controller;
use app\common\controller\Base;
use think\Cache;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use think\captcha\Captcha;
use lib\helper;
use app\model\memberModel;
use app\model\memberauthModel;

/**
 * Home基类控制器
 */
class Homebase extends Base{
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
	}

	/**
	 * 设置当前用户到session
	 */
	public function setportalUser($uid=null) {
		if (!($uid>0)) $uid=Session::get('portalUserUid');
		$member = memberModel::getByid($uid);
		$sdata=[
		'uid'=>$member['uid'],
		'user_name'=>$member['user_name'],
		'cate'=>$member['cate'],
		'nick_name'=>$member['nick_name'],
		'imgurl'=>helper::getImgurl($member['imgurl'],'memberlogo'),
		'mobile'=>$member['mobile'],
		'email'=>$member['email'],
		'qq'=>$member['qq'],
		'weixin'=>$member['weixin'],
		'sex'=>$member['sex'],
		'age'=>$member['age'],
		];
		$m_auth = memberauthModel::getOneByWhere(array('uid'=>$uid));
		$sdata['leaguer_no'] = $m_auth['leaguer_no'];
		$sdata['auth_cate'] = $m_auth['cate'];
		session('portalUser',$sdata);
		return $sdata;
	}
	
	/**
	 * 从session取出当前用户信息
	 */
	public function getportalUser() {
		if (Session::get('clear_web_cache')) {
			return $this->setportalUser();
		}
		$member=Session::get('portalUser');
		if ($member['uid']>0) {
			return $member;
		}
		return $this->setportalUser();
	}
	
	/**
	 * 是否登录
	 */
	public function isLogin(){
		$portalUserUid = Session::get('portalUserUid');
		return $portalUserUid>0?true:false;
	}
    
    /**
     * 会员是否认证
     */
    public function is_member_auth(){
		$this->uid = Session::get('portalUserUid');
		$renz_arr = memberauthModel::getOneByWhere(['uid'=>$this->uid,'is_chk'=>2,]);
		return $renz_arr;
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
	
}


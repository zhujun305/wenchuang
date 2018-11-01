<?php
namespace app\common\controller;
use app\common\controller\Base;
use think\Cache;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use think\captcha\Captcha;
use lib\helper;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\memberintegralModel;
use app\model\msgrecordsModel;
use app\model\adsModel;

/**
 * Home基类控制器
 */
class Homebase extends Base{
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$syssettings = readCacheFile("syssettings");
		$this->assign("syssettings", $syssettings);
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
		'imgurl'=>getImgURL($member['imgurl'],'memberlogo',''),
		'skilltags'=>$member['skilltags'],
		'mobile'=>$member['mobile'],
		'email'=>$member['email'],
		'qq'=>$member['qq'],
		'weixin'=>$member['weixin'],
		'sex'=>$member['sex'],
		'age'=>$member['age'],
		'balance'=>$member['balance'],
		'total_balance'=>$member['total_balance'],
		'logintime'=>$member['logintime'],
		'lastlogintime'=>$member['lastlogintime'],
		'regdate'=>$member['regdate'],
		'sign'=>$member['sign'],
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
	
	/**
	 * 发消息（1系统消息、2私信）
	 */
	public function send_msg($type,$fuid,$suid,$content){
		$data = [];
		$data['type'] = $type;
		$data['input_uid'] = $fuid;
		$data['uid'] = $suid;
		$data['content'] = $content;
		$rs = msgrecordsModel::add_data($data);
		return $rs;
	}
	
	/**
	 * 广告
	 */
	public function getAds($code) {
		$where = ['code'=>$code,'is_lock'=>1];
		$obj = adsModel::getOneByWhere($where,[],'sort desc,id desc');
		$pic_path = explode ( "|", $obj['picpath'] );
		$pic_name = explode ( "|", $obj['picname'] );
		$url = explode ( "|", $obj['url'] );
		$html = '';
		if($obj['type']==2){
			if(is_array($pic_path)){
				$html.= '<div class="hd">';
				foreach ($pic_path as $key=>$val){
					$html.= '<em>'.($key+1).'</em>';
				}
				$html.= '</div><div class="bd"><ul>';
				foreach ($pic_path as $key=>$val){
					$imgurl = getImgURL($val);
					$html.= '<li style="background:url('.$imgurl.') top center no-repeat;" title="'.$pic_name[$key].'"><a href="'.$url[$key].'" target="_black"></a></li>';
				}
				$html.= '</ul></div>';
			}
		}else{
			if($obj['picpath']!=''){
				$html.= '<a href="'.$obj['url'].'" target="_black">';
				$html.= '<img src="'.getImgURL($obj['picpath']).'" width="100%" height="'.$obj['height'].'" title="'.$obj['picname'].'" /></a>';
			}
		}
		return $html;
	}
	
	/**
	 * 从数组提取uid，并返回member数组['uid','nick_name','imgurl']
	 */
	public function getmlist($list,$field=[]){
		if(empty($field)) $field = ['uid','nick_name','imgurl'];
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],$field);
		$data = convert_arr_key($mlist,'uid');
		return $data;
	}
	
	/**
	 * 积分收支记录
	 * $uid 会员
	 * $cate 收支类型1收入2支出
	 * $income 收入方式1充值2登录3素材4众包5作品6动态新闻7首次注册8首次登陆
	 * $payjf 支出方式1下载素材
	 */
	public function addintegral($uid,$cate,$income='',$payjf='',$score=''){
		if($uid != Session::get('portalUserUid')){
			return 0;
		}
		if($cate==1){
			if(in_array($income,[7,8])){
				$one = memberintegralModel::getOneByWhere(['uid'=>$uid,'cate'=>['in',[7,8]]]);
				if(!empty($one)) return 0;
			}
			if($income==2){ //每日首次登陆
				$today = strtotime(date("Y-m-d",time()));
				$tomorrow = ($today+86400-1);
				$one = memberintegralModel::getOneByWhere(['uid'=>$uid,'cate'=>1,'income'=>$income,'input_time'=>['between',[$today,$tomorrow]]]);
				if(!empty($one)) return 0;
			}
			//赠送积分方式
			$income_arr = Config::get('config.income_arr');
			if($income_arr[$income]['onoff']==false){
				return 0;
			}
			$s_score = $income_arr[$income]['jifen'];
			$score = $income_arr[$income]['jifen'];
			$desc = $income_arr[$income]['desc'];
		}else{
			if(!($score>0)) return 0;
			$s_score = $score;
			$score = $score;
			$desc = '下载素材使用积分';
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
			//更新用户积分信息
			$shou = memberintegralModel::getOneByWhere(['uid'=>$uid,'cate'=>1],['SUM(score) totscore']);
			$zhic = memberintegralModel::getOneByWhere(['uid'=>$uid,'cate'=>2],['SUM(score) totscore']);
			$balance = ($shou['totscore'] - $zhic['totscore']);
			$upd = ['balance'=>$balance];
			$member = memberModel::getByid($uid);
			if($cate==1){
				$upd['total_balance'] = ($member['total_balance'] + $score);
			}
			$rs = memberModel::upd_data(['uid'=>$uid],$upd);
			$this->setportalUser($uid);
		}
		return $id;
	}
	
}


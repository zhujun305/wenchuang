<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\materialcateModel;
use app\model\materialuprecordModel;
use app\model\articleModel;
use app\model\helpModel;
use app\model\shopgoodsModel;
use app\model\memberintegralModel;

/**
 * 前台首页
 */
class Index extends Homebase
{
	public function _initialize() {
		parent::_initialize ();
		$this->uid = Session::get('portalUserUid');
		if(Request::instance()->action() == 'index'){
			$this->member = $this->setportalUser();
		}else{
			$this->member = $this->getportalUser();
		}
		$this->assign('controller', Request::instance()->controller());
	}
	
	/**
	 * 首页
	 */
	public function index(){
		$this->assign('member', $this->member);
		//广告
		$ads = $this->getAds('indexbanner');
		$this->assign('ads', $ads);
		//公告
		$where = ['is_lock'=>1,'type'=>2,'is_chk'=>2];
		$gglist = articleModel::getListByWhere($where,[],'is_recommend desc,pv desc,id desc','6');
		$this->assign('gglist', $gglist);
		//新闻
		$where['type'] = 1;
		$xwlist = articleModel::getListByWhere($where,[],'is_recommend desc,pv desc,id desc','6');
		$this->assign('xwlist', $xwlist);
		//商品分类
		$this->clist = readCacheFile("shopgoodscate");
		$this->assign('clist', $this->clist);
		//热门商品8个
		$rmlist = shopgoodsModel::getListByWhere(['is_lock'=>1,'is_chk'=>2],[],'pv desc,id desc','8');
		$this->assign('rmlist', $rmlist);
		//会员uid列表
		$mlist = $this->getmlist($rmlist);
		$this->assign("mlist", $mlist);
		//友情链接
		$this->friends = readCacheFile("friends");
		$this->assign('friends', $this->friends);
        return $this->fetch();
	}
	
	/**
	 * 登录
	 */
	public function login(){
		if($this->isLogin()){
			$this->success('登录成功、前往文创平台首页','home/Index/index');
		}
		if(Request::instance()->isPost()){
			// 做一个简单的登录 组合where数组条件
			$post = input('post.');
			// dump($map);
			// exit();
			$maparr['user_name'] = $post['username'];
			$maparr['password'] = md5($post['password']);
			$remember = input('is_pwd_ever/s'); //是否记住密码
			if($this->checkimgcode($post['imgcode'])){
    			$data = memberModel::getOneByWhere($maparr);
				if (empty($data)) {
					$this->error('账号或密码错误','home/Index/login');
				}else{
					$rs = memberModel::upd_data(['uid'=>$data['uid']],['logintime'=>time(),'lastlogintime'=>$data['logintime']]);
					session('portalUserUid',$data['uid']);
					$this->setportalUser($data['uid']);
					//如果用户选择了，记录登录状态就把用户名和加了密的密码放到cookie里面
					if(!empty($remember)){
						Cookie::set("user_name", $post['username'], 3600*24*7);
						Cookie::set("password", $post['password'], 3600*24*7);
					}
					if($data['logintime']=='' && $data['lastlogintime']==''){
						//用户首次登陆奖励5积分
						$this->addintegral($data['uid'],1,8);//会员，收支类型，收入方式，支出方式
					}
					//每日首次登陆奖励2积分
					$this->addintegral($data['uid'],1,2);//会员，收支类型，收入方式，支出方式
					$this->success('登录成功、前往文创平台首页','home/Index/index');
				}
			}else{
				$this->error('验证码错误','home/Index/login');
			}
		}else{
			$data['user_name'] = Cookie::get("user_name");
			$data['password'] = Cookie::get("password");
    		$this->assign('data', $data);
			return $this->fetch();
		}
	}

    /**
     * 退出
     */
    public function logout(){
        session('user',null);
        session('portalUserUid',null);
        $this->success('退出成功、前往登录页面','Home/Index/index');
    }


    /**
     * 忘记密码
     */
    public function forgetpwd(){

    	return $this->fetch();
    }
    
    /**
     * 注册
     */
    public function register(){
    	if(Request::instance()->isPost()){
    		$post = input('post.');
			$uname = $post['username'];
			$pwd1 = md5($post['password']);
			$pwd2 = md5($post['password2']);
			if (empty($uname)) {
				$this->error('您的帐号不能为空！','home/Index/register');
			}else if (preg_match('/\s+/', $uname)) {
				$this->error('您的帐号不能包含空格！','home/Index/register');
			}else if (strlen($uname)<3 || strlen($uname)>16) {
				$this->error('您的帐号长度必须在3位到16位之间！','home/Index/register');
			}else if (!preg_match('/^[\w|\d|\@|\.|\-|\_]+$/', $uname)) {
				$this->error('帐号不能包含( 字母 数字 @ . - _ ) 以外的其他字符！','home/Index/register');
			}
    		$d_uname = memberModel::getOneByWhere(array('user_name'=>$uname));
			if(!empty($d_uname)) {
				$this->error('您输入的帐号已被注册！','home/Index/register');
			}
			if (empty($pwd1)) {
				$this->error('您的密码不能为空！','home/Index/register');
			}else if (preg_match('/\s+/', $pwd1)) {
				$this->error('您的密码不能包含空格！','home/Index/register');
			}
			if (empty($pwd2)) {
				$this->error('您的确认密码不能为空！','home/Index/register');
			}else if (preg_match('/\s+/', $pwd2)) {
				$this->error('您的确认密码不能包含空格！','home/Index/register');
			}else if (md5($pwd1)!=md5($pwd2)) {
				$this->error('您输入的确认密码和密码不一致！','home/Index/register');
			}
			$suistr = array("0"=>"甲","1"=>"乙","2"=>"丙",);
			$data['user_name'] = $uname;
			$data['password'] = $pwd1;
			$data['nick_name'] = "路人".$suistr[mt_rand(0,2)];
			$data['regip'] = Request::instance()->ip(); //获取客户端IP地址
			$data['regdate'] = time();
			$uid = memberModel::add_data($data);
			if($uid>0){
				session('portalUserUid',$uid);
				$this->setportalUser($uid);
				//用户首次注册奖励50积分
				$this->addintegral($uid,1,7);//会员，收支类型，收入方式，支出方式
				$this->success('注册成功、前往选择会员',Url('home/Index/register2','tmp=2'));
			}else{
				$this->error('注册失败！','home/Index/register');
			}
    	}
		$xieyi = helpModel::getOneByWhere(['is_lock'=>1,'cate'=>'xieyi']);
		$this->assign('xieyi', $xieyi);
    	return $this->fetch();
    }
    
    /**
     * 注册--完善资料--选择会员
     */
    public function register2($tmp='', $cate=''){
    	if($tmp!=2){
			$this->redirect('home/Index/index');
    	}
    	$this->assign('cate', $cate);
		//判断是否认证
		$this->assign('is_member_auth', 0);
    	$renzobj = $this->is_member_auth();
		if(!empty($renzobj)){
    		$this->assign('is_member_auth', 1);
		}
		switch ($cate) {
			case '1':
				return $this->fetch('reg_auth_mc1');
			break;
			case '2':
				return $this->fetch('reg_auth_mc2');
			break;
			case '3':
				return $this->fetch('reg_auth_mc3');
			break;
			case '4':
				return $this->fetch('reg_auth_mc4');
			break;
		}
// 		if(Request::instance()->isPost() && $this->uid>0){
// 			$post = input('post.');
// 			$data['nick_name'] = $post['nick_name'];
// 			$data['sex'] = $post['sex']>0?$post['sex']:0;
// 			$data['age'] = $post['age'];
// 			$data['mobile'] = $post['mobile'];
// 			$data['email'] = $post['email'];
// 			$data['qq'] = $post['qq'];
// 			$data['weixin'] = $post['weixin'];
// 			$data['imgurl'] = basename($post['imgurl']);
// 			$rs = memberModel::upd_data(array('uid'=>$this->uid), $data);
// 			$this->setportalUser();
// 			$this->success('已经完善个人资料。',Url('home/index/register3','tmp=3'));
// 		}
//     	$suistr = array("0"=>"甲","1"=>"乙","2"=>"丙",);
//     	$nick_name = "路人".$suistr[mt_rand(0,2)];
//     	$this->assign('nick_name', $nick_name);
    	return $this->fetch();
    }
    
    /**
     * 注册--完善资料--选择会员
     */
    public function register3($tmp=''){
    	if($tmp!=3){
			$this->redirect('home/Index/index');
    	}
    	return $this->fetch();
    }
    
    /**
     * 注册--选择会员--会员认证
     */
    public function reg_auth_member(){
		$this->uid = Session::get('portalUserUid');
		//判断是否认证
    	$renzobj = $this->is_member_auth();
		if(!empty($renzobj)){
			$this->success('已认证，前往会员中心',Url('home/Member/index'));
		}
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$cate = $post['cate'];
			$data['uid'] = $this->uid;
			$data['input_uid'] = $data['uid'];
			$data['cate'] = $cate;
			$data['logo'] = basename($post['imgurl']);
			$data['a_mobile'] = $post['a_mobile'];
			$data['a_email'] = $post['a_email'];
			$data['a_address'] = $post['a_address'];
			$data['nick_name'] = '';
			if(!in_array($data['cate'],array('1','2','3','4',)) || empty($data['logo']) || empty($data['a_mobile']) || empty($data['a_email']) || empty($data['a_address'])){
				$this->error('参数错误',Url('home/Index/register2','tmp=2&cate='.$data['cate']));
			}
			if($cate == 1){
				$data['true_name'] = $post['true_name'];
				$data['IDcard'] = $post['IDcard'];
				$data['nick_name'] = $data['true_name'];
				if(empty($data['true_name']) || empty($data['IDcard'])){
					$this->error('参数错误',Url('home/Index/register2','tmp=2&cate='.$data['cate']));
				}
			}elseif($cate == 2){
				$data['true_name'] = $post['true_name'];
				$data['work_unit'] = $post['work_unit'];
				$data['diploma'] = $post['diploma'];
				$data['major'] = $post['major'];
				$data['IDcardimg_a'] = basename($post['IDcardimg_a']);
				$data['IDcardimg_b'] = basename($post['IDcardimg_b']);
				$data['graducerti'] = basename($post['graducerti']);
				$data['nick_name'] = $data['true_name'];
				if(empty($data['true_name']) || empty($data['work_unit']) || empty($data['diploma']) || empty($data['major']) || empty($data['IDcardimg_a']) || empty($data['IDcardimg_b']) || empty($data['graducerti'])){
					$this->error('参数错误',Url('home/Index/register2','tmp=2&cate='.$data['cate']));
				}
			}elseif($cate == 3){
				$data['team_cate'] = $post['team_cate'];
				$data['company'] = $post['company'];
				$data['legal'] = $post['legal'];
				$data['license'] = basename($post['license']);
				$data['nick_name'] = $data['company'];
				if(!($data['team_cate']>0) || empty($data['company']) || empty($data['legal']) || empty($data['license'])){
					$this->error('参数错误',Url('home/Index/register2','tmp=2&cate='.$data['cate']));
				}
			}elseif($cate == 4){
				$data['leaguer_no'] = $post['leaguer_no'];
				$data['company'] = $post['company'];
				$data['legal'] = $post['legal'];
				$data['org_code'] = basename($post['org_code']);
				$data['nick_name'] = $data['company'];
				if(empty($data['company']) || empty($data['legal']) || empty($data['org_code'])){
					$this->error('参数错误',Url('home/Index/register2','tmp=2&cate='.$data['cate']));
				}
			}
			$rs = memberauthModel::add_data($data);
			if($rs){
				//更新昵称
				$m_data = array('cate'=>$cate,'imgurl'=>$data['logo'],'nick_name'=>$data['nick_name']);
				memberModel::upd_data(array('uid'=>$this->uid), $m_data);
				$this->success('认证成功，前往会员中心首页',Url('home/Member/index'));
			}else{
				$this->error('认证失败',Url('home/Index/register2','tmp=2&cate='.$data['cate']));
			}
		}
	}
	
}


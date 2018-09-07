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
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;

/**
 * 会员中心
 */
class Member extends Homebase
{
	public function _initialize() {
		parent::_initialize ();
		$this->uid = Session::get('portalUserUid');
		if(!($this->isLogin())){
			$this->error('请登录','home/Index/login');
		}
		if(Request::instance()->action() == 'index'){
			$this->member = $this->setportalUser();
		}else{
			$this->member = $this->getportalUser();
		}
		if(empty($this->member['imgurl'])){
			$this->member['imgurl'] = '/webhome/images/defaulttouxiang1.png';
		}
		$this->assign('member', $this->member);
		//会员类型名称
		$membercate = Config::get('config.membercate');
		$this->assign('membercate', $membercate);
		$this->assign('action', Request::instance()->action());
	}
	
	/**
	 * 首页
	 */
	public function index(){
        return $this->fetch();
	}
	
	/**
	 * 个人资料
	 */
	public function modcard(){
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['nick_name'] = $post['nick_name'];
			$data['sex'] = $post['sex']>0?$post['sex']:0;
			$data['age'] = $post['age'];
			$data['mobile'] = $post['mobile'];
			$data['email'] = $post['email'];
			$data['qq'] = $post['qq'];
			$data['weixin'] = $post['weixin'];
			$data['imgurl'] = basename($post['imgurl']);
			$rs = memberModel::upd_data(array('uid'=>$this->uid), $data);
			$this->setportalUser();
			$this->success('个人资料修改成功。',Url('home/Member/index'));
		}
		return $this->fetch();
	}
	
	/**
	 * 修改密码
	 */
	public function changepwd(){
		$member = memberModel::getByid($this->uid);
    	if(Request::instance()->isPost() && $this->uid>0){
    		$post = input('post.');
			$old_pwd = $post['old_pwd'];
			$new_pwd = md5($post['new_pwd']);
			$new_pwd2 = md5($post['new_pwd2']);
			if (empty ( $old_pwd )) {
				$this->error('旧密码不能为空！','home/Member/changepwd');
			} else if (md5($old_pwd) != $member['password']) {
				$this->error('旧密码错误！','home/Member/changepwd');
			} else if (empty ( $new_pwd )) {
				$this->error('新密码不能为空！','home/Member/changepwd');
			} else if ($new_pwd != $new_pwd2) {
				$this->error('两次输入新密码不一致！','home/Member/changepwd');
			}
			$data['password'] = $new_pwd;
			$rs = memberModel::upd_data(array('uid'=>$this->uid), $data);
			$this->success('密码修改成功','home/Member/index');
    	}
		return $this->fetch();
	}
	
	/**
	 * 账号认证
	 */
	public function memberauth($cate=''){
    	$this->assign('cate', $cate);
    	$renzobj = $this->is_member_auth();
    	$this->assign('renzobj', $renzobj);
		//判断是否认证
		$this->assign('is_member_auth', 0);
		if(!empty($renzobj)){
    		$this->assign('is_member_auth', 1);
		}
		switch ($cate) {
			case '1':
				return $this->fetch('memberauth_mc1');
			break;
			case '2':
				return $this->fetch('memberauth_mc2');
			break;
			case '3':
				return $this->fetch('memberauth_mc3');
			break;
			case '4':
				return $this->fetch('memberauth_mc4');
			break;
		}

		return $this->fetch();
	}
	
    /**
     * 会员认证-实名认证
     */
    public function memberauth_data(){
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
			if(!in_array($data['cate'],array('1','2','3','4',)) || empty($data['logo']) || empty($data['a_mobile']) || empty($data['a_email']) || empty($data['a_address'])){
				$this->error('参数错误',Url('home/Member/memberauth'));
			}
			if($cate == 1){
				$data['true_name'] = $post['true_name'];
				$data['IDcard'] = $post['IDcard'];
				if(empty($data['true_name']) || empty($data['IDcard'])){
					$this->error('参数错误',Url('home/Member/memberauth'));
				}
			}elseif($cate == 2){
				$data['true_name'] = $post['true_name'];
				$data['work_unit'] = $post['work_unit'];
				$data['diploma'] = $post['diploma'];
				$data['major'] = $post['major'];
				$data['IDcardimg_a'] = basename($post['IDcardimg_a']);
				$data['IDcardimg_b'] = basename($post['IDcardimg_b']);
				$data['graducerti'] = basename($post['graducerti']);
				if(empty($data['true_name']) || empty($data['work_unit']) || empty($data['diploma']) || empty($data['major']) || empty($data['IDcardimg_a']) || empty($data['IDcardimg_b']) || empty($data['graducerti'])){
					$this->error('参数错误',Url('home/Member/memberauth'));
				}
			}elseif($cate == 3){
				$data['team_cate'] = $post['team_cate'];
				$data['company'] = $post['company'];
				$data['legal'] = $post['legal'];
				$data['license'] = basename($post['license']);
				if(!($data['team_cate']>0) || empty($data['company']) || empty($data['legal']) || empty($data['license'])){
					$this->error('参数错误',Url('home/Member/memberauth'));
				}
			}elseif($cate == 4){
				$data['company'] = $post['company'];
				$data['legal'] = $post['legal'];
				$data['org_code'] = basename($post['org_code']);
				if(empty($data['company']) || empty($data['legal']) || empty($data['org_code'])){
					$this->error('参数错误',Url('home/Member/memberauth'));
				}
			}
			$rs = memberauthModel::add_data($data);
			if($rs){
				//更新昵称
				$m_data = array('cate'=>$cate,'imgurl'=>$data['logo']);
				memberModel::upd_data(array('uid'=>$this->uid), $m_data);
				$this->success('认证成功，前往会员中心首页',Url('home/Member/index'));
			}else{
				$this->error('认证失败',Url('home/Member/memberauth'));
			}
		}
    }
	
	/**
	 * 我的关注（图书馆、设计团队、设计师）
	 */
	public function mycollmember(){
		return $this->fetch();
	}
	
	/**
	 * 我的收藏（素材）
	 */
	public function mycollmaterial(){
		return $this->fetch();
	}
	
	/**
	 * 消息管理
	 */
	public function newslist(){
		return $this->fetch();
	}
	
	
}


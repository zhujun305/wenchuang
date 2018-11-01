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
use app\model\memberintegralModel;
use app\model\membercollModel;
use app\model\membershipaddressModel;
use app\model\articleModel;
use app\model\msgrecordsModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;
use app\model\crowdsourcingModel;
use app\model\crowdfundingModel;
use app\model\shopcartModel;
use app\model\shopgoodsModel;
use app\model\shopgoodsnormsModel;
use app\model\shopgoodsruleModel;
use app\model\shoporderModel;

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
		$this->assign('action', Request::instance()->action());
		//会员类型名称
		$membercate = Config::get('config.membercate');
		$this->assign('membercate', $membercate);
		//广告
		$ads = $this->getAds('m_index');
		$this->assign('ads', $ads);
		//未读消息
		$wdnews = msgrecordsModel::getOneByWhere(['uid'=>$this->uid,'status'=>1],['count(1) num']);
		$this->assign('wdnews', $wdnews['num']);
	}
	
	/**
	 * 首页
	 */
	public function index($type='1'){
		if($this->member['auth_cate']==2 || $this->member['auth_cate']==3){
			//众包分类
			$catelist = readCacheFile("crowdsourcingcate");
			$this->assign('catelist', $catelist);
			$where = array('is_lock'=>1,'is_chk'=>2,'status'=>['<',7]);
			$tjlist = crowdsourcingModel::getList($where,[],'4');
			$this->assign('tjlist', $tjlist);
			$newlist = crowdsourcingModel::getList($where,[],'8');
			$this->assign('newlist', $newlist);
			//技能标签
			$catelist = readCacheFile("memberskilltags");
			$this->assign('catelist', $catelist);
			$xzarr = [];
			if(!empty($this->member['skilltags'])){
				$xzarr = array_filter(explode(",",$this->member['skilltags']));
			}
			$this->assign('xzarr', $xzarr);
		}
		$totnum = ['b'=>0,'c'=>0,'d'=>0,'e'=>0,];
		if($this->member['auth_cate']==4){
			if($type!=2) $type=1;
			$this->assign('type', $type);
			$list = articleModel::getList(['is_lock'=>1,'is_chk'=>2,'type'=>$type]);
			$this->assign("list", $list);
			//素材
			$scnum = materialModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$this->uid],['count(1) num']);
			$totnum['b'] = $scnum['num']>0?$scnum['num']:0;
			//商品
			$sgoods = shopgoodsModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$this->uid],['count(1) num']);
			$totnum['c'] = $sgoods['num']>0?$sgoods['num']:0;
			//众包
			$zbnum = crowdsourcingModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$this->uid],['count(1) num']);
			$totnum['d'] = $zbnum['num']>0?$zbnum['num']:0;
			//众筹
			$zcnum = crowdfundingModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$this->uid],['count(1) num']);
			$totnum['e'] = $zcnum['num']>0?$zcnum['num']:0;
		}
		$this->assign('totnum', $totnum);
		//判断是否认证
		$renzobj = memberauthModel::getOneByWhere(['uid'=>$this->uid,]);
		$this->assign('renzobj', $renzobj);
        return $this->fetch();
	}
	
	/**
	 * 个人资料
	 */
	public function modcard(){
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['nick_name'] = $post['nick_name'];
			$data['sign'] = $post['sign'];
			$data['sex'] = $post['sex']>0?$post['sex']:0;
			$data['age'] = $post['age'];
			$data['mobile'] = $post['mobile'];
			$data['email'] = $post['email'];
			$data['qq'] = $post['qq'];
			$data['weixin'] = $post['weixin'];
			$data['imgurl'] = basename($post['imgurl']);
			$rs = memberModel::upd_data(array('uid'=>$this->uid), $data);
			$rs = memberauthModel::upd_data(array('uid'=>$this->uid), ['nick_name'=>$data['nick_name']]);
			$this->setportalUser();
			$this->success('个人资料修改成功。',Url('home/Member/index'));
		}
		return $this->fetch();
	}
	
	/**
	 * 详细介绍
	 */
	public function modcontent(){
		$memobj = memberModel::getByid($this->uid);
		$this->assign('memobj', $memobj);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['info'] = $post['content'];
			$rs = memberModel::upd_data(array('uid'=>$this->uid), $data);
			$this->setportalUser();
			$this->success('详细介绍修改成功。',Url('home/Member/index'));
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
		//判断是否认证
		$renzobj = memberauthModel::getOneByWhere(['uid'=>$this->uid,]);
		$this->assign('renzobj', $renzobj);
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
				$data['leaguer_no'] = $post['leaguer_no'];
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
				$m_data = array('cate'=>$cate);
				memberModel::upd_data(array('uid'=>$this->uid), $m_data);
				$this->success('认证成功，前往会员中心首页',Url('home/Member/index'));
			}else{
				$this->error('认证失败',Url('home/Member/memberauth'));
			}
		}
    }
	
	/**
	 * 关注收藏（cate=1会员2素材3众包4众筹5商品）
	 */
	public function addmembercoll(){
		$cate = Request::instance()->post("cate");
		$collid = Request::instance()->post("id");
		$id = 0;
		$data = [];
		$data['cate'] = $cate; //1会员2素材3众包4众筹5商品
		$data['uid'] = $this->uid;
		$data['otherid'] = $collid;
		if($data['uid']>0 && $data['otherid']>0){
			$cobj = membercollModel::getOneByWhere($data,[]);
			if(empty($cobj)){
				$id = membercollModel::add_data($data);
			}
		}
		echo json_encode($id);
	}
	
	/**
	 * 取消关注收藏
	 */
	public function delmembercoll(){
		$id = Request::instance()->post("id");
		$rs = 0;
		if($id>0){
			$rs = membercollModel::del([$id]);
		}
		echo json_encode($rs);
	}
    
    /**
     * 技能标签
     */
    public function skilltags(){
		$catelist = readCacheFile("memberskilltags");
		$this->assign('catelist', $catelist);
		$xzarr = [];
		if(!empty($this->member['skilltags'])){
			$xzarr = array_filter(explode(",",$this->member['skilltags']));
		}
		$this->assign('xzarr', $xzarr);
    	if(Request::instance()->isPost() && $this->uid>0){
    		$post = input('post.');
			$data['skilltags'] = $post['idstr'];
			$rs = memberModel::upd_data(['uid'=>$this->uid], $data);
			$this->setportalUser();
			$this->success('技能标签设置成功。',Url('home/Member/index'));
    	}
    	return $this->fetch();
    }
    
    /**
     * 我的积分
     */
    public function integral(){
		$where = ['uid'=>$this->uid];
		$list = memberintegralModel::getList($where,[],'20');
		$this->assign('list', $list);
		//收入积分方式
		$income_arr = Config::get('config.income_arr');
		$this->assign('income_arr', $income_arr);
		//支出积分方式
		$payjf_arr = ['1'=>'下载素材'];
		$this->assign('payjf_arr', $payjf_arr);
    	return $this->fetch();
    }
    
    /**
     * 积分充值
     */
    public function integraladd(){
    	
    	return $this->fetch();
    }
	
	/**
	 * 收货地址
	 */
	public function myshipaddress(){
		$list = membershipaddressModel::getListByWhere(['is_lock'=>1,'uid'=>$this->uid],[],'is_default desc,id desc');
		$this->assign('list', $list);
		$areaerji = readCacheFile("areatop");
		$this->assign('areaerji', $areaerji);
		//已添加的地区
		$area = readCacheFile("arealist");
		$this->assign('area', $area);
		return $this->fetch();
	}
	//收货地址修改
	public function ajaxupddizhi($id){
		$data['truename'] = Request::instance()->post("truename");
		$data['mobile'] = Request::instance()->post("mobile");
		$data['province'] = Request::instance()->post("province");
		$data['city'] = Request::instance()->post("city");
		$data['county'] = Request::instance()->post("county");
		$data['address'] = Request::instance()->post("address");
		$data['zipcode'] = Request::instance()->post("zipcode");
		$area = readCacheFile("arealist");
		$data['info'] = $area[$data['province']]['name'].$area[$data['city']]['name'].$area[$data['county']]['name'].$data['address'];
		$rst = ['rs'=>0,'msg'=>''];
		if($id>0){
			$rst['rs'] = membershipaddressModel::upd_data(['id'=>$id],$data);
		}else{
			$data['uid'] = $this->uid;
			$data['input_uid'] = $data['uid'];
			$list = membershipaddressModel::getOneByWhere(['is_lock'=>1,'uid'=>$this->uid],['count(1) num']);
			if($list['num']<20){
				if($list['num']<1) $data['is_default'] = 2; //添加第一条收货地址设置默认
				$rst['rs'] = membershipaddressModel::add_data($data);
			}else{
				$rst = ['rs'=>0,'msg'=>'您最多可以保存20条收货地址记录。'];
			}
		}
		echo json_encode($rst);
	}
	//收货地址设为默认
	public function setmoren($id){
		$rs = membershipaddressModel::upd_data(['uid'=>$this->uid],['is_default'=>1]);
		$rs = membershipaddressModel::upd_data(['id'=>$id],['is_default'=>2]);
		$this->redirect(Url('/home/Member/myshipaddress'));
	}
	//收货地址删除
	public function deldizhi($id){
		$rs = membershipaddressModel::upd_data(['id'=>$id],['is_del'=>2]);
		$this->redirect(Url('/home/Member/myshipaddress'));
	}
	//收货地址编辑
	public function getdizhiobj(){
		$id = Request::instance()->post("id");
		$data = [];
		if($id>0){
			$areaids = [];
			$data = membershipaddressModel::getByid($id);
			$areaids[] = $data['city'];
			$areaids[] = $data['county'];
			if(!empty($areaids)){
				$area = readCacheFile("arealist");
				$data['city_name'] = $area[$data['city']]['name'];
				$data['county_name'] = $area[$data['county']]['name'];
			}
		}
		echo json_encode($data);
	}
	
	/**
	 * 我的关注（图书馆、设计团队、设计师）
	 */
	public function mycollmember(){
		//关注收藏（cate=1会员2素材3众包4众筹5商品）
		$where = ['cate'=>1,'uid'=>$this->uid];
		$list = membercollModel::getList($where,[],'20');
		$this->assign('list', $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['otherid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl','cate']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		return $this->fetch();
	}
	
	/**
	 * 我的收藏
	 */
	public function mycolllist($cate='2'){
		$this->assign('cate', $cate);
		//关注收藏（cate=1会员2素材3众包4众筹5商品）
		$where = ['uid'=>$this->uid];
		if($cate==2) $where['cate'] = 2;
		if($cate==3) $where['cate'] = 3;
		if($cate==4) $where['cate'] = 4;
		if($cate==5) $where['cate'] = 5;
		$list = membercollModel::getList($where,[],'20');
		$this->assign('list', $list);
		//
		$idsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$idsarr[] = $val['otherid'];
			}
		}
		$idsarr = array_unique(array_filter($idsarr));
		$mlie = [];
		$url = '';
		if($cate==2){
			$url = Url('home/Material/detail');
			$mlie = materialModel::getListByWhere(['id'=>['in',$idsarr]],[]);
			$mlie = convert_arr_key($mlie,'id');
			//素材分类
			$catelist = readCacheFile("materialcate");
			$this->assign('catelist', $catelist);
		}
		if($cate==3){
			$url = Url('home/Crowdsourcing/detail');
			$mlie = crowdsourcingModel::getListByWhere(['id'=>['in',$idsarr]],[]);
			$mlie = convert_arr_key($mlie,'id');
			//众包分类
			$catelist = readCacheFile("crowdsourcingcate");
			$this->assign('catelist', $catelist);
		}
		if($cate==4){
			$url = Url('home/Crowdfunding/detail');
			$mlie = crowdfundingModel::getListByWhere(['id'=>['in',$idsarr]],[]);
			$mlie = convert_arr_key($mlie,'id');
			//众筹分类
			$catelist = readCacheFile("crowdfundcate");
			$this->assign('catelist', $catelist);
		}
		if($cate==5){
			$url = Url('home/Shop/detail');
			$mlie = shopgoodsModel::getListByWhere(['id'=>['in',$idsarr]],[]);
			$mlie = convert_arr_key($mlie,'id');
			//商品分类
			$catelist = readCacheFile("shopgoodscate");
			$this->assign('catelist', $catelist);
		}
		$this->assign('url', $url);
		$this->assign('mlie', $mlie);
		
		return $this->fetch();
	}
	
	/**
	 * 消息管理
	 */
	public function newslist($type=''){
		$this->assign('type', $type);
		session('newsList',Request::instance()->url());
		$this->newsList = Session::get('newsList');
		$this->assign('newsList', $this->newsList);
		//列表
		$where = ['suid'=>$this->uid];
		if($type==1) $where['status'] = 1;
		if($type==2) $where['status'] = 2;
		$list = msgrecordsModel::getList($where,[],'20');
		$this->assign('list', $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['input_uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl','cate']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		return $this->fetch();
	}
	//消息查看
	public function readnews($id){
		$rs = msgrecordsModel::upd_data(['id'=>$id],['status'=>2]);
		$this->redirect(Session::get('newsList'));
	}
	
	/**
	 * 成员馆新闻动态文章
	 */
	public function trends(){
		session('trendsList',Request::instance()->url());
		$list = articleModel::getList(['is_lock'=>1,'type'=>3,'input_uid'=>$this->uid]);
		$this->assign("list", $list);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		return $this->fetch();
	}
	/**
	 * 添加
	 */
	public function trendsadd(){
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['input_uid'] = $this->uid;
			$data['type'] = 3; //成员馆动态
			$data['title'] = $post['title'];
			$data['summary'] = $post['summary'];
			$data['cover'] = basename($post['cover']);
			$content = $post['content'];
			$data['content'] = $content;
			$id = articleModel::add_data($data);
			$this->success('添加成功。', Session::get('trendsList'));
		}
		return $this->fetch();
	}
	/**
	 * 编辑
	 */
	public function trendsupd($id){
		$obj = articleModel::getByid($id);
		$this->assign("obj", $obj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['type'] = 3; //成员馆动态
			$data['title'] = $post['title'];
			$data['summary'] = $post['summary'];
			$data['cover'] = basename($post['cover']);
			$content = $post['content'];
			$data['content'] = $content;
			$rs = articleModel::upd_data(['id'=>$id], $data);
			$this->success('编辑成功。', Session::get('trendsList'));
		}
		return $this->fetch();
	}
	
	/**
	 * 我的购物车
	 */
	public function myshopcart(){
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $data['uid'];
			$data['gid'] = $post['gid'];
			$data['sgnid'] = isset($post['sgnid'])?$post['sgnid']:0;
			$data['num'] = $post['num'];
			$scobj = shopcartModel::getOneByWhere(['uid'=>$this->uid,'gid'=>$data['gid'],'sgnid'=>$data['sgnid']]);
			if(!empty($scobj)){
				$id = $scobj['id'];
				$num = ($scobj['num']+$data['num']);
				$upd = ['num'=>$num,'ruleid'=>0,'agio'=>0,'amount'=>0];
				$rst = $this->getzhjbygidnum($scobj['gid'],$num,$scobj['price']); //计算折扣
				if(!empty($rst)){
					$upd['ruleid'] = $rst['ruleid'];
					$upd['agio'] = $rst['agio'];
					$upd['amount'] = $rst['amount'];
				}
				$rs = shopcartModel::upd_data(['id'=>$scobj['id']],$upd);
			}else{
				if($data['sgnid']>0){
					$ggobj = shopgoodsnormsModel::getByid($data['sgnid']);
					$data['price'] = $ggobj['price'];
				}else{
					$gsobj = shopgoodsModel::getByid($data['gid']);
					$data['price'] = $gsobj['price'];
				}
				$rst = $this->getzhjbygidnum($data['gid'],$data['num'],$data['price']); //计算折扣
				if(!empty($rst)){
					$data['ruleid'] = $rst['ruleid'];
					$data['agio'] = $rst['agio'];
					$data['amount'] = $rst['amount'];
				}else{
					$data['ruleid'] = 0;
					$data['agio'] = 0;
					$data['amount'] = 0;
				}
				$id = shopcartModel::add_data($data);
			}
			$this->redirect(Url('home/Member/myshopcart'));
		}
		$field = ['a.*,b.title,b.cover,b.sno'];
		$list = shopcartModel::getLeftjoinList(['uid'=>$this->uid],$field);
		$this->assign('list', $list);
		//sgnid列表
		$idsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				if($val['sgnid']>0) $idsarr[] = $val['sgnid'];
			}
		}
		$idsarr = array_unique(array_filter($idsarr));
		$sgnlist = shopgoodsnormsModel::getListByWhere(['id'=>['in',$idsarr]]);
		$sgnlist = convert_arr_key($sgnlist,'id');
		$this->assign("sgnlist", $sgnlist);
		//规格
		$shopnorms = readCacheFile("shopnorms");
		$this->assign('shopnorms', $shopnorms);
		$snormsval = readCacheFile("shopnormsval");
		$this->assign('snormsval', $snormsval);
		return $this->fetch();
	}
	//异步更新购物车数量
	public function ajaxsetcartnum($id){
		$num = Request::instance()->post("num");
		$data = ['ruleid'=>0,'amount'=>0,'agio'=>0];
		if($id>0 && $num>0){
			$upd = ['num'=>$num,'ruleid'=>0,'agio'=>0,'amount'=>0];
			$obj = shopcartModel::getByid($id);
			$rst = $this->getzhjbygidnum($obj['gid'],$num,$obj['price']);
			if(!empty($rst)){
				$data['ruleid'] = $rst['ruleid'];
				$data['agio'] = $rst['agio'];
				$data['amount'] = $rst['amount'];
				$upd['ruleid'] = $rst['ruleid'];
				$upd['agio'] = $rst['agio'];
				$upd['amount'] = $rst['amount'];
			}
			$rs = shopcartModel::upd_data(['id'=>$id],$upd);
		}
		echo json_encode($data);
	}
	//根据商品gid、数量num、单价price返回规则折扣信息及折后价
	public function getzhjbygidnum($id,$num,$price){
		$data = [];
		$where = ['is_lock'=>1,'gid'=>$id,'cate'=>2,'num'=>['<=',$num]];
		$ruletwo = shopgoodsruleModel::getOneByWhere($where,[],'num desc');
		if(!empty($ruletwo)){
			$new_price = ($ruletwo['agio']/10) * ($num*$price); //折扣除以10
			$data['amount'] = $new_price;
			$data['agio'] = $ruletwo['agio'];
			$data['ruleid'] = $ruletwo['id'];
		}
		return $data;
	}
	//删除购物车
	public function delmsc($id){
		if($id>0){
			$rs = shopcartModel::delwhr(['id'=>$id]);
		}
		$this->redirect(Url('home/Member/myshopcart'));
	}
	
	/**
	 * 下单
	 */
	public function orderadd($ids){
		$this->assign('ids', $ids);
		//收货地址
		$dzlist = membershipaddressModel::getListByWhere(['is_lock'=>1,'uid'=>$this->uid],[],'is_default desc,id desc');
		$this->assign('dzlist', $dzlist);
		$areaerji = readCacheFile("areatop");
		$this->assign('areaerji', $areaerji);
		//商品列表
		$list = shopcartModel::getLeftjoinList(['id'=>['in',$ids]],['a.*,b.uid selluid,b.title,b.cover,b.sno']);
		$this->assign('list', $list);
		//sgnid列表
		$glie = [];
		$idsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$glie[$val['id']] = $val;
				if($val['sgnid']>0) $idsarr[] = $val['sgnid'];
			}
		}
		$idsarr = array_unique(array_filter($idsarr));
		$sgnlist = shopgoodsnormsModel::getListByWhere(['id'=>['in',$idsarr]]);
		$sgnlist = convert_arr_key($sgnlist,'id');
		$this->assign("sgnlist", $sgnlist);
		//
		$glie = convert_arr_key($list,'id');
		$selluid_arr = [];
		foreach ($list as $key=>$val){
			$selluid_arr[$val['selluid']][] = $val['id'];
		}
		if(Request::instance()->isPost() && $this->uid>0 && $ids!=''){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $data['uid'];
			$data['msarid'] = $post['msarid'];
			$data['is_invoice'] = $post['is_invoice'];
			$data['invoice_name'] = $post['invoice_name'];
			$data['invoice_sn'] = $post['invoice_sn'];
			$data['title'] = '购买商品';
			$obj = membershipaddressModel::getByid($data['msarid']);
			$data['sh_name'] = $obj['truename'];
			$data['sh_mobile'] = $obj['mobile'];
			$data['sh_area'] = $post['sh_area'];
			$data['leavemes'] = $post['leavemes'];
			//一个商家生成一个订单
			$ordsn_arr = [];
			foreach ($selluid_arr as $iii=>$item){
				$data['m_uid'] = $iii; //卖家uid
				$data['scidstr'] = implode(",",$item);
				$tot_price = 0;
				$sogarr = [];
				foreach ($item as $key=>$val){
					if($glie[$val]['ruleid']>0 && $glie[$val]['agio']>0 && $glie[$val]['amount']>0){
						$tot_price += $glie[$val]['amount'];
					}else{
						$tot_price += ($glie[$val]['num']*$glie[$val]['price']);
					}
					$data['title'].= '《'.$glie[$val]['title'].'》';
				}
				$data['amount'] = $tot_price;
				$ordsn = shoporderModel::add_data($data);
				$ordsn_arr[] = $ordsn;
				//订单商品表
				$sogarr = [];
				foreach ($item as $key=>$val){
					$sogarr[$key]['ordsn'] = $ordsn;
					$sogarr[$key]['uid'] = $this->uid;
					$sogarr[$key]['input_uid'] = $this->uid;
					$sogarr[$key]['input_time'] = time();
					$sogarr[$key]['gid'] = $glie[$val]['gid'];
					$sogarr[$key]['sgnid'] = $glie[$val]['sgnid'];
					$sogarr[$key]['sno'] = $glie[$val]['sno'];
					$sogarr[$key]['num'] = $glie[$val]['num'];
					$sogarr[$key]['price'] = $glie[$val]['price'];
					$sogarr[$key]['amount'] = $glie[$val]['num']*$glie[$val]['price'];
				}
				$rs = shoporderModel::sog_add_all($sogarr);
			}
			//删除购物车信息
			$rs = shopcartModel::delwhr(['id'=>['in',$ids]]);
			$this->success('下单成功，正在转到支付页面',Url('home/Member/paysuccess','sn='.implode(",",$ordsn_arr)));
		}
		return $this->fetch();
	}
	
	/**
	 * 付款 $sn订单号
	 */
	public function pay($sn){
		
		return $this->fetch();
	}
	
	/**
	 * 付款成功
	 */
	public function paysuccess($sn){
		$snarr = explode(",",$sn);
		$obj = [];
// 		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
// 		$this->assign('obj', $obj);
		$list = shoporderModel::getListByWhere(['ordsn'=>['in',$sn]]);
		$this->assign('list', $list);
		foreach ($list as $key=>$val){
			$obj['snstr'] .= $val['ordsn'].',';
			$obj['amount'] += $val['amount'];
		}
		$this->assign('obj', $obj);
		return $this->fetch();
	}
	
}


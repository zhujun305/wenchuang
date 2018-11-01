<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Cache;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\membercollModel;
use app\model\memberskilltagsModel;
use app\model\articleModel;
use app\model\materialModel;
use app\model\crowdfundingModel;
use app\model\crowdsourcingModel;
use app\model\crowdsourccateModel;
use app\model\crowdsourcwtbModel;
use app\model\csworksModel;
use app\model\csworkspingModel;
use app\model\shopgoodsModel;

/**
 * 设计师、设计团队、联盟会员
 */
class Webmember extends Homebase
{
	public function _initialize() {
		parent::_initialize ();
		$this->uid = Session::get('portalUserUid');
		if(Request::instance()->action() == 'index'){
			$this->member = $this->setportalUser();
		}else{
			$this->member = $this->getportalUser();
		}
		$this->assign('member', $this->member);
		$this->assign('method', Request::instance()->action());
		$this->get_uid = Request::instance()->get('uid');
		if(!($this->get_uid>0)){
			$this->error('会员不存在！', $_SERVER['HTTP_REFERER']);
		}
		$this->userobj = memberModel::getByid($this->get_uid);
		$this->assign('userobj', $this->userobj);
		$this->userauth = memberauthModel::getOneByWhere(array('uid'=>$this->get_uid));
		$this->assign('userauth', $this->userauth);
		if(!(in_array($this->userauth['cate'],['2','3','4']))){
			$this->error('会员未认证！', $_SERVER['HTTP_REFERER']);
		}
		//是否被关注（cate=1会员2素材3众包4众筹5商品）
		$coll_gz = membercollModel::getOneByWhere(['cate'=>1,'uid'=>$this->uid,'otherid'=>$this->get_uid],[]);
		$this->assign('collid', $coll_gz['id']);
	}
	
	/**
	 * 首页
	 */
	public function index(){
		if(in_array($this->userauth['cate'],['2','3'])){
			$xzarr = [];
			if(!empty($this->userobj['skilltags'])){
				$xzarr = array_filter(explode(",",$this->userobj['skilltags']));
			}
			$this->assign('xzarr', $xzarr);
			//标签
			$catelist = readCacheFile("memberskilltags");
			$this->assign('catelist', $catelist);
			//推荐作品
			$where = array('is_lock'=>1,'uid'=>$this->get_uid,'is_chk'=>2,'is_settop'=>2);
			$zplist = csworksModel::getListByWhere($where,[],'is_settop desc,id desc','8');
			$this->assign('zplist', $zplist);
		}elseif($this->userauth['cate']==4){
			$where = ['is_lock'=>1,'is_chk'=>2,'uid'=>$this->get_uid];
			$sclist = materialModel::getList($where,[],'8');
			$this->assign('sclist', $sclist);
		}
        return $this->fetch();
	}
	
	/**
	 * ajax返回统计信息
	 */
	public function ajaxRscount(){
    	$uid = Request::instance()->post("uid");
		$data = ['a'=>0,'b'=>0,'c'=>0,'d'=>0,'e'=>0,];
		//关注(member_coll字段cate=1)
		$gznum = membercollModel::getOneByWhere(['cate'=>1,'uid'=>$uid],['count(1) num']);
		$data['a'] = $gznum['num']>0?$gznum['num']:0;
		if(in_array($this->userauth['cate'],['2','3'])){
			//作品
			$zpnum = csworksModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$uid],['count(1) num']);
			$data['b'] = $zpnum['num']>0?$zpnum['num']:0;
			//项目
			$xmnum = crowdsourcwtbModel::getOneByWhere(['is_lock'=>1,'uid'=>$uid],['count(1) num']);
			$data['c'] = $xmnum['num']>0?$xmnum['num']:0;
		}
		if($this->userauth['cate']==4){
			//素材
			$scnum = materialModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$uid],['count(1) num']);
			$data['b'] = $scnum['num']>0?$scnum['num']:0;
			//商品
			$sgoods = shopgoodsModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$uid],['count(1) num']);
			$data['c'] = $sgoods['num']>0?$sgoods['num']:0;
			//众包
			$zbnum = crowdsourcingModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$uid],['count(1) num']);
			$data['d'] = $zbnum['num']>0?$zbnum['num']:0;
			//众筹
			$zcnum = crowdfundingModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$uid],['count(1) num']);
			$data['e'] = $zcnum['num']>0?$zcnum['num']:0;
		}
		echo json_encode($data);
	}
	
	/**
	 * 设计-作品
	 */
	public function sjcsworks(){
		//列表
		$where = array('is_lock'=>1,'uid'=>$this->get_uid,'is_chk'=>2);
		$list = csworksModel::getList($where,[],'16','is_settop desc,id desc');
		$this->assign('list', $list);
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
        return $this->fetch();
	}
	
	/**
	 * 设计-作品详情
	 */
	public function sjcsworksdetail($id){
		$obj = csworksModel::getByid($id);
		$this->assign('obj',$obj);
		//设计
		$wuser = memberModel::getByid($obj['uid'],['uid','cate','nick_name','imgurl']);
		$this->assign('wuser', $wuser);
		//图片列表
		$imglist = Db::table('cs_works_img')->where(['cswsid'=>$id])->select();
		$this->assign('imglist',$imglist);
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
		//点评列表
		$dplist = csworkspingModel::getListByWhere(['cswsid'=>$id],[],'id asc');
		$this->assign('dplist', $dplist);
		//会员uid列表
		$uidarr = [];
		if(!empty($dplist)){
			foreach ($dplist as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		//更新访问量
		csworksModel::upd_data(['id'=>$id],['pv'=>($obj['pv']+1)]);
		return $this->fetch();
	}
	
	//作品点赞
	public function dzlikes(){
		$id = Request::instance()->post("id");
		$dzlikes = unserialize(Cookie::get('dzlikes'));
		$dzlikesarr = unserialize(Cookie::get('dzlikesarr'));
		$data = ['rs'=>1,'msg'=>'点赞成功'];
		if(!in_array($id, $dzlikes) || !isset($dzlikesarr[$id])){
			$dzlikes[] = $id;
			$dzlikesarr[$id] = time();
			Cookie::set('dzlikes',serialize($dzlikes));
			Cookie::set('dzlikesarr',serialize($dzlikesarr));
		}else{
			$shicha = time()-$dzlikesarr[$id];
			if($shicha > 86400){
				$dzlikesarr[$id] = time();
				Cookie::set('dzlikesarr',serialize($dzlikesarr));
			}else{
				$data = ['rs'=>0,'msg'=>'同一个作品一天内只能点赞一次。'];
			}
		}
		if(count($dzlikes)>30){
			foreach ($dzlikesarr as $key=>$val){
				$shicha = time()-$val;
				if($shicha>86400){
					array_diff($dzlikes,[$key]);
					unset($dzlikesarr[$key]);
				}
			}
			Cookie::set('dzlikes',serialize($dzlikes));
			Cookie::set('dzlikesarr',serialize($dzlikesarr));
		}
		//点赞量+1
		if($data['rs']){
			$rs = csworksModel::upd_data(['id'=>$id],['likes'=>($obj['likes']+1)]);
		}
		echo json_encode($data);
	}
	
	//作品评论
	public function csworksping(){
		$data = [];
		$data['cswsid'] = Request::instance()->post("id");
		$data['uid'] = Request::instance()->post("uid");
		$data['pid'] = Request::instance()->post("pid");
		$data['content'] = Request::instance()->post("content");
		$data['input_time'] = time();
		if($data['cswsid']>0 && $data['uid']>0 && !empty($data['content'])){
			$id = csworkspingModel::add_data($data);
			$rst = ['rs'=>1,'msg'=>'点评成功'];
		}else{
			$rst = ['rs'=>0,'msg'=>'点评失败'];
		}
		echo json_encode($rst);
	}
	
	/**
	 * 设计-众包
	 */
	public function sjcsbao($type='1'){
		$this->assign('type',$type);
		$where = array('is_lock'=>1,'is_chk'=>2,'wuid'=>$this->get_uid);
		if($type==2){
			$where['status'] = ['>',4];
		}else{
			$where['status'] = ['<',5];
		}
		$list = crowdsourcingModel::getLeftjoinList($where);
		$this->assign('list', $list);
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
		//众包项目状态
		$status_arr = Config::get('config.cs_status');
		$this->assign('status_arr', $status_arr);
        return $this->fetch();
	}
	
	/**
	 * 联盟-素材
	 */
	public function lmmaterial(){
		$where = ['is_lock'=>1,'is_chk'=>2,'uid'=>$this->get_uid];
		$list = materialModel::getList($where,[],'16');
		$this->assign('list', $list);
		//素材分类
		$catelist = readCacheFile("materialcate");
		$this->assign('catelist', $catelist);
        return $this->fetch();
	}
	
	/**
	 * 联盟-商品
	 */
	public function lmgoods(){
		$where = ['is_lock'=>1,'is_chk'=>2,'uid'=>$this->get_uid];
		$list = shopgoodsModel::getList($where,[],'12');
		$this->assign("list", $list);
        return $this->fetch();
	}
	
	/**
	 * 联盟-众包
	 */
	public function lmcsourcing($type='1'){
		$this->assign('type',$type);
		$where = array('is_lock'=>1,'is_chk'=>2,'uid'=>$this->get_uid);
		if($type==2){
			$where['status'] = ['>',4];
		}else{
			$where['status'] = ['<',5];
		}
		$list = crowdsourcingModel::getList($where,[],'10');
		$this->assign('list', $list);
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
		//众包项目状态
		$status_arr = Config::get('config.cs_status');
		$this->assign('status_arr', $status_arr);
        return $this->fetch();
	}
	
	/**
	 * 联盟-众筹
	 */
	public function lmcfunding($type='1'){
		$this->assign('type',$type);
		//列表
		$where = array('is_lock'=>1,'is_chk'=>2,'uid'=>$this->get_uid);
		if($sts==1) $where['status'] = ['!=',2];
		if($sts==2) $where['status'] = 2;
		$list = crowdfundingModel::getList($where,[],'10');
		$this->assign('list', $list);
		//众筹分类
		$catelist = readCacheFile("crowdfundcate");
		$this->assign('catelist', $catelist);
        return $this->fetch();
	}
	
	/**
	 * 联盟-动态
	 */
	public function lmtrends(){
		$list = articleModel::getList(['is_lock'=>1,'is_chk'=>2,'type'=>3]);
		$this->assign("list", $list);
        return $this->fetch();
	}
	
	/**
	 * 联盟-动态详情
	 */
	public function lmtrendsxx($id){
		$obj = articleModel::getByid($id);
		$this->assign("obj", $obj);
        return $this->fetch();
	}
	
	/**
	 * 给投稿人发私信
	 */
	public function uidsendmsg(){
		$type = 2; //类型（1系统、2私信）
		$fuid = Request::instance()->post("fuid");
		$suid = Request::instance()->post("suid");
		if($csid>0){
			$content = "用户【".$this->userobj['nick_name']."】给您发来私信：";
		}
		$content.= Request::instance()->post("content");
		$rs = $this->send_msg($type,$fuid,$suid,$content); //type类型，fuid发件人，suid收件人,content内容
		echo json_encode($rs);
	}
	
}

<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\articleModel;

/**
 * 文章新闻公告
 */
class Article extends Homebase
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
	}
	
	/**
	 * 首页
	 */
	public function index($type=1){
		if($type!=2) $type=1;
		$this->assign('type', $type);
		$list = articleModel::getList(['is_lock'=>1,'is_chk'=>2,'type'=>$type]);
		$this->assign("list", $list);
		//热点新闻
		$where['type'] = 1;
		$xwlist = articleModel::getListByWhere($where,[],'is_recommend desc,pv desc,id desc','8');
		$this->assign('xwlist', $xwlist);
		//广告
		$ads = $this->getAds('articleright');
		$this->assign('ads', $ads);
        return $this->fetch('article');
	}
	
	/**
	 * 文章详情
	 */
	public function detail($id){
		$obj = articleModel::getByid($id);
		$this->assign('obj',$obj);
		//热点新闻
		$where['type'] = 1;
		$xwlist = articleModel::getListByWhere($where,[],'is_recommend desc,pv desc,id desc','8');
		$this->assign('xwlist', $xwlist);
		//广告
		$ads = $this->getAds('articleright');
		$this->assign('ads', $ads);
		return $this->fetch();
	}
	
	//作品点赞
	public function dzlikes(){
		$id = Request::instance()->post("id");
		$artids = unserialize(Cookie::get('artids'));
		$artidsarr = unserialize(Cookie::get('artidssarr'));
		$data = ['rs'=>1,'msg'=>'点赞成功'];
		if(!in_array($id, $artids) || !isset($artidsarr[$id])){
			$artids[] = $id;
			$artidsarr[$id] = time();
			Cookie::set('artids',serialize($artids));
			Cookie::set('artidssarr',serialize($artidsarr));
		}else{
			$shicha = time()-$artidsarr[$id];
			if($shicha > 86400){
				$artidsarr[$id] = time();
				Cookie::set('artidssarr',serialize($artidsarr));
			}else{
				$data = ['rs'=>0,'msg'=>'同一个文章一天内只能点赞一次。'];
			}
		}
		if(count($artids)>30){
			foreach ($artidsarr as $key=>$val){
				$shicha = time()-$val;
				if($shicha>86400){
					array_diff($artids,[$key]);
					unset($artidsarr[$key]);
				}
			}
			Cookie::set('artids',serialize($artids));
			Cookie::set('artidssarr',serialize($artidsarr));
		}
		//点赞量+1
		if($data['rs']){
			$rs = articleModel::upd_data(['id'=>$id],['likes'=>($obj['likes']+1)]);
		}
		echo json_encode($data);
	}
	
}

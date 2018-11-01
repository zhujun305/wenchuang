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
use app\model\articleModel;
use app\model\helpModel;

/**
 * 关于-帮助-联系我们
 */
class About extends Homebase
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
		$this->assign('controller', Request::instance()->controller());
		//单页类型
		$cate_arr = Config::get('config.help_cate');
		$this->assign('cate_arr', $cate_arr);
	}
	
	/**
	 * 首页
	 */
	public function index(){
		$obj = helpModel::getOneByWhere(['cate'=>'about']);
		$this->assign('obj',$obj);
		//热点新闻
		$where['type'] = 1;
		$xwlist = articleModel::getListByWhere($where,[],'is_recommend desc,pv desc,id desc','8');
		$this->assign('xwlist', $xwlist);
		//广告
		$ads = $this->getAds('articleright');
		$this->assign('ads', $ads);
        return $this->fetch('index/about');
	}
	
	/**
	 * 详情
	 */
	public function detail($id){
		$obj = helpModel::getByid($id);
		$this->assign('obj',$obj);
		//热点新闻
		$where['type'] = 1;
		$xwlist = articleModel::getListByWhere($where,[],'is_recommend desc,pv desc,id desc','8');
		$this->assign('xwlist', $xwlist);
		//广告
		$ads = $this->getAds('articleright');
		$this->assign('ads', $ads);
        return $this->fetch('index/about');
	}
	
	
}

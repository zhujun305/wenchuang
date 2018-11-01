<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\shoporderModel;
use app\model\shopgoodsnormsModel;

/**
 * 商品订单管理
 */
class Shoporders extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->shopordersList = Session::get('shopordersList');
		$this->assign('shopordersList', $this->shopordersList);
		//订单状态
		$this->status_arr = Config::get('config.sporder_status');
		$this->assign('status_arr', $this->status_arr);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/shoporders/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('shopordersList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'title,pay_status,status,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = shoporderModel::getList($findObj);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id){
		$obj = shoporderModel::getByid($id);
		$this->assign("obj", $obj);
		$member = memberModel::getByid($obj['uid'],['uid','nick_name']);
		$this->assign("member", $member);
		//规格商品列表
		$field = ['a.*,b.title,b.cover,b.sno'];
		$list = shoporderModel::getGoodsByWhere(['a.ordsn'=>$obj['ordsn']],$field);
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
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = shoporderModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->shopordersList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = shoporderModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->shopordersList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = shoporderModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->shopordersList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = shoporderModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->shopordersList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = shoporderModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->shopordersList);
	}
	
}

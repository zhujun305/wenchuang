<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberModel;
use app\model\crowdfundingModel;
use app\model\crowdfundcateModel;
use app\model\crowdfundorderModel;

/**
 * 众筹订单管理
 */
class Crowdfundingorder extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->crowdfundingorderList = Session::get('crowdfundingorderList');
		$this->assign('crowdfundingorderList', $this->crowdfundingorderList);
		//众包分类
		$catelist = readCacheFile("crowdfundingorder");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/crowdfundingorder/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('crowdfundingorderList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'ordsn,uid,cfid,status,time1,time2';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = crowdfundorderModel::getList($findObj,[],'20');
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		$idsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
				$idsarr[] = $val['cfid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		$idsarr = array_unique(array_filter($idsarr));
		$cdfdlie = crowdfundingModel::getListByWhere(['id'=>['in',$idsarr]]);
		$cdfdlie = convert_arr_key($cdfdlie,'id');
		$this->assign("cdfdlie", $cdfdlie);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id){
		$obj = crowdfundorderModel::getByid($id);
		$this->assign("obj", $obj);
		$member = memberModel::getByid($obj['uid'],['uid','nick_name']);
		$this->assign("member", $member);
		return $this->fetch();
	}
	
}

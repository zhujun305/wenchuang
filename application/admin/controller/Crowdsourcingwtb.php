<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\crowdsourcingModel;
use app\model\crowdsourccateModel;
use app\model\crowdsourcwtbModel;

/**
 * 众包投稿管理
 */
class Crowdsourcingwtb extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->cdccingList = Session::get('cdccingList');
		$this->assign('cdccingList', $this->cdccingList);
		//投稿状态
		$status_arr = Config::get('config.wtb_status');
		$this->assign('status_arr', $status_arr);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/crowdsourcingwtb/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('cdccingList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'id,uid,cs_id,title,status,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = crowdsourcwtbModel::getList($findObj);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		$csidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
				$csidarr[] = $val['cs_id'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		//众包信息
		$csidarr = array_unique(array_filter($csidarr));
		$cslist = crowdsourcingModel::getListByWhere(['id'=>['in',$csidarr]],['id','cover','title']);
		$this->assign("cslist", $cslist);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id){
		$obj = crowdsourcwtbModel::getByid($id);
		$this->assign("obj", $obj);
		$member = memberModel::getByid($obj['uid'],['uid','nick_name']);
		$this->assign("member", $member);
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = crowdsourcwtbModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->cdccingList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = crowdsourcwtbModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->cdccingList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = crowdsourcwtbModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->cdccingList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = crowdsourcwtbModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->cdccingList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = crowdsourcwtbModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->cdccingList);
	}
	
}

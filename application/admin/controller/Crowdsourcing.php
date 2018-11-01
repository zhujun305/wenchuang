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
 * 众包管理
 */
class Crowdsourcing extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->crowdsourcingList = Session::get('crowdsourcingList');
		$this->assign('crowdsourcingList', $this->crowdsourcingList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//众包项目状态
		$status_arr = Config::get('config.cs_status');
		$this->assign('status_arr', $status_arr);
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/crowdsourcing/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('crowdsourcingList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'cate,title,is_chk,status,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = crowdsourcingModel::getList($findObj);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id,$type=''){
		$this->assign("type", $type); //2认证审核
		$obj = crowdsourcingModel::getByid($id);
		$this->assign("obj", $obj);
		$member = memberModel::getByid($obj['uid'],['uid','nick_name']);
		$this->assign("member", $member);
		//中标会员
		$omem = memberModel::getByid($obj['wtb_uid'],['uid','nick_name']);
		$this->assign("omem", $omem);
		//中标稿件
		$zbgaoj = crowdsourcwtbModel::getByid($obj['wtb_id']);
		$this->assign("zbgaoj", $zbgaoj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['is_chk'] = isset($post['is_chk'])?$post['is_chk']:1;
			$remark = isset($post['remark'])?$post['remark']:'';
			$new_remark = "管理员".$this->user['id']."【".$this->user['truename']."】".date("Y-m-d H:i:s")." ";
			if($data['is_chk'] == 2){
				$data['status'] = 2; //项目状态（2已审核）
				$new_remark.= "众包审核通过。";
			}
			if($data['is_chk'] == 3) $new_remark.= "众包审核不通过。";
			$data['remark'] = $obj['remark'].$new_remark.$remark."\r\n";
			$where = ['id'=>$id];
			$rs = crowdsourcingModel::upd_data($where, $data);
// 			//6. 会员提交众包设计方案奖励10积分；
// 			if($data['is_chk']==2 && $obj['uid']>0){
// 				$this->addintegral($obj['uid'],1,4);//会员，收支类型，收入方式，支出方式
// 			}
			$this->success('审核提交成功。',$this->crowdsourcingList);
		}
		return $this->fetch();
	}
	
	/**
	 * 批量审核
	 */
	public function ischkall($ids){
		$rs = crowdsourcingModel::upd_list($ids, ['is_chk'=>2]);
		$this->redirect($this->crowdsourcingList);
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = crowdsourcingModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->crowdsourcingList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = crowdsourcingModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->crowdsourcingList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = crowdsourcingModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->crowdsourcingList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = crowdsourcingModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->crowdsourcingList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = crowdsourcingModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->crowdsourcingList);
	}
	
}

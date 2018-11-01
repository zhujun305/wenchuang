<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\crowdfundingModel;
use app\model\crowdfundcateModel;
use app\model\crowdfundruleModel;

/**
 * 众筹管理
 */
class Crowdfunding extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->crowdfundingList = Session::get('crowdfundingList');
		$this->assign('crowdfundingList', $this->crowdfundingList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//众筹项目状态
		$status_arr = Config::get('config.cf_status');
		$this->assign('status_arr', $status_arr);
		//众筹分类
		$catelist = readCacheFile("crowdfundcate");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/crowdfunding/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('crowdfundingList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'cate,title,is_chk,status,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = crowdfundingModel::getList($findObj);
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
		$obj = crowdfundingModel::getByid($id);
		$this->assign("obj", $obj);
		$member = memberModel::getByid($obj['uid'],['uid','nick_name']);
		$this->assign("member", $member);
		//折扣规则
		$list = crowdfundruleModel::getListByWhere(['cfid'=>$id]);
		$this->assign('list',$list);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['is_chk'] = isset($post['is_chk'])?$post['is_chk']:1;
			$remark = isset($post['remark'])?$post['remark']:'';
			$new_remark = "管理员".$this->user['id']."【".$this->user['truename']."】".date("Y-m-d H:i:s")." ";
			if($data['is_chk'] == 2){
				$data['status'] = 2; //项目状态（2已审核）
				$new_remark.= "众筹审核通过。";
			}
			if($data['is_chk'] == 3) $new_remark.= "众筹审核不通过。";
			$data['remark'] = $obj['remark'].$new_remark.$remark."\r\n";
			$where = ['id'=>$id];
			$rs = crowdfundingModel::upd_data($where, $data);
			$this->success('审核提交成功。',$this->crowdfundingList);
		}
		return $this->fetch();
	}
	
	/**
	 * 批量审核
	 */
	public function ischkall($ids){
		$rs = crowdfundingModel::upd_list($ids, ['is_chk'=>2]);
		$this->redirect($this->crowdfundingList);
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = crowdfundingModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->crowdfundingList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = crowdfundingModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->crowdfundingList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = crowdfundingModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->crowdfundingList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = crowdfundingModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->crowdfundingList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = crowdfundingModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->crowdfundingList);
	}
	
}

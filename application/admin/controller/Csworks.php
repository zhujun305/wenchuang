<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\csworksModel;

/**
 * 作品管理
 */
class Csworks extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->csworksList = Session::get('csworksList');
		$this->assign('csworksList', $this->csworksList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/csworks/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('csworksList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'uid,title,cate,is_settop,is_chk,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = csworksModel::getList($findObj);
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
		$this->assign("type", $type); //2审核
		$obj = csworksModel::getByid($id);
		$this->assign("obj", $obj);
		$member = memberModel::getByid($obj['uid'],['uid','nick_name']);
		$this->assign("member", $member);
		//图片列表
		$piclist = Db::table('cs_works_img')->where(['cswsid'=>$id])->select();
		$this->assign("piclist", $piclist);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['is_chk'] = isset($post['is_chk'])?$post['is_chk']:1;
			$remark = isset($post['remark'])?$post['remark']:'';
			$new_remark = "管理员".$this->user['id']."【".$this->user['truename']."】".date("Y-m-d H:i:s")." ";
			if($data['is_chk'] == 2){
				$new_remark.= "众包审核通过。";
			}
			if($data['is_chk'] == 3) $new_remark.= "众包审核不通过。";
			$data['remark'] = $obj['remark'].$new_remark.$remark."\r\n";
			$where = ['id'=>$id];
			$rs = csworksModel::upd_data($where, $data);
			//7. 设计师发布自己的作品奖励5积分；
			if($data['is_chk']==2 && $obj['uid']>0){
				$this->addintegral($obj['uid'],1,5);//会员，收支类型，收入方式，支出方式
			}
			$this->success('审核提交成功。',$this->csworksList);
		}
		return $this->fetch();
	}
	
	/**
	 * 批量审核
	 */
	public function ischkall($ids){
		//7. 设计师发布自己的作品奖励5积分；
		$ary = explode ( '-', $ids );
		$list = csworksModel::getListByWhere(['id'=>['in',$ary]]);
		foreach ($list as $key=>$val){
			if($val['is_chk']==1 && $val['uid']>0){
				$this->addintegral($val['uid'],1,5);//会员，收支类型，收入方式，支出方式
			}
		}
		$rs = csworksModel::upd_list($ids, ['is_chk'=>2]);
		$this->redirect($this->csworksList);
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = csworksModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->csworksList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = csworksModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->csworksList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = csworksModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->csworksList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = csworksModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->csworksList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = csworksModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->csworksList);
	}
	
}

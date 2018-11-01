<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\memberauthModel;

/**
 * 会员管理
 */
class Member extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->memberList = Session::get('memberList');
		$this->assign('memberList', $this->memberList);
		$sexarr = ['0'=>'不确定','1'=>'男','2'=>'女',];
		$this->assign('sexarr', $sexarr);
		//会员类型名称
		$membercate = Config::get('config.membercate');
		$this->assign('membercate', $membercate);
		//技能标签
		$this->stagslist = readCacheFile("memberskilltags");
		$this->assign('stagslist', $this->stagslist);
	}

	public function index(){
		$this->redirect(Url('/Admin/Member/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('memberList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'uid,user_name,nick_name,mobile,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = memberModel::getList($findObj);
		$this->assign("list", $list);
		//会员uid列表
		$tagsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				if($val['skilltags']!=''){
					$ary = explode(",",$val['skilltags']);
					$str = '';
					foreach ($ary as $kkk=>$vvv){
						$str.= isset($this->stagslist[$vvv]['name'])?$this->stagslist[$vvv]['name'].'，':'';
					}
					$tagsarr[$val['uid']] = $str;
				}
			}
		}
		$this->assign("tagsarr", $tagsarr);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id){
		$obj = memberModel::getByid($id);
		$this->assign("obj", $obj);
		$strtags = '';
		$ary = explode(",",$obj['skilltags']);
		foreach ($ary as $kkk=>$vvv){
			$strtags.= isset($this->stagslist[$vvv]['name'])?$this->stagslist[$vvv]['name'].'，':'';
		}
		$this->assign("strtags", $strtags);
		return $this->fetch();
	}
	
	/**
	 * 锁定
	 */
	public function lock($id){
		$rs = memberModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->memberList);
	}
	
	/**
	 * 解锁
	 */
	public function unlock($id){
		$rs = memberModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->memberList);
	}
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = memberModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->memberList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = memberModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->memberList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
		$rs = memberModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->memberList);
	}
	
}
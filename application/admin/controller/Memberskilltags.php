<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberskilltagsModel;

/**
 * 众包类别管理
 */
class Memberskilltags extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->skilltagsList = Session::get('skilltagsList');
		$this->assign('skilltagsList', $this->skilltagsList);
		//技能标签
		$catelist = readCacheFile("memberskilltags");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/memberskilltags/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('skilltagsList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'pid,name,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = memberskilltagsModel::getList($findObj,[],'10','sort desc,id asc');
		$this->assign("list", $list);
		//管理员列表
		$adminuserlist = readCacheFile("adminuserlist");
		$this->assign('adminuserlist', $adminuserlist);
		return $this->fetch();
	}
	
	/**
	 * 新增
	 */
	public function create($pid='') {
		$this->assign("pid", $pid);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = isset($post['pid'])?$post['pid']:'0';
			$data['name'] = isset($post['name'])?$post['name']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$id = memberskilltagsModel::add_data($data);
    		$this->cacheMemberskilltags(); //刷新缓存
			$this->success('添加成功。', $this->skilltagsList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = memberskilltagsModel::getByid($id);
		$this->assign("obj", $obj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = isset($post['pid'])?$post['pid']:'0';
			$data['name'] = isset($post['name'])?$post['name']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$rs = memberskilltagsModel::upd_data(['id'=>$id], $data);
    		$this->cacheMemberskilltags(); //刷新缓存
			$this->success('编辑成功。', $this->skilltagsList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = memberskilltagsModel::upd_list($id, ['is_lock'=>2]);
    	$this->cacheMemberskilltags(); //刷新缓存
		$this->redirect($this->skilltagsList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = memberskilltagsModel::upd_list($id, ['is_lock'=>1]);
    	$this->cacheMemberskilltags(); //刷新缓存
		$this->redirect($this->skilltagsList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = materialModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->skilltagsList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = materialModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->skilltagsList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = memberskilltagsModel::upd_list($id, ['is_del'=>2]);
    	$this->cacheMemberskilltags(); //刷新缓存
		$this->redirect($this->skilltagsList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = memberskilltagsModel::updsort($type, $id, $sort);
		$this->cacheMemberskilltags(); //刷新缓存
		echo $rs;
	}
	
}

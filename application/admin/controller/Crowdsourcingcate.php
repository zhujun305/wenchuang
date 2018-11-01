<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\crowdsourccateModel;

/**
 * 众包类别管理
 */
class Crowdsourcingcate extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->crowdsourcingcateList = Session::get('crowdsourcingcateList');
		$this->assign('crowdsourcingcateList', $this->crowdsourcingcateList);
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/crowdsourcingcate/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('crowdsourcingcateList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'title,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = crowdsourccateModel::getList($findObj,[],'20','sort desc,id asc');
		$this->assign("list", $list);
		//管理员列表
		$adminuserlist = readCacheFile("adminuserlist");
		$this->assign('adminuserlist', $adminuserlist);
		return $this->fetch();
	}
	
	/**
	 * 新增
	 */
	public function create() {
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['title'] = isset($post['title'])?$post['title']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$id = crowdsourccateModel::add_data($data);
    		$this->cacheCrowdsourcingcate(); //刷新缓存
			$this->success('添加成功。', $this->crowdsourcingcateList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$detail = crowdsourccateModel::getByid($id);
		$this->assign("detail", $detail);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['title'] = isset($post['title'])?$post['title']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$where = ['id'=>$id];
			$rs = crowdsourccateModel::upd_data($where, $data);
    		$this->cacheCrowdsourcingcate(); //刷新缓存
			$this->success('编辑成功。', $this->crowdsourcingcateList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = crowdsourccateModel::upd_list($id, ['is_lock'=>2]);
    	$this->cacheCrowdsourcingcate(); //刷新缓存
		$this->redirect($this->crowdsourcingcateList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = crowdsourccateModel::upd_list($id, ['is_lock'=>1]);
    	$this->cacheCrowdsourcingcate(); //刷新缓存
		$this->redirect($this->crowdsourcingcateList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = crowdsourccateModel::upd_list($ids, ['is_lock'=>2]);
    	$this->cacheCrowdsourcingcate(); //刷新缓存
		$this->redirect($this->crowdsourcingcateList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = crowdsourccateModel::upd_list($ids, ['is_lock'=>1]);
    	$this->cacheCrowdsourcingcate(); //刷新缓存
		$this->redirect($this->crowdsourcingcateList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = crowdsourccateModel::upd_list($id, ['is_del'=>2]);
    	$this->cacheCrowdsourcingcate(); //刷新缓存
		$this->redirect($this->crowdsourcingcateList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = crowdsourccateModel::updsort($type, $id, $sort);
		$this->cacheCrowdsourcingcate(); //刷新缓存
		echo $rs;
	}
	
}

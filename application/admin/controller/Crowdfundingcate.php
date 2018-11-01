<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\crowdfundcateModel;

/**
 * 众包类别管理
 */
class Crowdfundingcate extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->crowdfundingcateList = Session::get('crowdfundingcateList');
		$this->assign('crowdfundingcateList', $this->crowdfundingcateList);
		//众包分类
		$catelist = readCacheFile("crowdfundingcate");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/crowdfundingcate/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('crowdfundingcateList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'title,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = crowdfundcateModel::getList($findObj,[],'20','sort desc,id asc');
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
			$id = crowdfundcateModel::add_data($data);
    		$this->cacheCrowdfundcate(); //刷新缓存
			$this->success('添加成功。', $this->crowdfundingcateList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$detail = crowdfundcateModel::getByid($id);
		$this->assign("detail", $detail);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['title'] = isset($post['title'])?$post['title']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$where = ['id'=>$id];
			$rs = crowdfundcateModel::upd_data($where, $data);
    		$this->cacheCrowdfundcate(); //刷新缓存
			$this->success('编辑成功。', $this->crowdfundingcateList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = crowdfundcateModel::upd_list($id, ['is_lock'=>2]);
    	$this->cacheCrowdfundcate(); //刷新缓存
		$this->redirect($this->crowdfundingcateList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = crowdfundcateModel::upd_list($id, ['is_lock'=>1]);
    	$this->cacheCrowdfundcate(); //刷新缓存
		$this->redirect($this->crowdfundingcateList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = crowdfundcateModel::upd_list($ids, ['is_lock'=>2]);
    	$this->cacheCrowdfundcate(); //刷新缓存
		$this->redirect($this->crowdfundingcateList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = crowdfundcateModel::upd_list($ids, ['is_lock'=>1]);
    	$this->cacheCrowdfundcate(); //刷新缓存
		$this->redirect($this->crowdfundingcateList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = crowdfundcateModel::upd_list($id, ['is_del'=>2]);
    	$this->cacheCrowdfundcate(); //刷新缓存
		$this->redirect($this->crowdfundingcateList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = crowdfundcateModel::updsort($type, $id, $sort);
		$this->cacheCrowdfundcate(); //刷新缓存
		echo $rs;
	}
	
}

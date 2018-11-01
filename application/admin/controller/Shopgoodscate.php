<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\shopgoodscateModel;

/**
 * 商品分类管理
 */
class Shopgoodscate extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->shopgoodscateList = Session::get('shopgoodscateList');
		$this->assign('shopgoodscateList', $this->shopgoodscateList);
		//商品分类
		$catelist = readCacheFile("shopgoodscate");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/shopgoodscate/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('shopgoodscateList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'pid,title,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = shopgoodscateModel::getList($findObj,[],'20','sort desc,pid asc,id asc');
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
		$topobj = shopgoodscateModel::getByid($pid);
		$this->assign("topobj", $topobj);
		if($topobj['level']==2) $pid = $topobj['pid'];
		$this->assign("pid", $pid);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = $post['pid'];
			if($post['piderji']>0) $data['pid'] = $post['piderji'];
			$data['title'] = $post['title'];
			$data['sort'] = $post['sort'];
			$data['level'] = $post['level'];
			$id = shopgoodscateModel::add_data($data);
    		$this->cacheShopgoodscate(); //刷新缓存
			$this->success('添加成功。', $this->shopgoodscateList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = shopgoodscateModel::getByid($id);
		$pid = 0;
		$erji = 0;
		$topobj = [];
		$erjiobj = [];
		if($obj['level']==3){
			$erjiobj = shopgoodscateModel::getByid($obj['pid']);
			$topobj = shopgoodscateModel::getByid($erjiobj['pid']);
			$pid = $erjiobj['pid'];
			$erji = $obj['pid'];
		}elseif($obj['level']==2){
			$topobj = shopgoodscateModel::getByid($obj['pid']);
			$pid = $obj['pid'];
		}else{
			$topobj = $obj;
		}
		$this->assign("obj", $obj);
		$this->assign("pid", $pid);
		$this->assign("erji", $erji);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = $post['pid'];
			if($post['piderji']>0) $data['pid'] = $post['piderji'];
			$data['title'] = $post['title'];
			$data['sort'] = $post['sort'];
			$data['level'] = $post['level'];
			$rs = shopgoodscateModel::upd_data(['id'=>$id], $data);
    		$this->cacheShopgoodscate(); //刷新缓存
			$this->success('编辑成功。', $this->shopgoodscateList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = shopgoodscateModel::upd_list($id, ['is_lock'=>2]);
		$this->cacheShopgoodscate(); //刷新缓存
		$this->redirect($this->shopgoodscateList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = shopgoodscateModel::upd_list($id, ['is_lock'=>1]);
		$this->cacheShopgoodscate(); //刷新缓存
		$this->redirect($this->shopgoodscateList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = shopgoodscateModel::upd_list($ids, ['is_lock'=>2]);
		$this->cacheShopgoodscate(); //刷新缓存
		$this->redirect($this->shopgoodscateList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = shopgoodscateModel::upd_list($ids, ['is_lock'=>1]);
		$this->cacheShopgoodscate(); //刷新缓存
		$this->redirect($this->shopgoodscateList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = shopgoodscateModel::upd_list($id, ['is_del'=>2]);
		$this->cacheShopgoodscate(); //刷新缓存
		$this->redirect($this->shopgoodscateList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = shopgoodscateModel::updsort($type, $id, $sort);
		$this->cacheShopgoodscate(); //刷新缓存
		echo $rs;
	}
	
}

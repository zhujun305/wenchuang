<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\shopnormsModel;
use app\model\shopnormsvalModel;

/**
 * 商品规格管理
 */
class Shopnorms extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->shopnormsList = Session::get('shopnormsList');
		$this->assign('shopnormsList', $this->shopnormsList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//规格
		$shopnorms = readCacheFile("shopnorms");
		$this->assign('shopnorms', $shopnorms);
		$shopnormsval = readCacheFile("shopnormsval");
		$shopnormskeyval = readCacheFile("shopnormskeyval");
	}
	
	public function index(){
		$this->redirect(Url('/Admin/shopnorms/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('shopnormsList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'name,cate,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = shopnormsModel::getList($findObj,[],'20','sort desc,id asc');
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
			$data['name'] = $post['name'];
			$data['alias'] = $post['alias'];
			$data['cate'] = $post['cate'];
			$data['sort'] = $post['sort'];
			$id = shopnormsModel::add_data($data);
    		$this->cacheShopnorms(); //刷新缓存
			$this->success('添加成功。', $this->shopnormsList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = shopnormsModel::getByid($id);
		$this->assign("obj", $obj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['name'] = $post['name'];
			$data['alias'] = $post['alias'];
			$data['cate'] = $post['cate'];
			$data['sort'] = $post['sort'];
			$rs = shopnormsModel::upd_data(['id'=>$id], $data);
    		$this->cacheShopnorms(); //刷新缓存
			$this->success('编辑成功。', $this->shopnormsList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
		$rs = shopnormsModel::upd_list($id, ['is_lock'=>2]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = shopnormsModel::upd_list($id, ['is_lock'=>1]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = shopnormsModel::upd_list($ids, ['is_lock'=>2]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = shopnormsModel::upd_list($ids, ['is_lock'=>1]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = shopnormsModel::upd_list($id, ['is_del'=>2]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = shopnormsModel::updsort($type, $id, $sort);
		$this->cacheShopnorms(); //刷新缓存
		echo $rs;
	}
	
}

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
 * 商品规格值管理
 */
class Shopnormsval extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->get_pid = Request::instance()->get('pid'); //规格id
		$this->assign('pid', $this->get_pid);
		if(!($this->get_pid>0)){
			$this->error('请选择商品规格！', Session::get('shopnormsList'));
		}
		$this->shopnormsvalList = Session::get('shopnormsvalList');
		$this->assign('shopnormsvalList', $this->shopnormsvalList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/shopnormsval/browse','pid='.$this->get_pid));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('shopnormsvalList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'pid,name,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$findObj['pid'] = $this->get_pid;
		$list = shopnormsvalModel::getList($findObj,[],'20','sort desc,id asc');
		$this->assign("list", $list);
		//管理员列表
		$adminuserlist = readCacheFile("adminuserlist");
		$this->assign('adminuserlist', $adminuserlist);
		$this->assign("findObj", $findObj);
		return $this->fetch('shopnorms/sonlist');
	}
	
	/**
	 * 新增
	 */
	public function create() {
		//上级规格id信息
		$tobj = shopnormsModel::getByid($this->get_pid);
		$this->assign("tobj", $tobj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = $this->get_pid;
			$data['name'] = $post['name'];
			$data['alias'] = $post['alias'];
			$data['sort'] = $post['sort'];
			$data['cover'] = basename($post['cover']);
			$id = shopnormsvalModel::add_data($data);
    		$this->cacheShopnorms(); //刷新缓存
    		$this->statnormsvalnum($this->get_pid); //统计规格值数量
			$this->success('添加成功。', $this->shopnormsvalList);
		}
		return $this->fetch('shopnorms/sonadd');
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = shopnormsvalModel::getByid($id);
		$this->assign("obj", $obj);
		//上级规格id信息
		$tobj = shopnormsModel::getByid($this->get_pid);
		$this->assign("tobj", $tobj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = $this->get_pid;
			$data['name'] = $post['name'];
			$data['alias'] = $post['alias'];
			$data['sort'] = $post['sort'];
			$data['cover'] = basename($post['cover']);
			$rs = shopnormsvalModel::upd_data(['id'=>$id], $data);
    		$this->cacheShopnorms(); //刷新缓存
			$this->success('编辑成功。', $this->shopnormsvalList);
		}
		return $this->fetch('shopnorms/sonedit');
	}
	
    /**
     * 锁定
     */
    public function lock($id){
		$rs = shopnormsvalModel::upd_list($id, ['is_lock'=>2]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsvalList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = shopnormsvalModel::upd_list($id, ['is_lock'=>1]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsvalList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = shopnormsvalModel::upd_list($ids, ['is_lock'=>2]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsvalList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = shopnormsvalModel::upd_list($ids, ['is_lock'=>1]);
		$this->cacheShopnorms(); //刷新缓存
		$this->redirect($this->shopnormsvalList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = shopnormsvalModel::upd_list($id, ['is_del'=>2]);
		$this->cacheShopnorms(); //刷新缓存
		$this->statnormsvalnum($this->get_pid); //统计规格值数量
		$this->redirect($this->shopnormsvalList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = shopnormsvalModel::updsort($type, $id, $sort);
		$this->cacheShopnorms(); //刷新缓存
		echo $rs;
	}
	
	/**
	 * 统计规格值数量
	 */
	public function statnormsvalnum($pid){
		$numobj = shopnormsvalModel::getOneByWhere(['is_lock'=>1,'pid'=>$pid],['count(1) num']);
		$rs = 0;
		if(!empty($numobj) && $numobj['num']>0){
			$rs = shopnormsModel::upd_data(['id'=>$pid], ['contnum'=>$numobj['num']]);
		}
		return $rs;
	}
	
}

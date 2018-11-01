<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\friendsModel;

/**
 * 友情链接管理
 */
class Friends extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->friendsList = Session::get('friendsList');
		$this->assign('friendsList', $this->friendsList);
	}

	public function index(){
		$this->redirect(Url('/Admin/Friends/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('friendsList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'title,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = friendsModel::getList($findObj,[],'15','sort desc,id asc');
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
			$data['title'] = $post['title'];
			$data['url'] = $post['url'];
			$data['cover'] = basename($post['cover']);
			$id = friendsModel::add_data($data);
			$this->cacheFriends(); //刷新缓存
			$this->success('添加成功。', $this->friendsList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = friendsModel::getByid($id);
		$this->assign("obj", $obj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['title'] = $post['title'];
			$data['url'] = $post['url'];
			$data['cover'] = basename($post['cover']);
			$rs = friendsModel::upd_data(['id'=>$id], $data);
			$this->cacheFriends(); //刷新缓存
			$this->success('编辑成功。', $this->friendsList);
		}
		return $this->fetch();
	}
	
	/**
	 * 锁定
	 */
	public function lock($id){
		$rs = friendsModel::upd_list($id, ['is_lock'=>2]);
		$this->cacheFriends(); //刷新缓存
		$this->redirect($this->friendsList);
	}
	
	/**
	 * 解锁
	 */
	public function unlock($id){
		$rs = friendsModel::upd_list($id, ['is_lock'=>1]);
		$this->cacheFriends(); //刷新缓存
		$this->redirect($this->friendsList);
	}
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = friendsModel::upd_list($ids, ['is_lock'=>2]);
		$this->cacheFriends(); //刷新缓存
		$this->redirect($this->friendsList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = friendsModel::upd_list($ids, ['is_lock'=>1]);
		$this->cacheFriends(); //刷新缓存
		$this->redirect($this->friendsList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
		$rs = friendsModel::upd_list($id, ['is_del'=>2]);
		$this->cacheFriends(); //刷新缓存
		$this->redirect($this->friendsList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = friendsModel::updsort($type, $id, $sort);
		$this->cacheFriends(); //刷新缓存
		echo $rs;
	}
	
}
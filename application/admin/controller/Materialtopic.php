<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberModel;
use app\model\materialModel;
use app\model\materialtopicModel;

/**
 * 素材主题管理
 */
class Materialtopic extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->materialtopicList = Session::get('materialtopicList');
		$this->assign('materialtopicList', $this->materialtopicList);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/materialtopic/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('materialtopicList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'title,is_recommend,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$list = materialtopicModel::getList($findObj,[],'15','is_recommend desc,sort desc,id desc');
		$this->assign("list", $list);
		//管理员列表
		$adminuserlist = readCacheFile("adminuserlist");
		$this->assign('adminuserlist', $adminuserlist);
		//空判断
		$findObj['title'] = isset($findObj['title'])?$findObj['title']:'';
		$findObj['is_recommend'] = isset($findObj['is_recommend'])?$findObj['is_recommend']:'';
		$findObj['is_lock'] = isset($findObj['is_lock'])?$findObj['is_lock']:'';
		$this->assign("findObj", $findObj);
		return $this->fetch();
	}
	
	/**
	 * 新增
	 */
	public function create() {
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['title'] = isset($post['title'])?$post['title']:'';
			$data['summary'] = isset($post['summary'])?$post['summary']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$data['is_recommend'] = isset($post['is_recommend'])?$post['is_recommend']:'1';
			if(isset($post['cover']) && !empty($post['cover'])){
				$data['cover'] = basename($post['cover']);
			}
			$id = materialtopicModel::add_data($data);
			$this->cacheMaterialtopic(); //刷新缓存
			$this->success('添加成功。', $this->materialtopicList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$detail = materialtopicModel::getByid($id);
		$this->assign("detail", $detail);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['title'] = isset($post['title'])?$post['title']:'';
			$data['summary'] = isset($post['summary'])?$post['summary']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$data['is_recommend'] = isset($post['is_recommend'])?$post['is_recommend']:'1';
			if(isset($post['cover']) && !empty($post['cover'])){
				$data['cover'] = basename($post['cover']);
			}
			$where = ['id'=>$id];
			$rs = materialtopicModel::upd_data($where, $data);
			$this->cacheMaterialtopic(); //刷新缓存
			$this->success('编辑成功。', $this->materialtopicList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = materialtopicModel::upd_list($id, ['is_lock'=>2]);
		$this->cacheMaterialtopic(); //刷新缓存
		$this->redirect($this->materialtopicList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = materialtopicModel::upd_list($id, ['is_lock'=>1]);
		$this->cacheMaterialtopic(); //刷新缓存
		$this->redirect($this->materialtopicList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = materialtopicModel::upd_list($id, ['is_lock'=>2]);
		$this->cacheMaterialtopic(); //刷新缓存
		$this->redirect($this->materialtopicList);
	}
    
    /**
     * 是否推荐
     */
    public function is_recommend($id, $val){
    	if($val==2){
    		$val = 2;
    	}else{
    		$val = 1;
    	}
    	$rs = materialtopicModel::upd_list($id, ['is_recommend'=>$val]);
		$this->cacheMaterialtopic(); //刷新缓存
		$this->redirect($this->materialtopicList);
    }
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = materialtopicModel::updsort($type, $id, $sort);
		$this->cacheMaterialtopic(); //刷新缓存
		echo $rs;
	}
	
}

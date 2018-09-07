<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;

/**
 * 素材类别管理
 */
class Materialcate extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->materialcateList = Session::get('materialcateList');
		$this->assign('materialcateList', $this->materialcateList);
		//素材分类
		$catelist = readCacheFile("materialcate");
		$this->assign('catelist', $catelist);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/materialcate/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('materialcateList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'cname,cno,pid,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$list = materialcateModel::getList($findObj,[],'20','sort desc,pid asc,id asc');
		$this->assign("list", $list);
		//管理员列表
		$adminuserlist = readCacheFile("adminuserlist");
		$this->assign('adminuserlist', $adminuserlist);
		//空判断
		$findObj['cname'] = isset($findObj['cname'])?$findObj['cname']:'';
		$findObj['cno'] = isset($findObj['cno'])?$findObj['cno']:'';
		$findObj['pid'] = isset($findObj['pid'])?$findObj['pid']:'';
		$findObj['is_lock'] = isset($findObj['is_lock'])?$findObj['is_lock']:'';
		$this->assign("findObj", $findObj);
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
			$data['cname'] = isset($post['cname'])?$post['cname']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$cno = isset($post['cno'])?$post['cno']:'';
			$cnoobj = materialcateModel::getOneByWhere(['cno'=>$cno]);
			if(!empty($cnoobj)){
				$this->error('分类编号重复。', $this->materialcateList);
			}
			$data['cno'] = $cno;
			$id = materialcateModel::add_data($data);
			$this->success('添加成功。', $this->materialcateList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$detail = materialcateModel::getByid($id);
		$this->assign("detail", $detail);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = isset($post['pid'])?$post['pid']:'0';
			$data['cname'] = isset($post['cname'])?$post['cname']:'';
			$data['sort'] = isset($post['sort'])?$post['sort']:'';
			$cno = isset($post['cno'])?$post['cno']:'';
			$cnoobj = materialcateModel::getOneByWhere(['cno'=>$cno,'id'=>['neq',$id]]);
			if(!empty($cnoobj)){
				$this->error('分类编号重复。', $this->materialcateList);
			}
			$data['cno'] = $cno;
			$where = ['id'=>$id];
			$rs = materialcateModel::upd_data($where, $data);
			$this->success('编辑成功。', $this->materialcateList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = materialcateModel::upd_list($id, ['is_lock'=>2]);
    	$this->cacheMaterialcate(); //刷新缓存
		$this->redirect($this->materialcateList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = materialcateModel::upd_list($id, ['is_lock'=>1]);
    	$this->cacheMaterialcate(); //刷新缓存
		$this->redirect($this->materialcateList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = materialcateModel::upd_list($id, ['is_del'=>2]);
    	$this->cacheMaterialcate(); //刷新缓存
		$this->redirect($this->materialcateList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		echo materialcateModel::updsort($type, $id, $sort);
	}
	
}

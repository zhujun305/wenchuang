<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\sysroseModel;
use app\model\adminuserModel;

/**
 * 用户
 */
class Sysadminuser extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->sysadminuserList = Session::get('sysadminuserList');
		$this->assign('sysadminuserList', $this->sysadminuserList);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/sysadminuser/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('sysadminuserList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'name,truename,mobile';
		$this->getFindObj($findObj, $find, $fields);
		$list = adminuserModel::getList($findObj);
		$this->assign("list", $list);
		//角色列表
		$roselist = sysroseModel::getListByWhere();
		$roselist = convert_arr_key($roselist, 'id', 'name');
		$roselist['-1'] = '超级管理员';
		$this->assign('roselist', $roselist);
		//管理员列表
		$adminuserlist = readCacheFile("adminuserlist");
		$this->assign('adminuserlist', $adminuserlist);
		//空判断
		$findObj['name'] = isset($findObj['name'])?$findObj['name']:'';
		$findObj['truename'] = isset($findObj['truename'])?$findObj['truename']:'';
		$findObj['mobile'] = isset($findObj['mobile'])?$findObj['mobile']:'';
		$this->assign("findObj", $findObj);
		return $this->fetch();
	}
	
	/**
	 * 新增
	 */
	public function create() {
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['roseid'] = isset($post['roseid'])?$post['roseid']:'';
			$data['name'] = isset($post['name'])?$post['name']:'';
			$pwd2 = isset($post['pwd2'])?$post['pwd2']:'';
			$data['pwd'] = md5($pwd2);
			$data['truename'] = isset($post['truename'])?$post['truename']:'';
			$data['mobile'] = isset($post['mobile'])?$post['mobile']:'';
			$data['qq'] = isset($post['qq'])?$post['qq']:'';
			$data['weixin'] = isset($post['weixin'])?$post['weixin']:'';
			$data['email'] = isset($post['email'])?$post['email']:'';
			$id = adminuserModel::add_data($data);
			$this->cacheAdminuser(); //刷新缓存
			$this->success('添加成功。', $this->sysadminuserList);
		}
		$roselist = sysroseModel::getListByWhere();
		$this->assign('roselist', $roselist);
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$detail = adminuserModel::getByid($id);
		$this->assign("detail", $detail);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['roseid'] = isset($post['roseid'])?$post['roseid']:'';
			$data['truename'] = isset($post['truename'])?$post['truename']:'';
			$data['mobile'] = isset($post['mobile'])?$post['mobile']:'';
			$data['qq'] = isset($post['qq'])?$post['qq']:'';
			$data['weixin'] = isset($post['weixin'])?$post['weixin']:'';
			$data['email'] = isset($post['email'])?$post['email']:'';
			$where = ['id'=>$id];
			$rs = adminuserModel::upd_data($where, $data);
			$this->cacheAdminuser(); //刷新缓存
			$this->success('编辑成功。', $this->sysadminuserList);
		}
		$roselist = sysroseModel::getListByWhere();
		$roselist = convert_arr_key($roselist, 'id', 'name');
		$this->assign('roselist', $roselist);
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = adminuserModel::upd_list($id, ['is_lock'=>2]);
		$this->cacheAdminuser(); //刷新缓存
		$this->redirect($this->sysadminuserList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = adminuserModel::upd_list($id, ['is_lock'=>1]);
		$this->cacheAdminuser(); //刷新缓存
		$this->redirect($this->sysadminuserList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
		$rs = adminuserModel::upd_list($id, ['is_del'=>2]);
		$this->cacheAdminuser(); //刷新缓存
		$this->redirect($this->sysadminuserList);
	}
	
}

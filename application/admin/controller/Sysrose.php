<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Request;
use think\Session;
use think\Url;
use app\model\sysroseModel;
use app\model\syspowerModel;

/**
 * 角色
 */
class Sysrose extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->sysroseList = Session::get('sysroseList');
		$this->assign('sysroseList', $this->sysroseList);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/sysrose/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('sysroseList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'name';
		$this->getFindObj($findObj, $find, $fields);
		$list = sysroseModel::getList($findObj);
		$this->assign("list", $list);
		//空判断
		$findObj['name'] = isset($findObj['name'])?$findObj['name']:'';
		$this->assign("findObj", $findObj);
		return $this->fetch();
	}
	
	/**
	 * 新增
	 */
	public function create() {
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['name'] = isset($post['name'])?$post['name']:'';
			$id = sysroseModel::add_data($data);
			$this->success('添加成功。', $this->sysroseList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$detail = sysroseModel::getByid($id);
		$this->assign("detail", $detail);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['name'] = isset($post['name'])?$post['name']:'';
			$data['remark'] = isset($post['remark'])?$post['remark']:'';
			$where = ['id'=>$id];
			$rs = sysroseModel::upd_data($where, $data);
			$this->success('编辑成功。', $this->sysroseList);
		}
		return $this->fetch();
	}
	
	/**
	 * 授权
	 */
	public function auth($id){
		$detail = sysroseModel::getByid($id);
		$this->assign("detail", $detail);
		$list = syspowerModel::getListByWhere([],[],'sort desc,id asc');
		$list = convert_arr_key($list,'id');
		$powerarr = [];
		foreach ($list as $key=>$val){
			if($val['pid']==0){
				$powerarr[$key] = [];
			}else{
				if($val['type']==2){
					$powerarr[$val['pid']][$key] = [];
					foreach ($list as $kkk=>$vvv){
						if($vvv['type']==3 && $key==$vvv['pid']){
							$powerarr[$val['pid']][$key][$vvv['id']] = $vvv['id'];
						}
					}
				}
			}
		}
		$rosepower = sysroseModel::getrosepowerListByWhere(['roseid'=>$id]);
		$rosepower = convert_arr_key($rosepower,'powerid','powerid');
		$this->assign("rosepower", $rosepower);
		$this->assign("powerarr", $powerarr);
		$this->assign("list", $list);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$powerid_list = isset($post['powerid'])?$post['powerid']:'';
			$data = [];
			foreach ($powerid_list as $key=>$val){
				$data[$key]['roseid'] = $id;
				$data[$key]['powerid'] = $val;
			}
			$rs = sysroseModel::add_auth($id, $data);
			$this->success('授权成功。', $this->sysroseList);
		}
		return $this->fetch();
	}
	
	/**
	 * 删除
	 */
	public function del($id) {
		$rs = sysroseModel::del([$id]);
		$this->redirect($this->sysroseList);
	}
	
}

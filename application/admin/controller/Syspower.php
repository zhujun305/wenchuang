<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\syspowerModel;

/**
 * 权限
 */
class Syspower extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->syspowerList = Session::get('syspowerList');
		$this->assign('syspowerList', $this->syspowerList);
		//权限类型
		$power_type = array('1'=>'系统模块','2'=>'控制器','3'=>'方法');
		$this->assign ( 'power_type', $power_type );
	}

	public function index(){
		$this->redirect(Url('/Admin/syspower/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('syspowerList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'action,method';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = syspowerModel::getList($findObj,[],20,'pid asc,sort desc,id asc');
		$this->assign("list", $list);
		return $this->fetch();
	}
	
	/**
	 * 新增
	 */
	public function create() {
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = isset($post['pid'])?$post['pid']:'';
			if($data['pid']>0){
				$topobj = self::getTopParentsByPid($data['pid']);
				$data['toppid'] = $topobj['id'];
			}
			$data['action'] = isset($post['action'])?$post['action']:'';
			$data['actionName'] = isset($post['actionName'])?$post['actionName']:'';
			$data['type'] = 2; //控制器
			$id = syspowerModel::add_data($data);
			$this->success('添加成功。', $this->syspowerList);
		}
		//模块列表
		$powertop = syspowerModel::getListByWhere(['pid'=>0]);
		$powertop = convert_arr_key($powertop, 'id', 'moduel');
		$this->assign('powertop', $powertop);
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$detail = syspowerModel::getByid($id);
		$this->assign("detail", $detail);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['pid'] = isset($post['pid'])?$post['pid']:'';
			if($data['pid']>0){
				$topobj = self::getTopParentsByPid($data['pid']);
				$data['toppid'] = $topobj['id'];
			}
			$data['action'] = isset($post['action'])?$post['action']:'';
			$data['actionName'] = isset($post['actionName'])?$post['actionName']:'';
			$where = ['id'=>$id];
			$rs = syspowerModel::upd_data($where, $data);
			$this->success('编辑成功。', $this->syspowerList);
		}
		//模块列表
		$powertop = syspowerModel::getListByWhere(['pid'=>0]);
		$powertop = convert_arr_key($powertop, 'id', 'moduel');
		$this->assign('powertop', $powertop);
		return $this->fetch();
	}
	
	/**
	 * 设置方法
	 */
	public function setmethod($id) {
		//2级，控制器
		$detail = syspowerModel::getByid($id);
		if($detail['pid']>0){
			$topobj = self::getTopParentsByPid($detail['pid']);
			$detail['moduel'] = $topobj['moduel'];
		}
		$this->assign("detail", $detail);
		//3级，方法列表
		$method_list = syspowerModel::getListByWhere(['pid'=>$id]);
		$this->assign("method_list", $method_list);
		//添加修改方法
		if(Request::instance()->isPost()){
			$post = input('post.');
			$method = isset($post['method'])?$post['method']:'';
			$methodName = isset($post['methodName'])?$post['methodName']:'';
			$isShow = isset($post['isShow'])?$post['isShow']:'';
			$listid = isset($post['listid'])?$post['listid']:'';
			if(!empty($method)){
				foreach ($method as $key=>$val){
					$data = [];
					$data[$key]['pid'] = $id;
					$data[$key]['toppid'] = $detail['toppid'];
					$data[$key]['action'] = $detail['action'];
					$data[$key]['actionName'] = $detail['actionName'];
					$data[$key]['method'] = $val;
					$data[$key]['methodName'] = $methodName[$key];
					$data[$key]['type'] = 3;
					$data[$key]['isShow'] = $isShow[$key];
					if(isset($listid[$key]) && !empty($listid[$key])){
						$where = ['id'=>$listid[$key]];
						$rs = syspowerModel::upd_data($where, $data[$key]);
					}else{
						$rsid = syspowerModel::add_data($data[$key]);
					}
				}
			}
			$this->success('编辑成功。', $this->syspowerList);
		}

		return $this->fetch();
	}

	/**
	 * 查询顶级id
	 */
	static public function getTopParentsByPid($pid){
		$obj = syspowerModel::getByid($pid);
		if($obj['pid']==0){
			return $obj;
		}else{
			return self::getTopParentsByPid($obj['pid']);
		}
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = syspowerModel::upd_list($id, ['status'=>2]);
		$this->redirect($this->syspowerList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = syspowerModel::upd_list($id, ['status'=>1]);
		$this->redirect($this->syspowerList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = syspowerModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->syspowerList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = syspowerModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->syspowerList);
    }
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		echo syspowerModel::updsort($type, $id, $sort);
	}
	
}

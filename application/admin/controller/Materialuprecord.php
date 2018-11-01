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
use app\model\materialuprecordModel;
use app\model\materialuprecordpicModel;


/**
 * 素材批量上传管理
 */
class Materialuprecord extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->materialuprecordList = Session::get('materialuprecordList');
		$this->assign('materialuprecordList', $this->materialuprecordList);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/materialuprecord/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('materialuprecordList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'filename,is_daoru,is_shangc';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = materialuprecordModel::getList($findObj);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		return $this->fetch();
	}
	
}

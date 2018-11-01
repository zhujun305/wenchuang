<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberModel;
use app\model\msgrecordsModel;

/**
 * 会员消息管理
 */
class Msgrecords extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->msgrecordsList = Session::get('msgrecordsList');
		$this->assign('msgrecordsList', $this->msgrecordsList);
	}

	public function index(){
		$this->redirect(Url('/Admin/msgrecords/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('msgrecordsList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'type,fuid,suid,content,status,time1,time2';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = msgrecordsModel::getList($findObj);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
				$uidarr[] = $val['input_time'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		return $this->fetch();
	}
	
}
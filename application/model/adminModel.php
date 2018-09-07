<?php
namespace app\model;
use app\model\commonModel;
use think\Db;

class adminModel extends commonModel
{
	public function __construct() {
		parent::__construct ();
	}

	/**
	 * 得到一个用户的权限
	 */
	static public function authorize($user) {
		$roseid = $user['roseid'];
		$where = ['a.powerid'=>'b.id','a.roseid'=>$roseid];
		$field = ['b.action','b.method'];
		$list = Db::table('sys_rosepower a,sys_power b')->where($where)->field($field)->select();
		$rights = array();
		foreach ($list as $key=>$val){
			$rights[$val['action']][$val['method']] = true;
		}
		return $rights;
	}

} 

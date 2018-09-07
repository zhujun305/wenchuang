<?php
namespace app\model;
use app\model\commonModel;
use think\Db;

class sysroseModel extends commonModel
{
    static $table_name = 'sys_rose';
	public function __construct() {
		parent::__construct ();
	}

	/**
	 * 根据ID取一条记录
	 */
	static function getByid($id, $field = array()) {
		$return = Db::table(self::$table_name)->where("id",$id)->field($field)->find();
		return $return;
	}
	
	/**
	 * 根据where条件查询一条数据
	 */
	static public function getOneByWhere($where=array(), $field=array(), $order="id desc")
	{
		$return = Db::table(self::$table_name)->where($where)->field($field)->order($order)->find();
		return $return;
	}
	
	/**
	 * 新增
	 */
	static public function add_data($data = array())
	{
		$return = Db::table(self::$table_name)->insertGetId($data);
		return $return;
	}
	
	/**
	 * 编辑
	 */
	static public function upd_data($where = array(), $data = array())
	{
		$return = Db::table(self::$table_name)->where($where)->update($data);
		return $return;
	}
	
	/**
	 * 批量修改列
	 * $ids = array('id值');
	 * $ele_arr = array('列名'=>'值')
	 */
	static public function upd_list($ids, $data)
	{
		if (!empty($ids) && !empty($data) && is_array($data)){
			$ary = explode ( '-', $ids );
			$return = Db::table(self::$table_name)->where('id', 'in', $ary)->update($data);
			return $return;
		}else{
			return false;
		}
	}
	
	/**
	 * 字段值加减（$field-字段，$num-加减数量，$type-加或减）
	 */
	static public function set_incdec($where = array(), $field='', $num='1', $type='inc')
	{
		if($type=='inc'){
			$return = Db::table(self::$table_name)->where($where)->setInc($field, $num);
		}else{
			$return = Db::table(self::$table_name)->where($where)->setDec($field, $num);
		}
		return $return;
	}
	
	/**
	 * 批量删除
	 */
	static public function del($ids=[]){
		if (is_array($ids) && !empty($ids)){
			$return = Db::table(self::$table_name)->delete($ids);
			return $return;
		}else{
			return false;
		}
	}
	
	/**
	 * 查询分页列表
	 */
	static public function getList($where=array(), $field=array(), $page="15", $order="id asc")
	{
		if(!empty($where)){
			if(!empty($where['name'])){
				$where['name'] = ['like', '%'.$where['name'].'%'];
			}
		}
		$where = array_filter($where);
		$return = Db::table(self::$table_name)->where($where)->field($field)->order($order)
		->paginate($page, false, ['query' => request()->param()]);
		return $return;
	}
	
	/**
	 * 根据where条件查询列表
	 */
	static public function getListByWhere($where=array(), $field=array(), $order="id asc")
	{
		$return = Db::table(self::$table_name)->where($where)->field($field)->order($order)->select();
		return $return;
	}
	
	/**
	 * 新增授权
	 */
	static public function add_auth($id, $data = array())
	{
		$rs = Db::table('sys_rosepower')->where(['roseid'=>$id])->delete();
		$return = Db::table('sys_rosepower')->insertAll($data);
		return $return;
	}
	
	/**
	 * 根据where条件查询角色授权
	 */
	static public function getrosepowerListByWhere($where=array(), $field=array(), $order="id asc")
	{
		$return = Db::table('sys_rosepower')->where($where)->field($field)->order($order)->select();
		return $return;
	}
	
} 

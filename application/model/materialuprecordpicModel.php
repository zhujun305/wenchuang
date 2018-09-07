<?php
namespace app\model;
use app\model\commonModel;
use think\Db;

class materialuprecordpicModel extends commonModel
{
    static $table_name = 'material_uprecord_pic';
	public function __construct() {
		parent::__construct ();
	}
    
    /**
     * 根据ID取一条记录
     */
    static function getByid($id, $field = array()) {
//     	$where = array();
//     	$where['id'] = $id;
//     	$where['is_lock'] = 1;
//     	$where['is_del'] = 1;
//     	$return = Db::table(self::$table_name)->where($where)->field($field)->find();
    	$return = Db::table(self::$table_name)->where("id",$id)->field($field)->find();
    	return $return;
    }
    
    /**
     * 根据where条件查询一条数据
     */
    static public function getOneByWhere($where = array(), $field = array())
    {
    	$where['is_del'] = 1;
    	$return = Db::table(self::$table_name)->where($where)->field($field)->find();
    	return $return;
    }
    
    /**
     * 新增
     */
    static public function add_data($data = array())
    {
		$data['input_time'] = time();
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
     * 查询分页列表
     */
    static public function getList($where = array(), $field = array(), $page='15', $order="id desc")
    {
    	$where['is_del'] = 1;
        $return = Db::table(self::$table_name)->where($where)->field($field)->order($order)
        ->paginate($page, false, ['query' => request()->param()]);
        return $return;
    }
    
    /**
     * 根据where条件查询列表
     */
    static public function getListByWhere($where = array(), $field = array())
    {
    	$where['is_del'] = 1;
        $return = Db::table(self::$table_name)->where($where)->field($field)->select();
//         $return = Db::table(self::$table_name)->where($where)->column($field);
        return $return;
    }
    
} 

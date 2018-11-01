<?php
namespace app\model;
use app\model\commonModel;
use think\Db;
use think\Session;

class memberintegralModel extends commonModel
{
    static $table_name = 'member_integral';
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
    static public function getList($findObj=array(), $field=array(), $page="15", $order="id desc")
    {
    	$where = [];
		if(!empty($findObj)){
			if($findObj['uid']>0) $where['uid'] = ($findObj['uid']);
			if($findObj['cate']!='') $where['cate'] = ($findObj['cate']);
			if($findObj['income']!='') $where['income'] = ($findObj['income']);
			if($findObj['payjf']!='') $where['payjf'] = ($findObj['payjf']);
			if($findObj['time1']!='') $where['input_time'] = ['>',strtotime($findObj['time1'])];
			if($findObj['time2']!='') $where['input_time'] = ['<',strtotime($findObj['time2'])];
		}
        $return = Db::table(self::$table_name)->where($where)->field($field)->order($order)
        ->paginate($page, false, ['query' => request()->param()]);
        return $return;
    }
    
    /**
     * 根据where条件查询列表
     */
    static public function getListByWhere($where=array(), $field=array(), $order="id desc", $limit='')
    {
        $return = Db::table(self::$table_name)->where($where)->field($field)->order($order)->limit($limit)->select();
        return $return;
    }
    
} 

<?php
namespace app\model;
use app\model\commonModel;
use think\Db;
use think\Session;

class crowdsourcwtbModel extends commonModel
{
    static $table_name = 'crowdsourc_wtb';
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
    	$where['is_del'] = 1;
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
    static public function getList($findObj=array(), $field=array(), $page="15", $order="id asc")
    {
    	$where = [];
		if(!empty($findObj)){
			if(!empty($findObj['title'])) $where['title'] = ['like', '%'.$findObj['title'].'%'];
			if($findObj['id']>0) $where['id'] = ($findObj['id']);
			if($findObj['uid']>0) $where['uid'] = ($findObj['uid']);
			if($findObj['cs_id']>0) $where['cs_id'] = ($findObj['cs_id']);
			if($findObj['status']>0) $where['status'] = ($findObj['status']);
			if($findObj['is_lock']>0) $where['is_lock'] = ($findObj['is_lock']);
		}
		$where = array_filter($where);
    	$where['is_del'] = 1;
        $return = Db::table(self::$table_name)->where($where)->field($field)->order($order)
        ->paginate($page, false, ['query' => request()->param()]);
        return $return;
    }
    
    /**
     * 根据where条件查询列表
     */
    static public function getListByWhere($where=array(), $field=array(), $order="id asc", $limit='')
    {
    	$where['is_del'] = 1;
        $return = Db::table(self::$table_name)->where($where)->field($field)->order($order)->limit($limit)->select();
        return $return;
    }
    
} 

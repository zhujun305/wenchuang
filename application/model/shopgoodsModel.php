<?php
namespace app\model;
use app\model\commonModel;
use think\Db;

class shopgoodsModel extends commonModel
{
    static $table_name = 'shop_goods';
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
    static public function getList($findObj=[], $field=[], $page="15", $order="id desc")
    {
    	$where = [];
		if(!empty($findObj)){
			if(!empty($findObj['title'])) $where['title'] = ['like', '%'.$findObj['title'].'%'];
			if(!empty($findObj['cate'])) $where['cate'] = ['in',$findObj['cate']];
			if(!empty($findObj['cate_san'])) $where['cate_san'] = ['in',$findObj['cate_san']];
			if($findObj['uid']>0) $where['uid'] = $findObj['uid'];
			if($findObj['status']>0) $where['status'] = ($findObj['status']);
			if($findObj['is_chk']>0) $where['is_chk'] = ($findObj['is_chk']);
			if($findObj['is_recommend']>0) $where['is_recommend'] = ($findObj['is_recommend']);
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
    static public function getListByWhere($where=[], $field=[], $order="id desc", $limit='')
    {
    	$where['is_del'] = 1;
        $return = Db::table(self::$table_name)->where($where)->field($field)->order($order)->limit($limit)->select();
        return $return;
    }
    
} 

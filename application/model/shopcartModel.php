<?php
namespace app\model;
use app\model\commonModel;
use think\Db;

class shopcartModel extends commonModel
{
    static $table_name = 'shop_cart';
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
	 * 批量物理删除
	 */
	static public function delwhr($whr=[]){
		if (is_array($whr) && !empty($whr)){
			$return = Db::table(self::$table_name)->where($whr)->delete();
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
			if(isset($findObj['uid']) && $findObj['uid']>0) $where['uid'] = $findObj['uid'];
			if(isset($findObj['gid']) && $findObj['gid']>0) $where['gid'] = $findObj['gid'];
			if(isset($findObj['sgnid']) && $findObj['sgnid']>0) $where['sgnid'] = $findObj['sgnid'];
			if(isset($findObj['is_lock']) && $findObj['is_lock']>0) $where['is_lock'] = $findObj['is_lock'];
			if(isset($findObj['time1']) && $findObj['time1']!=''){
				$where['input_time'] = ['>',strtotime($findObj['time1'])];
			}
			if(isset($findObj['time2']) && $findObj['time2']!=''){
				$where['input_time'] = ['<',strtotime($findObj['time2'])];
			}
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
    
    /**
     * 查询列表
     */
    static public function getLeftjoinList($findObj=[], $field=[], $order="a.id desc")
    {
    	$where = [];
    	if(!empty($findObj)){
    		if(!empty($findObj['title'])) $where['b.title'] = ['like', '%'.$findObj['title'].'%'];
    		if(isset($findObj['id'])) $where['a.id'] = $findObj['id'];
    		if(isset($findObj['uid']) && $findObj['uid']>0) $where['a.uid'] = $findObj['uid'];
    		if(isset($findObj['cate']) && $findObj['cate']>0) $where['a.cate'] = $findObj['cate'];
    		if(isset($findObj['is_lock']) && $findObj['is_lock']>0) $where['a.is_lock'] = ($findObj['is_lock']);
			if(isset($findObj['time1']) && $findObj['time1']!=''){
				$where['input_time'] = ['>',strtotime($findObj['time1'])];
			}
			if(isset($findObj['time2']) && $findObj['time2']!=''){
				$where['input_time'] = ['<',strtotime($findObj['time2'])];
			}
    	}
    	$where = array_filter($where);
    	$where['a.is_del'] = 1;
    	$return = Db::table('shop_cart a')->alias('a')
			->join('shop_goods b','a.gid = b.id')
    		->where($where)
    		->field($field)
    		->order($order)
    		->select();
    	return $return;
    }
    
} 

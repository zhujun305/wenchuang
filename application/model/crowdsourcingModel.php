<?php
namespace app\model;
use app\model\commonModel;
use think\Db;

class crowdsourcingModel extends commonModel
{
    static $table_name = 'crowdsourcing';
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
			if(isset($findObj['uid']) && $findObj['uid']>0) $where['uid'] = $findObj['uid'];
			if(isset($findObj['cate']) && $findObj['cate']>0) $where['cate'] = $findObj['cate'];
			if(isset($findObj['is_chk']) && $findObj['is_chk']>0) $where['is_chk'] = $findObj['is_chk'];
			if(isset($findObj['status']) && $findObj['status']>0) $where['status'] = $findObj['status'];
			if(isset($findObj['is_lock']) && $findObj['is_lock']>0) $where['is_lock'] = ($findObj['is_lock']);
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
     * 查询分页列表
     */
    static public function getLeftjoinList($findObj=[], $field=['a.*,b.uid wuid'], $page="15", $order="a.id desc")
    {
    	$where = [];
    	if(!empty($findObj)){
    		if(!empty($findObj['title'])) $where['a.title'] = ['like', '%'.$findObj['title'].'%'];
    		if(isset($findObj['wuid']) && $findObj['wuid']>0) $where['b.uid'] = $findObj['wuid'];
    		if(isset($findObj['cate']) && $findObj['cate']>0) $where['a.cate'] = $findObj['cate'];
    		if(isset($findObj['is_chk']) && $findObj['is_chk']>0) $where['a.is_chk'] = $findObj['is_chk'];
    		if(isset($findObj['status']) && $findObj['status']>0) $where['a.status'] = $findObj['status'];
    		if(isset($findObj['is_lock']) && $findObj['is_lock']>0) $where['a.is_lock'] = ($findObj['is_lock']);
    	}
    	$where = array_filter($where);
    	$where['a.is_del'] = 1;
    	$return = Db::table('crowdsourcing a')->alias('a')
			->join('crowdsourc_wtb b','a.id = b.cs_id')
    		->where($where)
    		->field($field)
    		->order($order)
    		->paginate($page, false, ['query' => request()->param()]);
    	return $return;
    }
    
} 

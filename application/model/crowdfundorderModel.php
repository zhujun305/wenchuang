<?php
namespace app\model;
use app\model\commonModel;
use think\Db;
use think\Session;

class crowdfundorderModel extends commonModel
{
    static $table_name = 'crowdfund_order';
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
    	$id = Db::table(self::$table_name)->insertGetId($data);
    	$ord_sn = self::createOrderid($id);
    	return $id;
    }
	
	/**
	 * 生成订单号
	 */
	static private function createOrderid($id) {
    	$order = Db::table(self::$table_name)->where("id",$id)->find();
		$cate=2;
		$curtime = ($cate>=10?$cate:'D'.$cate).date('Ymd', time());
		$ord_sn = $curtime . str_repeat ( '0', 7 - strlen ( $order['uid'] ) ) . $order['uid'];
		//查询当天小于这个订单ID的订单数量
		$whr = ['is_lock'=>1,'ordsn'=>['like','%'.$curtime.'%'],'id'=>['<',$id]];
		$maxOrder = Db::table(self::$table_name)->where($whr)->field(['count(1) maxsn'])->find();
		$str = intval ( $maxOrder['maxsn'] ) + 1;
		$footstr = str_repeat ( '0', 5 - strlen ( $str ) ) . $str;
		$ord_sn = $ord_sn . $footstr;
		//写入订单编号
        $rs = Db::table(self::$table_name)->where("id",$id)->update(['ordsn'=>$ord_sn]);
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
			if(!empty($findObj['ordsn'])) $where['ordsn'] = ['like', '%'.$findObj['ordsn'].'%'];
			if(isset($findObj['uid']) && $findObj['uid']>0) $where['uid'] = ($findObj['uid']);
			if(isset($findObj['cfid']) && $findObj['cfid']>0) $where['cfid'] = ($findObj['cfid']);
			if(isset($findObj['status']) && $findObj['status']>0) $where['status'] = ($findObj['status']);
			if(isset($findObj['is_lock']) && $findObj['is_lock']>0) $where['is_lock'] = ($findObj['is_lock']);
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
    static public function getListByWhere($where=array(), $field=array(), $order="id asc", $limit='')
    {
    	$where['is_del'] = 1;
        $return = Db::table(self::$table_name)->where($where)->field($field)->order($order)->limit($limit)->select();
        return $return;
    }
    
} 

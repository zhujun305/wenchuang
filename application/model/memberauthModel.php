<?php
namespace app\model;
use app\model\commonModel;
use think\Db;

class memberauthModel extends commonModel
{
    static $table_name = 'member_auth';
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
    static public function getOneByWhere($where = array(), $field = array(), $order="id desc")
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
    static public function getList($findObj = array(), $field = array(), $page='15', $order="id desc")
    {
    	$where = [];
		if(!empty($findObj)){
			if($findObj['cate']==1 || $findObj['cate']==2){
				$where['true_name'] = ['like', '%'.$findObj['titname'].'%'];
			}elseif($findObj['cate']==3 || $findObj['cate']==4){
				$where['company'] = ['like', '%'.$findObj['titname'].'%'];
			}else{
				$where['true_name|company'] = ['like', '%'.$findObj['titname'].'%'];
			}
			if(!empty($findObj['leaguer_no'])) $where['leaguer_no'] = ['like', '%'.$findObj['leaguer_no'].'%'];
			if(!empty($findObj['a_mobile'])) $where['a_mobile'] = ['like', '%'.$findObj['a_mobile'].'%'];
			if($findObj['cate'] > 0) $where['cate'] = intval($findObj['cate']);
			if($findObj['is_chk'] > 0) $where['is_chk'] = intval($findObj['is_chk']);
			if($findObj['is_leaguer'] > 0) $where['is_leaguer'] = intval($findObj['is_leaguer']);
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
    static public function getListByWhere($where = array(), $field = array(), $order="uid desc", $limit='')
    {
    	$where['is_del'] = 1;
        $return = Db::table(self::$table_name)->where($where)->field($field)->order($order)->limit($limit)->select();
        return $return;
    }
    
} 

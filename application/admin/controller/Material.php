<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;

/**
 * 素材管理
 */
class Material extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->materialList = Session::get('materialList');
		$this->assign('materialList', $this->materialList);
		//素材分类
		$catelist = readCacheFile("materialcate");
		$this->assign('catelist', $catelist);
		//素材专题
		$topicarr = readCacheFile("topicarr");
		$this->assign('topicarr', $topicarr);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/material/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('materialList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'title,is_chk,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$list = materialModel::getList($findObj);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],'uid,nick_name');
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		//空判断
		$findObj['title'] = isset($findObj['title'])?$findObj['title']:'';
		$findObj['is_chk'] = isset($findObj['is_chk'])?$findObj['is_chk']:'';
		$findObj['is_lock'] = isset($findObj['is_lock'])?$findObj['is_lock']:'';
		$this->assign("findObj", $findObj);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id,$type=''){
		$this->assign("type", $type); //2认证审核
		$detail = materialModel::getByid($id);
		$this->assign("detail", $detail);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['is_chk'] = isset($post['is_chk'])?$post['is_chk']:1;
			$remark = isset($post['remark'])?$post['remark']:'';
			$new_remark = "管理员".$this->user['id']."【".$this->user['truename']."】".date("Y-m-d H:i:s")." ";
			if($data['is_chk'] == 2) $new_remark.= "素材审核通过。";
			if($data['is_chk'] == 3) $new_remark.= "素材审核不通过。";
			$data['remark'] = $detail['remark'].$new_remark.$remark."\r\n";
			$where = ['id'=>$id];
			$rs = materialModel::upd_data($where, $data);
			$this->success('审核提交成功。',$this->materialList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = materialModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->materialList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = materialModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->materialList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = materialModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->materialList);
	}
	
}

<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberModel;
use app\model\memberauthModel;

/**
 * 会员认证管理
 */
class Memberauth extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->memberauthList = Session::get('memberauthList');
		$this->assign('memberauthList', $this->memberauthList);
		//会员类型名称
		$membercate = Config::get('config.membercate');
		$this->assign('membercate', $membercate);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
	}

	public function index(){
		$this->redirect(Url('/Admin/Memberauth/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('memberauthList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'cate,titname,leaguer_no,a_mobile,is_chk,is_leaguer';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = memberauthModel::getList($findObj);
		$this->assign("list", $list);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id,$type=''){
		$this->assign("type", $type); //2认证审核，3认证成员馆
		$obj = memberauthModel::getByid($id);
		$this->assign("obj", $obj);
		//判断成员馆文件夹是否重名
		$cygfile = memberauthModel::getOneByWhere(['is_chk'=>2,'leaguer_no'=>$obj['leaguer_no']]);
		$is_refile = !empty($cygfile)?2:1;
		$this->assign("is_refile", $is_refile);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$leaguer_no = isset($post['leaguer_no'])?$post['leaguer_no']:'';
			$data['is_chk'] = isset($post['is_chk'])?$post['is_chk']:1;
			$remark = isset($post['remark'])?$post['remark']:'';
			$new_remark = "管理员".$this->user['id']."【".$this->user['truename']."】".date("Y-m-d H:i:s")." ";
			if($data['is_chk'] == 2) $new_remark.= "会员认证审核通过。";
			if($data['is_chk'] == 3) $new_remark.= "会员认证审核不通过。";
			if(!empty($leaguer_no)){
				$leaguer_no = strtoupper($leaguer_no);
				$cygobj = memberauthModel::getOneByWhere(['is_chk'=>2,'leaguer_no'=>$leaguer_no]);
				if(!empty($cygobj)){
					$this->error('成员馆文件夹名称重复！',Url('admin/memberauth/detail','id='.$id));
				}
				$data['leaguer_no'] = $leaguer_no;
				$new_remark.= "会员认证提交成员馆素材文件夹【".$data['leaguer_no']."】。";
			}
			$data['remark'] = $obj['remark'].$new_remark.$remark."\r\n";
			//设置成员馆
			$is_leaguer = isset($post['is_leaguer'])?$post['is_leaguer']:1;
			if($is_leaguer==2){
				$data = [];
				$data['is_leaguer'] = $is_leaguer;
				$data['remark'] = $obj['remark']."会员设置为成员馆。"."\r\n";
			}
			$where = ['id'=>$id];
			$rs = memberauthModel::upd_data($where, $data);
			$this->success('认证提交成功。',$this->memberauthList);
		}
		return $this->fetch();
	}
	
}
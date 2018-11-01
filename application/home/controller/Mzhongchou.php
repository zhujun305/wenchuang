<?php
namespace app\home\controller;
use app\home\controller\Member;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\crowdfundingModel;
use app\model\crowdfundruleModel;
use app\model\crowdfundorderModel;

/**
 * 会员中心-众筹管理
 */
class Mzhongchou extends Member
{
	public function _initialize() {
		parent::_initialize ();
		$this->mzhongchouList = Session::get('mzhongchouList');
		$this->assign('mzhongchouList', $this->mzhongchouList);
		$this->mzcorderList = Session::get('mzcorderList');
		$this->assign('mzcorderList', $this->mzcorderList);
		//判断是否认证
    	$renzobj = $this->is_member_auth();
		if(empty($renzobj)){
			$this->error('未认证，前往会员中心认证！',Url('home/Member/memberauth'));
		}
		//判断会员认证身份
		if($this->member['auth_cate']!=4){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		//众筹分类
		$catelist = readCacheFile("crowdfundcate");
		$this->assign('catelist', $catelist);
		//众筹项目状态
		$status_arr = Config::get('config.cf_status');
		$this->assign('status_arr', $status_arr);
		//众筹订单状态
		$cforder_status = Config::get('config.cforder_status');
		$this->assign('cforder_status', $cforder_status);
	}
	
	public function index(){
		$this->redirect(Url('/home/Mzhongchou/mzhongchou'));
	}
	
	/**
	 * 众筹列表
	 */
	public function mzhongchou($sts='',$find=''){
		session('mzhongchouList',Request::instance()->url());
		$this->assign('sts', $sts);
		$this->assign('find', htmlspecialchars($find));
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//列表
		$where = array('is_lock'=>1,'uid'=>$this->uid);
		if(!empty($find)) $where['title'] = $find;
		if($sts==1) $where['status'] = 1;
		if($sts==2) $where['status'] = 2;
		if($sts==3) $where['status'] = 3;
		$list = crowdfundingModel::getList($where,[],'10');
		$this->assign('list', $list);
		//会员uid列表
		$idarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$idarr[] = $val['id'];
			}
		}
		$idarr = array_unique(array_filter($idarr));
		$rulelist = crowdfundruleModel::getListByWhere(['cfid'=>['in',$idarr]],['cfid','COUNT(1) num'],'','','cfid');
		$rulearr = convert_arr_key($rulelist,'cfid','num');
		$this->assign('rulearr', $rulearr);
		return $this->fetch();
	}
	
	/**
	 * 众筹发布
	 */
	public function mzcadd(){
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $this->uid;
			$data['title'] = $post['title'];
			$data['cate'] = $post['cate'];
			$data['price'] = $post['price'];
			$data['new_price'] = $data['price']; //当前单价=单价
			$data['tot_num'] = $post['tot_num'];
			$data['tot_price'] = $post['tot_price'];
			$data['end_time'] = strtotime($post['end_time']);
			$data['cover'] = basename($post['cover']);
			$data['summary'] = $post['summary'];
			$content = $post['content'];
			$data['content'] = $content;
			$id = crowdfundingModel::add_data($data);
			if($id>0){
				$this->success('发布成功。',Url('home/mzhongchou/mzcsetrule','id='.$id.'&temp=2'));
			}else{
				$this->error('发布失败！',$this->mzhongchouList);
			}
		}
		return $this->fetch();
	}
	
	/**
	 * 众筹编辑
	 */
	public function mzcupd($id){
		$obj = crowdfundingModel::getByid($id);
		$obj['cover_path'] = getImgURL($obj['cover']);
		$this->assign('obj',$obj);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $this->uid;
			$data['title'] = $post['title'];
			$data['cate'] = $post['cate'];
			$data['price'] = $post['price'];
			$data['tot_num'] = $post['tot_num'];
			$data['tot_price'] = $post['tot_price'];
			$data['end_time'] = strtotime($post['end_time']);
			$data['cover'] = basename($post['cover']);
			$data['summary'] = $post['summary'];
			$content = $post['content'];
			$data['content'] = $content;
			$rs = crowdfundingModel::upd_data(['id'=>$id],$data);
			$this->success('编辑成功。',$this->mzhongchouList);
		}
		return $this->fetch();
	}
	
	/**
	 * 众筹发布-设置规则
	 */
	public function mzcsetrule($id,$temp=''){
		$this->assign('temp',$temp);
		$obj = crowdfundingModel::getByid($id);
		$this->assign('obj',$obj);
		//规则
		$list = crowdfundruleModel::getListByWhere(['cfid'=>$id]);
		$this->assign('list',$list);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$idsarr = $post['idsarr'];
			$catearr = $post['catearr'];
			$numarr = $post['numarr'];
			$valarr = $post['valarr'];
			$data = [];
			if(!empty($numarr)){
				foreach ($numarr as $key=>$val){
					$jiaglie = (isset($valarr[$key])&&($catearr[$key]==1))?$valarr[$key]:0;
					$agiolie = (isset($valarr[$key])&&($catearr[$key]==2))?$valarr[$key]:0;
					if(!empty($val) && ($jiaglie>0 || $agiolie>0)){
						if(isset($idsarr[$key]) && $idsarr[$key]>0){
							$updlie = [];
							$updlie['num'] = $val;
							$updlie['price'] = $jiaglie;
							$updlie['agio'] = $agiolie;
							$rs = crowdfundruleModel::upd_data(['id'=>$idsarr[$key]],$updlie);
						}else{
							$data[$key]['cfid'] = $id;
							$data[$key]['input_uid'] = $this->uid;
							$data[$key]['input_time'] = time();
							$data[$key]['cate'] = isset($catearr[$key])?$catearr[$key]:0; //类型1价格2折扣
							$data[$key]['num'] = $val;
							$data[$key]['price'] = $jiaglie;
							$data[$key]['agio'] = $agiolie;
						}
					}
				}
				$rs = crowdfundruleModel::add_all($data);
			}
			if($temp>0){
				$this->success('设置成功。',Url('home/mzhongchou/mzcsetrule','id='.$id.'&temp=3'));
			}else{
				$this->success('设置成功。',$this->mzhongchouList);
			}
		}
		return $this->fetch();
	}
	
	/**
	 * 认筹管理
	 */
	public function mzcbuylist(){
		$where = array('is_lock'=>1,'uid'=>$this->uid);
		$list = crowdfundorderModel::getList($where,[],'6');
		$this->assign('list', $list);
		//项目列表
		$idsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$idsarr[] = $val['cfid'];
			}
		}
		$idsarr = array_unique(array_filter($idsarr));
		$mlist = crowdfundingModel::getListByWhere(['id'=>['in',$idsarr]]);
		$mlist = convert_arr_key($mlist,'id');
		$this->assign("mlist", $mlist);
		return $this->fetch();
	}
	
	/**
	 * 我的订单
	 */
	public function mzcorder(){
		session('mzcorderList',Request::instance()->url());
		$cdfdlist = crowdfundingModel::getListByWhere(['is_lock'=>1,'uid'=>$this->uid],['id']);
		$cfidsarr = convert_arr_key($cdfdlist,'id','id');
		$cfidsarr = array_unique(array_filter($cfidsarr));
		$where = array('is_lock'=>1,'cfid'=>'','uid'=>$this->uid);
		if(!empty($cfidsarr)){
			$where['cfid'] = ['in',implode(",",$cfidsarr)];
		}
		$list = crowdfundorderModel::getList($where,[],'6');
		$this->assign('list', $list);
		//项目列表
		$uidsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidsarr[] = $val['uid'];
			}
		}
		$uidsarr = array_unique(array_filter($uidsarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidsarr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		return $this->fetch();
	}
	
	/**
	 * 申请取消订单
	 */
	public function askcancelorder(){
    	$id = Request::instance()->post("id");
    	$text = Request::instance()->post("text");
    	$rs = 0;
		if($id>0 && $text!=''){
			$rs = crowdfundorderModel::upd_data(['id'=>$id],['status'=>2,'cancel'=>$text]);
		}
		echo json_encode($rs);
	}
	
	/**
	 * 确认取消订单
	 */
	public function surecancelorder($id){
		$order = crowdfundorderModel::getByid($id);
		$obj = crowdfundingModel::getByid($order['cfid']);
		$totnum = $obj['zhe_num'] - $order['num'];
		$new_price = $obj['new_price'];
		$rs = crowdfundorderModel::upd_data(['id'=>$id],['status'=>3]);
		//计算减去数量后的单价
		$ruleone = crowdfundruleModel::getOneByWhere(['is_lock'=>1,'cfid'=>$order['cfid'],'cate'=>1,'num'=>['<=',$totnum]],[],'num desc');
		if(!empty($ruleone)){
			$new_price = $ruleone['price'];
		}
		$ruletwo = crowdfundruleModel::getOneByWhere(['is_lock'=>1,'cfid'=>$order['cfid'],'cate'=>2,'num'=>['<=',$totnum]],[],'num desc');
		if(!empty($ruletwo)){
			$new_price = ($ruletwo['agio']/10) * $new_price; //折扣除以10
		}
		$data = [];
		$data['zhe_num'] = $totnum;
		$data['zhe_price'] = $obj['zhe_price'] - $order['tot_price'];
		$data['new_price'] = $new_price;
		$percent_num = round(($data['zhe_num']/$obj['tot_num']),2) * 100;
		$percent_price = round(($data['zhe_price']/$obj['tot_price']),2) * 100;
		$data['percent'] = $percent_num>$percent_price?$percent_num:$percent_price;
		$rs = crowdfundingModel::upd_data(['id'=>$order['cfid']],$data);
		$this->success('订单已取消！',$this->mzcorderList);
	}
	
}

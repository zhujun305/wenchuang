<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Cache;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\memberModel;
use app\model\membercollModel;
use app\model\memberskilltagsModel;
use app\model\crowdsourcingModel;
use app\model\crowdsourccateModel;
use app\model\crowdsourcwtbModel;

/**
 * 众包
 */
class Crowdsourcing extends Homebase
{
	public function _initialize() {
		parent::_initialize ();
		$this->uid = Session::get('portalUserUid');
		if(Request::instance()->action() == 'index'){
			$this->member = $this->setportalUser();
		}else{
			$this->member = $this->getportalUser();
		}
		$this->assign('member', $this->member);
		$this->assign('controller', Request::instance()->controller());
		$this->crowdsourcList = Session::get('crowdsourcList');
		$this->assign('crowdsourcList', $this->crowdsourcList);
		//众包项目状态
		$status_arr = Config::get('config.cs_status');
		$this->assign('status_arr', $status_arr);
		//众包分类
		$catelist = readCacheFile("crowdsourcingcate");
		$this->assign('catelist', $catelist);
	}
	
	/**
	 * 首页
	 */
	public function index($cate='',$order=''){
		session('crowdsourcList',Request::instance()->url());
		$this->assign('cate', $cate);
		//列表
		$where = array('is_lock'=>1,'is_chk'=>2,'status'=>['<',7]);
		if($cate>0) $where['cate'] = $cate;
		$orderby = 'id desc';
		if($order==1) $orderby = 'id desc';
		if($order==2) $orderby = 'id asc';
		if($order==3) $orderby = 'end_time desc,id desc';
		if($order==4) $orderby = 'end_time asc,id desc';
		if($order==5) $orderby = 'wtb_num desc,id desc';
		if($order==6) $orderby = 'wtb_num asc,id desc';
		if($order==7) $orderby = 'price desc,id desc';
		if($order==8) $orderby = 'price asc,id desc';
		$this->assign('order', $order);
		$list = crowdsourcingModel::getList($where,[],'20',$orderby);
		$this->assign('list', $list);
		//广告
		$ads = $this->getAds('cdsourcbanner');
		$this->assign('ads', $ads);
        return $this->fetch('crowdsourcing');
	}
	
	/**
	 * 案例
	 */
	public function caselist($cate='',$order=''){
		$this->assign('cate', $cate);
		//列表
		$where = array('is_lock'=>1,'is_chk'=>2,'status'=>7);
		if($cate>0) $where['cate'] = $cate;
		$orderby = 'id desc';
		if($order==1) $orderby = 'id desc';
		if($order==2) $orderby = 'id asc';
		if($order==3) $orderby = 'end_time desc,id desc';
		if($order==4) $orderby = 'end_time asc,id desc';
		if($order==5) $orderby = 'pv desc,id desc';
		$this->assign('order', $order);
		$list = crowdsourcingModel::getList($where,[],'20',$orderby);
		$this->assign('list', $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid']; //发布会员
				$uidarr[] = $val['wtb_uid']; //投稿会员
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		return $this->fetch();
	}
	
	/**
	 * 众包项目详细
	 */
	public function detail($id,$type=''){
		$obj = crowdsourcingModel::getByid($id);
		$obj['cover_path'] = getImgURL($obj['cover']);
		$obj['file_path'] = getFileURL($obj['filename'],'zipfile');
		//是否被收藏（cate=1会员2素材3众包4众筹5商品）
		$obj['collid'] = 0;
		if($this->uid>0){
			$collobj = membercollModel::getOneByWhere(['cate'=>3,'uid'=>$this->uid,'otherid'=>$id],[]);
			if(!empty($collobj)){
				$obj['collid'] = $collobj['id'];
			}
		}
		$this->assign('obj',$obj);
		$fbmember = memberModel::getByid($obj['uid']);
		$this->assign('fbmember',$fbmember);
		//其他项目
		$otherlist = crowdsourcingModel::getListByWhere(['is_lock'=>1,'is_chk'=>2,'status'=>['<',7]],[],'10');
		$this->assign('otherlist',$otherlist);
		//投稿方案列表
		$falist = crowdsourcwtbModel::getListByWhere(['is_lock'=>1,'cs_id'=>$id],[]);
		$this->assign('falist',$falist);
		//会员uid列表
		$uidarr = [];
		if(!empty($falist)){
			foreach ($falist as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $this->uid;
			$data['cs_id'] = $id;
			$data['price'] = $post['price'];
			$data['cycle'] = $post['cycle'];
			$data['cover'] = basename($post['cover']);
			$data['filename'] = pathinfo($post['filename'],PATHINFO_BASENAME);
			$content = $post['content'];
			$data['content'] = $content;
			$tgid = crowdsourcwtbModel::add_data($data);
			$rs = crowdsourcingModel::upd_data(['id'=>$id],['wtb_num'=>($obj['wtb_num']+1)]);
			//6. 会员提交众包设计方案奖励10积分；
			if($tgid>0 && $obj['uid']>0){
				$this->addintegral($obj['uid'],1,4);//会员，收支类型，收入方式，支出方式
			}
			$this->success('发布成功。',$this->crowdsourcList);
		}
		if($type=='add'){
			//判断会员认证身份
			if($this->member['auth_cate']<2 || $this->member['auth_cate']>3){
				$this->error('只有设计师或设计团队可以交稿！',Url('home/Member/index'));
			}
			return $this->fetch('detailadd');
		}else{
			crowdsourcingModel::upd_data(['id'=>$id],['pv'=>($obj['pv']+1)]);
			return $this->fetch();
		}
	}
	
	/**
	 * 设计师/团队列表
	 */
	public function teamer($cate='',$mc=''){
		$this->assign('cate', $cate);
		$this->assign('mc', $mc);
		$catelist = readCacheFile("memberskilltags");
		$this->assign('catelist', $catelist);
		//列表
		$where = array('is_lock'=>1,);
		if(!in_array($mc,[1,2,3])) $where['cate'] = ['in',['2','3']];
		if($mc==1){
			$where['cate'] = 2;
			$where['team_cate'] = 2;
		}
		if($mc==2){
			$where['cate'] = 2;
			$where['team_cate'] = 1;
		}
		if($mc==3) $where['cate'] = 3;
		$stags = ["%,".$cate.",%"];
		if($cate>0){
			$clist = memberskilltagsModel::getListByWhere(['is_lock'=>1,'pid'=>$cate],[],'sort desc,id asc');
			if(!empty($clist)){
				foreach ($clist as $key=>$val){
					$stags[] = "%,".$val['id'].",%";
				}
			}
			if(!empty($stags)){
				$where['skilltags'] = $stags;
			}
		}
		$list = memberModel::getLeftjoinList($where,['a.*'],'20');
		$this->assign('list', $list);
		return $this->fetch();
	}
	
}

<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\memberModel;
use app\model\membercollModel;
use app\model\crowdfundingModel;
use app\model\crowdfundruleModel;
use app\model\crowdfundorderModel;

/**
 * 众筹
 */
class Crowdfunding extends Homebase
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
		//
		$this->crowfundingList = Session::get('crowfundingList');
		$this->assign('crowfundingList', $this->crowfundingList);
		//众筹分类
		$catelist = readCacheFile("crowdfundcate");
		$this->assign('catelist', $catelist);
		//众筹项目状态
		$status_arr = Config::get('config.cf_status');
		$this->assign('status_arr', $status_arr);
	}
	
	/**
	 * 首页
	 */
	public function index($cate=''){
		session('crowfundingList',Request::instance()->url());
		$this->assign('cate', $cate);
		//列表
		$where = array('is_lock'=>1,'is_chk'=>2);
		if($cate>0) $where['cate'] = $cate;
		$list = crowdfundingModel::getList($where,[],'6');
		$this->assign('list', $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		//广告
		$ads = $this->getAds('cdfundbanner');
		$this->assign('ads', $ads);
        return $this->fetch('crowdfunding');
	}

	/**
	 * 项目详细
	 */
	public function detail($id,$type=''){
		$obj = crowdfundingModel::getByid($id);
		$obj['cover_path'] = getImgURL($obj['cover']);
		//是否被收藏（cate=1会员2素材3众包4众筹5商品）
		$obj['collid'] = 0;
		if($this->uid>0){
			$collobj = membercollModel::getOneByWhere(['cate'=>4,'uid'=>$this->uid,'otherid'=>$id],[]);
			if(!empty($collobj)){
				$obj['collid'] = $collobj['id'];
			}
		}
		$this->assign('obj',$obj);
		$fbmember = memberModel::getByid($obj['uid']);
		$fbmember['logo_path'] = getImgURL($fbmember['imgurl'],'memberlogo');
		$this->assign('fbmember',$fbmember);
		//发起项目总数
		$xmnum = crowdfundingModel::getOneByWhere(['is_lock'=>1,'is_chk'=>2,'uid'=>$this->uid],['count(1) num']);
		$fqxmnum = $xmnum['num']>0?$xmnum['num']:0;
		$this->assign('fqxmnum',$fqxmnum);
		//认购项目总数
		$rgnum = crowdfundorderModel::getOneByWhere(['is_lock'=>1,'uid'=>$this->uid],['count(1) num']);
		$rgxmnum = $rgnum['num']>0?$rgnum['num']:0;
		$this->assign('rgxmnum',$rgxmnum);
		//认购列表
		$ordlist = crowdfundorderModel::getListByWhere(['is_lock'=>1,'cfid'=>$id]);
		$this->assign('ordlist',$ordlist);
		//会员uid列表
		$uidarr = [];
		if(!empty($ordlist)){
			foreach ($ordlist as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		crowdfundingModel::upd_data(['id'=>$id],['pv'=>($obj['pv']+1)]); //更新浏览量
		return $this->fetch();
	}
	
	/**
	 * 认购订单
	 */
	public function rcorder($id){
		$obj = crowdfundingModel::getByid($id);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$rcnum = $post['rcnum'];
			$data = [];
			$data['uid'] = $this->uid;
			$data['input_uid'] = $data['uid'];
			$data['cfid'] = $id;
			$data['num'] = $rcnum;
			$id = crowdfundorderModel::add_data($data);
			if($id>0){
				$this->rcsetpriceorder($id);
				$this->success('认筹成功。', $this->crowfundingList);
			}else{
				$this->error('认筹失败！', $this->crowfundingList);
			}
		}
		return $this->fetch();
	}
	
	/**
	 * 认筹降价规则
	 */
	private function rcsetpriceorder($id){
		$order = crowdfundorderModel::getByid($id);
		$obj = crowdfundingModel::getByid($order['cfid']);
		$totnum = $obj['zhe_num'] + $order['num']; //订单认筹总数量
		$data = [];
		$data['old_price'] = $totnum * $obj['new_price']; //原总价
		$new_price = $obj['new_price'];
		$ruleids = [];
		$ruleone = crowdfundruleModel::getOneByWhere(['is_lock'=>1,'cfid'=>$order['cfid'],'cate'=>1,'num'=>['<=',$totnum]],[],'num desc');
		if(!empty($ruleone)){
			$new_price = $ruleone['price'];
			$ruleids[] = $ruleone['id'];
		}
		$ruletwo = crowdfundruleModel::getOneByWhere(['is_lock'=>1,'cfid'=>$order['cfid'],'cate'=>2,'num'=>['<=',$totnum]],[],'num desc');
		if(!empty($ruletwo)){
			$new_price = ($ruletwo['agio']/10) * $new_price; //折扣除以10
			$ruleids[] = $ruletwo['id'];
		}
		$data['tot_price'] = $totnum * $new_price;
		if(!empty($ruleids)){
			$data['ruleids'] = implode(",",$ruleids);
		}
		$rs = crowdfundorderModel::upd_data(['id'=>$id],$data);
		$darr = [];
		$darr['new_price'] = $new_price;
		$darr['zhe_num'] = $totnum;
		$darr['zhe_price'] = $obj['zhe_price'] + $data['tot_price'];
		$percent_num = round(($darr['zhe_num']/$obj['tot_num']),2) * 100;
		$percent_price = round(($darr['zhe_price']/$obj['tot_price']),2) * 100;
		$darr['percent'] = $percent_num>$percent_price?$percent_num:$percent_price;
		$rs = crowdfundingModel::upd_data(['id'=>$order['cfid']],$darr);
	}
}

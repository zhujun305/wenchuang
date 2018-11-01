<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberModel;
use app\model\membercollModel;
use app\model\materialModel;
use app\model\crowdsourcingModel;
use app\model\crowdfundingModel;
use app\model\shopgoodsModel;

/**
 * 会员收藏管理
 */
class Membercoll extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->membercollList = Session::get('membercollList');
		$this->assign('membercollList', $this->membercollList);
		//收藏分类
		$catearr = Config::get('config.membercoll');
		$this->assign('catearr', $catearr);
	}

	public function index(){
		$this->redirect(Url('/Admin/Membercoll/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('membercollList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'uid,cate,otherid,time1,time2';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = membercollModel::getList($findObj);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		$matarr = []; //素材id
		$cdscarr = []; //众包id
		$cdfuncarr = []; //众筹id
		$goodsarr = []; //商品id
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
				if($val['cate']==1){ //会员
					$uidarr[] = $val['otherid'];
				}elseif($val['cate']==2){ //素材
					$matarr[] = $val['otherid'];
				}elseif($val['cate']==3){ //众包
					$cdscarr[] = $val['otherid'];
				}elseif($val['cate']==4){ //众筹
					$cdfuncarr[] = $val['otherid'];
				}elseif($val['cate']==5){ //商品
					$goodsarr[] = $val['otherid'];
				}
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		//当前页-素材
		$matarr = array_unique(array_filter($matarr));
		$matlist = materialModel::getListByWhere(['id'=>['in',$matarr]],['id','title','cover']);
		$matlist = convert_arr_key($matlist,'id');
		$this->assign("matlist", $matlist);
		//当前页-众包
		$cdscarr = array_unique(array_filter($cdscarr));
		$cdsclist = crowdsourcingModel::getListByWhere(['id'=>['in',$cdscarr]],['id','title','cover']);
		$cdsclist = convert_arr_key($cdsclist,'id');
		$this->assign("cdsclist", $cdsclist);
		//当前页-众筹
		$cdfuncarr = array_unique(array_filter($cdfuncarr));
		$cdfclist = crowdfundingModel::getListByWhere(['id'=>['in',$cdfuncarr]],['id','title','cover']);
		$cdfclist = convert_arr_key($cdfclist,'id');
		$this->assign("cdfclist", $cdfclist);
		//当前页-商品
		$goodsarr = array_unique(array_filter($goodsarr));
		$goodslist = shopgoodsModel::getListByWhere(['id'=>['in',$goodsarr]],['id','title','cover']);
		$goodslist = convert_arr_key($goodslist,'id');
		$this->assign("goodslist", $goodslist);
		return $this->fetch();
	}
	
}
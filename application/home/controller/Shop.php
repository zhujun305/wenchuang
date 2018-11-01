<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\membercollModel;
use app\model\shopgoodsModel;
use app\model\shopgoodscateModel;
use app\model\shopgoodsimagesModel;
use app\model\shopgoodsnormsModel;
use app\model\shopgoodspingModel;
use app\model\shopgoodsruleModel;

/**
 * 商品
 */
class Shop extends Homebase
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
		//商品分类
		$this->catelist = readCacheFile("shopgoodscate");
		$this->assign('catelist', $this->catelist);
	}
	
	/**
	 * 首页
	 */
	public function index(){
		//广告
		$ads = $this->getAds('goodsbanner');
		$this->assign('ads', $ads);
		$catelist = $this->catelist;
		//联盟成员馆
		$cyglist = memberauthModel::getListByWhere(['is_lock'=>1,'cate'=>4,'is_leaguer'=>2],[],'','37');
		$cygarr = convert_arr_key($cyglist,'uid','uid');
		$uidstr = '';
		if(!empty($cygarr)) $uidstr = implode(",",$cygarr);
		$mlist = memberModel::getListByWhere(['is_lock'=>1,'uid'=>['in',$uidstr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign('mlist', $mlist);
		$this->assign('cyglist', $cyglist);
		//商品
		$where = ['is_lock'=>1,'is_chk'=>2];
		//最新商品8个
		$zxlist = shopgoodsModel::getListByWhere($where,[],'id desc','8');
		$this->assign('zxlist', $zxlist);
		//热门商品8个
		$rmlist = shopgoodsModel::getListByWhere($where,[],'pv desc,id desc','8');
		$this->assign('rmlist', $rmlist);
		//是否被收藏（cate=1会员2素材3众包4众筹5商品）
		$colllist = [];
		if($this->uid>0){
			$gidsarr = [];
			foreach ($zxlist as $key=>$val){
				$gidsarr[] = $val['id'];
			}
			foreach ($rmlist as $key=>$val){
				$gidsarr[] = $val['id'];
			}
			if(!empty($gidsarr)){
				$colllist = membercollModel::getListByWhere(['cate'=>5,'uid'=>$this->uid,'otherid'=>['in',implode(",",$gidsarr)]]);
				$colllist = convert_arr_key($colllist,'otherid','id');
			}
		}
		$this->assign('colllist', $colllist);
		//前六位分类
		$clist = shopgoodscateModel::getListByWhere(['is_lock'=>1],[],'pid asc,sort desc,id asc','6');
		$this->assign('clist', $clist);
		//分类1取9条记录
		$cid_a = isset($clist[0])?$clist[0]:['id'=>0];
		$list_ta = [];
		if($cid_a['id']>0){
			$cidstr = isset($catelist[$cid_a['id']]['sonidstr'])?$catelist[$cid_a['id']]['sonidstr']:'';
			if($cidstr!=''){
				$where['cate'] = ['in',$cidstr];
				$list_ta = shopgoodsModel::getListByWhere($where,[],'is_recommend desc,id desc','9');
			}
		}
		$this->assign('cid_a', $cid_a);
		$this->assign('list_ta', $list_ta);
		//分类2取12条记录
		$cid_b = isset($clist[1])?$clist[1]:['id'=>0];
		$list_tb = [];
		if($cid_b['id']>0){
			$cidstr = isset($catelist[$cid_b['id']]['sonidstr'])?$catelist[$cid_b['id']]['sonidstr']:'';
			if($cidstr!=''){
				$where['cate'] = ['in',$cidstr];
				$list_tb = shopgoodsModel::getListByWhere($where,[],'is_recommend desc,id desc','12');
			}
		}
		$this->assign('cid_b', $cid_b);
		$this->assign('list_tb', $list_tb);
		//分类3取12条记录
		$cid_c = isset($clist[2])?$clist[2]:['id'=>0];
		$list_tc = [];
		if($cid_c['id']>0){
			$cidstr = isset($catelist[$cid_c['id']]['sonidstr'])?$catelist[$cid_c['id']]['sonidstr']:'';
			if($cidstr!=''){
				$where['cate'] = ['in',$cidstr];
				$list_tc = shopgoodsModel::getListByWhere($where,[],'is_recommend desc,id desc','12');
			}
		}
		$this->assign('cid_c', $cid_c);
		$this->assign('list_tc', $list_tc);
		//分类4取12条记录
		$cid_d = isset($clist[3])?$clist[3]:['id'=>0];
		$list_td = [];
		if($cid_d['id']>0){
			$cidstr = isset($catelist[$cid_d['id']]['sonidstr'])?$catelist[$cid_d['id']]['sonidstr']:'';
			if($cidstr!=''){
				$where['cate'] = ['in',$cidstr];
				$list_td = shopgoodsModel::getListByWhere($where,[],'is_recommend desc,id desc','12');
			}
		}
		$this->assign('cid_d', $cid_d);
		$this->assign('list_td', $list_td);
		//分类5取6条记录
		$cid_e = isset($clist[4])?$clist[4]:['id'=>0];
		$list_te = [];
		if($cid_e['id']>0){
			$cidstr = isset($catelist[$cid_e['id']]['sonidstr'])?$catelist[$cid_e['id']]['sonidstr']:'';
			if($cidstr!=''){
				$where['cate'] = ['in',$cidstr];
				$list_te = shopgoodsModel::getListByWhere($where,[],'is_recommend desc,id desc','6');
			}
		}
		$this->assign('cid_e', $cid_e);
		$this->assign('list_te', $list_te);
		//分类6取12条记录
		$cid_f = isset($clist[5])?$clist[5]:['id'=>0];
		$list_tf = [];
		if($cid_f['id']>0){
			$cidstr = isset($catelist[$cid_f['id']]['sonidstr'])?$catelist[$cid_f['id']]['sonidstr']:'';
			if($cidstr!=''){
				$where['cate'] = ['in',$cidstr];
				$list_tf = shopgoodsModel::getListByWhere($where,[],'is_recommend desc,id desc','12');
			}
		}
		$this->assign('cid_f', $cid_f);
		$this->assign('list_tf', $list_tf);
        return $this->fetch('shop');
	}
	
	/**
	 * 商品列表
	 */
	public function shopgoods($paixu='1',$pid='',$cate=''){
		$this->assign("paixu", $paixu);
		$this->assign("pid", $pid);
		$this->assign("cate", $cate);
		$where = ['is_lock'=>1,'is_chk'=>2];
		if($cate>0){
			$topcate = shopgoodscateModel::getByid($cate);
			if($topcate['level']==2){
				$where['cate'] = $cate;
			}else{
				$where['cate_san'] = $cate;
			}
		}elseif($pid>0){
			$cidstr = isset($this->catelist[$pid]['sonidstr'])?$this->catelist[$pid]['sonidstr']:'';
			$where['cate'] = $cidstr;
		}
		$order = 'id desc';
		if($paixu==2) $order = 'sale_num desc,id desc';
		if($paixu==3) $order = 'sale_num asc,id desc';
		if($paixu==4) $order = 'id desc';
		if($paixu==5) $order = 'id asc';
		$list = shopgoodsModel::getList($where,[],'16',$order);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		$gidsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
				$gidsarr[] = $val['id'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		//是否被收藏（cate=1会员2素材3众包4众筹5商品）
		$colllist = [];
		if($this->uid>0){
			if(!empty($gidsarr)){
				$colllist = membercollModel::getListByWhere(['cate'=>5,'uid'=>$this->uid,'otherid'=>['in',implode(",",$gidsarr)]]);
				$colllist = convert_arr_key($colllist,'otherid','id');
			}
		}
		$this->assign('colllist', $colllist);
		return $this->fetch();
	}
	
	/**
	 * 商品推荐列表
	 */
	public function shopgoodstj($paixu='1',$pid='',$cate=''){
		$this->assign("paixu", $paixu);
		$this->assign("pid", $pid);
		$this->assign("cate", $cate);
		$where = ['is_lock'=>1,'is_chk'=>2,'is_recommend'=>2];
		if($cate>0) $where['cate'] = $cate;
		if($pid>0){
			$cidstr = isset($this->catelist[$pid]['sonidstr'])?$this->catelist[$pid]['sonidstr']:'';
			$where['cate'] = $cidstr;
		}
		$order = 'id desc';
		if($paixu==2) $order = 'sale_num desc,id desc';
		if($paixu==3) $order = 'sale_num asc,id desc';
		if($paixu==4) $order = 'id desc';
		if($paixu==5) $order = 'id asc';
		$list = shopgoodsModel::getList($where,[],'16',$order);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		$gidsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
				$gidsarr[] = $val['id'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		//是否被收藏（cate=1会员2素材3众包4众筹5商品）
		$colllist = [];
		if($this->uid>0){
			if(!empty($gidsarr)){
				$colllist = membercollModel::getListByWhere(['cate'=>5,'uid'=>$this->uid,'otherid'=>['in',implode(",",$gidsarr)]]);
				$colllist = convert_arr_key($colllist,'otherid','id');
			}
		}
		$this->assign('colllist', $colllist);
		return $this->fetch();
	}
	
	/**
	 * 商品详情
	 */
	public function detail($id){
		$obj = shopgoodsModel::getByid($id);
		$catesan = shopgoodscateModel::getByid($obj['cate_san'],['id']);
		$obj['catesan'] = !empty($catesan)?$catesan['id']:0;
		$this->assign("obj", $obj);
		//图片列表
		$imglist = shopgoodsimagesModel::getListByWhere(['gid'=>$id],[],'id asc');
		$this->assign('imglist',$imglist);
		//规格列表
		$shopnorms = readCacheFile("shopnorms");
		$this->assign('shopnorms', $shopnorms);
		$snormsval = readCacheFile("shopnormsval");
		$this->assign('snormsval', $snormsval);
		$gglist = shopgoodsnormsModel::getListByWhere(['is_lock'=>1,'gid'=>$id]);
		$this->assign('gglist', $gglist);
		$snkvarr = [];
		$salejia = ['min_price'=>$obj['price'],'max_price'=>$obj['price'],];
		$oldjia = ['min_price'=>$obj['old_price'],'max_price'=>$obj['old_price'],];
		$ys_name = [];
		$snvlist = [];
		$a_b = [];
		$b_a = [];
		foreach ($gglist as $key=>$val){
			if($salejia['min_price'] > $val['price']) $salejia['min_price'] = $val['price'];
			if($salejia['max_price'] < $val['price']) $salejia['max_price'] = $val['price'];
			if($oldjia['min_price'] > $val['old_price']) $oldjia['min_price'] = $val['old_price'];
			if($oldjia['max_price'] < $val['old_price']) $oldjia['max_price'] = $val['old_price'];
			if($val['snvidone']>0) $snkvarr[$val['snidone']][$val['snvidone']] = $val['snvidone'];
			if($val['snvidtwo']>0) $snkvarr[$val['snidtwo']][$val['snvidtwo']] = $val['snvidtwo'];
			if($val['snvidthree']>0) $snkvarr[$val['snidthree']][$val['snvidthree']] = $val['snvidthree'];
			$ys_name[$val['snidone']] = 'snidone';
			$ys_name[$val['snidtwo']] = 'snidtwo';
			$ys_name[$val['snidthree']] = 'snidthree';
			//
			foreach ($gglist as $kkk=>$vvv){
				if($val['snvidone']==$vvv['snvidone'] && $vvv['snvidone']>0){
					if($val['snvidtwo']==$vvv['snvidtwo'] && $vvv['snvidtwo']>0){
						$a_b[$val['snvidone']][$vvv['snvidtwo']] = $vvv['snvidtwo'];
						$b_a[$vvv['snvidtwo']][$val['snvidone']] = $val['snvidone'];
						foreach ($gglist as $yyy=>$uuu){
							if($vvv['snvidthree']==$uuu['snvidthree'] && $uuu['snvidthree']>0){
// 								$snvlist[$val['snvidone']][$vvv['snvidtwo']][$uuu['snvidthree']] = $uuu['snvidthree'];
								$a_c[$val['snvidone']][$vvv['snvidthree']] = $vvv['snvidthree'];
								$b_c[$vvv['snvidtwo']][$val['snvidthree']] = $val['snvidthree'];
								$c_b[$vvv['snvidthree']][$val['snvidtwo']] = $val['snvidtwo'];
								$c_a[$vvv['snvidthree']][$val['snvidone']] = $val['snvidone'];
							}
						}
					}
				}
			}
		}
		$snvlist['a_b'] = $a_b;
		$snvlist['b_a'] = $b_a;
		$snvlist['a_c'] = $a_c;
		$snvlist['b_c'] = $b_c;
		$snvlist['c_b'] = $c_b;
		$snvlist['c_a'] = $c_a;
		$this->assign('ys_name', $ys_name);
		$this->assign('salejia', $salejia);
		$this->assign('oldjia', $oldjia);
		$this->assign('snkvarr', $snkvarr);
		$this->assign('snvlist', $snvlist);
		//批发价
		$pifalist = shopgoodsruleModel::getListByWhere(['gid'=>$id]);
		$this->assign('pifalist',$pifalist);
		//评价列表
		$pjlist = shopgoodspingModel::getListByWhere(['gid'=>$id],[]);
		$this->assign('pjlist', $pjlist);
		//会员uid列表
		$uidarr = [];
		if(!empty($pjlist)){
			foreach ($pjlist as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		shopgoodsModel::upd_data(['id'=>$id],['pv'=>($obj['pv']+1)]); //更新浏览量
		return $this->fetch();
	}
	//返回-下单选择规格
	public function getnormsbysnvid($id){
		$lavel = Request::instance()->post("lavel");
		$snvid_a = Request::instance()->post("snvid_a");
		$snvid_b = Request::instance()->post("snvid_b");
		$snvid_c = Request::instance()->post("snvid_c");
		$data = ['obj'=>[],'list_a'=>[],'list_b'=>[],'list_c'=>[]];
		$gglist = [];
		if($lavel==1 && $snvid_a>0){
			$where = ['is_lock'=>1,'gid'=>$id,'snvidone'=>$snvid_a];
			$data['obj'] = shopgoodsnormsModel::getOneByWhere($where);
		}elseif($lavel==2){
			if($snvid_a>0 && $snvid_b>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidone'=>$snvid_a,'snvidtwo'=>$snvid_b];
				$data['obj'] = shopgoodsnormsModel::getOneByWhere($where);
			}
			if($snvid_a>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidone'=>$snvid_a];
				$gglist = shopgoodsnormsModel::getListByWhere($where);
				foreach ($gglist as $key=>$val){
					$data['list_b'][$val['snvidtwo']] = $val['snvidtwo'];
				}
			}elseif($snvid_b>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidtwo'=>$snvid_b];
				$gglist = shopgoodsnormsModel::getListByWhere($where);
				foreach ($gglist as $key=>$val){
					$data['list_a'][$val['snvidone']] = $val['snvidone'];
				}
			}
		}elseif($lavel==3){
			if($snvid_a>0 && $snvid_b>0 && $snvid_c>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidone'=>$snvid_a,'snvidtwo'=>$snvid_b,'snvidthree'=>$snvid_c];
				$data['obj'] = shopgoodsnormsModel::getOneByWhere($where);
			}
			if($snvid_a>0 && $snvid_b>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidone'=>$snvid_a,'snvidtwo'=>$snvid_b];
				$gglist = shopgoodsnormsModel::getListByWhere($where);
			}elseif($snvid_a>0 && $snvid_c>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidone'=>$snvid_a,'snvidthree'=>$snvid_c];
				$gglist = shopgoodsnormsModel::getListByWhere($where);
			}elseif($snvid_b>0 && $snvid_c>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidtwo'=>$snvid_b,'snvidthree'=>$snvid_c];
				$gglist = shopgoodsnormsModel::getListByWhere($where);
			}elseif($snvid_a>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidone'=>$snvid_a];
				$gglist = shopgoodsnormsModel::getListByWhere($where);
			}elseif($snvid_b>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidtwo'=>$snvid_b];
				$gglist = shopgoodsnormsModel::getListByWhere($where);
			}elseif($snvid_c>0){
				$where = ['is_lock'=>1,'gid'=>$id,'snvidthree'=>$snvid_c];
				$gglist = shopgoodsnormsModel::getListByWhere($where);
			}
		}
		echo json_encode($data);
	}
	
	/**
	 * 联盟成员馆列表
	 */
	public function cyglist(){
		$where = ['is_lock'=>1,'cate'=>4,'is_leaguer'=>2];
		$list = memberauthModel::getList($where,[],'4');
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name','imgurl']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		return $this->fetch();
	}
	
}

<?php
namespace app\home\controller;
use app\home\controller\Member;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\memberModel;
use app\model\memberauthModel;
use app\model\membershipaddressModel;
use app\model\shopgoodsModel;
use app\model\shopgoodscateModel;
use app\model\shopgoodsimagesModel;
use app\model\shopgoodsnormsModel;
use app\model\shopgoodspingModel;
use app\model\shopgoodsruleModel;
use app\model\shopnormsModel;
use app\model\shopnormsvalModel;
use app\model\shoporderModel;

/**
 * 商品
 */
class Mshangpin extends Member
{
	public function _initialize() {
		parent::_initialize ();
		$this->mshangpinList = Session::get('mshangpinList');
		$this->assign('mshangpinList', $this->mshangpinList);
		//判断是否认证
    	$renzobj = $this->is_member_auth();
		if(empty($renzobj)){
			$this->error('未认证，前往会员中心认证！',Url('home/Member/memberauth'));
		}
		//判断会员认证身份
		if($this->member['auth_cate']!=4){
			$this->error('您当前会员等级无权限操作！',Url('home/Member/index'));
		}
		//商品分类
		$catelist = readCacheFile("shopgoodscate");
		$this->assign('catelist', $catelist);
		//商品订单状态
		$this->status_arr = Config::get('config.sporder_status');
		$this->assign('status_arr', $this->status_arr);
		//物流公司
		$this->logistics_gs = Config::get('config.logistics_gs');
		$this->assign('logistics_gs', $this->logistics_gs);
	}
	
	public function index(){
		$this->redirect(Url('/home/Mshangpin/mshangpin'));
	}
	
	/**
	 * 商品首页
	 */
	public function mshangpin($paixu='',$ischk='',$status='',$title=''){
		session('mshangpinList',Request::instance()->url());
		//列表
		$where = array('is_lock'=>1,'uid'=>$this->uid);
		if($ischk>0) $where['is_chk'] = $ischk;
		if($status>0) $where['status'] = $status;
		if($title!='') $where['title'] = $title;
		$order = 'id desc';
		if($paixu==2) $order = 'id asc';
		if($paixu==3) $order = 'stock_num asc,id desc';
		if($paixu==4) $order = 'stock_num desc,id asc';
		if($paixu==5) $order = 'price asc,id desc';
		if($paixu==6) $order = 'price desc,id asc';
		$list = shopgoodsModel::getList($where,[],'10',$order);
		$this->assign('list', $list);
		$this->assign('paixu', $paixu);
		$this->assign('ischk', $ischk);
		$this->assign('status', $status);
		$this->assign('title', $title);
        return $this->fetch();
	}
	
	/**
	 * 发布商品
	 */
	public function mspadd(){
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['uid'] = $this->uid;
			$data['input_uid'] = $this->uid;
			$data['is_recommend'] = $post['is_recommend'];
			$data['title'] = $post['title'];
			$data['subtitle'] = $post['subtitle'];
			$data['cate'] = $post['cate'];
			$data['cate_san'] = $post['cate_san'];
			$data['sno'] = $post['sno'];
			$data['gno'] = !empty($post['gno'])?$post['gno']:$post['sno'];
			$data['gbarcode'] = strtotime($post['gbarcode']);
			$data['stock_num'] = $post['stock_num'];
			$data['price'] = $post['price'];
			$data['old_price'] = $post['old_price'];
			$data['weight'] = $post['weight'];
			$data['cover'] = basename($post['cover']);
			$data['summary'] = $post['summary'];
			$data['seo_title'] = $post['seo_title'];
			$data['seo_keywords'] = $post['seo_keywords'];
			$data['seo_desc'] = $post['seo_desc'];
			$id = shopgoodsModel::add_data($data);
			if($id>0){
				$this->success('发布成功。',Url('home/mshangpin/mspupddetail','id='.$id));
			}else{
				$this->error('发布失败！',$this->mshangpinList);
			}
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑商品
	 */
	public function mspupd($id){
		$obj = shopgoodsModel::getByid($id);
		$obj['cover_path'] = getImgURL($obj['cover']);
		$topcate = shopgoodscateModel::getByid($obj['cate'],['pid']);
		$obj['catepid'] = $topcate['pid'];
		$catesan = shopgoodscateModel::getByid($obj['cate_san'],['id']);
		$obj['catesan'] = !empty($catesan)?$catesan['id']:0;
		$this->assign('obj',$obj);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data['is_recommend'] = $post['is_recommend'];
			$data['title'] = $post['title'];
			$data['subtitle'] = $post['subtitle'];
			$data['cate'] = $post['cate'];
			$data['cate_san'] = $post['cate_san'];
			$data['sno'] = $post['sno'];
			$data['gno'] = $post['gno'];
			$data['gbarcode'] = strtotime($post['gbarcode']);
			$data['stock_num'] = $post['stock_num'];
			$data['price'] = $post['price'];
			$data['old_price'] = $post['old_price'];
			$data['weight'] = $post['weight'];
			$data['cover'] = basename($post['cover']);
			$data['summary'] = $post['summary'];
			$data['seo_title'] = $post['seo_title'];
			$data['seo_keywords'] = $post['seo_keywords'];
			$data['seo_desc'] = $post['seo_desc'];
			$rs = shopgoodsModel::upd_data(['id'=>$id],$data);
			$this->success('编辑成功。',Url('home/mshangpin/mspupddetail','id='.$id));
		}
		return $this->fetch();
	}
	
	/**
	 * 删除商品
	 */
	public function mspdel($id){
		$rs = shopgoodsModel::upd_data(['id'=>$id],['is_del'=>2]);
		if($rs){
			$this->success('删除成功',$this->mshangpinList);
		}else{
			$this->error('删除失败！',$this->mshangpinList);
		}
	}
	
	/**
	 * 商品上架下架
	 */
	public function mspsxjia($id,$type=''){
		if($type==2){
			$type = 2;
			$msg = '上架';
		}else{
			$type = 1;
			$msg = '下架';
		}
		$rs = shopgoodsModel::upd_data(['id'=>$id],['status'=>$type]);
		if($rs){
			$this->success($msg.'成功',$this->mshangpinList);
		}else{
			$this->error($msg.'失败！',$this->mshangpinList);
		}
	}
	
	/**
	 * 商品批量上架下架
	 */
	public function mspsxjiaall($ids,$type=''){
		if($type==2){
			$type = 2;
			$msg = '上架';
		}else{
			$type = 1;
			$msg = '下架';
		}
		$rs = shopgoodsModel::upd_list($ids,['status'=>$type]);
		if($rs){
			$this->success('批量'.$msg.'成功',$this->mshangpinList);
		}else{
			$this->error('批量'.$msg.'失败！',$this->mshangpinList);
		}
	}
	
	/**
	 * 编辑商品-商品详情
	 */
	public function mspupddetail($id){
		$obj = shopgoodsModel::getByid($id);
		$this->assign('obj',$obj);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$content = $post['content'];
			$data['content'] = $content;
			$rs = shopgoodsModel::upd_data(['id'=>$id],$data);
			$this->success('编辑成功。',Url('home/mshangpin/mspupdimages','id='.$id));
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑商品-商品相册
	 */
	public function mspupdimages($id){
		$obj = shopgoodsModel::getByid($id);
		$this->assign('obj',$obj);
		//商品图片列表
		$imglist = shopgoodsimagesModel::getListByWhere(['gid'=>$id],[],'id asc');
		$this->assign('imglist',$imglist);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$ids = isset($post['ids'])?$post['ids']:[];
			if(!empty($ids)){
				shopgoodsimagesModel::del($ids);
			}
			$picarr = isset($post['picarr'])?$post['picarr']:[];
			$data = [];
			foreach($picarr as $key=>$val){
				$data[$key]['gid'] = $id;
				$data[$key]['pic'] = $val;
			}
			if(!empty($data)){
				$num = shopgoodsimagesModel::add_all($data); //返回插入数量
			}
			$this->success('编辑成功。',Url('home/mshangpin/mspupdnorms','id='.$id));
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑商品-商品规格
	 */
	public function mspupdnorms($id){
		$obj = shopgoodsModel::getByid($id);
		$this->assign('obj',$obj);
		//规格
		$shopnorms = readCacheFile("shopnorms");
		$this->assign('shopnorms', $shopnorms);
		$snormsval = readCacheFile("shopnormsval");
		$this->assign('snormsval', $snormsval);
		$snormskeyval = readCacheFile("shopnormskeyval");
		$this->assign('snormskeyval', $snormskeyval);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$ids_arr = isset($post['ids'])?$post['ids']:[];
			$snvstr_arr = isset($post['snvstr'])?$post['snvstr']:[];
			$snidstr_arr = isset($post['snidstr'])?$post['snidstr']:[];
			$gno_arr = isset($post['gno'])?$post['gno']:[];
			$stock_num_arr = isset($post['stock_num'])?$post['stock_num']:[];
			$old_price_arr = isset($post['old_price'])?$post['old_price']:[];
			$price_arr = isset($post['price'])?$post['price']:[];
			$data = [];
			foreach($snvstr_arr as $key=>$val){
				$data[$key]['gid'] = $id;
				$data[$key]['snvstr'] = $val;
				if($val!=''){
					$arr = array_filter(explode(",",$val));
					$data[$key]['snvidone'] = isset($arr[0])?$arr[0]:0;
					$data[$key]['snvidtwo'] = isset($arr[1])?$arr[1]:0;
					$data[$key]['snvidthree'] = isset($arr[2])?$arr[2]:0;
				}
				$data[$key]['snidstr'] = $snidstr_arr[$key];
				if($data[$key]['snidstr']!=''){
					$arr = array_filter(explode(",",$data[$key]['snidstr']));
					$data[$key]['snidone'] = isset($arr[0])?$arr[0]:0;
					$data[$key]['snidtwo'] = isset($arr[1])?$arr[1]:0;
					$data[$key]['snidthree'] = isset($arr[2])?$arr[2]:0;
				}
				$data[$key]['gno'] = $gno_arr[$key];
				$data[$key]['stock_num'] = $stock_num_arr[$key];
				$data[$key]['old_price'] = $old_price_arr[$key];
				$data[$key]['price'] = $price_arr[$key];
				//更新
				if(!empty($ids_arr)){
					$nid = $ids_arr[$key];
					if($nid>0) shopgoodsnormsModel::upd_data(['id'=>$nid],$data[$key]);
				}
				$data[$key]['snvstr'] = ','.$val.',';
				$data[$key]['snidstr'] = ','.$snidstr_arr[$key].',';
				$data[$key]['input_uid'] = $this->uid;
				$data[$key]['input_time'] = time();
			}
			if(!empty($data) && empty($ids_arr)){
				$rs = shopgoodsnormsModel::delwhr(['gid'=>$id]);
				$num = shopgoodsnormsModel::add_all($data); //返回插入数量
			}
			$this->success('编辑成功。',Url('home/mshangpin/mspupdrule','id='.$id));
		}
		//规格列表
		$list = shopgoodsnormsModel::getListByWhere(['is_lock'=>1,'gid'=>$id]);
		$snkvarr = [];
		foreach ($list as $key=>$val){
			if($val['snvidone']>0) $snkvarr[$val['snidone']][$val['snvidone']] = $val['snvidone'];
			if($val['snvidtwo']>0) $snkvarr[$val['snidtwo']][$val['snvidtwo']] = $val['snvidtwo'];
			if($val['snvidthree']>0) $snkvarr[$val['snidthree']][$val['snvidthree']] = $val['snvidthree'];
		}
		$this->assign('snkvarr',$snkvarr);
		$this->assign('list',$list);
		return $this->fetch();
	}
	
	/**
	 * 批发价格
	 */
	public function mspupdrule($id){
		$obj = shopgoodsModel::getByid($id);
		$this->assign('obj',$obj);
		//规则
		$list = shopgoodsruleModel::getListByWhere(['gid'=>$id]);
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
							$rs = shopgoodsruleModel::upd_data(['id'=>$idsarr[$key]],$updlie);
						}else{
							$data[$key]['gid'] = $id;
							$data[$key]['input_uid'] = $this->uid;
							$data[$key]['input_time'] = time();
							$data[$key]['cate'] = isset($catearr[$key])?$catearr[$key]:0; //类型1价格2折扣
							$data[$key]['num'] = $val;
							$data[$key]['price'] = $jiaglie;
							$data[$key]['agio'] = $agiolie;
						}
					}
				}
				$rs = shopgoodsruleModel::add_all($data);
			}
			$this->success('编辑成功。',$this->mshangpinList);
		}
		return $this->fetch();
	}
	
	/**
	 * 删除一行规格
	 */
	public function delsnvid(){
    	$id = Request::instance()->post("id");
    	$rs = 0;
    	if($id>0){
    		$rs = shopgoodsnormsModel::delwhr(['id'=>$id]);
    	}
    	echo json_encode($rs);
	}
	
	/**
	 * 购买订单
	 */
	public function mspbuyorder($sts=''){
		//列表
		$where = array('is_lock'=>1,'uid'=>$this->uid);
		if(in_array($sts,['1','2','3','4','5','6','7'])){
			$where['status'] = $sts;
		}else{
			$sts = '';
		}
		$this->assign('sts', $sts);
		$list = shoporderModel::getList($where,[],'10');
		$this->assign('list', $list);
		return $this->fetch();
	}
	
	/**
	 * 购买订单详情
	 */
	public function mspbuyorderdetail($sn){
		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
		$this->assign('obj', $obj);
		//收货地址
		$dzobj = membershipaddressModel::getByid($obj['msarid']);
		$this->assign('dzobj', $dzobj);
		//规格商品列表
		$field = ['a.*,b.title,b.cover,b.sno'];
		$list = shoporderModel::getGoodsByWhere(['a.ordsn'=>$sn],$field);
		$this->assign('list', $list);
		//sgnid列表
		$idsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				if($val['sgnid']>0) $idsarr[] = $val['sgnid'];
			}
		}
		$idsarr = array_unique(array_filter($idsarr));
		$sgnlist = shopgoodsnormsModel::getListByWhere(['id'=>['in',$idsarr]]);
		$sgnlist = convert_arr_key($sgnlist,'id');
		$this->assign("sgnlist", $sgnlist);
		//规格
		$shopnorms = readCacheFile("shopnorms");
		$this->assign('shopnorms', $shopnorms);
		$snormsval = readCacheFile("shopnormsval");
		$this->assign('snormsval', $snormsval);
		return $this->fetch();
	}
	
	/**
	 * 卖出订单
	 */
	public function mspsellorder($sts='',$find=''){
		//列表
		$where = array('a.is_lock'=>1,'c.uid'=>$this->uid);
		if(in_array($sts,['1','2','3','4','5','6','7'])){
			$where['a.status'] = $sts;
		}else{
			$sts = '';
		}
		$this->assign('sts', $sts);
		$findObj = ['ordsn'=>'','sh_name'=>'','pay_status'=>'','pay_cate'=>'','is_fahuo'=>'','time1'=>'','time2'=>'',];
		if($find!=''){
			$fields = ['ordsn','sh_name','pay_status','pay_cate','is_fahuo','time1','time2',];
			$conAry = explode("_", $find);
			if(is_array($conAry)){
				foreach ($fields as $key => $val) {
					$findObj[$val]=isset($conAry[$key])?$conAry[$key]:'';
				}
			}
		}
		if($findObj['ordsn']!='') $where['a.ordsn'] = ['like', '%'.$findObj['ordsn'].'%'];
		if($findObj['sh_name']!='') $where['a.sh_name'] = ['like', '%'.$findObj['sh_name'].'%'];
		if($findObj['pay_status']>0) $where['a.pay_status'] = $findObj['pay_status'];
		if($findObj['pay_cate']>0) $where['a.pay_cate'] = $findObj['pay_cate'];
		if($findObj['is_fahuo']>0) $where['b.is_fahuo'] = $findObj['is_fahuo'];
		if($findObj['time1']!='') $where['a.input_time'] = ['>',strtotime($findObj['time1'])];
		if($findObj['time2']!='') $where['a.input_time'] = ['<',strtotime($findObj['time2'])];
		$this->assign('findObj', $findObj);
		$list = shoporderModel::getSellByWhere($where,['a.*'],'10');
		$this->assign('list', $list);
		//goods列表
		$snarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$snarr[] = $val['ordsn'];
			}
		}
		$snarr = array_unique(array_filter($snarr));
		$field = ['a.*,b.uid selluid,b.title,b.cover,b.sno'];
		$snlist = shoporderModel::getGoodsByWhere(['b.uid'=>$this->uid,'a.ordsn'=>['in',$snarr]],$field);
		//sgnid列表
		$sn_arr = [];
		$idsarr = [];
		if(!empty($snlist)){
			foreach ($snlist as $key=>$val){
				$sn_arr[$val['ordsn']][] = $val;
				if($val['sgnid']>0) $idsarr[] = $val['sgnid'];
			}
		}
		$idsarr = array_unique(array_filter($idsarr));
		$sgnlist = shopgoodsnormsModel::getListByWhere(['id'=>['in',$idsarr]]);
		$sgnlist = convert_arr_key($sgnlist,'id');
		$this->assign("sgnlist", $sgnlist);
		$this->assign("snlist", $sn_arr);
		//规格
		$shopnorms = readCacheFile("shopnorms");
		$this->assign('shopnorms', $shopnorms);
		$snormsval = readCacheFile("shopnormsval");
		$this->assign('snormsval', $snormsval);
		return $this->fetch();
	}
	
	/**
	 * 卖出订单详情
	 */
	public function mspsellorderdetail($sn){
		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
		$this->assign('obj', $obj);
		$user = memberModel::getByid($obj['uid']);
		$this->assign('user', $user);
		//收货地址
		$dzobj = membershipaddressModel::getByid($obj['msarid']);
		$this->assign('dzobj', $dzobj);
		$areaerji = readCacheFile("areatop");
		$this->assign('areaerji', $areaerji);
		//发货地址
		$fhobj = membershipaddressModel::getByid($obj['fh_id']);
		$this->assign('fhobj', $fhobj);
		//商品列表
		$field = ['a.*,b.title,b.cover,b.sno,b.weight'];
		$list = shoporderModel::getGoodsByWhere(['b.uid'=>$this->uid,'a.ordsn'=>$sn],$field);
		$this->assign('list', $list);
		//sgnid列表
		$idsarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				if($val['sgnid']>0) $idsarr[] = $val['sgnid'];
			}
		}
		$idsarr = array_unique(array_filter($idsarr));
		$sgnlist = shopgoodsnormsModel::getListByWhere(['id'=>['in',$idsarr]]);
		$sgnlist = convert_arr_key($sgnlist,'id');
		$this->assign("sgnlist", $sgnlist);
		//我的商品总金额
		$tot_price = 0;
		foreach ($list as $key=>$val){
			$tot_price += $val['amount'];
		}
		$this->assign('tot_price', $tot_price);
		//规格
		$shopnorms = readCacheFile("shopnorms");
		$this->assign('shopnorms', $shopnorms);
		$snormsval = readCacheFile("shopnormsval");
		$this->assign('snormsval', $snormsval);
		//地区
		$dizhilist = membershipaddressModel::getListByWhere(['is_lock'=>1,'uid'=>$this->uid],[],'is_default desc,id desc');
		$this->assign('dizhilist', $dizhilist);
		$areaerji = readCacheFile("areatop");
		$this->assign('areaerji', $areaerji);
		$area = readCacheFile("arealist");
		$this->assign('area', $area);
		return $this->fetch();
	}
	//更新订单状态
	public function setorderstatus($sn){
		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
		$user = memberModel::getByid($this->uid);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$status = $post['status'];
			$post_info = $post['info'];
			if($status>0){
				$info = '';
				$info.= date("Y-m-d H:i:s",time()).' 会员【'.$user['nick_name'].'】更新订单状态，';
				$info.= '由【'.$this->status_arr[$obj['status']].'】更新为【'.$this->status_arr[$status].'】。'.$post_info.'。';
				$info.= '\r\n'.$obj['info'];
				$rs = shoporderModel::upd_data(['ordsn'=>$sn],['status'=>$status,'info'=>$info]);
			}
			$this->success('状态更新成功',Url('home/Mshangpin/mspsellorderdetail','sn='.$sn));
		}
	}
	//更新订单状态--商家发货
	public function setorderfahuo($sn){
		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
		$user = memberModel::getByid($this->uid);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data = [];
			$data['status'] = 4; //已发货
			$data['fh_id'] = $post['fh_id'];
			$data['fh_logistics'] = $post['fh_logistics'];
			$data['fh_waybillno'] = $post['fh_waybillno'];
			$dzobj = membershipaddressModel::getByid($obj['fh_id']);
			$area = readCacheFile("arealist");
			$data['fh_name'] = $dzobj['truename'];
			$data['fh_mobile'] = $dzobj['mobile'];
			$data['fh_area'] = $area[$dzobj['province']]['name'].$area[$dzobj['city']]['name'].$area[$dzobj['county']]['name'].$dzobj['address'];
			$data['fh_time'] = time();
			if(!empty($data)){
				$info = '';
				$info.= date("Y-m-d H:i:s",time()).' 商家【'.$user['nick_name'].'】确认订单已发货，';
				$info.= '物流公司【'.$data['fh_logistics'].'】运单号【'.$data['fh_waybillno'].'】，';
				$info.= '发货地址【'.$data['fh_name'].'】【'.$data['fh_mobile'].'】【'.$data['fh_area'].'】。';
				$info.= '\r\n'.$obj['info'];
				$rs = shoporderModel::upd_data(['ordsn'=>$sn],$data);
			}
			$this->success('商家发货更新成功',Url('home/Mshangpin/mspsellorderdetail','sn='.$sn));
		}
	}
	//更新订单状态--确认收货
	public function setordershouhuo($sn){
		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
		$user = memberModel::getByid($this->uid);
		$data = [];
		$data['status'] = 5; //已收货
		$data['sh_time'] = time();
		if(!empty($data)){
			$info = '';
			$info.= date("Y-m-d H:i:s",time()).' 买家【'.$user['nick_name'].'】确认已收货。';
			$info.= '\r\n'.$obj['info'];
			$rs = shoporderModel::upd_data(['ordsn'=>$sn],$data);
		}
		$this->success('确认收货成功',Url('home/Mshangpin/mspbuyorderdetail','sn='.$sn));
	}
	//更新订单状态--商品评价
	public function setorderpingjia($sn){
		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
		$user = memberModel::getByid($this->uid);
		if(Request::instance()->isPost() && $this->uid>0){
			$post = input('post.');
			$data = [];
			$data['status'] = 7; //已评论
			$info = '';
			$info.= date("Y-m-d H:i:s",time()).' 买家【'.$user['nick_name'].'】对商品评价。';
			$info.= '\r\n'.$obj['info'];
			$rs = shoporderModel::upd_data(['ordsn'=>$sn],$data);
			$gid = $post['gid'];
			$pj_cont = $post['pj_cont'];
			$rs = shopgoodspingModel::add_data(['gid'=>$gid,'uid'=>$this->uid,'content'=>$pj_cont]);
			$this->success('商品评价成功',Url('home/Mshangpin/mspbuyorderdetail','sn='.$sn));
		}
	}
	//修改发票
	public function updorderinvoice($sn){
    	$invoice_name = Request::instance()->post("invoice_name");
    	$invoice_sn = Request::instance()->post("invoice_sn");
		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
		$user = memberModel::getByid($this->uid);
		$rs = 0;
		if($invoice_name!=''){
			$info = ''.$obj['info'];
			$info.= date("Y-m-d H:i:s",time()).' 会员【'.$user['nick_name'].'】修改发票信息，';
			$info.= '发票抬头【'.$obj['invoice_name'].'】修改为【'.$invoice_name.'】，';
			$info.= '纳税人识别号【'.$obj['invoice_sn'].'】修改为【'.$invoice_sn.'】。'.$post_info.'。\r\n';
			$data = ['invoice_name'=>$invoice_name,'invoice_sn'=>$invoice_sn,'info'=>$info];
			$rs = shoporderModel::upd_data(['ordsn'=>$sn],$data);
		}
		echo json_encode($rs);
	}
	//修改收货人
	public function updordership($sn){
    	$truename = Request::instance()->post("truename");
    	$mobile = Request::instance()->post("mobile");
    	$area = Request::instance()->post("info");
		$obj = shoporderModel::getOneByWhere(['ordsn'=>$sn]);
		$user = memberModel::getByid($this->uid);
		$rs = 0;
		if($truename!='' && $mobile!='' && $area!=''){
			$info = ''.$obj['info'];
			$info.= date("Y-m-d H:i:s",time()).' 会员【'.$user['nick_name'].'】修改收货人信息，';
			$info.= '收货人【'.$obj['sh_name'].'】修改为【'.$truename.'】，';
			$info.= '电话【'.$obj['sh_mobile'].'】修改为【'.$mobile.'】，';
			$info.= '地址【'.$obj['sh_area'].'】修改为【'.$area.'】。'.$post_info.'。\r\n';
			$data = ['sh_name'=>$truename,'sh_mobile'=>$mobile,'sh_area'=>$area,'info'=>$info];
			$rs = shoporderModel::upd_data(['ordsn'=>$sn],$data);
		}
		echo json_encode($rs);
	}
	
	//生成商品编号
	public function createmspno(){
		$mspno = 'S';
		$list = shopgoodsModel::getOneByWhere([],['MAX(id) maxid']);
		$maxid = 1;
		if(!empty($list) && $list['maxid']>0){
			$maxid = $maxid + intval($list['maxid']);
		}
		$maxid = strval($maxid); //int转string
		$mspno.= str_repeat( '0', 9-strlen($maxid) ).$maxid;
		echo json_encode($mspno);
	}
	//根据传入的规格值返回规格组合信息
	public function getnormsbysnvids($id){
		$obj = shopgoodsModel::getByid($id,['id','gno','stock_num','old_price','price']);
		$this->assign('obj',$obj);
    	$snid_str = Request::instance()->post("snid_str");
    	$snvid_str = Request::instance()->post("snvid_str");
    	$data = [];
    	if($snvid_str=='') echo json_encode($data);
		//商品规格
		$snlist = shopnormsModel::getListByWhere(array('is_lock'=>1,'id'=>['in',$snid_str]),[],'sort desc,id asc');
		//商品规格值
		$snvlist = shopnormsvalModel::getListByWhere(array('is_lock'=>1,'id'=>['in',$snvid_str]),[],'sort desc,id asc');
		$snkvarr = [];
		$ggone = 0;
		$ggtwo = 0;
		$ggthree = 0;
		$html = '<table class="listTable" border="0" cellpadding="0" cellspacing="0">
				  <thead>
					<tr class="thead">';
		foreach ($snlist as $key=>$val){
			foreach ($snvlist as $kkk=>$vvv){
				if($val['id'] == $vvv['pid']){
					$snkvarr[$val['id']][] = $vvv['id'];
				}
			}
			$html.= '<th>'.$val['name'].'</th>';
			if($ggone==0){
				$ggone = $val['id'];
			}elseif($ggtwo==0){
				$ggtwo = $val['id'];
			}elseif($ggthree==0){
				$ggthree = $val['id'];
			}
		}
		$html.= '<th><i class="important">*</i>商品货号</th>
					<th><i class="important">*</i>库存</th>
					<th>市场价</th>
					<th><i class="important">*</i>销售价</th>
					<th>删除</th>
					</tr>
				  </thead>
				  <tbody>';
		$data['snkvarr'] = $snkvarr;
		$snkv_list = [];
		if(isset($snkvarr[$ggone])){
			$snormsval = readCacheFile("shopnormsval");
			$this->assign('snormsval', $snormsval);
			$iii = 0;
			foreach ($snkvarr[$ggone] as $ka=>$va){
				if(isset($snkvarr[$ggtwo])){
					foreach ($snkvarr[$ggtwo] as $kb=>$vb){
						if(isset($snkvarr[$ggthree])){
							foreach ($snkvarr[$ggthree] as $kc=>$vc){
								$iii += 1;
				$html.= '<tr class="data" _i='.$iii.'>';
				$three_tot_num = 0;
				if(count($snkvarr[$ggtwo])>1){
					if(count($snkvarr[$ggthree])>1){
						$one_son_num = 0;
						for($uuu=1;$uuu<=count($snkvarr[$ggtwo]);$uuu++){
							$one_son_num+= count($snkvarr[$ggthree]);
						}
						if($iii%$one_son_num==1){
							$html.= '<td class="spec-value" val_id="'.$va.'" rowspan="'.$one_son_num.'">'.$snormsval[$va]['name'].'</td>';
						}
						$two_son_num = count($snkvarr[$ggthree]);
						if($iii%$two_son_num==1){
							$html.= '<td class="spec-value" val_id="'.$vb.'" rowspan="'.$two_son_num.'">'.$snormsval[$vb]['name'].'</td>';
						}
					}else{
						for($uuu=1;$uuu<=count($snkvarr[$ggone]);$uuu++){
							$three_tot_num+= count($snkvarr[$ggtwo]);
						}
						$two_son_num = count($snkvarr[$ggtwo]);
						if($iii%$two_son_num==1){
							$html.= '<td class="spec-value" val_id="'.$va.'" rowspan="'.$two_son_num.'">'.$snormsval[$va]['name'].'</td>';
						}
						$html.= '<td class="spec-value" val_id="'.$vb.'" rowspan="1">'.$snormsval[$vb]['name'].'</td>';
					}
				}else{
					$one_num = count($snkvarr[$ggone]);
					$three_num = count($snkvarr[$ggthree]);
					if(count($snkvarr[$ggthree])>1){
						$two_son_num = 0;
						for($uuu=1;$uuu<=count($snkvarr[$ggone]);$uuu++){
							$two_son_num+= count($snkvarr[$ggthree]);
						}
						if($iii%$three_num==1){
							$html.= '<td class="spec-value" val_id="'.$va.'" rowspan="'.$three_num.'">'.$snormsval[$va]['name'].'</td>';
						}
						if($iii%$two_son_num==1){
							$html.= '<td class="spec-value" val_id="'.$vb.'" rowspan="'.$two_son_num.'">'.$snormsval[$vb]['name'].'</td>';
						}
					}else{
						$html.= '<td class="spec-value" val_id="'.$va.'" rowspan="'.$one_num.'">'.$snormsval[$va]['name'].'</td>';
						if($iii%$one_num==1){
							$html.= '<td class="spec-value" val_id="'.$vb.'" rowspan="'.$one_num.'">'.$snormsval[$vb]['name'].'</td>';
						}
					}
				}
				$three_tot_num = $three_tot_num>0?$three_tot_num:1;
				if($iii%$three_tot_num==1){
					$html.= '<td class="spec-value" val_id="'.$vc.'" rowspan="'.$three_tot_num.'">'.$snormsval[$vc]['name'].'</td>';
				}else{
					$html.= '<td class="spec-value" val_id="'.$vc.'" rowspan="1">'.$snormsval[$vc]['name'].'</td>';
				}
		        $html.= '<td>
							<input type="hidden" name="snvstr[]" value="'.($va.','.$vb.','.$vc).'" />
							<input type="hidden" name="snidstr[]" value="'.($ggone.','.$ggtwo.','.$ggthree).'" />
							<input type="text" class="text text-goods-sn" name="gno[]" value="'.$obj['gno'].'-'.$iii.'" />
				        </td>
				        <td>
				            <input type="text" class="text text-stock" name="stock_num[]" value="'.$obj['stock_num'].'" />
				        </td>
				        <td>
				            <input type="text" class="text text-price" name="old_price[]" value="'.$obj['old_price'].'" />
				        </td>
				        <td>
				        	<input type="text" class="text text-price" name="price[]" value="'.$obj['price'].'" />
				        </td>
				        <td>
				            <a class="btn btn_del" href="javascript:;">删除</a>
				        </td>
				    </tr>';
							}
						}else{
							$iii += 1;
							$html.= '<tr class="data" _i='.$iii.'>';
							$one_son_num = count($snkvarr[$ggtwo]);
							if($one_son_num>1){
								if($iii%$one_son_num==1){
									$html.= '<td class="spec-value" val_id="'.$va.'" rowspan="'.$one_son_num.'">'.$snormsval[$va]['name'].'</td>';
								}
								$html.= '<td class="spec-value" val_id="'.$vb.'" rowspan="'.$one_num.'">'.$snormsval[$vb]['name'].'</td>';
							}else{
								$one_num = 1;
								$one_num = count($snkvarr[$ggone]);
								$html.= '<td class="spec-value" val_id="'.$va.'" rowspan="1">'.$snormsval[$va]['name'].'</td>';
								if($iii%$one_num==1){
									$html.= '<td class="spec-value" val_id="'.$vb.'" rowspan="'.$one_num.'">'.$snormsval[$vb]['name'].'</td>';
								}
							}
							$html.= '<td>
							<input type="hidden" name="snvstr[]" value="'.($va.','.$vb).'" />
							<input type="hidden" name="snidstr[]" value="'.($ggone.','.$ggtwo).'" />
							<input type="text" class="text text-goods-sn" name="gno[]" value="'.$obj['gno'].'-'.$iii.'" />
				        </td>
				        <td>
				            <input type="text" class="text text-stock" name="stock_num[]" value="'.$obj['stock_num'].'" />
				        </td>
				        <td>
				            <input type="text" class="text text-price" name="old_price[]" value="'.$obj['old_price'].'" />
				        </td>
				        <td>
				        	<input type="text" class="text text-price" name="price[]" value="'.$obj['price'].'" />
				        </td>
				        <td>
				            <a class="btn btn_del" href="javascript:;">删除</a>
				        </td>
				    </tr>';
						}
					}
				}else{
					$iii += 1;
					$html.= '<tr class="data" _i='.$iii.'>';
					$html.= '<td class="spec-value" val_id="'.$va.'" rowspan="'.$one_son_num.'">'.$snormsval[$va]['name'].'</td>';
					$html.= '<td>
							<input type="hidden" name="snvstr[]" value="'.$va.'" />
							<input type="hidden" name="snidstr[]" value="'.$ggone.'" />
							<input type="text" class="text text-goods-sn" name="gno[]" value="'.$obj['gno'].'-'.$iii.'" />
				        </td>
				        <td>
				            <input type="text" class="text text-stock" name="stock_num[]" value="'.$obj['stock_num'].'" />
				        </td>
				        <td>
				            <input type="text" class="text text-price" name="old_price[]" value="'.$obj['old_price'].'" />
				        </td>
				        <td>
				        	<input type="text" class="text text-price" name="price[]" value="'.$obj['price'].'" />
				        </td>
				        <td>
				            <a class="btn btn_del" href="javascript:;">删除</a>
				        </td>
				    </tr>';
				}
			}
		}
		$html.= '</tbody></table>';
		$data['html'] = $html;
		echo json_encode($data);
	}
	
}

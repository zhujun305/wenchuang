<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\shopgoodsModel;
use app\model\shopgoodscateModel;
use app\model\shopgoodsimagesModel;
use app\model\shopgoodsnormsModel;
use app\model\shopgoodsruleModel;

/**
 * 商品管理
 */
class Shopgoods extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->shopgoodsList = Session::get('shopgoodsList');
		$this->assign('shopgoodsList', $this->shopgoodsList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//商品分类
		$catelist = readCacheFile("shopgoodscate");
		$this->assign('catelist', $catelist);
		$status_arr = ['1'=>'<font>未上架</font>','2'=>'<font color="green">已上架</font>'];
		$this->assign('status_arr', $status_arr);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/shopgoods/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('shopgoodsList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'cate,title,is_chk,status,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$where = $findObj;
		if($findObj['cate']>0){
			$clist = shopgoodscateModel::getListByWhere(['is_lock'=>1,'pid'=>$findObj['cate']],[],'sort desc,id asc');
			$clist = convert_arr_key($clist,'id','id');
			if(!empty($clist)) $where['cate'] = implode(",",$clist);
		}
		$list = shopgoodsModel::getList($where);
		$this->assign("list", $list);
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
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id,$type=''){
		$this->assign("type", $type); //2认证审核
		$obj = shopgoodsModel::getByid($id);
		$catesan = shopgoodscateModel::getByid($obj['cate_san'],['id']);
		$obj['catesan'] = !empty($catesan)?$catesan['id']:0;
		$this->assign("obj", $obj);
		$member = memberModel::getByid($obj['uid'],['uid','nick_name']);
		$this->assign("member", $member);
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
		foreach ($gglist as $key=>$val){
			if($val['snvidone']>0) $snkvarr[$val['snidone']][$val['snvidone']] = $val['snvidone'];
			if($val['snvidtwo']>0) $snkvarr[$val['snidtwo']][$val['snvidtwo']] = $val['snvidtwo'];
			if($val['snvidthree']>0) $snkvarr[$val['snidthree']][$val['snvidthree']] = $val['snvidthree'];
		}
		$this->assign('snkvarr', $snkvarr);
		//批发价
		$pifalist = shopgoodsruleModel::getListByWhere(['gid'=>$id]);
		$this->assign('pifalist',$pifalist);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['is_chk'] = isset($post['is_chk'])?$post['is_chk']:1;
			$remark = isset($post['remark'])?$post['remark']:'';
			$new_remark = "管理员".$this->user['id']."【".$this->user['truename']."】".date("Y-m-d H:i:s")." ";
			if($data['is_chk'] == 2){
				$data['status'] = 2; //项目状态（2已上架）
				$new_remark.= "商品审核通过。";
			}
			if($data['is_chk'] == 3) $new_remark.= "商品审核不通过。";
			$data['remark'] = $obj['remark'].$new_remark.$remark."\r\n";
			$where = ['id'=>$id];
			$rs = shopgoodsModel::upd_data($where, $data);
			$this->success('审核提交成功。',$this->shopgoodsList);
		}
		return $this->fetch();
	}
	
	/**
	 * 批量审核
	 */
	public function ischkall($ids){
		$rs = shopgoodsModel::upd_list($ids, ['is_chk'=>2]);
		$this->redirect($this->shopgoodsList);
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = shopgoodsModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->shopgoodsList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = shopgoodsModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->shopgoodsList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = shopgoodsModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->shopgoodsList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = shopgoodsModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->shopgoodsList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = shopgoodsModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->shopgoodsList);
	}
	
}

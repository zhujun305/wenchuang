<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Cache;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use app\model\memberModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;
use lib\helper;

/**
 * 素材
 */
class Material extends Homebase
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
		//素材分类
		$this->catearr = readCacheFile("materialcate");
		$this->assign('catearr', $this->catearr);
	}
	
	/**
	 * 首页
	 */
	public function index($cate=''){
		$this->assign('cate', $cate);
		//专题top10条记录
		$topicwhr = ['is_lock'=>1,'is_recommend'=>2,'cover'=>['neq','']];
		$topiclist = materialtopicModel::getListByWhere($topicwhr,[],'sort desc,id desc','10');
		$this->assign('topiclist', $topiclist);
		//顶级分类
		$catelist = materialcateModel::getListByWhere(['is_lock'=>1,'pid'=>0],[],'sort desc,id asc');
		$this->assign('catelist', $catelist);
		//素材列表
		$pageID = 1;
    	$page = 4;
    	$limit = $page*($pageID-1).','.$page;
		$list = materialModel::getListByWhere(['is_chk'=>2],[],'id desc',$limit);
		$this->assign('list', $list);
		$this->assign('pageID', $pageID);
		
// 		print_r(Db::getlastsql());
        return $this->fetch('material');
	}
	
	/**
	 * ajax分页
	 */
	public function getajaxlist(){
    	$pageID = Request::instance()->post("page");
    	$pageID = $pageID>0?$pageID:1;
    	$page = 4;
    	$limit = $page*($pageID-1).','.$page;
		$list = materialModel::getListByWhere(['is_chk'=>2],[],'id desc',$limit);
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$url = Url('home/Material/matdetail','id='.$val['id']);
				$imgsrc = getImgURL($val['cover']);
				$cname = $this->catearr[$val['cate']]['cname'];
				$list[$key]['url'] = $url;
				$list[$key]['imgsrc'] = $imgsrc;
				$list[$key]['cname'] = $cname;
			}
		}
		echo json_encode($list);
	}
	
	/**
	 * 专题列表
	 */
	public function topiclist(){
		$where = ['is_lock'=>1];
		$list = materialtopicModel::getList($where,[],'15','is_recommend desc,sort desc,id desc');
		$this->assign("list", $list);
		return $this->fetch();
	}
	
	/**
	 * 列表页
	 */
	public function matlist($cate='',$topic='',$type=''){
		$this->assign('cate', $cate);
		$this->assign('topic', $topic);
		$this->assign('type', $type);
		$where = ['is_chk'=>2];
		$order = 'id desc';
		if($cate>0){
			$clist = materialcateModel::getListByWhere(['is_lock'=>1,'pid'=>$cate],[],'sort desc,id asc');
			$clist = convert_arr_key($clist,'id','id');
			if(!empty($clist)) $where['cate'] = implode(",",$clist);
		}
		if($topic>0) $where['topic'] = $topic;
		if($type==2) $order = 'id asc';
		if($type==3) $order = 'pv desc,id desc';
		if($type==4) $order = 'download desc,id desc';
		$list = materialModel::getList($where,[],'4',$order);
		$this->assign('list', $list);
		//顶级分类
		$catelist = materialcateModel::getListByWhere(['is_lock'=>1,'pid'=>0],[],'sort desc,id asc');
		$this->assign('catelist', $catelist);
		//专题top10条记录
		$topicwhr = ['is_lock'=>1,'is_recommend'=>2,'cover'=>['neq','']];
		$topiclist = materialtopicModel::getListByWhere($topicwhr,[],'sort desc,id desc','20');
		$this->assign('topiclist', $topiclist);
		return $this->fetch();
	}
	
	/**
	 * 素材详细
	 */
	public function matdetail($id){
		$detail = materialModel::getByid($id);
		$detail['erjimenu'] = $this->catearr[$detail['cate']]['cname'];
		$detail['yijimenu'] = '';
		$toppid = $this->catearr[$detail['cate']]['pid'];
		if($toppid>0) $detail['yijimenu'] = $this->catearr[$toppid]['cname'];
		//是否被收藏
		$detail['is_coll'] = 0;
		if($this->uid>0){
			$collobj = Db::table('member_coll_material')->where(['uid'=>$this->uid,'matid'=>$id])->find();
			if(!empty($collobj)){
				$detail['is_coll'] = 1;
			}
		}
		$this->assign("detail", $detail);
		//素材专题
		$topicarr = readCacheFile("topicarr");
		$this->assign('topicarr', $topicarr);
		//相关素材(类别，专题)
		$topicsql = '';
		if(!empty($detail['topic_str'])){
			$topicarr = explode(",",$detail['topic_str']);
			foreach ($topicarr as $key=>$val){
				if($val>0) $topicsql.= " or topic_str like '%,".$val.",%'";
			}
		}
		$bywhr = "is_del=1 AND is_lock=1 AND is_chk=2 AND ";
		$sql = "select * from material where ".$bywhr."id<>".$id." AND (cate=".$detail['cate'].$topicsql.")";
		$xglist = Db::query($sql);
		$this->assign('xglist', $xglist);
		//更新访问量
		materialModel::upd_data(array('id'=>$id),array('pv'=>($detail['pv']+1)));
		return $this->fetch();
	}
	
	/**
	 * 素材收藏
	 */
	public function coll_mat($uid,$matid){
		$data = [];
		$data['uid'] = $uid;
		$data['matid'] = $matid;
		$data['input_time'] = time();
		$id = Db::table('member_coll_material')->insertGetId($data);
		echo json_encode($id);
	}
	
	/**
	 * 取消素材收藏
	 */
	public function del_coll_mat($id){
		$rs = Db::table('member_coll_material')->delete($id);
		echo json_encode($rs);
	}
	
}

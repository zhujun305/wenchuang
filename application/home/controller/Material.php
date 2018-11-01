<?php
namespace app\home\controller;
use app\common\controller\Homebase;
use think\Cache;
use think\Cookie;
use think\Request;
use think\Session;
use think\Db;
use think\Url;
use lib\helper;
use app\model\memberModel;
use app\model\membercollModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;
use app\model\materialdownloadrsModel;

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
		//广告
		$ads = $this->getAds('materialbanner');
		$this->assign('ads', $ads);
		//专题top10条记录
		$topicwhr = ['is_lock'=>1,'is_recommend'=>2,'cover'=>['neq','']];
		$topiclist = materialtopicModel::getListByWhere($topicwhr,[],'sort desc,id desc','10');
		$this->assign('topiclist', $topiclist);
		//顶级分类
		$catelist = materialcateModel::getListByWhere(['is_lock'=>1,'pid'=>0],[],'sort desc,id asc');
		$this->assign('catelist', $catelist);
		//素材列表
		$pageID = 1;
    	$page = 16;
    	$limit = $page*($pageID-1).','.$page;
		$list = materialModel::getListByWhere(['is_chk'=>2],[],'id desc',$limit);
		$this->assign('list', $list);
		$this->assign('pageID', $pageID);
		
        return $this->fetch('material');
	}
	
	/**
	 * ajax分页
	 */
	public function getajaxlist(){
    	$pageID = Request::instance()->post("page");
    	$pageID = $pageID>0?$pageID:1;
    	$page = 16;
    	$limit = $page*($pageID-1).','.$page;
		$list = materialModel::getListByWhere(['is_chk'=>2],[],'id desc',$limit);
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$url = Url('home/Material/detail','id='.$val['id']);
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
	public function matlist($cate='',$topic='',$type='',$title=''){
		$this->assign('cate', $cate);
		$this->assign('topic', $topic);
		$this->assign('type', $type);
		$this->assign('title', $title);
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
		if($title!='') $where['title'] = $title;
		$list = materialModel::getList($where,[],'16',$order);
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
	public function detail($id){
		$obj = materialModel::getByid($id);
		$obj['erjimenu'] = $this->catearr[$obj['cate']]['cname'];
		$obj['yijimenu'] = '';
		$toppid = $this->catearr[$obj['cate']]['pid'];
		if($toppid>0) $obj['yijimenu'] = $this->catearr[$toppid]['cname'];
		//是否被收藏（cate=1会员2素材3众包4众筹5商品）
		$obj['collid'] = 0;
		if($this->uid>0){
			$collobj = membercollModel::getOneByWhere(['cate'=>2,'uid'=>$this->uid,'otherid'=>$id],[]);
			if(!empty($collobj)){
				$obj['collid'] = $collobj['id'];
			}
		}
		$this->assign("obj", $obj);
		//素材专题
		$topicarr = readCacheFile("topicarr");
		$this->assign('topicarr', $topicarr);
		//相关素材(类别，专题)
		$topicsql = '';
		if(!empty($obj['topic_str'])){
			$topicarr = explode(",",$obj['topic_str']);
			foreach ($topicarr as $key=>$val){
				if($val>0) $topicsql.= " or topic_str like '%,".$val.",%'";
			}
		}
		$bywhr = "is_del=1 AND is_lock=1 AND is_chk=2 AND ";
		$sql = "select * from material where ".$bywhr."id<>".$id." AND (cate=".$obj['cate'].$topicsql.")";
		$xglist = Db::query($sql);
		$this->assign('xglist', $xglist);
		//更新访问量
		materialModel::upd_data(array('id'=>$id),array('pv'=>($obj['pv']+1)));
		return $this->fetch();
	}
	
	/**
	 * 下载素材
	 */
	public function downloadsc(){
		$id = Request::instance()->post("id");
		$uid = Request::instance()->post("uid");
		$obj = materialModel::getByid($id);
		$member = memberModel::getByid($uid,['uid','balance']);
		$mdrobj = materialdownloadrsModel::getOneByWhere(['uid'=>$uid,'mid'=>$id,]);
		$data = ['rs'=>0,'msg'=>''];
		if(empty($mdrobj)){
			if($obj['score']>0){
				if($member['balance'] >= $obj['score']){
					$data['rs'] = 1;
				}else{
					$data = ['rs'=>0,'msg'=>'会员积分不足'];
				}
			}else{
				$data['rs'] = 1;
			}
		}else{
			$data['rs'] = 1;
		}
		echo json_encode($data);
	}
	
	/**
	 * 强制下载文件
	 * @param string $file 文件路径
	 */
	function force_download($id,$uid){
		$obj = materialModel::getByid($id);
		$member = memberModel::getByid($uid,['uid','balance']);
		$data = ['rs'=>0,];
		$mdrobj = materialdownloadrsModel::getOneByWhere(['uid'=>$uid,'mid'=>$id,]);
		if(empty($mdrobj)){
			if($obj['score']>0){
				if($member['balance'] >= $obj['score']){
					//下载素材支出积分
					$r_id = $this->addintegral($this->uid,2,'',1,$obj['score']);//会员，收支类型，收入方式，支出方式
					if($r_id>0){
						$data['rs'] = 1;
						//添加素材下载记录
						$d_mdr = ['uid'=>$uid,'mid'=>$id,'score'=>$obj['score'],];
						$idmdr = materialdownloadrsModel::add_data($d_mdr);
					}
				}
			}else{
				$data['rs'] = 1;
			}
			//更新下载量
			materialModel::upd_data(['id'=>$id],['download'=>($obj['download']+1)]);
		}else{
			$data['rs'] = 1;
		}
		if($data['rs'] == 1){
			$file = ROOT_PATH.$obj['filepath'];
			if ((isset($file)) && (file_exists($file))) {
				header("Content-length: ".filesize($file));
				header('Content-Type: application/octet-stream');
				$dlfname = time().'.'.pathinfo($file,PATHINFO_EXTENSION);
				header('Content-Disposition: attachment; filename="'.$dlfname.'"');
				readfile($file);
			} else {
				echo "No file selected";
			}
		}
	}
	
}

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
use app\model\adsModel;

/**
 * 广告管理
 */
class Ads extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->adsList = Session::get('adsList');
		$this->assign('adsList', $this->adsList);
		//广告位
		$adspos_arr = Config::get('config.adspos');
		$this->assign('adspos_arr', $adspos_arr);
	}

	public function index(){
		$this->redirect(Url('/Admin/ads/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('adsList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'type,title,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = adsModel::getList($findObj,[],'sort desc,id desc');
		$this->assign("list", $list);
		//管理员列表
		$adminuserlist = readCacheFile("adminuserlist");
		$this->assign('adminuserlist', $adminuserlist);
		return $this->fetch();
	}
	
	/**
	 * 新增
	 */
	public function create() {
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['code'] = $post['code'];
			$data['title'] = $post['title'];
			$data['type'] = $post['type'];
			$data['start_time'] = strtotime($post['start_time']);
			$data['end_time'] = strtotime($post['end_time']);
			$data['width'] = $post['width'];
			$data['height'] = $post['height'];
			$data['sort'] = $post['sort'];
			$data['picpath'] = '';
			if(!empty($post['picpath'])){
				$picpatharr = [];
				foreach ($post['picpath'] as $key=>$val){
					$picpatharr[$key] = basename($val);
				}
				$data['picpath'] = implode("|",$picpatharr);
			}
			$data['picname'] = '';
			if(!empty($post['picname'])){
				$picnamearr = [];
				foreach ($post['picname'] as $key=>$val){
					$picnamearr[$key] = ($val);
				}
				$data['picname'] = implode("|",$picnamearr);
			}
			$data['url'] = '';
			if(!empty($post['url'])){
				$urlarr = [];
				foreach ($post['url'] as $key=>$val){
					$urlarr[$key] = ($val);
				}
				$data['url'] = implode("|",$urlarr);
			}
			$id = adsModel::add_data($data);
			$this->success('添加成功。', $this->adsList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = adsModel::getByid($id);
		$obj['start_time'] = $obj['start_time']>0?date("Y-m-d",$obj['start_time']):'';
		$obj['end_time'] = $obj['end_time']>0?date("Y-m-d",$obj['end_time']):'';
		$this->assign("obj", $obj);
		$imglist = [];
		$picpatharr = [];
		$picnamearr = [];
		$urlarr = [];
		if(!empty($obj['picpath'])) $picpatharr = explode("|",$obj['picpath']);
		if(!empty($obj['picname'])) $picnamearr = explode("|",$obj['picname']);
		if(!empty($obj['url'])) $urlarr = explode("|",$obj['url']);
		if(!empty($picpatharr)){
			foreach ($picpatharr as $key=>$val){
				$imglist[$key]['pic'] = $val;
				$imglist[$key]['name'] = isset($picnamearr[$key])?$picnamearr[$key]:'';
				$imglist[$key]['url'] = isset($urlarr[$key])?$urlarr[$key]:'';
			}
		}
		$this->assign("imglist", $imglist);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['code'] = $post['code'];
			$data['title'] = $post['title'];
			$data['type'] = $post['type'];
			$data['start_time'] = strtotime($post['start_time']);
			$data['end_time'] = strtotime($post['end_time']);
			$data['width'] = $post['width'];
			$data['height'] = $post['height'];
			$data['sort'] = $post['sort'];
			$data['picpath'] = '';
			if(!empty($post['picpath'])){
				$picpatharr = [];
				foreach ($post['picpath'] as $key=>$val){
					$picpatharr[$key] = basename($val);
				}
				$data['picpath'] = implode("|",$picpatharr);
			}
			$data['picname'] = '';
			if(!empty($post['picname'])){
				$picnamearr = [];
				foreach ($post['picname'] as $key=>$val){
					$picnamearr[$key] = ($val);
				}
				$data['picname'] = implode("|",$picnamearr);
			}
			$data['url'] = '';
			if(!empty($post['url'])){
				$urlarr = [];
				foreach ($post['url'] as $key=>$val){
					$urlarr[$key] = ($val);
				}
				$data['url'] = implode("|",$urlarr);
			}
			$rs = adsModel::upd_data(['id'=>$id], $data);
			$this->success('编辑成功。', $this->adsList);
		}
		return $this->fetch();
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = adsModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->adsList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = adsModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->adsList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = adsModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->adsList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = adsModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->adsList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = adsModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->adsList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = adsModel::updsort($type, $id, $sort);
		echo $rs;
	}
	
}
<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberModel;
use app\model\helpModel;

/**
 * 单页管理
 */
class Help extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->helpList = Session::get('helpList');
		$this->assign('helpList', $this->helpList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		//单页类型
		$cate_arr = Config::get('config.help_cate');
		$this->assign('cate_arr', $cate_arr);
	}

	public function index(){
		$this->redirect(Url('/Admin/Help/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('helpList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'cate,title,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = helpModel::getList($findObj);
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
			$data['cate'] = $post['cate'];
			$data['title'] = $post['title'];
			$data['short_title'] = $post['short_title'];
			$data['summary'] = $post['summary'];
			$data['tz_url'] = $post['tz_url'];
			$data['sort'] = $post['sort'];
			$data['cover'] = basename($post['cover']);
			$content = $post['content'];
			$data['content'] = $content;
			$id = helpModel::add_data($data);
			$this->success('添加成功。', $this->helpList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = helpModel::getByid($id);
		$this->assign("obj", $obj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['cate'] = $post['cate'];
			$data['title'] = $post['title'];
			$data['short_title'] = $post['short_title'];
			$data['summary'] = $post['summary'];
			$data['tz_url'] = $post['tz_url'];
			$data['sort'] = $post['sort'];
			$data['cover'] = basename($post['cover']);
			$content = $post['content'];
			$data['content'] = $content;
			$rs = helpModel::upd_data(['id'=>$id], $data);
			$this->success('编辑成功。', $this->helpList);
		}
		return $this->fetch();
	}
	
	/**
	 * 锁定
	 */
	public function lock($id){
		$rs = helpModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->helpList);
	}
	
	/**
	 * 解锁
	 */
	public function unlock($id){
		$rs = helpModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->helpList);
	}
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = helpModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->helpList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = helpModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->helpList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
		$rs = helpModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->helpList);
	}
	
	/**
	 * 排序
	 */
	public function upd_sort($type='sort'){
		$id = Request::instance()->post("id");
		$sort = Request::instance()->post("sort");
		$rs = helpModel::updsort($type, $id, $sort);
		echo $rs;
	}
	
}
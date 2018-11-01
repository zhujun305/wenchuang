<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\memberModel;
use app\model\articleModel;

/**
 * 文章管理
 */
class Article extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
		$this->articleList = Session::get('articleList');
		$this->assign('articleList', $this->articleList);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
		$type_arr = ['1'=>'新闻','2'=>'公告','3'=>'成员馆动态',];
		$this->assign('type_arr', $type_arr);
	}

	public function index(){
		$this->redirect(Url('/Admin/Article/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('articleList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'type,title,is_chk,is_recommend,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$list = articleModel::getList($findObj);
		$this->assign("list", $list);
		//管理员列表
		$adminuserlist = readCacheFile("adminuserlist");
		$this->assign('adminuserlist', $adminuserlist);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				if($val['type']==3){
					$uidarr[] = $val['input_uid'];
				}
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$mlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$mlist = convert_arr_key($mlist,'uid');
		$this->assign("mlist", $mlist);
		return $this->fetch();
	}
	
	/**
	 * 新增
	 */
	public function create() {
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['type'] = $post['type'];
			$data['title'] = $post['title'];
			$data['author'] = $post['author'];
			$data['source'] = $post['source'];
			$data['keywords'] = $post['keywords'];
			$data['summary'] = $post['summary'];
			$data['cover'] = basename($post['cover']);
			$content = $post['content'];
			$data['content'] = $content;
			$id = articleModel::add_data($data);
			$this->success('添加成功。', $this->articleList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = articleModel::getByid($id);
		$this->assign("obj", $obj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['type'] = $post['type'];
			$data['title'] = $post['title'];
			$data['author'] = $post['author'];
			$data['source'] = $post['source'];
			$data['keywords'] = $post['keywords'];
			$data['summary'] = $post['summary'];
			$data['cover'] = basename($post['cover']);
			$content = $post['content'];
			$data['content'] = $content;
			$rs = articleModel::upd_data(['id'=>$id], $data);
			$this->success('编辑成功。', $this->articleList);
		}
		return $this->fetch();
	}
	
	/**
	 * 审核
	 */
	public function chk($id){
		$rs = articleModel::upd_list($id, ['is_chk'=>2,'chk_time'=>time(),'chk_uid'=>Session::get('user_id')]);
		//8. 成员馆发布动态或新闻奖励1积分。
		$obj = articleModel::getByid($id);
		if($obj['input_uid']>0 && $obj['type']==3){
			$this->addintegral($obj['input_uid'],1,6);//会员，收支类型，收入方式，支出方式
		}
		$this->redirect($this->articleList);
	}
	
	/**
	 * 批量审核
	 */
	public function ischkall($ids){
		//8. 成员馆发布动态或新闻奖励1积分。
		$ary = explode ( '-', $ids );
		$list = articleModel::getListByWhere(['id'=>['in',$ary]]);
		foreach ($list as $key=>$val){
			if($val['is_chk']==1 && $val['input_uid']>0 && $val['type']==3){
				$this->addintegral($val['input_uid'],1,6);//会员，收支类型，收入方式，支出方式
			}
		}
		$rs = articleModel::upd_list($ids, ['is_chk'=>2,'chk_time'=>time(),'chk_uid'=>Session::get('user_id')]);
		$this->redirect($this->articleList);
	}
	
	/**
	 * 推荐
	 */
	public function recommend($id,$val){
		if($val==2){
			$val = 2;
		}else{
			$val = 1;
		}
		$rs = articleModel::upd_list($id, ['is_recommend'=>$val]);
		$this->redirect($this->articleList);
	}
	
	/**
	 * 锁定
	 */
	public function lock($id){
		$rs = articleModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->articleList);
	}
	
	/**
	 * 解锁
	 */
	public function unlock($id){
		$rs = articleModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->articleList);
	}
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = articleModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->articleList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = articleModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->articleList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
		$rs = articleModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->articleList);
	}
	
}
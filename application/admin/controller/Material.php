<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Db;
use think\Request;
use think\Session;
use think\Url;
use app\model\memberModel;
use app\model\materialModel;
use app\model\materialcateModel;
use app\model\materialtopicModel;

/**
 * 素材管理
 */
class Material extends Adminbase
{ 
	/**
	 * 初始化方法
	 */
	public function _initialize(){
		parent::_initialize();
		$this->materialList = Session::get('materialList');
		$this->assign('materialList', $this->materialList);
		//素材分类
		$catelist = readCacheFile("materialcate");
		$this->assign('catelist', $catelist);
		//素材专题
		$topicarr = readCacheFile("topicarr");
		$this->assign('topicarr', $topicarr);
		//是否审核
		$chk_arr = Config::get('config.is_chk');
		$this->assign('chk_arr', $chk_arr);
	}
	
	public function index(){
		$this->redirect(Url('/Admin/material/browse'));
	}
	
	/**
	 * 列表
	 */
	public function browse($find=''){
		session('materialList', Request::instance()->url()); //当前页url
		$findObj = [];
		$fields = 'cate,title,is_chk,is_lock';
		$this->getFindObj($findObj, $find, $fields);
		$this->assign("findObj", $findObj);
		$where = $findObj;
		if($findObj['cate']>0){
			$clist = materialcateModel::getListByWhere(['is_lock'=>1,'pid'=>$findObj['cate']],[],'sort desc,id asc');
			$clist = convert_arr_key($clist,'id','id');
			if(!empty($clist)) $where['cate'] = implode(",",$clist);
		}
		$list = materialModel::getList($where);
		$this->assign("list", $list);
		//会员uid列表
		$uidarr = [];
		if(!empty($list)){
			foreach ($list as $key=>$val){
				$uidarr[] = $val['uid'];
			}
		}
		$uidarr = array_unique(array_filter($uidarr));
		$memberlist = memberModel::getListByWhere(['uid'=>['in',$uidarr]],['uid','nick_name']);
		$memberlist = convert_arr_key($memberlist,'uid');
		$this->assign("memberlist", $memberlist);
		return $this->fetch();
	}
	
	/**
	 * 查看详细
	 */
	public function detail($id,$type=''){
		$this->assign("type", $type); //2认证审核
		$obj = materialModel::getByid($id);
		$this->assign("obj", $obj);
		$member = memberModel::getByid($obj['uid'],['uid','nick_name']);
		$this->assign("member", $member);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['is_chk'] = isset($post['is_chk'])?$post['is_chk']:1;
			$remark = isset($post['remark'])?$post['remark']:'';
			$new_remark = "管理员".$this->user['id']."【".$this->user['truename']."】".date("Y-m-d H:i:s")." ";
			if($data['is_chk'] == 2) $new_remark.= "素材审核通过。";
			if($data['is_chk'] == 3) $new_remark.= "素材审核不通过。";
			$data['remark'] = $obj['remark'].$new_remark.$remark."\r\n";
			$where = ['id'=>$id];
			$rs = materialModel::upd_data($where, $data);
			//5. 素材上传成功奖励2积分；
			if($data['is_chk']==2 && $obj['uid']>0){
				$this->addintegral($obj['uid'],1,3);//会员，收支类型，收入方式，支出方式
			}
			$this->success('审核提交成功。',$this->materialList);
		}
		return $this->fetch();
	}
	
	/**
	 * 编辑
	 */
	public function edit($id){
		$obj = materialModel::getByid($id);
		$this->assign("obj", $obj);
		//素材分类
		$cateobj = materialcateModel::getOneByWhere(['id'=>$obj['cate']]);
		$catetop = materialcateModel::getOneByWhere(['id'=>$cateobj['pid']]);
		$this->assign("catetop", $catetop);
		//素材专题
		$topiclist = materialtopicModel::getListByWhere(array('is_lock'=>1),array('id','title'));
		$topiclist = convert_arr_key($topiclist,'id');
		$topic_str = array();
		$topic_arr = array();
		if(!empty($obj['topic_str'])){
			$topic_arr = explode(",",$obj['topic_str']);
			foreach ($topic_arr as $key=>$val){
				$topic_str[$val] = $topiclist[$val]['title'];
			}
		}
		$this->assign('topic_str', implode(",",$topic_str));
		$this->assign('topic_arr', $topic_arr);
		$this->assign('topiclist', $topiclist);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['score'] = $post['score'];
			$data['metadata'] = $post['metadata'];
			$data['ext'] = $post['ext'];
			$data['title'] = $post['title'];
			$data['cate'] = $post['cate'];
			$data['topic_str'] = $post['topic_str'];
			$data['supply_tit'] = $post['supply_tit'];
			$data['author'] = $post['author'];
			$data['keywords'] = $post['keywords'];
			$data['themewords'] = $post['themewords'];
			$data['version'] = $post['version'];
			$data['originaltime'] = $post['originaltime'];
			$data['makingtime'] = $post['makingtime'];
			$data['roomarea'] = $post['roomarea'];
			$data['desc'] = $post['desc'];
			$data['cover'] = basename($post['cover']);
			$rs = materialModel::upd_data(['id'=>$id], $data);
			$this->success('编辑成功。', $this->materialList);
		}
		return $this->fetch();
	}
	
	/**
	 * 批量审核
	 */
	public function ischkall($ids){
		//5. 素材上传成功奖励2积分；
		$ary = explode ( '-', $ids );
		$list = materialModel::getListByWhere(['id'=>['in',$ary]]);
		foreach ($list as $key=>$val){
			if($val['is_chk']==1 && $val['uid']>0){
				$this->addintegral($val['uid'],1,3);//会员，收支类型，收入方式，支出方式
			}
		}
		$rs = materialModel::upd_list($ids, ['is_chk'=>2]);
		$this->redirect($this->materialList);
	}
	
    /**
     * 锁定
     */
    public function lock($id){
    	$rs = materialModel::upd_list($id, ['is_lock'=>2]);
		$this->redirect($this->materialList);
    }
    
    /**
     * 解锁
     */
    public function unlock($id){
    	$rs = materialModel::upd_list($id, ['is_lock'=>1]);
		$this->redirect($this->materialList);
    }
	
    /**
     * 批量锁定
     */
    public function lockall($ids){
    	$rs = materialModel::upd_list($ids, ['is_lock'=>2]);
		$this->redirect($this->materialList);
    }
    
    /**
     * 批量解锁
     */
    public function unlockall($ids){
    	$rs = materialModel::upd_list($ids, ['is_lock'=>1]);
		$this->redirect($this->materialList);
    }
	
	/**
	 * 删除
	 */
	public function del($id) {
    	$rs = materialModel::upd_list($id, ['is_del'=>2]);
		$this->redirect($this->materialList);
	}
	
}

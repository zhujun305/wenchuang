<?php 
namespace app\admin\controller;
use app\common\controller\Adminbase;
use think\Config;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use app\model\syssettingsModel;

/**
 * 系统设置
 */
class Syssettings extends Adminbase
{
	public function _initialize(){
		parent::_initialize();
	}

	public function index(){
		$this->redirect(Url('/Admin/Syssettings/browse'));
	}
	
	/**
	 * 编辑
	 */
	public function edit(){
		$obj = syssettingsModel::getObjone();
		$this->assign("obj", $obj);
		if(Request::instance()->isPost()){
			$post = input('post.');
			$data['platname'] = $post['platname'];
			$data['mobile'] = $post['mobile'];
			$data['phone'] = $post['phone'];
			$data['kefu_phone'] = $post['kefu_phone'];
			$data['address'] = $post['address'];
			$data['keeprecord'] = $post['keeprecord'];
			$data['iswater'] = $post['iswater'];
			$data['watertext'] = $post['watertext'];
			if($obj['id']>0){
				$rs = syssettingsModel::upd_data(['id'=>$obj['id']], $data);
			}else{
				$rs = syssettingsModel::add_data($data);
			}
			$this->cacheSyssettings(); //刷新
			$this->success('编辑成功。', Url('/Admin/Syssettings/edit'));
		}
		return $this->fetch('index/syssettings');
	}
	
}
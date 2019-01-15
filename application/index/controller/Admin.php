<?php
namespace app\index\controller;
use think\Request;
use app\index\model\Admin as AdminModel;
use think\Session;
use think\Validate;

class Admin extends Base
{
	/**
	 * 管理员登录
	 */
	public function login()
	{
		$request = Request::instance();
		$post = $request->only(['account','password']);
		$admin = new AdminModel;
		$acount = $admin->where('tel',$post['account'])->field('tel,password,admin_id')->find();
		if(!$acount || $acount['password']!=md5($post['password'])){
			return json(['success'=>false,'code'=>'007','message'=>'账号或密码不正确']);
		}
		if(self::writeSession($acount['admin_id'],$acount)){
			return json(['success'=>true,'code'=>'000','message'=>'登陆成功','data'=>['admin_id'=>$acount['admin_id'],'admin_tel'=>$acount['tel']]]);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>"登陆出错，请稍后重试"]);
		}
	}

	/**
	 * 添加管理员
	 */
	public function adminRegster()
	{
		// $request = Request::instance();
	}

	/**
	 * 写入session
	 */
	public static function writeSession($adminId, $data)
	{
		Session::set('admin_id',$adminId);
		foreach ($data as $key => $value) {
			Session::set($key,$value);
		}
		return true;
	}
}
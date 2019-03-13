<?php
namespace app\admin\controller;

use \think\Request; 
use app\admin\model\AdminModel;
use \think\Session;
/**
* 测试
*/
class Login
{
	/**
	 * 管理员登录
	 */
	public function login()
	{
		// $request = Request::instance();
		// $post = $request->only(['account','password']);
		$post = input('get.');
		// print_r($post);
		$acount = db('admin')->where('tel',$post['account'])->field('tel,password,admin_id,role_id')->find();
//		$acount = db('admin')->where('tel',$post['account'])->field('tel,password,admin_id,role_id')->find();
		if(!$acount || $acount['password']!=md5($post['password'])){
			sendJson(-1,'账号或密码不正确');
			//return json(['success'=>false,'code'=>'007','message'=>'账号或密码不正确']);
		}
		    //print_r($acount);
		    session('admin_id',$acount['admin_id']);
            session('admin_tel',$acount['tel']);
            session('role_id',$acount['role_id']);
			sendJson(1,'登陆成功',['admin_id'=>$acount['admin_id'],'admin_tel'=>$acount['tel']]);
//			sendJson(1,'登陆成功',['admin_id'=>$acount['admin_id'],'admin_tel'=>$acount['tel'],'role_id'=>$acount['role_id']]);
			// return json(['success'=>true,'code'=>'000','message'=>'登陆成功','data'=>]]);
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
		    echo $key;
		    echo $value;
			Session::set($key,$value);
		}
		return true;
	}
}
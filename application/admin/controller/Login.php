<?php
namespace app\admin\controller;

use app\admin\model\CourseEditModel;
use think\Env;
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
	public function login(Request $request)
	{





		$tel=$request->post('account',null);
		$password=$request->post('password',null);

		if(empty($tel)||empty($password)){
            return json(['code'=>'002','message'=>'缺少参数！','data'=>array()]);
        }

         $where=array();
        $where['tel']=$tel;
        $where['examine']=1;//审核必须通过
        $where['flag']=1;

		$acount = db('admin')->where($where)->field('tel,password,admin_id,name,role_id,secret,flag,examine')->find();


		   if($acount&&$acount['examine']!=1){//待审核的状态
               return json(['code'=>'006','message'=>'账号待审核通过！','data'=>array()]);
           }

		if(!$acount || $acount['password']!=md5($password.$acount['secret'])||$acount['flag']!=1){
            return json(['code'=>'006','message'=>'账号或密码不正确！','data'=>array()]);
		}



        //查询所有权限节点
        $kp = db('role')->where(['id'=>$acount['role_id']])->value('rule');


        if(empty($kp)){
            return json(['code'=>'006','message'=>'账号异常！','data'=>array()]);
        }else if('*' == $kp){
            session('admin_id',$acount['admin_id']);
            session('admin_tel',$acount['tel']);
            session('role_id',$acount['role_id']);
            session('rule_shang', Env::get('rule_super.rule_shang'));


            return json(['code'=>'000','message'=>'欢迎超级管理员！','data'=>array('name'=>$acount['name'],'role'=>'*')]);

        }else{

            session('admin_id',$acount['admin_id']);
            session('admin_tel',$acount['tel']);
            session('role_id',$acount['role_id']);

            $where = 'id in(' . $kp . ')';
            $res = db('node')->where($where)->select();

            //普通管理员获取所有权限列表
            foreach($res as $key=>$vo){
                if('#' != $vo['action_name']){
                    $res[$key]['judge'] = $vo['control_name'] . '/' . $vo['action_name'];
//                    $nodes[] = $vo['control_name'] . '/' . $vo['action_name'];
                }
            }

             AdminModel::where($where)->update(array('login_time'=>time(),'ip'=>$request->ip()));

            return json(['code'=>'000','message'=>'成功!','data'=>array('name'=>$acount['name'],'role'=>$res)]);
//                      return json(['code'=>'000','message'=>'欢迎超级管理员！','data'=>array('admin_id'=>$acount['admin_id'],'admin_tel'=>$acount['tel'],'role'=>$res)]);
        }





    }


	/**
	 * 退出
	 */
	public function adminOut()
	{
        session_start();
        session_destroy();
        return json(['code'=>'000','message'=>'成功!','data'=>array()]);
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
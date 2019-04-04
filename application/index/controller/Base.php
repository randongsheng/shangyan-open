<?php
namespace app\index\controller;
use think\Controller;

class Base extends Controller
{
	/**
	 * 常规返回信息方法
	 */
	protected function message($data, $httpCode=200)
	{
		// 回溯查询
		if(debug_backtrace()[2]['class']=='ReflectionMethod'){
			return json($data,$httpCode);
		}else{
			return $data;
		}
	}

	public function _initialize()
    {
        //权限管理,做好添加再打开
        // 检查缓存
        $this->cacheCheck();

        // 检测权限
        $control = lcfirst(request()->controller());
        $action = lcfirst(request()->action());
        file_put_contents(ROOT_PATH."application/auth.txt",$control."|".$action,FILE_APPEND);
        if(empty(authCheck($control . '/' . $action))){
            sendJson(-1,'没有权限');
        }
    }
    private function cacheCheck()
    {
        $action = session('role_id');
       //var_dump(is_null($action) || empty($action));
        $result = [];

        if(!is_null($action) || !empty($action)){

            // 获取该管理员的角色信息
//            $role_id = session('role_id');
            $info = db('role')->where(['id'=>$action])->value('rule');
            //echo db('role')->getLastSql();
//            var_dump($info) ;
//            die;
            // 超级管理员权限是 *
            if(empty($info)){
                $result['action'] = '';
                return $result;
            }else if('*' == $info){
                $where = '';
            }else{
                $where = 'id in(' . $info . ')';
            }

            // 查询权限节点
            //获取角色所有允许访问的操作名称
            $res = db('node')->field('control_name,action_name')->where($where)->select();
            foreach($res as $key=>$vo){
                if('#' != $vo['action_name']){
                    $result['action'][] = $vo['control_name'] . '/' . $vo['action_name'];
                }
            }
            session('rule', $result['action']);
        }
    }
}
<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class common extends Controller
{
	public function _initialize()
    {
        //权限管理,做好添加再打开
//        // 检查缓存
//        $this->cacheCheck();
//
//        // 检测权限
//        $control = lcfirst(request()->controller());
//        $action = lcfirst(request()->action());
//
//        if(empty(authCheck($control . '/' . $action))){
//            sendJson(-1,'没有权限');
//        }else{
//            echo '有权限';
//        }
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
	//倾听/咨询订单状态判断
	public function checkOrder($orderid){
		$order = db('order')->where(['orderid'=>$orderid])->find();
		if ($order['status'] != 0) {
			return 0;
		}
		$now = time();
		//查询订单是否过期
		if ($order['createtime']+15*60 < $now) {
			# 过期更改订单状态
			db('order')->where(['orderid'=>$orderid])->update(['status'=>3]);
			return 0;
		}
		//计算剩余时间
		return $order['createtime']+15*60-$now;
	}
		//获取话题字符串
	public function getTopicStr($topicStr){
			$topic = trim($topicStr,',');
			if (is_numeric($topic)) {
				$str = db('topic')->where(['id'=>$topic])->value('title');
				return $str;
			}
    		$arr = explode(',', $topic);
    		$topicarr = [];
    		foreach ($arr as $v) {
    			# code...
    			$topicarr[] = db('topic')->where(['id'=>$v])->value('title');
    		}
    		$str = implode("#", $topicarr);
    		return $str;
	}
	
}
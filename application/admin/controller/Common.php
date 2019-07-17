<?php
namespace app\admin\controller;
use app\admin\model\OperationLogModel;
use think\Db;
use think\Controller;
use think\Env;
class common extends Controller
{
	public function _initialize()
    {
        //权限管理,做好添加再打开
        // 检查缓存
      $super_shang=$this->cacheCheck();

        // 检测权限
        $control = lcfirst(request()->controller());
        $action = lcfirst(request()->action());


            //如果是超级管理员  则通过

            if($super_shang!=Env::get('rule_super.rule_shang')){


           if(empty(authCheck($control . '/' . $action))){
            sendJson(-1,'没有权限');
        }
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


//            var_dump($info) ;
//            die;
            // 超级管理员权限是 *
            if(empty($info)){
                $result['action'] = '';
                return $result;
            }else if('*' == $info){

                 //判断是否修改env文件,如此事发生则 为总管理员信息篡改,紧急形态
                if(session('rule_shang')==Env::get('rule_super.rule_shang')) {

                    session('rule_shang', Env::get('rule_super.rule_shang'));
                    return Env::get('rule_super.rule_shang');
                }
                else{
                    //警示:如果返回信息!!!得到则为篡改紧急状态
                    sendJson('!!!','你的非法攻击以及篡改以及被我方追踪,将受到法律严惩！');

                }

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
            session('rule_shang', $result['action']);
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

	//添加操作日志

    public function add_log($tid,$con,$aid)
    {


       OperationLogModel::insert(array('target_id'=>$tid,'content'=>$con,'ad_id'=>$aid,'create_at'=>time()));

    }
	
}
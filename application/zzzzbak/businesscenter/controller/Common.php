<?php
namespace app\businesscenter\controller;
use think\Db;
class common
{
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
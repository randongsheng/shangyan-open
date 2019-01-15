<?php
namespace app\index\controller;
use think\Request;
use think\Session;
use app\index\service\OrderTest;
use app\index\model\Order as OrderModel;
use app\index\service\OrderConsult;

class Order extends Base
{
	/**
	 * 某用户查询测试订单
	 */
	public function userOrderTest()
	{
		$request = Request::instance();
		$post = $request->only(['order_id','order_status','order_time','label_id','user_id']);
		$orderWhere = [];
		$orderTest = new OrderTest;

		if(!empty($post['order_id'])){
			$orderWhere['o.orderid'] = $post['order_id'];
		}

		if(!empty($post['order_status'])){
			$orderWhere['o.status'] = $post['order_status'];
		}

		if(!empty($post['order_time'])){
			$startTime = strtotime(date('Y-m-d 00:00:00',strtotime($post['order_time'])));
			$endTime = strtotime(date('Y-m-d 23:59:59',strtotime($post['order_time'])));
			$orderWhere['o.createtime'] = ['between',[$startTime,$endTime]];
		}
		// 查找标签ID（表结构被迫）
		if(!empty($post['label_id'])){
			$orderWhere['testm.topic'] = ['like','%,'.trim($post['label_id']).',%'];
		}

		if(!empty($post['user_id'])){
			$orderWhere['o.uid'] = $post['user_id'];
		}

		$orders = $orderTest->alias('o')
        ->join('sy_examtitle testm','testm.id=o.titleid','LEFT')
        ->where($orderWhere)
        ->order('o.createtime','desc')
        ->field(['o.orderid','o.money','o.createtime','testm.topic','o.status','testm.id as test_id','testm.title'])
        ->group('o.id')
        ->paginate(15);

		if($orders){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$orders]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'该用户还没有做过测试']);
		}
	}

	/**
	 * 某用户倾诉订单
	 */
	public function userListenOrder()
	{
		$request = Request::instance();
		$post = $request->only(['order_id','order_status','order_time','label_id','user_id']);
		$orderWhere = [];
		if(!empty($post['order_id'])){
			$orderWhere['o.orderid'] = $post['order_id'];
		}
		if(!empty($post['order_status'])){
			$orderWhere['o.status'] = $post['order_status'];
		}
		if(!empty($post['order_time'])){
			$startTime = strtotime(date('Y-m-d 00:00:00',strtotime($post['order_time'])));
			$endTime = strtotime(date('Y-m-d 23:59:59',strtotime($post['order_time'])));
			$orderWhere['o.createtime'] = ['between',[$startTime,$endTime]];
		}
		// 查找标签ID（表结构被迫）
		if(!empty($post['label_id'])){
			$orderWhere['testm.topic'] = ['like','%,'.trim($post['label_id']).',%'];
		}

		if(!empty($post['user_id'])){
			$orderWhere['o.uid'] = $post['user_id'];
		}
		$orderWhere['o.type'] = 1;
		$order = new OrderModel;
		$orders = $order->alias('o')
		->join('sy_listenrecord l','l.orderid=o.orderid','LEFT')
		->where($orderWhere)
		->field(['o.orderid','o.createtime','o.serverpersonid','o.ordermoney','o.topic','o.alltime','o.sytime','o.status'])
		->group('o.id')
		->order('o.createtime','desc')
		->paginate(15);
		if($orders){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$orders]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'该用户还没有倾诉过']);
		}
	}

	/**
	 * 某用户咨询订单
	 */
	public function userConsultOrder()
	{
		$request = Request::instance();
		$post = $request->only(['order_id','order_status','order_time','label_id','user_id']);
		$orderWhere = [];
		if(!empty($post['order_id'])){
			$orderWhere['o.orderid'] = $post['order_id'];
		}
		if(!empty($post['order_status'])){
			$orderWhere['o.status'] = $post['order_status'];
		}
		if(!empty($post['order_time'])){
			$startTime = strtotime(date('Y-m-d 00:00:00',strtotime($post['order_time'])));
			$endTime = strtotime(date('Y-m-d 23:59:59',strtotime($post['order_time'])));
			$orderWhere['o.createtime'] = ['between',[$startTime,$endTime]];
		}
		// 查找标签ID（表结构被迫）
		if(!empty($post['label_id'])){
			$orderWhere['testm.topic'] = ['like','%,'.trim($post['label_id']).',%'];
		}
		
		if(!empty($post['user_id'])){
			$orderWhere['o.uid'] = $post['user_id'];
		}
		$orderWhere['o.type'] = 2;
		$order = new OrderModel;
		$consult = new OrderConsult;
		$consultCount = $consult->alias('c')->where('c.orderid=o.orderid')->field('count(*)')->buildSql();
		$consultSyCount = $consult->alias('c')->where('c.orderid=o.orderid')->where('status','between',[1,2])->field('count(*)')->buildSql();
		$orders = $order->alias('o')
		->join('sy_ordermore om','o.orderid=om.orderid','LEFT')
		->where($orderWhere)
		->field(['o.orderid','o.createtime','o.serverpersonid','o.clinicid','o.ordermoney','om.mode','o.status','o.topic',$consultCount.' as allonce',$consultSyCount.' as syonce'])
		->group('o.id')
		->order('o.createtime','desc')
		->paginate(15);
		if($orders){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$orders]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'该用户还没有咨询过']);
		}
	}

}
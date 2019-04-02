<?php
namespace app\index\controller;
use think\Request;
use think\Session;
use think\Db;
use think\Validate;
use app\index\model\Topic;
use app\index\model\Order;
use app\index\service\TestTitle;
use app\index\service\TopicRelevance;
use app\index\model\Article;
use app\index\model\User;

class Label extends Base
{
	/**
	 * 标签列表
	 */
	public function labels()
	{
		$request = Request::instance();
		$post = $request->only(['label_id','update_at','label_name','label_status']);
		$topic = new Topic;
		$order = new Order;
		$test = new TestTitle;
		$article = new Article;
		$user = new User;
		$topicWhere = [];
		if(!empty($post['label_id'])){
			$topicWhere['t.label_id'] = $post['label_id'];
		}
		if(!empty($post['update_at'])){
			$startTime = strtotime(date('Y-m-d 00:00:00',strtotime($post['update_at'])));
			$endTime = strtotime(date('Y-m-d 23:59:59',strtotime($post['update_at'])));
			$topicWhere['t.update_at'] = ['between',[$startTime,$endTime]];
		}
		if(!empty($post['label_name'])){
			// $topicWhere['t.title'] = ['like',];
		}
		// 关联倾听订单数
		$listenCount = $order->alias('o')->where('o.topic like concat("%,",t.id,",%")')->where(['o.type'=>1])->field('count(*)')->buildSql();
		// 关联咨询订单数
		$consultCount = $order->alias('o')->where('o.topic like concat("%,",t.id,",%")')->where(['o.type'=>2])->field('count(*)')->buildSql();
		// 测试订单数
		$testCount = $test->alias('te')->where('te.topic like concat("%,",t.id,",%")')->field('count(*)')->buildSql();
		// 文章关联书
		$articleCount = $article->alias('a')->where('a.keywords like concat("%,",t.id,",%")')->field('count(*)')->buildSql();
		// 关联用户数
		$userCount = $user->alias('u')->where('u.topic like concat("%,",t.id,",%")')->field('count(*)')->buildSql();
		$labels = $topic->alias('t')
		->where($topicWhere)
		->field(['t.*',$listenCount.' as listens_like',$consultCount.' as consults_like',$testCount.' as test_count',$articleCount.' as article_count',$userCount.' as user_count','null as obey_label','null as obey_id'])
		->paginate(100);
		
		if($labels){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$labels]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'还未添加任何标签！']);
		}
	}

	/**
	 * 标签详情
	 */
	public function labelDetails()
	{
		$labelId = input('param.label_id');
		$topic = new Topic;
		$topicData = $topic->where('id',$labelId)->find();
		$rele = new TopicRelevance;
		$relevance = $rele->getRele($labelId);
		if($topicData){
			return json([
				'success'=>true,
				'code'=>'000',
				'message'=>'查询成功',
				'data'=>[
					'details'=>$topicData,
					'relevance'=>$relevance,
				],
			]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'查询出错']);
		}
	}

	/**
	 * 添加标签
	 */
	public function addLabel()
	{
		$request = Request::instance();
		$post = $request->only(['label_name','obey_id']);
		if(empty($post['label_name'])){
			return json(['success'=>false,'code'=>'002','message'=>'标签名称不能为空']);
		}
		$insertData = [
			'title'=>$post['label_name'],
			'status'=>1,
		];
		$topic = new Topic;
		if(empty($post['obey_id'])){
			$result = $topic->createLabel($insertData);
		}else{
			$result = $topic->createLabel($insertData,$post['obey_id']);
		}
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'标签已保存']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'标签保存出错，请稍后再试']);
		}
	}

	/**
	 * 添加关联标签
	 */
	public function relevance()
	{
		$request = Request::instance();
		$post = $request->only(['obey_id','master_id']);
		if(empty($post['master_id'])){
			return json(['success'=>false,'code'=>'002','message'=>'标签ID不能为空']);
		}
		$rele = new TopicRelevance();
		$result = $rele->setRele($post['master_id'],$post['obey_id']);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'标签关联成功']);
		}else{
			return json(['success'=>false,'code'=>"006",'message'=>'标签已经关联或不存在']);
		}
	}
}
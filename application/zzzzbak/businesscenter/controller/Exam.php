<?php
namespace app\businesscenter\controller;
use think\Db;
use app\businesscenter\controller;
class Exam extends Common
{
    public function index()
    {

        return 'index';
    }
    //测试业务订单
    public function ExamOrder(){
    	$data = input('post.');
    	$where = [];
    	if (isset($data['orderid'])) {
    		//订单id
    		# code...
    		$where['orderid'] = $data['orderid'];
    	}elseif (isset($data['createtime'])) {
    		//下单时间
    		# code...
    		$stime = strtotime($data['createtime']);
    		$etime = $stime+86399;
    		$where['createtime'] = ['between',[$stime,$etime]];
    	}elseif (isset($data['status'])) {
    		//订单状态
    		# code...
    		$where['o.status'] = $data['status'];
    	}elseif (isset($data['titleid'])) {
    		//测试id
    		# code...
    		$where['titleid'] = $data['titleid'];
    	}elseif (isset($data['title'])) {
    		//测试标题
    		# code...
    		$title = $data['title'];
    		$_where['title'] = ['like',['%'.$title,$title.'%','%'.$title.'%'],'OR'];
    		$titleids = db('examtitle')->where($_where)->column('id');
    		// echo db('examtitle')->getLastSql();
    		// print_r($titleids);
    		$where['titleid'] = ['in',$titleids];
    	}elseif (isset($data['nickname'])) {
    		//用户昵称
    		# code...
    		$nickname = $data['nickname'];
    		$_where['nickname'] = ['like',['%'.$nickname,$nickname.'%','%'.$nickname.'%'],'OR'];
    		$uids = db('user')->where($_where)->column('id');
    		$where['uid'] = ['in',$uids];
    	}elseif (isset($data['biaoti'])) {
    		//标题
    		# code...
    		$biaoti = $data['biaoti'];
    		$where['biaoti'] = ['like',['%'.$biaoti,$biaoti.'%','%'.$biaoti.'%'],'OR'];
    	}
    	//分页操作
    	$count = db('examorder o')->field('orderid,createtime,nickname,titleid,title,biaoti,t.money money,o.status')->join('examtitle t','t.id=o.titleid')->join('user u','u.id=o.uid')->where($where)->count();
    	//print_r($count);
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page/d',1));
    	// print_r($page);
    	// die;
    	$page = $page<=0?1:$page;
    	$limit = ($page-1)*$pageSize.','.$page*$pageSize;
    	//print_r($limit);
    	$data['page'] = ['totalpages'=>$totalpages,'page'=>$page];
    	$list = db('examorder o')->field('orderid,createtime,nickname,titleid,title,biaoti,t.money money,o.status')->join('examtitle t','t.id=o.titleid')->join('user u','u.id=o.uid')->where($where)->limit($limit)->select();
    	$data['list'] = $list;
    	// print_r($list);
    	sendJson(1,'测试订单列表',$data);
    }
    //测试题目列表
    public function examTitle(){
    	$data = input('post.');
    	$where = [];
    	if (isset($data['id'])) {
    		# code...
    		$where['id'] = $data['id'];
    	}elseif (isset($data['title'])) {
    		# code...
    		$title = $data['title'];
    		$where['title'] = ['like',['%'.$title,$title.'%','%'.$title.'%'],'OR'];
    	}elseif (isset($data['status'])) {
    		# code...
    		$where['status']=$data['status'];
    	}
    	$count = db('examtitle')->field('id,title,description,topic,money,status')->where($where)->count();
    	//接受页数
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page/d',1));
    	$page = $page<=0?1:$page;
    	//print_r($totalpages);
    	$data['page'] = ['totalpages'=>$totalpages,'page'=>$page];
    	$list = db('examtitle')->field('id,title,description,topic,money,status')->where($where)->page($page,$pageSize)->select();
    	foreach ($list as $key => $value) {
    		# code...
    		
    		$topic = trim($value['topic'],',');
    		$arr = explode(',', $topic);
    		$topicarr = [];
    		foreach ($arr as $v) {
    			# code...
    			$topicarr[] = db('topic')->where(['id'=>$v])->value('title');
    		}
    		$list[$key]['topic'] = implode("#", $topicarr);
    		$list[$key]['num'] = db('examquestion')->where(['titleid'=>$value['id']])->count();
    	}
    	$data['list'] = $list;
    	//print_r($list);
    	sendJson(1,'心理测试列表',$data);
    }
    //添加测试标题
    	//添加试卷
	//$title = input('title');
	// $description = input('description');
	// $money = input('money');
	// $picurl = input('picurl');
	// topic 话题
	// know 须知
	// content 介绍
	public function addtitle(){
		$data = input('post.');
		if (!isset($data['title'])&&!isset($data['description'])&&!isset($data['topic'])&&!isset($data['content'])&&!isset($data['know'])&&!isset($data['picurl'])&&!isset($data['biaoti'])&&!isset($data['money'])) {
			# code...
			sendJson(-1,'数据错误,请全部填写');
		}
		$data['topic'] = ','.$data['topic'].',';
		$data['update_at'] = time();
		$res = db('examtitle')->insert($data);
		$id = db('examtitle')->getLastInsID();
		if($res){
			sendJson(1,'试卷添加成功',$id);
		}else{
			sendJson(-1,'试卷添加失败');
		}

	}
	//添加试卷后待编辑题目,还未发布获取试卷列表
	public function getTitleList(){
		$list = db('examtitle')->field('id,title')->where(['status'=>0])->select();
		sendJson(1,'成功',$list);
	}
	//给试卷添加问题
	//number序号
	//question 问题
	//weidu 维度
	//titleid 试卷id
	//is_end 是否为最后的问题
	public function addQuestion(){
		$data = input('post.');
		$res = db('examquestion')->insert($data);
		$id = db('examquestion')->getLastInsID();
		if($res){
			sendJson(1,'问题添加成功',$id);
		}else{
			sendJson(-1,'问题添加失败');
		}
	}
	//添加答案
	//questionid 问题的id(不是number)
	//option 选项 A,B,C
	//content 内容
	//score 分数
	//jumpid 跳转到哪道题(这里是number)
	public function addAnswer(){
		$data = input('post.');
		$res = db('examanswer')->insert($data);
		if($res){
			sendJson(1,'答案添加成功');
		}else{
			sendJson(-1,'答案添加失败');
		}
	}
	//添加参考结果
	//titleid 
	//weidu
	//max
	//min
	//content
	public function addEnd(){
		$data = input('post.');
		if(!isset($data['weidu'])){
			$data['weidu'] = '总评';
		}
		$res = db('examend')->insert($data);
		if($res){
			sendJson(1,'参考结果成功');
		}else{
			sendJson(-1,'参考结果失败');
		}
	}

	//测试项目详情
	//获得试卷目前已经有的所有信息
	//titleid 试卷的id
	public function getAllInfo(){
		$titleid = input('titleid');
		$info = db('examtitle')->where(['id'=>$titleid])->find();
		$topic = trim($info['topic'],',');
    		$arr = explode(',', $topic);
    		$topicarr = [];
    		foreach ($arr as $v) {
    			# code...
    			$topicarr[] = db('topic')->where(['id'=>$v])->value('title');
    		}
    		$info['topic'] = implode("#", $topicarr);
		$data = [];
		$question = db('examquestion')->where(['titleid'=>$titleid])->select();
		foreach ($question as $key => $value) {
			$value['answer'] = db('examanswer')->where(['questionid'=>$value['id']])->select();
			$data[]=$value;
		}
		$info['test'] = $data;
		sendJson(1,'获取成功',$info);
	}
	//心理测试订单详情
	public function getOrderInfo(){
		$orderid = input('orderid');
		$order = db('examorder')->where(['orderid'=>$orderid])->find();
		$user = db('user')->field('id,nickname,avatarurl,level,gender,mobile')->where(['id'=>$order['uid']])->find();
		$exam = db('examtitle')->field('title,description,id,topic')->where(['id'=>$order['titleid']])->find();
		$exam['topic'] = $this->getTopicStr($exam['topic']);
		$exam['num'] = db('examquestion')->where(['titleid'=>$exam['id']])->count();
		$result = db('exam_result')->where(['uid'=>$order['uid'],'examtitle'=>$order['titleid']])->select();
		// print_r($result);
		$data = ['order'=>$order,'user'=>$user,'exam'=>$exam,'result'=>$result];
		sendJson(1,'测试订单详情',$data);
	}
}

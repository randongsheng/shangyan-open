<?php
namespace app\admin\controller;
use app\admin\model\ExamanswerModel;
use app\admin\model\ExamquestionModel;
use app\admin\model\ExamtitleModel;
use think\Db;
use app\admin\controller;
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
    	if (isset($data['orderid'])&&!empty($data['orderid'])) {
    		//订单id
    		# code...
    		$where['orderid'] = $data['orderid'];
    	}elseif (isset($data['createtime'])&&!empty($data['createtime'])) {
    		//下单时间
    		# code...
    		$stime = strtotime($data['createtime']);
    		$etime = $stime+86399;
    		$where['createtime'] = ['between',[$stime,$etime]];
    	}elseif (isset($data['status'])&&!empty($data['status'])) {
    		//订单状态
    		# code...
    		$where['o.status'] = $data['status'];
    	}elseif (isset($data['titleid'])&&!empty($data['titleid'])) {
    		//测试id
    		# code...
    		$where['titleid'] = $data['titleid'];
    	}elseif (isset($data['title'])&&!empty($data['title'])) {
    		//测试标题
    		# code...
    		$title = $data['title'];
    		$_where['title'] = ['like',['%'.$title,$title.'%','%'.$title.'%'],'OR'];
    		$titleids = db('examtitle')->where($_where)->column('id');
    		// echo db('examtitle')->getLastSql();
    		// print_r($titleids);
    		$where['titleid'] = ['in',$titleids];
    	}elseif (isset($data['nickname'])&&!empty($data['nickname'])) {
    		//用户昵称
    		# code...
    		$nickname = $data['nickname'];
    		$_where['nickname'] = ['like',['%'.$nickname,$nickname.'%','%'.$nickname.'%'],'OR'];
    		$uids = db('user')->where($_where)->column('id');
    		$where['uid'] = ['in',$uids];
    	}elseif (isset($data['biaoti'])&&!empty($data['biaoti'])) {
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
    	$data['page'] = ['totalpages'=>$totalpages,'page'=>$page,'count'=>$count];
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
    	$data['page'] = ['totalpages'=>$totalpages,'page'=>$page,'count'=>$count];
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
        $titleid = input('post.titleid');
        unset($data['titleid']);
        $validate = new \app\admin\validate\Examvalidate;
        $checkData = $validate->scene('addtitle')->check($data);
        if (!$checkData){
            sendJson(-1,$validate->getError()) ;
        }
//        $check = $this->validate($data,'Examvalidate.addtitle');
//        if (true !== $check){
//            sendJson(-1,$check);
//        }
//		if (!isset($data['title'])&&!isset($data['description'])&&!isset($data['topic'])&&!isset($data['content'])&&!isset($data['know'])&&!isset($data['picurl'])&&!isset($data['biaoti'])&&!isset($data['money'])) {
//			# code...
//			sendJson(-1,'数据错误,请全部填写');
//		}
		$data['topic'] = ','.$data['topic'].',';
		$data['update_at'] = time();
        if (!$titleid) {
            $res = db('examtitle')->insert($data);
            $id = db('examtitle')->getLastInsID();
        }else{
            $res = db('examtitle')->where(['id'=>$titleid])->update($data);
            $id = $titleid;
        }
		if($res){
			sendJson(1,'试卷添加成功',$id);
		}else{
			sendJson(-1,'试卷添加失败');
		}

	}
    //修改试卷
    public function readtitle(){
        $titleid = input('post.titleid');
        //查询试卷标题
        $title = db('examtitle')->where(['id'=>$titleid])->find();
        sendJson(1,'试卷标题',$title);
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
        $validate = new \app\admin\validate\Examvalidate;
        $checkData = $validate->scene('addquestion')->check($data);
        if (!$checkData){
            sendJson(-1,$validate->getError()) ;
        }
        unset($data['json']);
        //查询问题是否存在
        $titleid = input('post.titleid');
        $number = input('post.number');
        $question = db('examquestion')->where(['titleid'=>$titleid,'number'=>$number])->find();
        if (!$question) {
            $res = db('examquestion')->insert($data);
            $id = db('examquestion')->getLastInsID();
        }else{
            $res = db('examquestion')->where(['id'=>$question['id']])->update($data);
            $id = $question['id'];
        }
		
        $answer = input('post.json');
        $res = $this->addAnswer($answer,$id);
		if($res){
			sendJson(1,'添加成功',$id);
		}else{
			sendJson(-1,'添加失败');
		}
	}
	//添加答案
	//questionid 问题的id(不是number)
	//option 选项 A,B,C
	//content 内容
	//score 分数
	//jumpid 跳转到哪道题(这里是number)
	public function addAnswer($json,$id){
        $answer = json_decode($json,true);
        foreach ($answer as $key => $value) {
            $answer[$key]['questionid']=$id;
        }
        //删除所有questionid 的答案
        db('examanswer')->where(['questionid'=>$id])->delete();
        $res = db('examanswer')->insertAll($answer);
        if ($res) {
            return true;
        }else{
            return false;
        }
	}
    //获取试卷问题
    public function readquestion(){
        $titleid = input('post.titleid');
        $question = db('examquestion')->where(['titleid'=>$titleid])->select();
        foreach ($question as $key => $value) {
            $value['answer'] = db('examanswer')->where(['questionid'=>$value['id']])->select();
            $data[]=$value;
        }
        sendJson(1,'获取试卷问题',$data);
    }
    //获取测试结果参考
    public function readend(){
        $titleid = input('post.titleid');
        $end = db('examend')->where(['titleid'=>$titleid])->group('weidu')->column('weidu');
        $examend = [];
        foreach ($end as $a) {
            $examend[] = db('examend')->where(['weidu'=>$a,'titleid'=>$titleid])->select();
        }
        sendJson(1,'获取测试结果参考',$examend);
    }
	//添加参考结果
	//titleid 
	//weidu
	//max
	//min
	//content
	// public function addEnd(){
	// 	$data = input('post.');
	// 	if(!isset($data['weidu'])){
	// 		$data['weidu'] = '总评';
	// 	}
	// 	$res = db('examend')->insert($data);
	// 	if($res){
	// 		sendJson(1,'参考结果成功');
	// 	}else{
	// 		sendJson(-1,'参考结果失败');
	// 	}
	// }
    //添加结果
    public function addEnd(){
        $json = input('post.json');
        $titleid = input('post.titleid');
        $arr = json_decode($json,true);
        $data = [];
        foreach ($arr as $key => $value) {
            $weidu = $value['weidu'];
            foreach ($value['list'] as $k => $v) {
                $v['weidu'] = $weidu;
                $v['titleid'] = $titleid;
                $data[] = $v; 
            }
        }
        db('examend')->where(['titleid'=>$titleid])->delete();
        $res = db('examend')->insertAll($data);
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
		$examtitle = new ExamtitleModel();
		$info = $examtitle->where(['id'=>$titleid])->find();
		$topic = trim($info['topic'],',');
    		$arr = explode(',', $topic);
    		$topicarr = [];
    		foreach ($arr as $v) {
    			# code...
    			$topicarr[] = db('topic')->field('id,title')->where(['id'=>$v])->find();//value('title');
    		}
    		$info['topic'] = $topicarr;//implode("#", $topicarr);
		$data = [];
		$examquestion = new ExamquestionModel();
		$question = $examquestion->where(['titleid'=>$titleid])->select();
		$examanswer = new ExamanswerModel();
		foreach ($question as $key => $value) {
			$value['answer'] = $examanswer->where(['questionid'=>$value['id']])->select();
			$data[]=$value;
		}
		$info['test'] = $data;
        //获取参考答案
        $end = db('examend')->where(['titleid'=>$titleid])->group('weidu')->column('weidu');
        $examend = [];
        foreach ($end as $a) {
            $examend[] = db('examend')->where(['weidu'=>$a,'titleid'=>$titleid])->select();
        }
        $info['end'] = $examend;
		sendJson(1,'获取成功',$info);
	}
	//心理测试订单详情
	public function getOrderInfo(){
		$orderid = input('orderid');
		if (!$orderid){
		    sendJson(-1,'orderid不能为空');
        }
		$order = db('examorder')->where(['orderid'=>$orderid])->find();
		$user = db('user')->field('id,nickname,avatarurl,level,gender,mobile')->where(['id'=>$order['uid']])->find();
		$exam = db('examtitle')->field('title,description,id,topic')->where(['id'=>$order['titleid']])->find();
		$exam['topic'] = $this->getTopicStr($exam['topic']);
		$exam['num'] = db('examquestion')->where(['titleid'=>$exam['id']])->count();
		$result = db('exam_result')->where(['uid'=>$order['uid'],'examtitle'=>$order['titleid']])->select();
		foreach ($result as $key =>$value)
        {
            $result[$key]['examresult'] = json_decode($value['examresult']);
        }

		// print_r($result);
        $commnet = \db('examcomment')->where(['orderid'=>$orderid])->find();
		$data = ['order'=>$order,'user'=>$user,'exam'=>$exam,'result'=>$result,'comment'=>$commnet];

		sendJson(1,'测试订单详情',$data);
	}
	//测试题上架
    public function upExam(){
        $id = input('post.id');
        \db('examtitle')->where(['id'=>$id])->update(['status'=>1]);
        //是否有题目
        $question = \db('examquestion')->where(['titleid'=>$id])->find();
        if (!$question){
            sendJson(-1,'还没有添加题目去添加吧');
        }
        //是否有参考答案
        $end = \db('examend')->where(['titleid'=>$id])->find();
        if (!$end)
        {
            sendJson(-1,'还没有添加测试答案去添加吧');
        }
        $is_end = \db('examquestion')->where(['titleid'=>$id,'is_end'=>1])->find();;
        if (!$is_end)
        {
            sendJson(-1,'测试题没有完结题目');
        }
        sendJson(1,'测试上架');
    }
    //测试题下架
    public function downExam(){
        $id = input('post.id');
        \db('examtitle')->where(['id'=>$id])->update(['status'=>0]);
        sendJson(1,'测试下架');
    }
    //测试题删除
    public function delExam(){
        $id = input('post.id');
        db('examtitle')->where(['id'=>$id])->delete();
        sendJson(1,'测试删除');
    }
}

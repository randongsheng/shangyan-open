<?php
namespace app\admin\controller;
use app\admin\model\ClinicModel;
use app\admin\model\UserModel;
use think\Db;
use app\admin\controller;
class Listen extends Common
{
	public function listenOrder(){
		$data = input('post.');
		$where = [];
    	$where['type'] = 1;
    	$where['o.status'] = ['NEQ',3];
    	if (isset($data['orderid'])&&!empty($data['orderid'])) {
    		//订单id
    		# code...
    		$where['orderid'] = $data['orderid'];
    	}
    	if (isset($data['createtime'])&&!empty($data['createtime'])) {
    		//下单时间
    		# code...
    		$stime = strtotime($data['createtime']);
    		$etime = $stime+86399;
    		$where['createtime'] = ['between',[$stime,$etime]];
    	}
    	if ((isset($data['status'])&&!empty($data['status'])) || ($data['status'] === 0)) {
    		//订单状态
    		# code...
    		$where['o.status'] = $data['status'];
    	}
    	if (isset($data['nickname'])&&!empty($data['nickname'])) {
    		//用户昵称
    		# code...
            $_where = [];
    		$nickname = $data['nickname'];
    		$_where['nickname'] = ['like',['%'.$nickname,$nickname.'%','%'.$nickname.'%'],'OR'];
    		$uids = db('user')->where($_where)->column('id');
    		// print_r($uids);
    		// die;
    		$where['o.uid'] = ['in',$uids];
    	}
    	if (isset($data['teachername'])&&!empty($data['teachername'])) {
    		//倾听师姓名
    		# code...
            $_where = [];
    		$teachername = $data['teachername'];
    		$_where['realname'] = ['like',['%'.$teachername,$teachername.'%','%'.$teachername.'%'],'OR'];
    		$uids = db('userfield')->where($_where)->column('uid');
    		$where['serverpersonid'] = ['in',$uids];
    	}
    	if (isset($data['clinic_name'])&&!empty($data['clinic_name'])) {
    		//机构名称
    		# code...
    		$clinic_name = $data['clinic_name'];
    		$c_where['clinic_name'] = ['like',['%'.$clinic_name,$clinic_name.'%','%'.$clinic_name.'%'],'OR'];
    		$clinicids = db('clinic')->where($c_where)->column('id');
    		$where['o.clinicid'] = ['in',$clinicids];
    	}
    	$count = db('order o')->field('orderid,o.createtime,nickname,realname,clinic_name,o.topic,ordermoney,sytime,alltime,o.status')->join('user u','u.id=o.uid')->join('userfield f','f.uid=o.serverpersonid')->join('clinic c','c.id=o.clinicid')->where($where)->count();
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page/d',1));
    	$page = $page<=0?1:$page;
    	//print_r($totalpages);
    	$data['page'] = ['pagesize'=>$pageSize,'page'=>$page,'count'=>$count];
    	$list = db('order o')->field('type,orderid,o.createtime,nickname,realname,clinic_name,o.topic,ordermoney,sytime,alltime,o.status')->join('user u','u.id=o.uid')->join('userfield f','f.uid=o.serverpersonid')->join('clinic c','c.id=o.clinicid')->page($page,$pageSize)->where($where)->order('o.id','desc')->select();
    	foreach ($list as $k => $v) {
    		$list[$k]['rest'] = $this->checkOrder($v['orderid']);
    		$list[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
    		$list[$k]['alltime'] = secondToStr($v['alltime']);
    		$list[$k]['sytime'] = secondToStr($v['sytime']);
    		$topic = trim($v['topic'],',');
	        if (is_numeric($topic)) {
	            $list[$k]['topic'] = db('topic')->where(['id'=>$topic])->value('title');
	        }else{
	            $topicArr = explode(",", $topic);
	            foreach ($topicArr as $key => $value) {
	               $topicarr[] = db('topic')->where(['id'=>$value])->value('title'); 
	            }
	            $list[$k]['topic'] = implode('#', $topicarr);
	        } 
    	}
    	
    	$data['list'] = $list;
    	
    	sendJson(1,'倾听订单列表',$data);
	}
	//业务设置数据
	public function setBusiness(){
		$system = db('system')->where(['business_module'=>'listen'])->column('name,value');
		sendJson(1,'数据',$system);
		//print_r($system);
	}
	//获取管理员电话
	public function getAdministratorTelephone(){
		$mobile = db('system')->where(['business_module'=>'administrator','name'=>'telephone'])->value('value');
		sendJson(1,'管理员电话',$mobile);
	}

	//设置业务状态
	public function setBusinessStatus(){
		//查询站点配置数据库找到管理员电话,防止他人操作
		$mobile = db('system')->where(['business_module'=>'administrator','name'=>'telephone'])->value('value');
		// 接收验证码
		$code = input('post.code');
		$checkcode = controller('tool/tools','controller')->checkSmscode($mobile,$code);
		print_r($checkcode);
		//如果验证成功修改数据
		if($checkcode){
			//查询当前状态
			$nowstatus = db('system')->where(['business_module'=>'listen','name'=>'business_status'])->value('value');
			if($nowstatus == 1){
				$update = 0;
			}else{
				$update = 1;
			}
			$setBusinessStatus = db('system')->where(['business_module'=>'listen','name'=>'business_status'])->update(['value'=>$update]);
			if($setBusinessStatus){
				sendJson(1,'设置业务状态成功');
			}else{
				sendJson(-1,'设置业务状态失败');
			}
		}
	}
	//设置价格
	public function setPrice(){
		$data = input('post.');
		if (!isset($data['starting_time'])||!isset($data['starting_price'])||!isset($data['inc_time'])||!isset($data['inc_price'])) {
			sendJson(-1,'所有值不能为空');
		}
		if (is_numeric($data['starting_time'])) {
			db('system')->where(['business_module'=>'listen','name'=>'starting_time'])->update(['value'=>$data['starting_time']]);
		}
		if (is_numeric($data['starting_price'])) {
			db('system')->where(['business_module'=>'listen','name'=>'starting_price'])->update(['value'=>$data['starting_price']]);
		}
		if (is_numeric($data['inc_time'])) {
			db('system')->where(['business_module'=>'listen','name'=>'inc_time'])->update(['value'=>$data['inc_time']]);
		}
		if (is_numeric($data['inc_price'])) {
			db('system')->where(['business_module'=>'listen','name'=>'inc_price'])->update(['value'=>$data['inc_price']]);
		}
		sendJson(1,'保存成功');	
	}
	//设置费率
	public function setRate(){
		$rate = input('post.rate');
		if (isset($rate)&&is_numeric($rate)) {
			db('system')->where(['business_module'=>'listen','name'=>'service_rate'])->update(['value'=>$rate]);
			sendJson(1,'费率保存成功');	
		}
	}
	//订单详情
	public function orderInfo(){
		$orderid = input('post.orderid');
		if (!$orderid)
        {
            sendJson(-1,'orderid不能为空');
        }
		$order = db('order')->field('status,orderid,ordermoney,topic,clinicid,paymode,uid,content,serverpersonid,alltime,sytime,createtime,paytime,completion_time,total_money,outtime,outtimeprice')->where(['orderid'=>$orderid])->find();
        if (!$order){
            sendJson(-1,'订单未找到');
        }
		$order['topic'] = $this->getTopicStr($order['topic']);
		$user = new UserModel();
		$visitor = $user->field('avatarurl,nickname,id,uname,gender,mobile,level')->where(['id'=>$order['uid']])->find();
		$teacher = $user->alias('u')->field('avatarurl,level,id,realname,mobile,gender,title')->join('userfield f','u.id=f.uid')->where(['id'=>$order['serverpersonid']])->find();
        $teacher['title'] = db('teacher_certificate')->where(['uid'=>$order['serverpersonid']])->column('certificate_name');
		$clinicObj = new ClinicModel();
        $clinic = $clinicObj->field('level,clinic_name,logo,id,nature,operator_tel')->where(['id'=>$order['clinicid']])->find();
		$listenrecord = db('listenrecord r')->field('r.id,stime,etime,timelong,sytime,score,content')->join('listencomment c','r.id=c.recordid','LEFT')->where(['r.orderid'=>$orderid])->select();
		//print_r($ordermore);
		$data = ['order'=>$order,'visitor'=>$visitor,'teacher'=>$teacher,'clinic'=>$clinic,'listenrecord'=>$listenrecord];
		sendJson(1,'订单详情',$data);
	}
}
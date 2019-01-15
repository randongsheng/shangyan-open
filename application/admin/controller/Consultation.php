<?php
namespace app\admin\controller;
use app\admin\model\ClinicModel;
use app\admin\model\UserModel;
use think\Db;
use app\admin\controller;
class Consultation extends Common
{
	//咨询订单列表
	public function consultationOrder(){
		$data = input('post.');
		$where = [];
    	$where['type'] = 2;
    	$where['o.status'] = ['NEQ',3];
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
    	}elseif (isset($data['nickname'])&&!empty($data['nickname'])) {
    		//用户昵称
    		# code...
    		$nickname = $data['nickname'];
    		$_where['nickname'] = ['like',['%'.$nickname,$nickname.'%','%'.$nickname.'%'],'OR'];
    		$uids = db('user')->where($_where)->column('id');
    		// print_r($uids);
    		// die;
    		$where['o.uid'] = ['in',$uids];
    	}elseif (isset($data['teachername'])&&!empty($data['teachername'])) {
    		//倾听师姓名
    		# code...
    		$teachername = $data['teachername'];
    		$_where['realname'] = ['like',['%'.$teachername,$teachername.'%','%'.$teachername.'%'],'OR'];
    		$uids = db('userfield')->where($_where)->column('uid');
    		$where['serverpersonid'] = ['in',$uids];
    	}elseif (isset($data['clinic_name'])&&!empty($data['clinic_name'])) {
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
    	$data['page'] = ['totalpages'=>$totalpages,'page'=>$page,'count'=>$count];
    	$list = db('order o')->field('type,orderid,o.createtime,nickname,realname,clinic_name,o.topic,ordermoney,o.status')->join('user u','u.id=o.uid')->join('userfield f','f.uid=o.serverpersonid')->join('clinic c','c.id=o.clinicid')->page($page,$pageSize)->where($where)->select();
    	// echo db('order o')->getLastSql();
    	// die;
    	foreach ($list as $k => $v) {
    		$list[$k]['rest'] = $this->checkOrder($v['orderid']);
    		// 查询总预约次数
    		$list[$k]['alltimes'] = db('ordermore')->where(['orderid'=>$v['orderid']])->count();
    		// 查询已经使用的次数
    		$list[$k]['usetimes'] = db('ordermore')->where(['orderid'=>$v['orderid'],'status'=>['NEQ',0]])->count();

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
    	
    	sendJson(1,'咨询订单列表',$data);
	}
	//业务设置数据
	public function setBusiness(){
		$system = db('system')->where(['business_module'=>'consultation'])->column('name,value');
		sendJson(1,'数据',$system);
		//print_r($system);
	}
	//设置业务状态
	public function setBusinessStatus(){
		//查询站点配置数据库找到管理员电话,防止他人操作
		$mobile = db('system')->where(['business_module'=>'administrator','name'=>'telephone'])->value('value');
		// 接收验证码
		$code = input('post.code');
		$checkcode = controller('tool/tools','controller')->checkSmscode($mobile,$code);
		//print_r($checkcode);
		//如果验证成功修改数据
		if($checkcode){
			//查询当前状态
			$nowstatus = db('system')->where(['business_module'=>'consultation','name'=>'business_status'])->value('value');
			if($nowstatus == 1){
				$update = 0;
			}else{
				$update = 1;
			}
			$setBusinessStatus = db('system')->where(['business_module'=>'consultation','name'=>'business_status'])->update(['value'=>$update]);
			if($setBusinessStatus){
				sendJson(1,'设置业务状态成功',$update);
			}else{
				sendJson(-1,'设置业务状态失败');
			}
		}
	}
	//设置价格
	public function setPrice(){
		$data = input('post.');
		if (!isset($data['lv1_sprice'])||!isset($data['lv1_eprice'])||!isset($data['lv2_sprice'])||!isset($data['lv2_eprice'])||!isset($data['lv3_sprice'])||!isset($data['lv3_eprice'])||!isset($data['lv4_sprice'])||!isset($data['lv4_eprice'])) {
			sendJson(-1,'所有值不能为空');
		}
		if (is_numeric($data['lv1_sprice'])) {
			db('system')->where(['business_module'=>'consultation','name'=>'lv1_sprice'])->update(['value'=>$data['lv1_sprice']]);
		}
		if (is_numeric($data['lv1_eprice'])) {
			db('system')->where(['business_module'=>'consultation','name'=>'lv1_eprice'])->update(['value'=>$data['lv1_eprice']]);
		}
		if (is_numeric($data['lv2_sprice'])) {
			db('system')->where(['business_module'=>'consultation','name'=>'lv2_sprice'])->update(['value'=>$data['lv2_sprice']]);
		}
		if (is_numeric($data['lv2_eprice'])) {
			db('system')->where(['business_module'=>'consultation','name'=>'lv2_eprice'])->update(['value'=>$data['lv2_eprice']]);
		}
		if (is_numeric($data['lv3_sprice'])) {
			db('system')->where(['business_module'=>'consultation','name'=>'lv3_sprice'])->update(['value'=>$data['lv3_sprice']]);
		}
		if (is_numeric($data['lv3_eprice'])) {
			db('system')->where(['business_module'=>'consultation','name'=>'lv3_eprice'])->update(['value'=>$data['lv3_eprice']]);
		}
		if (is_numeric($data['lv4_sprice'])) {
			db('system')->where(['business_module'=>'consultation','name'=>'lv4_sprice'])->update(['value'=>$data['lv4_sprice']]);
		}
		if (is_numeric($data['lv4_eprice'])) {
			db('system')->where(['business_module'=>'consultation','name'=>'lv4_eprice'])->update(['value'=>$data['lv4_eprice']]);
		}
		sendJson(1,'保存成功');	
	}
	//设置费率
	public function setRate(){
		$rate = input('post.rate');
		if (isset($rate)&&is_numeric($rate)) {
			db('system')->where(['business_module'=>'consultation','name'=>'service_rate'])->update(['value'=>$rate]);
			sendJson(1,'费率保存成功');	
		}
	}
	//订单详情
	public function orderInfo(){
		$orderid = input('post.orderid');
		if(!$orderid)
        {
            sendJson(-1,'orderid不为空');
        }
        $user = new UserModel();
		$order = db('order')->field('status,orderid,ordermoney,topic,clinicid,paymode,uid,content,serverpersonid,createtime,paytime,completion_time,total_money,consultation_num,hospital_diagnosis')->where(['orderid'=>$orderid])->find();
		$order['topic'] = $this->getTopicStr($order['topic']);
		$order['num'] = db('ordermore')->where(['orderid'=>$orderid])->count();
        $order['surplus_num'] = db('ordermore')->where(['orderid'=>$orderid,'status'=>0])->count();
        $order['mode'] = db('ordermore')->where(['orderid'=>$orderid])->value('mode');
		$jj = db('ordermore')->field('jjname,jjtie,jjmobile')->where(['orderid'=>$orderid])->find();
		$visitor = $user->field('avatarurl,nickname,id,uname,gender,mobile,level')->where(['id'=>$order['uid']])->find();
		$teacher = $user->alias('u')->field('avatarurl,level,id,realname,mobile,gender,title')->join('userfield f','u.id=f.uid')->where(['id'=>$order['serverpersonid']])->find();
        $teacher['title'] = db('teacher_certificate')->where(['uid'=>$order['serverpersonid']])->column('certificate_name');
		$clinicObj = new ClinicModel();
        $clinic = $clinicObj->field('level,clinic_name,logo,id,nature,operator_tel')->where(['id'=>$order['clinicid']])->find();
		$ordermore = db('ordermore m')->field('m.id,status,date,week,starttime,endtime,content,score,teachersay,number')->join('zixuncomment z','m.id=z.moreid','LEFT')->where(['orderid'=>$orderid])->select();
		//print_r($ordermore);
		$data = ['order'=>$order,'emergency_contact'=>$jj,'visitor'=>$visitor,'teacher'=>$teacher,'clinic'=>$clinic,'ordermore'=>$ordermore];
		sendJson(1,'订单详情',$data);
	}
	//获取个案报告
	public function getCaseReport(){
		$id = input('post.id');
		$case = db('ordermore')->field('orderid,number,casereport,date,starttime,endtime,mode')->where(['id'=>$id])->find();
		$orderid = $case['orderid'];
		sendJson(1,'个案报告',$case);
	}
}
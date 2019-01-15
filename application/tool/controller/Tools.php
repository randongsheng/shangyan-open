<?php
namespace app\tool\controller;
use think\Controller; 
use \think\Request;
use \Qcloud\keys\WebRTCSigApi;
class Tools extends Controller
{
	//发起订单支付
	 public function goPay(){
	 	//$orderid = input('post.orderid');
	 	$orderid =input('post.orderid');
	 	$attach = input('post.attach');//备注,填写是 exam:心理测试订单 还是 order:咨询倾听订单,
	 	//print_r($orderid);
	 	if ($attach == 'exam') {
	 		$order = db('examorder')->where(['orderid'=>$orderid])->find();
	 		$total_fee = $order['money']*100;
	 	}elseif ($attach == 'order') {
	 		$order = db('order')->where(['orderid'=>$orderid])->find();
	 		$total_fee = $order['ordermoney']*100;
	 	}else{
	 		$order =false;
	 	}
	 	print_r($order);
	 	if(!$order){
	 		sendJson(-1,'没有找到订单');
	 	}
	 	if($order['status'] != 0){
	 		sendJson(-1,'订单状态不正确');
	 	}
	 	
	 	$openid = session('openid');
	 	if(!$openid){
	 		sendJson(-1,'没有获取到openid');
	 	}
	 	$body = '测试订单';
	 	$result = controller('index/pay', 'controller')->payfee($orderid,$total_fee,$openid,$body,$attach);
	 	print_r($result);
	 }
	//开始电话倾诉
	//orderid 订单号
	public function startListen($orderid){
		//查询订单相关数据
		$orderinfo =db('order')->where(['orderid'=>$orderid])->find();
		$serverpersonid=$orderinfo['serverpersonid'];
		//更改咨询师操作状态
		$this->setTeacherStatus($serverpersonid,1);
		//开始绑定关系号
		$fm = db('user')->where(['id'=>$serverpersonid])->value('mobile');
		$tm = $orderinfo['mobile'];
		// print_r($fm);
		// die;
		$result = controller('Call', 'controller')->autoCallTransferForSp($orderid,$fm,$tm);
		$result = json_decode($result,true);
		if ($result['result'] == 0) {
			$data = [
				'orderid'=>$orderid,
				'virtualMobile'=>$result['virtualMobile'],
				'fm'=>$fm,
				'tm'=>$tm,
				'exptime'=>ceil($result['endTime']/1000)
			];
			db('callbind')->insert($data);
			sendJson(1,'关系号绑定成功',['exptime'=>$result['endTime'],'virtualMobile'=>$result['virtualMobile']]);
		}else{
			sendJson(-1,$result['error']);
		}

	}
	//结束倾听
	public function endListen($orderid,$vm){
		//查询订单相关数据
		$orderinfo =db('order')->where(['orderid'=>$orderid])->find();
		$serverpersonid=$orderinfo['serverpersonid'];
		//更改咨询师操作状态
		$this->setTeacherStatus($serverpersonid,5);
		$res = db('callbind')->where(['orderid'=>$orderid,'virtualMobile'=>$vm])->order('id desc')->find();
		// echo db('callbind')->getLastSql();
		// print_r($res);
		// die;
		$vm = $res['virtualMobile'];
		$fm = $res['fm'];
		$tm = $res['tm'];
		$result = controller('Call', 'controller')->unbindCallTransferForSp($fm,$tm,$vm);
		$result = json_decode($result,true);
		if ($result['result'] == 0) {
			sendJson(1,'关系号解绑成功');
		}else{
			sendJson(-1,$result['error']);
		}

	}
	//更改老师服务状态
	public function setTeacherStatus($serverpersonid,$status=5){
		db('user')->where(['id'=>$serverpersonid])->update(['serverstatus'=>$status]);
	}
	//发送短信验证码操作
	public function sendSmsCode($mobile,$operation,$explong=5){
		$have = db('smscode')->where(['mobile'=>$mobile])->find();
		$code = $this->createSMSCode();
		$now = time();
		$data = array(
			'mobile' =>$mobile , 
			'code'=>$code,
			'sendtime'=>$now,
			'exptime'=>$now+$explong*60
			);
		if ($have) {
			db('smscode')->where(['mobile'=>$mobile])->update($data);
		}else{
			db('smscode')->insert($data);
		}	
		controller('Sms', 'controller')->sendCodeSms($mobile,$operation,$code);
	}
	//验证短信验证码
	public function checkSmscode($mobile,$code){
		$checkTime = $this->checkTime($mobile);
		if ($checkTime) {
			//没过期,检验验证码
			$check = db('smscode')->where(['mobile'=>$mobile,'code'=>$code])->find();
			if ($check) {
				return true;
			}else{
				sendJson(-1,'验证码不正确');
			}
		}else{
			sendJson(-1,'验证码过期,重新获取');
		}
	}
	public function checkTime($mobile){
		$exptime = db('smscode')->where(['mobile'=>$mobile])->value('exptime');
		$now = time();
		if ($now > $exptime) {
			//过期
			return false;
		}else{
			//没过期
			return true;
		}
	}
	// 生成短信验证码

	public function createSMSCode($length = 4){

	$min = pow(10 , ($length - 1));

	$max = pow(10, $length) - 1;

	return rand($min, $max);

	}
	//实时音视频获取 key 信息
	public function getKeyInfo($uid){
		//vendor('keys.WebRTCSigApi.php');
		import('keys.WebRTCSigApi', EXTEND_PATH ,'.php');
		$userid = $uid;
		$WebRTCSigApi = new WebRTCSigApi();
		$usersig = $WebRTCSigApi->genUserSig($userid,86400);
		$roomid = $uid;
		$privatemapkey = $WebRTCSigApi->genPrivMapEncrypt($userid, $roomid);
		//print_r($usersig);	
		$data = ['userid'=>$userid,'usersig'=>$usersig,'roomid'=>$roomid,'privatemapkey'=>$privatemapkey];
		sendJson(1,'key信息',$data);
	}

}
<?php
namespace app\tool\controller;
use think\Controller; 
use \think\Request;

class Callback extends Controller
{
	public function _initialize(){
		
	}
	//接受通话记录
	public function notifyCallUrl(){
		$data = [];
		$seqId = input('post.seqId');
		$starttime = ceil(input('post.timestamp')/1000);
		$virtualMobile = input('post.virtualMobile');
		$from = input('post.from');
		$to = input('post.to');
		$callStart = ceil(input('post.callStart')/1000);
		$callAnswer = ceil(input('post.callAnswer')/1000);
		$callEnd = ceil(input('post.callEnd')/1000);
		$call = input('post.call');
		$status = input('post.status');
		$noAnswerReason = input('post.noAnswerReason');
		$sign = input('post.sign');
		$data = [
			'seqId'=>$seqId,
			'starttime'=>$starttime,
			'virtualMobile'=>$virtualMobile,
			'from'=>$from,
			'to'=>$to,
			'callStart'=>$callStart,
			'callAnswer'=>$callAnswer,
			'callEnd'=>$callEnd,
			'call'=>$call,
			'status'=>$status,
			'noAnswerReason'=>$noAnswerReason,
			'sign'=>$sign
		];
		//$str = var_export($data,true);
		//file_put_contents('/home/wwwroot/application/tool/controller/log.txt',$str,FILE_APPEND);
		//查找是否有重复的seqid和starttime/防止重复写入
		$check = db('calllog')->where(['seqId'=>$seqId,'starttime'=>$starttime])->find();
		if(!$check){
			//写入日志
			db('calllog')->insert($data);
			//判断接通状态,如果接通
			if($status == 0){
				//计算通话时间,在订单中减去时间
				$time = $callEnd-$callAnswer;
				//file_put_contents('/home/wwwroot/application/tool/controller/log.txt',$time,FILE_APPEND);
				db('order')->where(['orderid'=>$seqId])->setDec('sytime', $time);
				//写入倾诉记录 listenrecord
				$timelong = $callEnd-$callAnswer;
				$sytime = db('order')->where(['orderid'=>$seqId])->value('sytime');
				$listenrecord = [
					'orderid'=>$seqId,
					'stime'=>$callAnswer,
					'etime'=>$callEnd,
					'timelong'=>$timelong,
					'sytime'=>$sytime
				];
				db('listenrecord')->insert($listenrecord);
			}
		}
		//接受通知,证明已经挂断电话,解除电话与虚拟号的绑定
		controller('Tools', 'controller')->endListen($seqId,$virtualMobile);
	}
	//单独聊天之前
	public function videoNotify(){
		//file_put_contents('/home/wwwroot/application/tool/controller/log1.txt','回调成功',FILE_APPEND);

		$json = file_get_contents("php://input") ;//{"CallbackCommand":"State.StateChange","Info":{"To_Account":"1","Action":"Login","Reason":"Register"}}
		file_put_contents('/home/wwwroot/application/tool/controller/log.txt',$json,FILE_APPEND);
		$data = json_decode($json,true);
		$CallbackCommand = $data['CallbackCommand'];
		switch ($CallbackCommand)
		{
		case "State.StateChange":
		//状态改变
		  $info = $data['Info'];
		  $uid = $info["To_Account"];
		  $status = $info["Action"];
		  $this->stateChange($uid,$status);
		  break;  
		case 'C2C.CallbackBeforeSendMsg':
		//{"MsgBody":[{"MsgType":"TIMTextElem","MsgContent":{"Text":"123"}}],"CallbackCommand":"C2C.CallbackBeforeSendMsg","From_Account":"1","To_Account":"user_01","FriendShip":3,"MsgRandom":1109223749,"MsgSeq":3678786,"MsgTime":1543209719}
		  $uid = $data['From_Account'];
		  //$fuid = $data['From_Account'];
		  $tuid = $data['To_Account'];
		  $MsgBody = $data["MsgBody"];
		  foreach ($MsgBody as $key => $value) {
		  	$msg_type = $value['MsgType'];
		  	$msg_content = var_export($value['MsgContent'],true);
		  	$this->c2c_msg($CallbackCommand,$uid,$tuid,$msg_type,$msg_content);
		  }
		  break;
		case 'C2C.CallbackAfterSendMsg':
		  $uid = $data['From_Account'];
		  $tuid = $data['To_Account'];
		  $MsgBody = $data["MsgBody"];
		  foreach ($MsgBody as $key => $value) {
		  	$msg_type = $value['MsgType'];
		  	$msg_content = var_export($value['MsgContent'],true);
		  	$this->c2c_msg($CallbackCommand,$uid,$tuid,$msg_type,$msg_content);
		  }
		  break; 
		default:
		  return false;
		}
		$this->imCallBackLog($uid,$CallbackCommand,$json);
	}
	//用户状态改变
	public function stateChange($uid,$status){
		if ($status == "Login") {
			//在线状态
			db('user')->where(['id'=>$uid])->update(['serverstatus'=>5]);
		}else{
			db('user')->where(['id'=>$uid])->update(['serverstatus'=>4]);
		}
	}
	//单聊记录
	public function c2c_msg($CallbackCommand,$fuid,$tuid,$msg_type,$msg_content){
		$data = [
			'callbackcommand'=>$CallbackCommand,
			'fuid'=>$fuid,
			'tuid'=>$tuid,
			'msg_type'=>$msg_type,
			'msg_content'=>$msg_content
		];
		db('c2cmsg')->insert($data);
	}
	//IM回调日志
	public function imCallBackLog($uid,$CallbackCommand,$json){
		$data = [
			'uid'=>$uid,
			'callbackcommand'=>$CallbackCommand,
			'callbackjson'=>$json
		];
		db('imcallbacklog')->insert($data);
	}
}
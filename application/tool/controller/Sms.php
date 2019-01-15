<?php
namespace app\tool\controller;
use think\Controller; 
use \think\Request;
use Qcloud\Sms\SmsSingleSender;
class Sms extends Controller
{
	public function _initialize(){
		$this->baseappid=1400160510;
		$this->baseappkey="2044e705774b5b891b4d949d20478189";
		$this->basesmsSign="尚言";
		vendor('Qcloud.Sms.index.php');
	}
	public function sendSms($mobile,$content){
		try {
		
	    $ssender = new SmsSingleSender($this->baseappid,$this->baseappkey);
	    $result = $ssender->send(0, "86", $mobile,
	        "【".$this->basesmsSign."】".$content, "", "");
	    $rsp = json_decode($result);
	    echo $result;
		} catch(\Exception $e) {
		    echo var_dump($e);
		}	
	}
	// 单发短信
	// operation 操作如注册,修改手机号等..
	// 
	public function sendCodeSms($mobile,$operation,$code){
		$templateId = 230394; 
		try {
		    $ssender = new SmsSingleSender($this->baseappid, $this->baseappkey);
		    $params = [$operation,$code];
		    $result = $ssender->sendWithParam("86", $mobile, $templateId,
		        $params, $this->basesmsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
		    $rsp = json_decode($result);
		    echo $result;
		} catch(\Exception $e) {
		    echo var_dump($e);
		}
	}
}
<?php
namespace app\tool\controller;
use think\Controller; 
use \think\Request;

class Call extends Controller
{
	public $base_array;
	public function _initialize(){
		$this->url = 'http://sandbox.teleii.com/';
		$timestamp = time()."000";
		$this->base_array = array(
			//商户号
			'id' =>'565',
			//商户key
			'spKey'=>'SI8BRODA0QY9AUX3IFZ6XGE0',
			//毫秒级时间戳
			'timestamp'=>$timestamp,
			
			
		);
	}
	//关系号呼转接口
	//$fm来源号码,$tm呼叫转至的号码
	public function autoCallTransferForSp($orderid,$fm,$tm,$bindTime=60){
		$url = $this->url.'autoCallTransferForSp.do';
		$need_data = array(
			//商户平台唯一标识
			'seqId'=>$orderid,
			'fm'=>$fm, 
			'tm'=>$tm,
			'bindTime'=>$bindTime,
			);
		//整合数据
		$data = array_merge($this->base_array,$need_data);
		$sign_source=$data['spKey'].$data['id'].$data['seqId'].$data['timestamp'].$fm.$tm;
		//生成签名
		$data['sign'] = md5($sign_source); 
		//print_r($data);
		//拼接模拟传输数据
		$curlparams = curlparams($data);
		//echo $curlparams;
		$result=postUrlForCalling($url, $curlparams);
		return $result;
		//print_r($result);
	}
	//关系虚号解绑接口
	public function unbindCallTransferForSp($fm,$tm,$vm){
		$url = $this->url.'unbindCallTransferForSp.do';
		$need_data = array(
			'fm'=>$fm, 
			'tm'=>$tm,
			'vm'=>$vm,
			);
		$data = array_merge($this->base_array,$need_data);
		$sign_source=$data['spKey'].$data['id'].$data['timestamp'].$fm.$tm.$vm;
		//生成签名
		$data['sign'] = md5($sign_source); 
		$curlparams = curlparams($data);
		$result=postUrlForCalling($url, $curlparams);
		return $result;
		//print_r($result);
	}
}
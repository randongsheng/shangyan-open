<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;

use app\admin\model\TeacherModel;
use module\RedisOp;
use Qcloud\Sms\SmsSingleSender;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;


class Sms extends Common
{


    public function _initialize(){
        $this->baseappid=1400184061;//1400160510;
        $this->baseappkey='a5fa0e5278b30e65888b768fe7e17783';//"2044e705774b5b891b4d949d20478189";
        $this->basesmsSign='尚言心理';//"尚言";
        vendor('Qcloud.Sms.index.php');

    }


    public function io()
    {
        RedisOp::set('shichong',123);
    }


    // 单发短信
    // operation 操作如注册,修改手机号等..
    //
    public function sendCodeSms($mobile=15733118589,$operation='操作',$code=123456){
        $templateId = 274708;
        try {
            $ssender = new SmsSingleSender($this->baseappid, $this->baseappkey);
            $params = [$operation,$code];
            $result = $ssender->sendWithParam("86", $mobile, $templateId,
                $params, $this->basesmsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            $rsp = json_decode($result);
            echo json_encode(['success'=>true,'code'=>1,'message'=>'调用成功','data'=>$result]);
        } catch(\Exception $e) {
            echo var_dump($e);
        }
    }


    public function sendOtherSms($templateId,$mobile,$params)
    {
//        $templateId = 230394;
        try {
            $ssender = new SmsSingleSender($this->baseappid, $this->baseappkey);
//            $params = [$operation,$code];
            $result = $ssender->sendWithParam("86", $mobile, $templateId,
                $params, $this->basesmsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            $rsp = json_decode($result,true);
            return $rsp['result'];
//            echo $result;
        } catch(\Exception $e) {
            $rsp = json_decode($e);
            return $rsp['result'];
//            echo var_dump($e);
        }
    }



   







}
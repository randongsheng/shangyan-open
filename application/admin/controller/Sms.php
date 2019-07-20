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




    // 单发短信
    // operation 操作如注册,修改手机号等..
    //
    public function sendCodeSms(){


        $mobile=input('mobile',null);

        if(empty($mobile)){
            return json(['code'=>'002','message'=>'请输入电话号码','data'=>array()]);
        }
        $templateId = 274708;

        $code=rand(111111,999999);

        $redis=new Redis();
        try {
            $ssender = new SmsSingleSender($this->baseappid, $this->baseappkey);
            $params = ['尚言心理',$code];
            $result = $ssender->sendWithParam("86", $mobile, $templateId,
                $params, $this->basesmsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
//            $rsp = json_decode($result);
           $redis->set(config('redis_type.teacher_add').$mobile,$code);

            $redis->expire(config('redis_type.teacher_add').$mobile,10);

            return json(['code'=>'000','message'=>'成功','data'=>array()]);
        } catch(\Exception $e) {
            return json(['code'=>'006','message'=>'失败','data'=>array()]);
        }
    }






   







}
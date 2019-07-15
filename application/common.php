<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Validate;
use think\Request;
use Qiniu\Auth as QiniuAuth;
use Qiniu\Storage\UploadManager;
use think\Env;

// 应用公共文件
function generate_rand( $length = 8 , $type = false) { 
    // 密码字符集，可任意添加你需要的字符 
    if($type){
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    }else{
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`=,.;:/?|'; 
    }
    $password = ''; 
    for ( $i = 0; $i < $length; $i++ ) 
    { 
        // 这里提供两种字符获取方式 
        // 第一种是使用 substr 截取$chars中的任意一位字符； 
        // 第二种是取字符数组 $chars 的任意元素 
        // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1); 
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ]; 
    } 
    return $password; 
}


function sendJson($code,$msg='',$data=array()){
    //$data = array_values($data);
    $array = ['code'=>$code,'message'=>$msg,'data'=>$data];
    echo json_encode($array,true);
    die;
}

/**
 * 权限检测
 * @param $rule
 */
function authCheck($rule)
{
    $control = explode('/', $rule)['0'];
    //如果允许login与index不需要权限控制,写在这里
    if(in_array($control, ['login'])){
        return true;
    }
    $control_name = $control;
    $action_name = explode('/', $rule)['1'];

      //如果没有权限名称则返回false
    $is_have = db('node')->where(['control_name'=>$control_name,'action_name'=>$action_name])->find();
    if (!$is_have){
        return false;
    }


          if(is_array(session('rule_shang'))) {

              if (in_array($rule, session('rule_shang'))) {

                  return true;
              }

          }

    return false;
}

/**
 * 时间转换
 */
function time_to_date($s) {
    if($s<=3600){
        return floor($s / 60).'分钟';
    }else if($s>3600){
        return round($s / 3600,2).'小时';
    }
}

function bankInfo($card) {
    $bankList = json_decode(file_get_contents(ROOT_PATH.'public/static/BankName.json'),true);
    $card_8 = substr($card, 0, 8);
    if (isset($bankList[$card_8])) {
        return $bankList[$card_8];
    }
    $card_6 = substr($card, 0, 6);
    if (isset($bankList[$card_6])) {
        return $bankList[$card_6];
    }
    $card_5 = substr($card, 0, 5);
    if (isset($bankList[$card_5])) {
        return $bankList[$card_5];
    }
    $card_4 = substr($card, 0, 4);
    if (isset($bankList[$card_4])) {
        return $bankList[$card_4];
    }
    return '未识别';
}

/**
 * @param $birthday 出生时间 uninx时间戳
 * @param $time 当前时间
 */
function get_age($birthday) {
    $birthday = strtotime($birthday);
    //格式化出生时间年月日
    $byear  = date('Y',$birthday);
    $bmonth = date('m',$birthday);
    $bday   = date('d',$birthday);
    //格式化当前时间年月日
    $tyear  = date('Y');
    $tmonth = date('m');
    $tday   = date('d');
    //开始计算年龄
    $age    = $tyear - $byear;
    if($bmonth>$tmonth || $bmonth==$tmonth && $bday>$tday){
        $age--;
    }
    return $age;
}

/**
 * 图片上传（上传到oss）
 * @param string name 请求上传图片的字段名称
 * @param array imageNames 可上传的字段集合
 * @param array imgPath 字段集合对应的相对地址
 * @param array imgs 支持多图片上传的字段
 * @return array ['success','code','message','filename']
 */
function put_oss($name, $imgPath, $imgs = []) {
    $request = Request::instance();
    $path = date('Ymd').'/';
    $imageNames = array_keys($imgPath);
    if(!in_array($name, $imageNames)){
        return ['success'=>false,'code'=>'006','message'=>'抱歉，不能上传这个类型的图片'];
    }

    $photo = $request->file($name);

    $auth = new QiniuAuth(Env::get('qiniu.accesskey'),Env::get('qiniu.secretkey'));
    $bucket = Env::get('qiniu.clinicbucket');
    $token = $auth->uploadToken($bucket);
    $uploadMgr = new UploadManager();

    if(in_array($name, $imgs)){ // 多图片

        $filenames = '';

        foreach ($photo as $img) {

            $imgValid = Validate::is($img,'image');

            if($imgValid !== true){
                return ['success'=>false,'code'=>'002','message'=>'请上传图片格式！'];
            }

            $ext = pathinfo($img->getInfo('filename')['name'])['extension'];
            
            $filename = md5(generate_rand(15).time().uniqid()).'.'.$ext;

            list($res,$error) = $uploadMgr->putFile($token,$imgPath[$name].$path.$filename,$img->getInfo('filename')['tmp_name']);

            if($error!=null){
                return ['success'=>false,'code'=>'008','message'=>$error->getResponse()->error];
            }

            $filenames .= $imgPath[$name].$path.$filename.',';

        }
        $filenameEnd = rtrim($filenames,',');
    }else{ // 单张图

        $photoValid = Validate::is($photo,'image');

        if($photoValid !== true){
            return ['success'=>false,'code'=>'002','message'=>'请上传图片格式！'];
        }

        $ext = pathinfo($photo->getInfo('filename')['name'])['extension'];

        $filename = md5(generate_rand(15).time().uniqid()).'.'.$ext;

        list($res,$error) = $uploadMgr->putFile($token,$imgPath[$name].$path.$filename,$photo->getInfo('filename')['tmp_name']);

        if($error!=null){// 图片上传失败结果
            return ['success'=>false,'code'=>'008','message'=>$error->getResponse()->error];
        }
        $filenameEnd = $imgPath[$name].$path.$filename;
    }
    return ['success'=>true,'code'=>'000','message'=>'上传完成','filename'=>$filenameEnd];
}
/**
 * 整理出tree数据 ---  layui tree
 * @param $pInfo
 * @param $spread
 */
function getTree($pInfo, $spread = true)
{

    $res = [];
    $tree = [];
    //整理数组
    foreach($pInfo as $key=>$vo){

        if($spread){
            $vo['spread'] = true;  //默认展开
        }
        $res[$vo['id']] = $vo;
        $res[$vo['id']]['children'] = [];
    }
    unset($pInfo);

    //查找子孙
    foreach($res as $key=>$vo){
        if(0 != $vo['pid']){
            $res[$vo['pid']]['children'][] = &$res[$key];
        }
    }

    //过滤杂质
    foreach( $res as $key=>$vo ){
        if(0 == $vo['pid']){
            $tree[] = $vo;
        }
    }
    unset( $res );

    return $tree;
}
/**
 * 对象转换成数组
 * @param $obj
 */
function objToArray($obj)
{
    return json_decode(json_encode($obj), true);
}
//秒数转变成分钟:秒
function secondToStr($times){
    $result = '0';
    if ($times>0) {
        $minute = floor($times/60);
        if ($times%60 == 0)
        {
            return $minute;
        }
        $second = ceil(($times - 60 * ($minute-1)) % 60);
        if ($second < 10)
        {
            $second = str_pad($second,2,"0" ,STR_PAD_LEFT);
        }
        $result = $minute.':'.$second;
    }else{
        $times = abs($times);
        $minute = floor($times/60);
        if ($times%60 == 0)
        {
            return '-'.$minute;
        }
        $second = ceil(($times - 60 * ($minute-1)) % 60);
        if ($second < 10)
        {
            $second = str_pad($second,2,"0" ,STR_PAD_LEFT);
        }
        $result = '-'.$minute.':'.$second;
    }
    return $result;
}
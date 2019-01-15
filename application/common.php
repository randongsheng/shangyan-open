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
    if(in_array($control, ['login', 'index'])){
        return true;
    }
    if(in_array($rule, session('rule'))){
        return true;
    }

    return false;
}

/**
 * 时间转换
 */
function time_to_date($s) {
    if($s<=3600){
        return floor($s / 60).'分';
    }else if($s>3600){
        return round($s / 3600,2).'时';
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
 * @param  $birthday 出生时间 uninx时间戳
 * @param  $time 当前时间
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
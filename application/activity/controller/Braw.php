<?php
namespace app\activity\controller;

use think\Db;
use think\Loader;

class Braw
{
    /**
     * 判断用户是否已经抽过签了
     */
    public function checkIsToDeaw()
    {
        $openid = input('post.openid');
        $date = date('Y-m-d');
        $user = Db::name('draw_user')->where(['openid'=>$openid,'date'=>$date])->find();
        //判断用户是否中过奖并且有没有绑定手机号
        $need = Db::name('draw_user')->where(['openid'=>$openid,'draw_id'=>['in',[15,16,30]],'mobile'=>0])->find();
        if ($need){
            if ($need['draw_id'] = 15){
                $need_bind_mobile = 2;
            }
            if ($need['draw_id'] = 16){
                $need_bind_mobile = 1;
            }
            if ($need['draw_id'] = 30){
                $need_bind_mobile = 3;
            }

        }else{
            $need_bind_mobile = 0;
        }
        if ($user){
            $data = Db::name('draw')->field('draw_id,draw_name,draw_explain,draw_template')->where(['draw_id'=>$user['draw_id']])->find();
            $data['need_bind_mobile'] = $need_bind_mobile;
            sendJson(-2,'今天已经抽过奖了,明天再来试吧',$data);
        }else{
            sendJson(1,'可以抽签');
        }
    }
    /**
     * 抽签动作
     */
    public function toDraw()
    {
        $openid = input('post.openid');
        $date = date('Y-m-d');
        $baseNum = mt_rand(1,10);
        //判断用户是否openid是否重复
        $user = Db::name('draw_user')->where(['openid'=>$openid,'date'=>$date])->find();
        if ($user){
            sendJson(-2,'今天已经抽过奖了,明天再来试吧');
        }
        $draw = '';
//        if ($date == '2019-02-05')
//        {
//            $is_ok = \db('draw_user')->where(['draw_id'=>15,'date'=>$date])->find();
//            if ($is_ok){
//                $baseNum = 1;
//            }
//            if ($baseNum == 15)
//            {
//                $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>$baseNum])->find();
//            }
//        }elseif($date == '2019-02-06'){
//            $is_ok = \db('draw_user')->where(['draw_id'=>15,'date'=>$date])->find();
//            if ($is_ok){
//                $baseNum = 1;
//            }
//            if ($baseNum == 15)
//            {
//                $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>$baseNum])->find();
//            }
////            $draw = Db::name('draw')->where(['draw_status'=>1])->whereNotIn('draw_id',[15,16])->orderRaw('rand()')->find();
//        }elseif($date == '2019-02-07'){
//            $is_ok = \db('draw_user')->where(['draw_id'=>15,'date'=>$date])->find();
//            if ($is_ok){
//                $baseNum = 1;
//            }
//            if ($baseNum == 15)
//            {
//                $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>$baseNum])->find();
//            }
//        }elseif($date == '2019-02-08'){
//            $is_ok = \db('draw_user')->where(['draw_id'=>15,'date'=>$date])->find();
//            if ($is_ok){
//                $baseNum = 1;
//            }
//            if ($baseNum == 15)
//            {
//                $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>$baseNum])->find();
//            }
//        }elseif($date == '2019-02-09'){
//            $is_ok = \db('draw_user')->where(['draw_id'=>15,'date'=>$date])->find();
//            if ($is_ok){
//                $baseNum = 1;
//            }
//            if ($baseNum == 15)
//            {
//                $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>$baseNum])->find();
//            }
//        }else{
//            $draw = '';
//        }
        //判断用户是否中过奖
        $is_drawed = Db::name('draw_user')->where(['openid'=>$openid,'draw_id'=>['in',[15,16,30]]])->find();
//        if ($baseNum == 15){
//            $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>15])->find();
//        }
        if ($baseNum == 1){
            $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>30])->find();
        }
        if ($is_drawed){
            $draw = '';
        }
        if (!$draw){
            $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>['not in',[15,16,30]]])->orderRaw('rand()')->find();
        }
//        if ($openid == 'ogl191SRBfwz2cmaeSE1DgK-rGYA'){
//            $draw = Db::name('draw')->where(['draw_status'=>1,'draw_id'=>30])->find();
//        }

        Db::startTrans();
        $where = [
            'draw_hit_count'=>$draw['draw_hit_count'],
            'draw_id'=>$draw['draw_id']
        ];
        $data = ['draw_hit_count'=>$draw['draw_hit_count']+1];
        $result = Db::name('draw')->where($where)->update($data);
        if ($result){
            if ($draw['draw_id'] == 16)
            {
                Db::name('draw')->where(['draw_id'=>$draw['draw_id']])->update(['draw_status'=>0]);
            }
            if ($draw['draw_id'] == 15 && $draw['draw_hit_count']==4)
            {
                Db::name('draw')->where(['draw_id'=>$draw['draw_id']])->update(['draw_status'=>0]);
            }
            if ($draw['draw_id'] == 30 && $draw['draw_hit_count']==49)
            {
                Db::name('draw')->where(['draw_id'=>$draw['draw_id']])->update(['draw_status'=>0]);
            }

            Db::name('draw_user')->insert(['openid'=>$openid,'nickname'=>$openid,'draw_id'=>$draw['draw_id'],'date'=>$date,'addtime'=>date('Y-m-d H:i:s')]);
            Db::commit();
            sendJson(1,'抽签成功',$draw);
//            return $draw;
        }else{
            Db::rollback();
            sendJson(-1,'网络错误');
        }
    }
    /**
     * 绑定中奖用户的手机号
     */
    public function bindMobile()
    {
        $mobile = input('post.mobile');
        $code = input('post.code');
        $openid = input('post.openid');
        $date = date('Y-m-d');
        //验证短信验证码
        controller('tool/tools','controller')->checkSmscode($mobile,$code);
        $where = ['openid'=>$openid,'date'=>$date];
        $draw = \db('draw_user')->where($where)->find();
        if (!$draw)
        {
            sendJson(-1,'今天还没抽奖呢');
        }
        \db('draw_user')->where($where)->update(['mobile'=>$mobile]);
        sendJson(1,'绑定成功');
    }
    /**
     * 运营获取抽奖结果
     */
    public function drawResult()
    {
        $gifts = \db('draw_user')->field('draw_id,mobile')->where(['draw_id'=>['in',[15,16,30]]])->select();
        foreach ($gifts as $key => $value) {
            $giftname = '';
            if ($value['draw_id'] == 15){
                $giftname = '锦鲤大奖';
            }
            if ($value['draw_id'] == 16){
                $giftname = '心想事成奖';
            }
            if ($value['draw_id'] == 30){
                $giftname = '尚言历';
            }
            $gifts[$key]['mobile'] = $value['mobile'];//substr($value['mobile'],0,3).'****'.substr($value['mobile'],7);
            $gifts[$key]['giftname']=$giftname;
        }
        $json = json_encode($gifts);
        $djson =  json_decode($json,true);
        $str = '';
        foreach ($djson as $v){
            $str .= $v['mobile'].'|'.$v['giftname'].'/n';
        }
//        $data = var_export($gifts,true);
        $this->gettxt('draw.txt',$str);
//        sendJson(1,'抽奖结果',$gifts);
    }
    /**
    * 活动统计
    */
    public function tongji(){
    	 $total = db('draw_user')->count();
    	 $str = '活动总人次:'.$total."|";
    	 $day = db('draw_user')->field("date,count('openid') as num ")->group('date')->select();
    	 $totalnum = Db::name('draw_user')->group('openid')->count();
    	 foreach ($day as $v){
    	 		$str .= $v['date']."|".$v['num']."&";
    	 	}
    	 	$str .= "覆盖人数:".$totalnum;
    	 	$this->gettxt('tongji.txt',$str);
    	}

    /**
     * txt格式文件
     *
     */
    public function gettxt($filename,$content){
        header("Content-Type: application/octet-stream");
        if (preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT']) ) {
            header('Content-Disposition:  attachment; filename="' . $filename . '"');
        } elseif (preg_match("/Firefox/", $_SERVER['HTTP_USER_AGENT'])) {
            header('Content-Disposition: attachment; filename*="utf8' .  $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' .  $filename . '"');
        }
        echo $content;
    }
    /**
     * 循环测试
     */
    public function forDarw()
    {
//        $i = mt_rand(1,10000);
//        $draw = $this->toDraw($i);
//        print_r($draw);
//        set_time_limit(0);
//        for ($i=0;$i<1000;$i++){
//            $draw = $this->toDraw($i);
//            if ($draw['draw_gift'] == 1){
//                echo $i;
//            }
//        }

    }
    /**
     * 分享数据
     */
    public function shareDraw()
    {
        Loader::import("jssdk",EXTEND_PATH);
        $jssdk = new \jssdk("wx9eb026f5850bff04", "50a56fecbd67bcb4b22a604cef28983a");
        $signPackage = $jssdk->GetSignPackage();
        print_r($signPackage);
    }
//    /**
//     * 绘制海报
//     */
//    public function createPoster()
//    {
//        $image = Image::open('F:\phpstudy\PHPTutorial\WWW\tp\public\static/draw/1.jpg');
//        $image->text('十|年|磨|一|剑| - |为|A|P|I|开|发|设|计|的|高|性|能|框|架','ygyxsziti2.0.ttf',30,'#ffffff',\Think\Image::WATER_NORTHEAST,[-20,20])->save('text_image.png');
////        $_FILES['image'] = file('text_image.png');
//        print_r($_FILES);
////        print_r();
//        die;
//        $this->uploadImage();
////        $image = \think\Image::open('./image.png');
////        $img = imagecreatefromjpeg('F:\phpstudy\PHPTutorial\WWW\tp\public\static/draw/1.jpg');
////        // 输出图像并释放内存
////        header('Content-type: image/jpeg');
////        $name = md5(date('YmdHis')).'.jpg';
////        imagejpeg($img,"static/draw_pic/".$name);
////        imagedestroy($img);
//    }
//    public function goUploadImage()
//    {
//
//            $logourl = $this->uploadImage();
//            if($logourl == -1){
//            sendJson(-1,'上传失败',$logourl);
//            }
//            sendJson(1,'上传成功',$logourl);
//
//    }
//    /**
//     * 七牛上传
//     */
//    public function uploadImage()
//    {
//        $accessKey = '2PE43Z0Y1XP-rBlzm5TDlnF5LdW-w1Yj6YUTp-Wq';//'LmqTmjDgkw9jlDdos17lLBZ-3BimlCH-uO1wTqaE';//$config['accessKey'];
//        $secretKey = 'IubRBvLUDRSpd9ZXx8tp9swB0ZFh3kudItNdk7K9';//'BFjJHhnwd5zMsJMcu8iUGftg7kDja91eztAo6BDh';//$config['secretKey'];
//        $bucket = 'activity';//$config['bucket'];// 要上传的空间
//        try{
//            $qiniu = new Qiniu($accessKey,$secretKey,$bucket);
//            $name = md5(date('YmdHis')).'.png';
//            $result = $qiniu->upload($name);
//            print_r($result);
//        }catch (Exception $e){
//            dump($e->getMessage());
//        }
//    }

}

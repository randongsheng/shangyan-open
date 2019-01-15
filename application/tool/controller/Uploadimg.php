<?php
namespace app\tool\controller;
use think\Controller; 
use \think\Request;
use think\File;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
class Uploadimg extends Controller
{
	public function uploadImg(){
		//$image = request()->file('image');
        $filePath = $_FILES['image']['tmp_name'];
    	if($filePath){
//            $str=var_export($image,TRUE);
//            file_put_contents("/home/back.chineselvyou.com/application/admin/controller/upload1.txt",$str,FILE_APPEND);
    		$logourl = $this->Qiniu_upload($filePath);
    		//$logourl = $this->upload($image,'image');
	    	if($logourl == -1){
	    		sendJson(-1,'上传失败',$logourl);
	    	}
	    	sendJson(1,'上传成功',$logourl);
    	}else{
    		sendJson(-1,'未接收到文件');
    	}
	}
	//单文件上传
    public function upload($file,$dirname){
	    // 获取表单上传文件 例如上传了001.jpg 
	    // image必须是单一对象input标签name要为image;
	    // $file = request()->file('image');
	    
	    // 移动到框架应用根目录/public/uploads/ 目录下
	    if($file){
	        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/'.$dirname);
	        if($info){
	            // 成功上传后 获取上传信息
	            // 输出 uploads/20181020/2e8cdf9cdea009c7981f0fb843446a8c.png
	           $url = 'https://'.$_SERVER['HTTP_HOST'].'/public/uploads/'.$dirname.'/'.$info->getSaveName(); 
	           return  [1,$url]; 
	        }else{
	            // 上传失败获取错误信息
	            return [-1,$file->getError()];
	        }
	    }
	}
	/**
     * 七牛上传
     */
    public function Qiniu_upload($filePath)
    {
    	vendor('qiniu.autoload');
        //vendor('qiniu.autoload.php');
        //$config = Config::get('UPLOAD_Qiniu_CONFIG');
        $accessKey = 'LmqTmjDgkw9jlDdos17lLBZ-3BimlCH-uO1wTqaE';//$config['accessKey'];
        $secretKey = 'BFjJHhnwd5zMsJMcu8iUGftg7kDja91eztAo6BDh';//$config['secretKey'];
        $auth = new Auth($accessKey, $secretKey);
        $bucket = 'cloud';//$config['bucket'];// 要上传的空间
        $token = $auth->uploadToken($bucket);// 生成上传 Token
        // 要上传文件的本地路径
        //$filePath = $_FILES['image']['tmp_name'];
        //$file = new File($filePath);
        // $info = $file->webuploader_move(ROOT_PATH . 'public' . DS . 'uploads');//本地上传

        // 上传到七牛后保存的文件名
        // if($info){
        //     $key = $info->getFilename();
        // }else{
            $key = md5(time()).'.png';
        // }
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err === null) {
            $data['url'] = $ret['key'];//$config['domain'].$ret['key'];
            return $data['url'];
        }
            return false;
	}
	/**
 * 图片上传（上传到oss）
 * @param string name 请求上传图片的字段名称
 * @param array imageNames 可上传的字段集合
 * @param array imgPath 字段集合对应的相对地址
 * @param array imgs 支持多图片上传的字段
 * @return array ['success','code','message','filename']
 */
// function put_oss($name, $imgPath, $imgs = []) {
//     $request = Request::instance();
//     $path = date('Ymd').'/';
//     $imageNames = array_keys($imgPath);
//     if(!in_array($name, $imageNames)){
//         return ['success'=>false,'code'=>'006','message'=>'抱歉，不能上传这个类型的图片'];
//     }

//     $photo = $request->file($name);

//     $auth = new QiniuAuth(Env::get('qiniu.accesskey'),Env::get('qiniu.secretkey'));
//     $bucket = Env::get('qiniu.clinicbucket');
//     $token = $auth->uploadToken($bucket);
//     $uploadMgr = new UploadManager();

//     if(in_array($name, $imgs)){ // 多图片

//         $filenames = '';

//         foreach ($photo as $img) {

//             $imgValid = Validate::is($img,'image');

//             if($imgValid !== true){
//                 return ['success'=>false,'code'=>'002','message'=>'请上传图片格式！'];
//             }

//             $ext = pathinfo($img->getInfo('filename')['name'])['extension'];
            
//             $filename = md5(generate_rand(15).time().uniqid()).'.'.$ext;

//             list($res,$error) = $uploadMgr->putFile($token,$imgPath[$name].$path.$filename,$img->getInfo('filename')['tmp_name']);

//             if($error!=null){
//                 return ['success'=>false,'code'=>'008','message'=>$error->getResponse()->error];
//             }

//             $filenames .= $path.$filename.',';

//         }
//         $filenameEnd = rtrim($filenames,',');
//     }else{ // 单张图

//         $photoValid = Validate::is($photo,'image');

//         if($photoValid !== true){
//             return ['success'=>false,'code'=>'002','message'=>'请上传图片格式！'];
//         }

//         $ext = pathinfo($photo->getInfo('filename')['name'])['extension'];

//         $filename = md5(generate_rand(15).time().uniqid()).'.'.$ext;

//         list($res,$error) = $uploadMgr->putFile($token,$imgPath[$name].$path.$filename,$photo->getInfo('filename')['tmp_name']);

//         if($error!=null){// 图片上传失败结果
//             return ['success'=>false,'code'=>'008','message'=>$error->getResponse()->error];
//         }
//         $filenameEnd = $path.$filename;
//     }
//     return ['success'=>true,'code'=>'000','message'=>'上传完成','filename'=>$filenameEnd];
// }
}
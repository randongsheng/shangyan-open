<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Loader;
class Index extends Controller
{
//	public function test(){
//		Loader::import("PhpServerSdk.TimRestApi",EXTEND_PATH);
//		$api = new \TimRestAPI();
//		$sdkappid = 1400163119;
//		$identifier = 'admin';
//		$api->init($sdkappid, $identifier);
//		$tool_path = 'F:\phpstudy\PHPTutorial\WWW\tp5.0\extend\PhpServerSdk\signature\windows-signature64.exe';
//		$protected_key_path = '';
//		$sig = $api->generate_user_sig($identifier, 86400, 'private.pem', $tool_path);
//		print_r($sig);
//	}
    public function index()
    {
//        echo 123;
        echo ROOT_PATH.'/dist/index.html';
    }

    public function jinru()
    {
        $this->redirect('http://back.com/dist/index.html',302);
    }
}
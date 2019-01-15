<?php
namespace app\appconfig\controller;
use think\Controller;
use think\Db;
class Clinicapp extends Controller
{
	//获取资源位1列表
    public function getResource1()
    {
    	controller('Userapp', 'controller')->getBanner(4);
    }
    //获取资源位2列表
    public function getResource2()
    {
    	controller('Userapp', 'controller')->getBanner(5);
    }
	//添加资源位1
    public function addResource1(){
    	$count = Db::name('banner')->where(['position'=>4])->count();
    	if($count>9){
    		sendJson(-1,'资源位1不能超过10个');
    	}
    	$data = input('post.');
    	$res = controller('Userapp', 'controller')->addBanner($data,4);
    	if ($res) {
    		sendJson(1,'添加资源位1成功');
    	}else{
    		sendJson(-1,'添加资源位1失败');
    	}
    }
    //添加资源位2
    public function addResource2(){
    	$count = Db::name('banner')->where(['position'=>5])->count();
    	if($count>9){
    		sendJson(-1,'资源位2不能超过10个');
    	}
    	$data = input('post.');
    	$res = controller('Userapp', 'controller')->addBanner($data,5);
    	if ($res) {
    		sendJson(1,'添加资源位2成功');
    	}else{
    		sendJson(-1,'添加资源位2失败');
    	}
    }
    //保存提交的修改
    //保存修改
    public function saveBanner(){
    	$data = input('post.');
    	$id = input('post.id');
    	if (!$id) {
    		sendJson(-1,'没有获取到id');
    	}
    	$res = Db::name('banner')->where(['id'=>$id])->update($data);
    	print_r($res);
    	if ($res) {
    		sendJson(1,'修改成功');
    	}
    }
}
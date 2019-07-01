<?php
namespace app\index\controller;
use think\Request;
use app\index\model\Admin;
use app\index\model\User as UserModel;
use app\index\model\Teacher;
use app\index\model\Clinic;
use app\index\model\Blacklist;
use think\Session;
use think\Db;
use think\Validate;

class Test extends Base
{

    public function index()
    {
        echo  '测试成功,继续测试ssh';

    }



}
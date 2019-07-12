<?php
namespace app\index\controller;
use app\index\service\InnerMail;
use think\Cache;
use think\cache\driver\Redis;
use think\Request;
use app\index\model\Admin;
use app\index\model\User as UserModel;
use app\index\model\Teacher;
use app\index\model\Clinic;
use app\index\model\Blacklist;
use think\Session;
use think\Db;
use think\Validate;
use module\RedisOp;

class Test extends Base
{

    public function index()
    {


        \session('uid',Input('i',null));

        echo \session('uid');
    }


    public function ii()
    {

        echo phpinfo();

    }

    public function oo()
    {






    }



}
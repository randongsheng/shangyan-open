<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Env;

class Upload extends Common
{





    public function uploadImgs()
    {

        // 执行上传
        return json(put_oss(input('post.name'), config('IMG')));




    }
}
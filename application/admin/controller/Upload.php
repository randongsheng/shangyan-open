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
        $name = input('post.name');



        $imgPath = config('IMG');
        // 执行上传

        $filename = put_oss($name, $imgPath);

            return json($filename);


    }
}
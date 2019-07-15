<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------


// 跨域资源共享
$ori = empty($_SERVER['HTTP_ORIGIN'])?'*':$_SERVER['HTTP_ORIGIN'];
header('Access-Control-Allow-Origin: '.$ori);
if($ori!='*'){header("Access-Control-Allow-Credentials:true");}
header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE');
header('Access-Control-Allow-Headers:Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    echo 'options成功';
    die;
}
// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

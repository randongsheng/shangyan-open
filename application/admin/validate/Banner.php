<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/26
 * Time: 15:35
 */

namespace app\admin\validate;

use think\Validate;
class Banner extends Validate
{
    protected $rule = [
        'picurl' => 'require'
    ];
    protected  $message = [
        'picurl.require' => '图片地址必须填写'
    ];
}
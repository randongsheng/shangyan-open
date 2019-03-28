<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;
use app\admin\model\BaseModel;
class OrderModel extends BaseModel
{
   // 确定链接表名
    protected $name = 'order';
//    public function getCompletionTimeAttr($value)
//    {
////        $value = '';
//        return date('Y-m-d H:i:s',$value);
//    }
//    public function getPaytimeAttr($value)
//    {
////        $value = '';
//        return date('Y-m-d H:i:s',$value);
//    }
}
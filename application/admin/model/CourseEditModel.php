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
class CourseEditModel extends BaseModel
{
   // 确定链接表名
    protected $table = 'sy_course_edit_log';

    public function getCreateAtAttr($value)
    {

        return date('Y-m-d H:i:s',$value);
    }

    public function course()
    {

        return $this->hasOne('CourseModel','id','course_id')->field('id,title,keywords');
    }
}
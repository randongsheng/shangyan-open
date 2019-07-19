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
class EveModel extends BaseModel
{
   // 确定链接表名
    protected $table = 'sy_eve';

    public function getCreateAtAttr($value)
    {

        return date('Y-m-d H:i:s',$value);
    }


    public function any()
    {

        if($this->range==1){
            return $this->hasOne('TeacherModel','id','range_id')->field('id,name');
        }
        elseif ($this->range==2){
            return $this->hasOne('AdminModel','admin_id','range_id')->field('admin_id,name');
        }
        else{
            return $this->hasOne('CourseModel','id','range_id')->field('id,title');
        }


    }



    public function getKeywordsAttr($value){//处理数组
        return explode(',',$value);
    }

    public function teacher()//关联老师
    {

            return $this->hasOne('TeacherModel','id','range_id')->field('id,name');


    }

    public function admin()//关联机构
    {
        return $this->hasOne('AdminModel','admin_id','range_id')->field('admin_id,name');

    }

    public function course()//关联课程
    {
        return $this->hasOne('CourseModel','id','range_id')->field('id,title');

    }
}
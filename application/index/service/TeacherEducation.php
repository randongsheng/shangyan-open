<?php
// +----------------------------------------------------------------------
// | 尚言
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2028 http://www.chineselvyou.com
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: SC-CG <sc-cg.cn>
// +----------------------------------------------------------------------
namespace app\index\service;

use think\Model;
use think\Validate;
use app\index\model\Teacher;

/**
 * 老师资质
 */
class TeacherEducation extends Model
{
	protected $pk = 'education_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_teacher_education';

    public function getEduAttr($value,$data)
    {
        $edu = [0=>'其他',1=>'中专',2=>'高中',3=>'专科',4=>'本科',5=>'硕士研究生',6=>'博士研究生'];
        return $edu[$data['education_level']];
    }

    /**
     * 转换时间格式
     */
    public function getCreateAtAttr($value)
    {
        if(!$value) return $value;
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转换时间格式
     */
    public function getUpdateAtAttr($value)
    {
        if(!$value) return $value;
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 图片
     */
    public function getEducationPhotoAttr($value)
    {
        if(Validate::is($value,'url')) return $value;
        
        return config('save_protocol').config('save_url').ltrim($value,'/');
    }
}
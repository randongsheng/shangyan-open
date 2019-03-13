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
use app\index\model\Teacher;

/**
 * 老师资质
 */
class TeacherCertificate extends Model
{
	protected $pk = 'certificate_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_teacher_certificate';

    /**
     * 获取某个老师的资质title
     */
    public function getTitle($teacherId)
    {
        return TeacherCertificate::where('teacher_id',$teacherId)->column('certificate_name');
    }

    /**
     * 时间转换
     */
    public function getCreateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 时间转换
     */
    public function getUpdateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
}
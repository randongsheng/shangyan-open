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
namespace app\admin\model;

use think\Model;

/**
 * 
 */
class TeacherCertificateModel extends Model
{

    protected $table = 'sy_teacher_certificate';


    public function getCreateAtAttr($value)
    {

        return date('Y-m-d H:i:s',$value);

    }



}
<?php 

namespace app\index\validate;

use think\Validate;

class BlacklistValidate extends Validate
{
    protected $rule = [
        'black_reason|理由'=>'require',
        'clinic_id|机构ID'=>'require',
        'user_id|用户ID'=>'require',
        'teacher_id|老师ID'=>'require',
        'black_reason_other|其他理由'=>'max:150',
    ];

    protected $message  =   [ ];

    protected $scene = [
        'clinic'=>['black_reason','clinic_id','black_reason_other'],
        'user'=>['black_reason','user_id','black_reason_other'],
        'teacher'=>['black_reason','teacher_id','black_reason_other'],
    ];
}
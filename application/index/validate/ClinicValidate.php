<?php 

namespace app\index\validate;

use think\Validate;

class ClinicValidate extends Validate
{
    protected $rule = [
        'rule_title|标题'=>'require|chsDash|max:150',
        'rule_content|内容'=>'require',
        'rule_module_name|导航名称'=>'chsDash|max:100',
        'clinic_id|机构ID'=>'require',
        'type|类型'=>'require',
        'number_email'=>'require|email',
        'password'=>'require|length:6,16',
        'repassword'=>'require|confirm:password',
    ];

    protected $message  =   [
        'rule_title.require' => '标题必填',
        'rule_title.max'     => '标题最长不能超过150个字符',
        'rule_title.chsDash' => '标题只能是汉字、字母、数字和_-',
        'rule_content.require'=> '你是不是忘了写内容？', 
         /*------注册表单------*/
        'number_email.require' => '请填写邮箱地址',
        'number_email.email'   => '请填写正确的邮箱地址',
        'password.require' => '请填写密码',
        'password.length'  => '密码长度6-16位',
        'password.confirm' => '请确认密码是否一致',
        'repassword.require' => '请确认密码是否一致',
        'repassword.confirm' => '请确认密码是否一致',
    ];

    protected $scene = [
        // 规则
        'rule'=>['rule_title','rule_content','rule_module_name'],
        // 审核
        'shelf'=>['clinic_id','type'],
        // 账号
        'account'=>['number_email','password','repassword'],
    ];
}
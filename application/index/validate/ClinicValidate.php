<?php 

namespace app\index\validate;

use think\Validate;
use app\index\model\Clinic;

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
        /*------空间基本信息start------*/
        'clinic_name'=>'require|chsDash',
        'logo_no'=>'checkFileStr',
        'business_license_no'=>'checkFileStr',
        'found_time'=>'require',
        'introduce|机构描述'=>'require|max:500',
        /*------运营人员信息start------*/
        'operator_name'=>'require|chsDash',
        'operator_identity_A_no'=>"checkFileStr",
        'operator_identity_B_no'=>"checkFileStr",
        'operator_tel'=>"require|max:11|regex:/^1[3-9]{1}[0-9]{9}$/",
        'operator_ycode'=>"require",
        /*------法人信息start------*/
        'liable_name'=>"require|chsDash",
        'liable_identity_A_no'=>"checkFileStr",
        'liable_identity_B_no'=>"checkFileStr",
        'liable_tel'=>"require|max:11|regex:/^1[3-9]{1}[0-9]{9}$/",
        'liable_ycode'=>"require",
        /*------场地信息start------*/
        'address'=>"require|max:150",
        'full_address|详细地址'=>"require|max:150",
        'scene_photo_no'=>'require',
        /*------其他信息start------*/
        'related_name'=>'chsDash',
        'related_desc'=>'max:300',
        'related_link'=>'url',
        'related_photo_no'=>'require',
        'related_id'=>'require',
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
        /*------机构的基本信息------*/
        'clinic_name.require'=>'机构名称必填',
        'clinic_name.chsDash'=>'机构名称不包括特殊字符',
        'logo_no.checkFileStr'=>'请上传logo图片2',
        'business_license_no.checkFileStr'=>'营业执照必传',
        'found_time.require'=>'请填写机构成立时间',
        /*------运营人员------*/
        'operator_name.require'=>'运营人员姓名必填',
        'operator_name.chsDash'=>'运营人员姓名不可存在特殊字符',
        'operator_identity_A_no.checkFileStr'=>'运营人员正面身份证必传',
        'operator_identity_B_no.checkFileStr'=>'运营人员反面身份证必传',
        'operator_tel.require'=>'运营人员手机号码必填',
        'operator_tel.max'=>'手机号码格式不正确',
        'operator_tel.regex'=>'手机号码格式不正确',
        'operator_ycode.require'=>'验证码必填',
        /*------法人------*/
        'liable_name.require'=>'法人姓名必填',
        'liable_name.chsDash'=>'法人姓名不可存在特殊字符',
        'liable_identity_A_no.checkFileStr'=>'请上传法人身份证正面后提交',
        'liable_identity_B_no.checkFileStr'=>'请上传法人身份证反面后提交',
        'liable_tel.require'=>'法人手机号码必填',
        'liable_tel.max'=>'手机号码格式不正确',
        'liable_tel.regex'=>'手机号码格式不正确',
        'liable_ycode.require'=>'验证码必填',
        /*------场地信息------*/
        'address.require'=>'请填写诊所地址',
        /*------其他资料------*/
        'related_name.chsDash'=>'其他信息名称不可存在特殊字符',
        'related_desc.max'=>'其他信息描述字数不超过300',
        'related_link.url'=>'相关资料必须是网址',
        'related_photo_no.require'=>'资料照片必须上传',
    ];

    protected $scene = [
        'clinic_info_all'=>[
            'clinic_name','logo_no','business_license_no','found_time',
            'operator_name','operator_identity_A_no','operator_identity_B_no','operator_tel','operator_ycode',
            'liable_name','liable_identity_A_no','liable_identity_B_no','liable_tel','liable_ycode',
            'address','full_address','introduce','city','clinic_id',
        ],
        'related' =>  ['related_name','related_desc','related_link'],
        // 规则
        'rule'=>['rule_title','rule_content','rule_module_name'],
        // 审核
        'shelf'=>['clinic_id','type'],
        // 账号
        'account'=>['number_email','password','repassword'],
        // 
        'regster'  =>  ['number_email','password','repassword',/*'ycode'*/],
        'clinic' =>  ['clinic_name','found_time'],
        'operator' =>  ['operator_name','operator_tel'],
        'liable' =>  ['liable_name','liable_tel'],
        'scene' =>  ['address','full_address','city',],
    ];

    protected function checkFileStr($value,$rule,$data)
    {
        $clinicId = $data['clinic_id'];
        $clinic = new Clinic;
        $clinicData = $clinic->get($clinicId);
        if($clinicData['status']==0){
            if(empty($value)){
                return '请上传图片后提交';
            }
        }
        return true;
    }
}
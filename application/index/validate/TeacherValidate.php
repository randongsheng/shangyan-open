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
namespace app\index\validate;

use think\Validate;
use app\index\model\Teacher;

class TeacherValidate extends Validate
{
	protected $rule = [
        /*------老师基本信息------*/
        'teacher_name'=>"require|chsDash",
        'sex'=>'require|in:0,1,2',
        'birthday'=>'require|dateFormat:Y-m-d',
        'enter_date'=>'require|dateFormat:Y-m',
        'province'=>'chsDash',
        'city'=>'require',
        'tel'=>'require|regex:/^1[3-9]{1}[0-9]{9}$/',
        /*------专业信息------*/
        'listen_label|倾听标签'=>'array|checkListenLabel',
        'consult_label|咨询标签'=>'array|checkConsultLabel',
        'certificate_name|证书名称'=>'require|chsDash',
        'certificate_no|证书号'=>'require|alphaNum',
        'award_date|发证时间'=>'require|dateFormat:Y-m-d',
        'certificate_id'=>'require',
        'consult_number|个案咨询数量'=>'number',
        'consult_duration|个案咨询时长'=>'number',
        'team_consult_number|团队个案咨询数量'=>'number',
        'team_consult_duration|团队个案咨询时长'=>'number',
        'listen_number|个案倾听数量'=>'number',
        'listen_duration|个案倾听时长'=>'number',
        'growth_duration|成长时长'=>'number',
        'team_growth_duration|团体成长时长'=>'number',
        'train_mechanism|培训机构'=>'require|max:50',
        'train_start_time|开始培训时间'=>'require|dateFormat:Y-m',
        'train_end_time|结束培训时间'=>'dateFormat:Y-m',
        'train_course|培训课程名称'=>'require|max:20',
        'train_id'=>'require',
        'supervise_mode|督导方式'=>'require|max:20',
        'supervise_name|督导老师姓名'=>'require|chsDash|max:20',
        'supervise_tel|督导老师联系方式'=>'max:100',
        'supervise_duration|督导时长'=>'require|number',
        'supervise_id'=>'require',
        'school|学校名称'=>'require|chsDash',
        'start_time|入学时间'=>'require|dateFormat:Y-m-d',
        'end_time|毕业时间'=>'dateFormat:Y-m-d',
        'major|所学专业'=>'require|chsDash',
        'unified_if|是否统招'=>'require|in:0,1',
        'education_level|学历'=>'require|in:0,1,2,3,4,5,6',
        'education_id'=>'require',
        /*------图片对应地址------*/
        'certificate_photo_no|资质证明图'=>'require',
        'train_photo_no|培训证明图'=>'require',
        'education_photo_no|学历证明图'=>'require',
        'teacher_photo_no|老师上身照'=>'require',
        /*------公共------*/
        'teacher_id'=>'require',
        'ycode|验证码'=>'require',
        'info_id'=>'require',
        'type'=>'require',
    ];

    protected $message  =   [
    	'teacher_name.require'=>'老师姓名不能为空',
    	'teacher_name.chsDash'=>'老师姓名不允许有特殊字符',
    	'sex.require'=>'性别必选',
    	'sex.in'=>'所选性别不存在',
    	'birthday.require'=>'出生年月日必填',
    	'birthday.dateFormat'=>'出生年月日的格式不正确',
    	'enter_date.require'=>'入行时间必填',
    	'enter_date.dateFormat'=>'入行时间的格式不正确',
    	'province.require'=>'省份项必选',
    	'province.chsDash'=>'省份项格式不正确',
    	'city.require'=>'城市项必选',
    	'city.chsDash'=>'城市项格式不正确',
    	'tel.require'=>'手机号码必填',
    	'tel.regex'=>'手机号码格式不正确',
    ];

    protected $scene = [
        // 老师入驻第一步
        'info'=>['teacher_name','sex','birthday','enter_date','province','city','tel','teacher_photo_no'],
        // 老师入驻第四部资质证书
        'certificate_eidt'  =>  ['certificate_name','certificate_no','award_date','teacher_id'],
        // 老师入驻第四部培训
        'train_edit'=>['train_mechanism','train_start_time','train_end_time','train_course','teacher_id'],
        // 老师入驻第四部督导
        'supervise_edit'=>['supervise_mode','supervise_name','supervise_tel','supervise_duration','teacher_id'],
        // 老师入驻第四部学历
        'education_edit'=>['school','start_time','end_time','major','unified_if','education_level','teacher_id'],
        // 老师入驻第四部时长
        'major_consult_eidt'=>['consult_number','consult_duration','listen_number','listen_duration','teacher_id','type','team_consult_duration','team_consult_number'],
        'major_growth_eidt'=>['growth_duration','teacher_id','type','team_growth_duration'],
        // 老师入驻第四部资质证书
        'certificate'  =>  ['certificate_name','certificate_no','award_date'],
        // 老师入驻第四部培训
        'train'=>['train_mechanism','train_start_time','train_end_time','train_course'],
        // 老师入驻第四部督导
        'supervise'=>['supervise_mode','supervise_name','supervise_tel','supervise_duration'],
        // 老师入驻第四部学历
        'education'=>['school','start_time','end_time','major','unified_if','education_level'],
        // 老师入驻第四部时长
        'major'=>['listen_label','consult_label','consult_number','consult_duration','listen_number','listen_duration','growth_duration','teacher_id','team_consult_duration','team_consult_number','team_growth_duration'],
        // 老师入驻第四部所有字段
        'teachers'=>['teacher_id','listen_label','consult_label','consult_number','consult_duration','listen_number','listen_duration','growth_duration','certificate_name','certificate_no','award_date','train_mechanism','train_start_time','train_end_time','train_course','supervise_mode','supervise_name','supervise_tel','supervise_duration','school','start_time','end_time','major','unified_if','education_level',],
        'info_edit'=>['teacher_name','sex','birthday','enter_date','province','city','tel'],
        'tel'=>['tel','ycode'],
        'del_info'=>['info_id','type'],
    ];

    // 倾听标签验证
    protected function checkListenLabel($value,$rule,$data)
    {
        $teacherData = Teacher::where(['teacher_id'=>$data['teacher_id']])->find();
        if(!$teacherData){
            return '没有这位老师的信息';
        }
        $role = $teacherData['teacher_role']>0;
        if(!$role&&empty($value)){
            return '请选择倾听标签';
        }
        return true;
    }

    // 咨询标签验证
    protected function checkConsultLabel($value,$rule,$data)
    {
        $teacherData = Teacher::where(['teacher_id'=>$data['teacher_id']])->find();
        if(!$teacherData){
            return '没有这位老师的信息';
        }
        $role = $teacherData['teacher_role']>0;
        if($role&&empty($value)){
            return '请选择咨询标签';
        }
        return true;
    }
}
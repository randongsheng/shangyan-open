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
namespace app\index\model;

use think\Model;
use think\Validate;
use think\Db;
use app\index\model\Topic;
use app\index\model\Teacher;
use app\index\service\ClinicDeposit;

/**
 * 诊所用户
 */
class Clinic extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_clinic';

    /**
     * 创建数据
     */
    public function createData($data)
    {
        $nowTime = time();
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        $this->create_at = $nowTime;
        if($this->save()){
            return $this->id;
        }else{
            return false;
        }
    }

    /**
     * 修改数据
     */
    public function editData($clinicId,$data)
    {
        $nowTime = time();
        $this_ = $this->where('id',$clinicId)->find();
        foreach ($data as $key => $value) {
            $this_->$key = $value;
        }
        $this_->update_at = $nowTime;
        if($this_->save()){
            return $this_->id;
        }else{
            return false;
        }
    }

    /**
     * 通过审核
     */
    public function adopt($clinicId,$run=false)
    {
        $clinic = $this->where('id',$clinicId)->find();
        $clinic->status = 2;
        $clinic->apply_schedule = 3;
        if($run){
            $clinic->run_status = 1;
        }
        return $clinic->save();
    }

    /**
     * 秒单位转换
     */
    public function getAlltimeAttr($value)
    {
        return time_to_date($value);
    }

    /**
     * 秒单位转换
     */
    public function getSytimeAttr($value)
    {
        return time_to_date($value);
    }

    /**
     * 倾听开始
     */
    public function getStimeAttr($value)
    {
        if(empty($value)) return $value;
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 倾听结束
     */
    public function getEtimeAttr($value)
    {
        if(empty($value)) return $value;
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 拒绝通过
     */
    public function refuse($clinicId)
    {
        $clinic = $this->where('id',$clinicId)->find();
        $clinic->status = -1;
        $clinic->apply_schedule = 1;
        return $clinic->save();
    }

    /**
     * 上下架
     */
    public function shelf($clinicId,$uplow)
    {
        $deposit = new ClinicDeposit;
        $clinic = $this->where('id',$clinicId)->find();
        if($uplow=='up'){
            if(!in_array($clinicId, config('white_list'))){
                $balance = $deposit->getBalance($clinicId);
                if($balance<config('deoisit')){
                    return false;
                }
            }
            if($clinic->run_status!=-2||$clinic->status!=2){
                return false;
            }
            $clinic->run_status = 1;
        }else if($uplow=='down'){
            if($clinic->run_status!=1){
                return false;
            }
            $clinic->run_status = -2;
        }else{
            return false;
        }
        return $clinic->save();
    }

    /**
     * 翻译标签
     */
    public function getTopicAttr($value)
    {
        $topic = new Topic;
        return implode(',', $topic->getText($value));
    }

    /**
     * logo 地址拼接
     */
    public function getLogoAttr($value)
    {
        if(Validate::is($value,'url') || empty($value)) return $value;
    	return config('save_protocol').rtrim(config('save_url'),'/').'/'.ltrim($value,'/');
    }

    /**
     * 头像地址
     */
    public function getAvatarurlAttr($value)
    {
        if(Validate::is($value,'url') || empty($value)) return $value;
        return config('save_protocol').rtrim(config('save_url'),'/').'/'.ltrim($value,'/');
    }

    /**
     * business_license
     */
    public function getBusinessLicenseAttr($value)
    {
        if(Validate::is($value,'url') || empty($value)) return $value;
        return config('save_protocol').rtrim(config('save_url'),'/').'/'.ltrim($value,'/');
    }

    /**
     * operator_identity
     */
    public function getOperatorIdentityAttr($value)
    {
        $imgs = explode(',', $value);
        if(count($imgs)<2){
            return $value;
        }else{
            $url = config('save_protocol').rtrim(config('save_url'),'/').'/';
            return $url.ltrim($imgs[0],'/').','.$url.ltrim($imgs[1],'/');
        }
    }

    /**
     * operator_identity_A
     */
    public function getOperatorIdentityAAttr($value,$data)
    {
        $imgs = explode(',', $data['operator_identity']);
        if(count($imgs)<2){
            return $data['operator_identity'];
        }else{
            if(empty($imgs[0])) return '';
            $url = config('save_protocol').rtrim(config('save_url'),'/').'/';
            return $url.ltrim($imgs[0],'/');
        }
    }

    /**
     * operator_identity_B
     */
    public function getOperatorIdentityBAttr($value,$data)
    {
        $imgs = explode(',', $data['operator_identity']);
        if(count($imgs)<2){
            return $data['operator_identity'];
        }else{
            if(empty($imgs[1])) return '';
            $url = config('save_protocol').rtrim(config('save_url'),'/').'/';
            return $url.ltrim($imgs[1],'/');
        }
    }

    /**
     * liable_identity
     */
    public function getLiableIdentityAttr($value)
    {
        $imgs = explode(',', $value);
        if(count($imgs)<2){
            return $value;
        }else{
            $url = config('save_protocol').rtrim(config('save_url'),'/').'/';
            return $url.ltrim($imgs[0],'/').','.$url.ltrim($imgs[1],'/');
        }
    }

    /**
     * liable_identity_A
     */
    public function getLiableIdentityAAttr($value,$data)
    {
        $imgs = explode(',', $data['liable_identity']);
        if(count($imgs)<2){
            return $data['liable_identity'];
        }else{
            if(empty($imgs[0])) return '';
            $url = config('save_protocol').rtrim(config('save_url'),'/').'/';
            return $url.ltrim($imgs[0],'/');
        }
    }

    /**
     * liable_identity_B
     */
    public function getLiableIdentityBAttr($value,$data)
    {
        $imgs = explode(',', $data['liable_identity']);
        if(count($imgs)<2){
            return $data['liable_identity'];
        }else{
            if(empty($imgs[1])) return '';
            $url = config('save_protocol').rtrim(config('save_url'),'/').'/';
            return $url.ltrim($imgs[1],'/');
        }
    }

    /**
     * scene_photo1
     */
    public function getScenePhoto1Attr($value,$data)
    {
        $photos = explode(',',$data['scene_photo']);
        $url = config('save_protocol')
            .rtrim(config('save_url'),'/').'/';
        if(empty($photos[0])) return '';
        return $url.$photos[0];
    }

    /**
     * scene_photo2
     */
    public function getScenePhoto2Attr($value,$data)
    {
        $photos = explode(',',$data['scene_photo']);
        $url = config('save_protocol')
            .rtrim(config('save_url'),'/').'/';
        if(empty($photos[1])) return '';
        return $url.$photos[1];
    }

    /**
     * scene_photo3
     */
    public function getScenePhoto3Attr($value,$data)
    {
        $photos = explode(',',$data['scene_photo']);
        $url = config('save_protocol')
            .rtrim(config('save_url'),'/').'/';
        if(empty($photos[2])) return '';
        return $url.$photos[2];
    }

    /**
     * status 转换中文
     */
    public function getStatusAttr($value,$data)
    {
    	$statusDe = [
            -9=>'审核通过但保证金不足'
            ,-8=>'已关停申请开启'
            ,-7=>'管理员关停'
            ,-6=>'机构已关停'
            ,-5=>'关停待审'
            ,-4=>'保证金不达标暂停运营'
            ,-3=>'暂停营业'
            ,-2=>'自行撤回修改'
            ,-1=>'被驳回（修改中）'
            ,0=>'未填写'
            ,1=>'待审核'
            ,2=>'正常'
        ];
        if(!array_key_exists($value, $statusDe)){
            return $value;
        }
        if($value==2 && !in_array($data['clinic_id'], config('white_list'))){
            $deposit = new ClinicDeposit;
            $balance = $deposit->getBalance($data['clinic_id']);
            if($balance<config('deoisit')){
                return '审核通过但保证金不足';
            }
        }
    	return $statusDe[$data['status']];
    }

    /**
     * 机构运行状态
     */
    public function getRunStatusAttr($value)
    {
        $runstatusDe = [-2=>'管理员下架',-1=>'关停',0=>'暂停营业中',1=>'营业中'];
        if(!array_key_exists($value, $runstatusDe)){
            return '暂停营业中';
        }
        return $runstatusDe[$value];
    }

    /**
     * 机构类型
     */
    public function getNatureAttr($value,$data)
    {
        if(!$value) return $value;
    	$natureDe = [1=>'个人',2=>"企业"];
    	return $natureDe[$data['nature']];
    }

    /**
     * 入住时间
     */
    public function getCreateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 信息更新时间
     */
    public function getUpdateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 机构标签
     */
    public function getListenLabelAttr($value,$data)
    {
        return $this->getForTeacherListenLabel($data['id']);
    }

    /**
     * 咨询
     */
    public function getConsultLabelAttr($value,$data)
    {
        return $this->getForTeacherConsultLabel($data['id']);
    }

    /**
     * scene_photo
     */
    public function getScenePhotoAttr($value)
    {
        if(Validate::is($value,'url') || empty($value)) return $value;
        $photos = explode(',',$value);
        $result = '';
        $url = config('save_protocol')
            .rtrim(config('save_url'),'/').'/';
        foreach ($photos as $k => $v) {
            $result .= $url.$v.',';
        }
        return trim($result,',');
    }

    /**
     * 时间
     */
    public function getListenAllTimeAttr($value)
    {
        return time_to_date($value);
    }

    /**
     * 时间
     */
    public function getConsultAllTimeAttr($value)
    {
        return time_to_date($value);
    }

    /**
     * 获取旗下老师的倾听标签
     */
    public function getForTeacherListenLabel($clinicId)
    {
        $teacher = new Teacher;
        $topic = new Topic;
        $teachers = Db::name('teacher')->where(['clinic_id'=>$clinicId])->field('listen_label')->select();
        if(!$teachers){
            return [];
        }
        // $teachers->topic
        $listenLabels = [];
        foreach ($teachers as $k => $v) {
            if(empty($v->listen_label)){
                continue;
            }
            $listenLabels = array_merge(explode(',',trim($v->listen_label,',')),$listenLabels);
        }
        $listenLabels = array_values(array_unique($listenLabels));
        return $topic->getText($listenLabels,false);
    }

    /**
     * 获取旗下老师的咨询标签
     */
    public function getForTeacherConsultLabel($clinicId)
    {
        $teacher = new Teacher;
        $topic = new Topic;
        $teachers = Db::name('teacher')->where(['clinic_id'=>$clinicId])->field('consult_label')->select();
        if(!$teachers){
            return [];
        }
        // $teachers->topic
        $consultLabels = [];
        foreach ($teachers as $k => $v) {
            if(empty($v->consult_label)){
                continue;
            }
            $consultLabels = array_merge(explode(',',trim($v->consult_label,',')),$consultLabels);
        }
        $consultLabels = array_values(array_unique($consultLabels));
        return $topic->getText($consultLabels,false);
    }

    /**
     * 获取评论
     */
    public function getComments($clinicId,$limit=false)
    {
        $teacher = new Teacher;
        $teachers = $teacher->where(['clinic_id'=>$clinicId,'uid'=>['not','null'],'uid'=>['<>',0]])->column('uid');
        return $teacher->getComments($teachers,$limit);
    }
}
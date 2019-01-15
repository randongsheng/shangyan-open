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
use app\index\model\Teacher;

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
            $clinic->run_status = 3;
        }
        return $clinic->save();
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
        $clinic = $this->where('id',$clinicId)->find();
        if($uplow=='up'){
            if($clinic->run_status!=-2){
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
     * logo 地址拼接
     */
    public function getLogoAttr($value)
    {
        if(Validate::is($value,'url')) return $value;
    	return config('save_protocol').rtrim(config('save_url'),'/').'/'.ltrim($value,'/');
    }

    /**
     * business_license
     */
    public function getBusinessLicenseAttr($value)
    {
        if(Validate::is($value,'url')) return $value;
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
     * status 转换中文
     */
    public function getStatusAttr($value,$data)
    {
    	$statusDe = [
            -7=>'管理员关停'
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
     * 获取旗下老师的标签
     */
    public function getForTeacherListenLabel($clinicId)
    {
        $teacher = new Teacher;
        $topic = new Topic;
        $teachers = $teacher->where(['clinic_id'=>$clinicId])->field('listen_label')->select();
        if(!$teachers){
            return [];
        }
        // $teachers->topic
        $listenLabels = [];
        foreach ($teachers as $k => $v) {
            $listenLabels = array_merge(explode(',',trim($v->listen_label,',')),$listenLabels);
        }
        $listenLabels = array_unique($listenLabels);
        return $topic->getText($listenLabels,false);
    }

    /**
     * 获取旗下老师的标签
     */
    public function getForTeacherConsultLabel($clinicId)
    {
        $teacher = new Teacher;
        $topic = new Topic;
        $teachers = $teacher->where(['clinic_id'=>$clinicId])->field('consult_label')->select();
        if(!$teachers){
            return [];
        }
        // $teachers->topic
        $consultLabels = [];
        foreach ($teachers as $k => $v) {
            $consultLabels = array_merge(explode(',',trim($v->consult_label,',')),$consultLabels);
        }
        $consultLabels = array_unique($consultLabels);
        return $topic->getText($consultLabels,false);
    }

    /**
     * 获取评论
     */
    public function getComments($clinicId)
    {
        $teacher = new Teacher;
        $teachers = $teacher->where(['clinic_id'=>$clinicId,'uid'=>['not','null'],'uid'=>['<>',0]])->column('uid');
        return $teacher->getComments($teachers);
    }
}
<?php
// +----------------------------------------------------------------------
// | 尚言
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2028 http://www.shangyanxinli.com
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: SC-CG <sc-cg.cn>
// +----------------------------------------------------------------------
namespace app\index\service;

use think\Model;
use think\Validate;

/**
 * 老师关联记录
 */
class TeacherRelevantRecord extends Model
{
	protected $pk = 'relevant_record_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_teacher_relevantrecord';

    /**
     * 添加关联
     */
    public function addRelevant($releData)
    {
    	$nowTime = time();
    	foreach ($releData as $k => $v) {
    		$this->$k = $v;
    	}
        $this->create_at = $nowTime;
    	$this->valid_is = 1;
    	return $this->save();
    }

    /**
     * 查询关联
     */
    public function getTeacherRele($teacherId,$field)
    {
        return $this
        ->where(['teacher_id'=>$teacherId,'valid_is'=>1])
        ->field($field)
        ->find();
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
     * 查询所有关联
     */
    public function getAllTeacherRele($teacherId,$limit=false)
    {
        if($limit){
            return $this
            ->join('sy_clinic c','c.id=sy_teacher_relevantrecord.clinic_id','LEFT')
            ->where(function($query)use($teacherId){
                if(is_array($teacherId)){
                    $query->where('sy_teacher_relevantrecord.teacher_id','in',implode(',',$teacherId));
                }else{
                    $query->where(['sy_teacher_relevantrecord.teacher_id'=>$teacherId]);
                }
            })
            ->field(['sy_teacher_relevantrecord.*','c.clinic_name','c.logo'])
            ->limit($limit)
            ->select();
        }else{
            return $this
            ->join('sy_clinic c','c.id=sy_teacher_relevantrecord.clinic_id','LEFT')
            ->where(function($query)use($teacherId){
                if(is_array($teacherId)){
                    $query->where('sy_teacher_relevantrecord.teacher_id','in',implode(',',$teacherId));
                }else{
                    $query->where(['sy_teacher_relevantrecord.teacher_id'=>$teacherId]);
                }
            })
            ->field(['sy_teacher_relevantrecord.*','c.clinic_name','c.logo'])
            ->paginate(20);
        }
    }

    /**
     * 机构关联
     */
    public function getAllClinicRele($clinicId,$limit=false)
    {
        if($limit){
            return $this
            ->join('sy_teacher t','t.teacher_id=sy_teacher_relevantrecord.teacher_id','LEFT')
            ->join('sy_userfield uf','uf.uid=t.uid','LEFT')
            ->join('sy_user u','u.id=t.uid','LEFT')
            ->where(function($query)use($clinicId){
                if(is_array($clinicId)){
                    $query->where(['sy_teacher_relevantrecord.clinic_id'=>['in',implode(',', $clinicId)]]);
                }else{
                    $query->where(['sy_teacher_relevantrecord.clinic_id'=>$clinicId]);
                }
            })
            ->field(['sy_teacher_relevantrecord.*','uf.realname','u.avatarurl'])
            ->limit($limit)
            ->select();
        }else{
            return $this
            ->join('sy_teacher t','t.teacher_id=sy_teacher_relevantrecord.teacher_id','LEFT')
            ->join('sy_userfield uf','uf.uid=t.uid','LEFT')
            ->join('sy_user u','u.id=t.uid','LEFT')
            ->where(function($query)use($clinicId){
                if(is_array($clinicId)){
                    $query->where(['sy_teacher_relevantrecord.clinic_id'=>['in',implode(',', $clinicId)]]);
                }else{
                    $query->where(['sy_teacher_relevantrecord.clinic_id'=>$clinicId]);
                }
            })
            ->field(['sy_teacher_relevantrecord.*','uf.realname','u.avatarurl'])
            ->paginate(20);
        }
        
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
     * 操作时间
     */
    public function getCreateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
}

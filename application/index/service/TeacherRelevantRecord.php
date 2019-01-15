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
        return $this->where(['teacher_id'=>$teacherId,'valid_is'=>1])->field($field)->find();
    }

    /**
     * 查询所有关联
     */
    public function getAllTeacherRele($teacherId)
    {
        return $this
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where('teacher_id','in',implode(',',$teacherId));
            }else{
                $query->where(['teacher_id'=>$teacherId]);
            }
        })
        ->select();
    }

    /**
     * 机构关联
     */
    public function getAllClinicRele($clinicId)
    {
        return $this
        ->where(function($query)use($clinicId){
            if(is_array($clinicId)){
                $query->where(['clinic_id'=>['in',implode(',', $clinicId)]]);
            }else{
                $query->where(['clinic_id'=>$clinicId]);
            }
        })
        ->select();
    }
}
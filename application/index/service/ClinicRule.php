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
use app\index\model\Clinic;

/**
 * 诊所用户
 */
class ClinicRule extends Model
{
	protected $pk = 'rule_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_clinic_rule';
    // 创建时间字段
    protected $createTime = 'create_at';
    // 更新时间字段
    protected $updateTime = 'update_at';
    private $clinic;

    /*function __construct(Clinic $clinic)
    {
    	parent::__construct();
    	$this->clinic = $clinic;
    }*/

    /**
     * 机构规则添加
     */
    public function createRule($createData)
    {
    	$nowTime = time();
    	$createData = $this->ruleModule($createData);
    	foreach ($createData as $key => $value) {
    		$this->$key = $value;
    	}
    	$this->create_at = $nowTime;
    	if($this->save()){
    		return $this->rule_id;
    	}else{
    		return false;
    	}
    }

    /**
     * 修改规则信息
     */
    public function editRule($ruleId, $editData)
    {
    	$nowTime = time();
    	$rule = self::get($ruleId);
    	foreach ($editData as $key => $value) {
    		$rule->$key = $value;
    	}
    	$rule->update_at = $nowTime;
    	if($rule->save()){
    		return $rule->rule_id;
    	}else{
    		return false;
    	}
    }

    /**
     * 规则分类
     */
    public function ruleModule($module)
    {
    	if(empty($module['module'])){
    		$rules = $this->order('module','desc')->find();
    		$module['module'] = $rules['module']+1;
    	}else if($module['module']>0){
    		$module['module_name'] = $this->where('module',$module['module'])->value('module_name');
    	}else{
    		$module['module_name'] = '其他';
    		$module['module'] = 0;
    	}
    	return $module;
    }

    /**
     * 转换state
     */
    public function getStateTextAttr($value,$data)
    {
    	$stateDe = ['弃用','已发布'];
    	return $stateDe[$data['state']];
    }

    /**
     * 转换create_at
     */
    public function getCreateAtAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转换update_at
     */
    public function getUpdateAtAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }
}
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

/**
 * 关停申请
 */
class ClinicClosure extends Model
{
	protected $pk = 'closure_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_clinic_closure';

    /**
     * 转文字
     */
    public function getReasonAttr($value)
    {
    	$reasonDe = [0=>'没有客户',1=>'没时间经营',2=>'不善于经营',3=>'机构管理出问题',4=>'资金链断裂',5=>'其他'];
    	return $reasonDe[$value];
    }

    /**
     * 转文字
     */
    public function getProgressStatusAttr($value)
    {
    	$statusDe = [-1=>'驳回修改',0=>'申请提交',1=>'待审核',2=>'审核通过'];
    	return $statusDe[$value];
    }

    /**
     * 转时间
     */
    public function getCreateAtAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转时间
     */
    public function getUpdateAtAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }
}
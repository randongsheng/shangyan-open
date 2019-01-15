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
 * 保证金
 */
class ClinicBankCard extends Model
{
	protected $pk = 'card_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_clinic_bankcard';

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
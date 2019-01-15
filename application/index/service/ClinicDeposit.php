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
class ClinicDeposit extends Model
{
	protected $pk = 'deposit_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_clinic_deposit';

    /**
     * 保证金余额
     */
    public function getBalance($clinicId)
    {
        $paySum = ClinicDeposit::where(['add_subtract'=>1,'clinic_id'=>$clinicId])->sum('recharge_money');
        $charSum = ClinicDeposit::where(['add_subtract'=>0,'clinic_id'=>$clinicId])->sum('charging_money');
        return $paySum-$charSum;
    }

    /**
     * 转时间
     */
    public function getOrderCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转时间
     */
    public function getDepositCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转文字
     */
    public function getPayStateAttr($value)
    {
         $payDe = [0=>'未支付',1=>'支付成功'];
         if(!array_key_exists($value,$payDe)) return '未支付';
         return $payDe[$value];
    }

    /**
     * 转文字
     */
    public function getAddSubtractAttr($value)
    {
         $payDe = [0=>'扣款',1=>'充值'];
         if(!array_key_exists($value,$payDe)) return '扣款';
         return $payDe[$value];
    }

    /**
     * 扣款
     */
    public function deduction($clinicId,$quota,$reason='')
    {
        $nowTime = time();
        $this->clinic_id = $clinicId;
        $this->charging_money = $quota;
        $this->sketch = $reason;
        $this->add_subtract = 0;
        $this->create_at = $nowTime;
        if($this->save()){
            return $this->deposit_id;
        }else{
            return false;
        }
    }
}
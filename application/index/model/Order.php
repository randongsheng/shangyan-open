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
use app\index\service\UserField;
use app\index\model\Topic;
use app\index\model\Clinic;
/**
 * 
 */
class Order extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_order';

    /**
     * 返回老师名字
     */
    public function getServerpersonidAttr($value)
    {
    	$realname = UserField::where('uid',$value)->value('realname');
    	return $realname ? $realname :'未匹配';
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
     * 订单状态
     */
    public function getStatusAttr($value)
    {
    	$statusDe = [0=>'待支付',1=>'已支付待使用',2=>'交易完成',3=>'关闭'];
        if(!array_key_exists($value, $statusDe)){
            return '待支付';
        }
    	return $statusDe[$value];
    }

    /**
     * 时间戳转时间
     */
    public function getCreatetimeAttr($value)
    {
    	return date('Y年m月d日 H:i',$value);
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
     * 查询商户名称
     */
    public function getClinicidAttr($value)
    {
    	$clinic = new Clinic;
    	$clinicName = $clinic->where('id',$value)->value('clinic_name');
        return $clinicName ? $clinicName :'未匹配';
    }

    /**
     * 咨询方式
     */
    public function getModeAttr($value)
    {
        $modeDe = [0=>'未知',1=>"视频咨询",2=>'当面咨询'];
        if(!array_key_exists($value, $modeDe)) return $value;
        return $modeDe[$value];
    }

    /**
     * 转时间
     */
    public function getCreateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
}
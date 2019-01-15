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
use app\index\model\Order;

/**
 * 倾听订单查询
 */
class ListenOrder extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_listenrecord';

    /**
     * 查询与用户相关的订单
     */
    public function userOrders($userId)
    {
    	$order = new Order;
    	$orders = $order
    	->join()
    	->select();
    }

    /**
     * 查询与老师相关的倾听订单
     */
    public function teacherOrders($teahcerId)
    {

    }

    /**
     * 查询与机构相关的倾听订单
     */
    public function clinicOrders($clinicId)
    {

    }
}
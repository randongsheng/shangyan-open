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
 * 测试订单查询
 */
class TestTitle extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_examtitle';

    public function profile()
    {
        return $this->hasOne('OrderTest','titleid','id','OrderTest');
    }
}
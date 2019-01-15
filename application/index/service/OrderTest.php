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
use app\index\model\Topic;

/**
 * 测试订单查询
 */
class OrderTest extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_examorder';

    public function getStatusAttr($value)
    {
        $statusDe = [0=>'未付款',1=>'已付款',2=>'关闭',3=>'待评价',4=>'完成'];
        return $statusDe[$value];
    }

    public function getTopicAttr($value)
    {
    	$topic = new Topic;
	    return implode(',', $topic->getText($value));
    }

    public function getCreatetimeAttr($value)
    {
    	return date('Y-m-d H:i:s',$value);
    }
}
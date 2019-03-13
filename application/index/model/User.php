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
use app\index\model\Topic;
use think\Validate;

/**
 * 
 */
class User extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_user';
    protected $readonly = ['email','password'];

    /**
     * 查询普通用户
     */
    public function queryUser()
    {
    	// return $this->where('role',0);
    }

    /**
     * 转换标签
     */
    public function getTopicAttr($value)
    {
    	$topic = new Topic;
    	return $topic->getText($value);
    }

    /**
     * 用户性别转换
     */
    public function getGenderAttr($value,$data)
    {
    	$sex = [0=>'未知',1=>"男",2=>'女'];
    	return empty($data['gender']) ? '未知' : $sex[$data['gender']];
    }

    /**
     * 服务状态转文字
     */
    public function getServerstatusAttr($value)
    {
        $serviceDe = [1=>'电话服务中',2=>'视频服务中',3=>'面对面服务中',4=>'离线',5=>'空闲'];
        if(!array_key_exists($value,$serviceDe)) return $value;
        return $serviceDe[$value];
    }

    /**
     * 角色转换文字
     */
    public function getRoleAttr($value)
    {
        if(!$value) return $value;
        $roleDe = [1=>'心理倾诉',2=>'心理咨询'];
        return $roleDe[$value];
    }

    /**
     * 用户状态
     */
    public function getStatusAttr($value)
    {
        $statusDe = [-1=>'拉黑',0=>'正常',1=>'待审核'];
        if(!array_key_exists($value, $statusDe)){
            return '未识别';
        }
        return $statusDe[$value];
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
     * 转换 create_at
     */
    public function getRegtimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转时间
     */
    public function getCreateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 秒单位转换
     */
    public function getSytimeAttr($value)
    {
        return time_to_date($value);
    }

    /**
     * 转时间
     */
    public function getStimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转时间
     */
    public function getEtimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
}

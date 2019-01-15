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

/**
 * 
 */
class Article extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_articles';

    /**
     * 转文字
     */
    public function getKeywordsAttr($value)
    {
    	$topic = new Topic;
    	return implode('#',$topic->getText($value));
    }

    /**
     * 转文字
     */
    public function getStatusAttr($value)
    {
        $statusDe = [0=>'未识别',1=>'草稿',2=>'待发布',3=>'上架',4=>'下架',5=>'待审核',6=>'驳回',7=>'删除'];
        if(!array_key_exists($value, $statusDe)){
            return '未识别';
        }
        return $statusDe[$value];
    }

    /**
     * 转时间
     */
    public function getAddTimeAttr($value)
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

    /**
     * 文章排序
     */
    public function getArticleSortAttr($value,$data)
    {
        // $data['id'];
    }
}
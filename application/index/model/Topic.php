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
use app\index\service\TopicRelevance;

/**
 * 标签
 */
class Topic extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_topic';

    /**
     * 添加标签
     */
    public function createLabel($insertData,$obeyId='')
    {
        $nowTime = time();
        $rele = new TopicRelevance;
        $this->startTrans();
        $rele->startTrans();
        foreach ($insertData as $key => $value) {
            $this->$key = $value;
        }
        $this->create_at = $nowTime;
        if($this->save()){
            if(!empty($obeyId)){
                $result = $rele->setRele($this->id,$obeyId);
                if(!$result){
                    $this->rollback();
                    $rele->rollback();
                    return false;
                }
                $rele->commit();
            }
            $this->commit();
            return $this->id;
        }else{
            $this->rollback();
            return false;
        }
    }

    /**
     * 查询标签
     */
    public function getText($str,$rele=false)
    {
    	if(empty($str)) return [];
        if(is_array($str)){
            $arr = $str;
        }else{
            $arr = explode(',', trim($str,','));
        }
    	$de = [];
        if($rele){
            for ($i=0; $i < count($arr); $i++) {
                $de[$arr[$i]] = Topic::where('id',$arr[$i])->value('title');
            }
        }else{
            for ($i=0; $i < count($arr); $i++) {
                $de[] = Topic::where('id',$arr[$i])->value('title');
            }
        }
    	return $de;
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

    /**
     * 转文字
     */
    public function getStatusAttr($value)
    {
        $statusDe = [0=>'下架',1=>"正常"];
        if(!array_key_exists($value, $statusDe)) return $value;
        return $statusDe[$value];
    }

    /**
     * 关联标签
     */
    public function getObeyLabelAttr($value,$data)
    {
        $rele = new TopicRelevance;
        return implode('、',$rele->getRele($data['id']));
    }

    /**
     * 关联标签ID
     */
    public function getObeyIdAttr($value,$data)
    {
        $rele = new TopicRelevance;
        return array_keys($rele->getRele($data['id'],true));
    }
}
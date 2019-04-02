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
 * 标签关联
 */
class TopicRelevance extends Model
{
	protected $pk = 'relevance_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_topic_relevance';

    /**
     * 设置关联
     */
    public function setRele($masterId,$obeyId)
    {
        $nowTime = time();
        Topic::where(['id'=>$masterId])->save(['update_at'=>$nowTime]);
        if(empty($obeyId)){
            return $this->where(['master_id'=>$masterId])->delete();
        }
    	if(is_array($obeyId)){
            $this->where(['master_id'=>$masterId])->delete();
    		$labels = [];
    		for ($i=0; $i < count($obeyId); $i++) { 
                $label = [];
                $label['master_id'] = $masterId;
                $label['obey_id'] = $obeyId[$i];
    			$label['create_at'] = $nowTime;
                $labels[] = $label;
    		}
    		return $this->saveAll($labels);
    	}
    	if($this->where(['master_id'=>$masterId,'obey_id'=>$obeyId])->value('master_id')){
			$this->where(['master_id'=>$masterId,'obey_id'=>$obeyId[$i]])->delete();
		}
    	$this->master_id = $masterId;
    	$this->obey_id = $obeyId;
    	$this->create_at = $nowTime;
    	return $this->save(); 
    }

    /**
     * 取消关联
     */
    public function cancelRele($masterId,$obeyId)
    {
    	return $this->where(['master_id'=>$masterId,'obey_id'=>$obeyId])->delete();
    }

    /**
     * 获取关联
     */
    public function getRele($masterId,$rele=true)
    {
    	$obeyId = $this->where(['master_id'=>$masterId])->column('obey_id');
    	if(!$obeyId){
    		return $obeyId;
    	}
    	$topic = new Topic;
    	return $topic->getText($obeyId,$rele);
    }
}
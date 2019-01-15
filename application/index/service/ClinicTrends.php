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
use app\index\model\Clinic;

/**
 * 商户动态
 */
class ClinicTrends extends Model
{
	protected $pk = 'trends_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_clinic_trends';
    // 创建时间字段
    protected $createTime = 'create_at';
    // 更新时间字段
    protected $updateTime = 'update_at';

    public function createTrends($createData)
    {
        $nowTime = time();
        foreach ($createData as $k => $v) {
            $this->$k = $v;
        }
        $this->create_at = $nowTime;
        if($this->save()){
            return $this->trends_id;
        }else{
            return false;
        }
    }
}
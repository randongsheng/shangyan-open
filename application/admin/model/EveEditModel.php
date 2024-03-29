<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;
use app\admin\model\BaseModel;
class EveEditModel extends BaseModel
{
   // 确定链接表名
    protected $table = 'sy_eve_edit_log';

    public function getCreateAtAttr($value)
    {

        return date('Y-m-d H:i:s',$value);
    }

    public function eve()
    {

        return $this->hasOne('EveModel','id','eve_id')->field('id,title,keywords');
    }
}
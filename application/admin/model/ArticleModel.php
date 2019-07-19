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
namespace app\admin\model;

use think\Model;
use app\admin\model\BaseModel;
/**
 * 
 */
class ArticleModel extends BaseModel
{
    protected $table = 'sy_articles';

    public function getCreateAtAttr($value)
    {

        return date('Y-m-d H:i:s',$value);
    }
    public function getKeywordsAttr($value){//处理数组
        return explode(',',$value);
    }
}
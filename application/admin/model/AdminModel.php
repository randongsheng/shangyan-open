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

/**
 * 
 */
class AdminModel extends Model
{
//	protected $pk = 'admin_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_admin';


    public function getCreateAtAttr($value)
    {

        return date('Y-m-d H:i:s',$value);

    }

     //关联机构的信息
    public function jigou()
    {

        return $this->hasOne('AdminJigouModel','relate_id');

    }

}
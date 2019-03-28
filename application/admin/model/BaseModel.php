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
use think\Validate;
/**
 * 
 */
class BaseModel extends Model
{
    public function getPicurlAttr($value)
    {
        if (Validate::is($value,'url'))
        {
            return $value;
        }
        if(!$value){
            return $value;
        }
        return config('pro_img').$value;
    }
    public function getAnswerpicurlAttr($value)
    {
        if (Validate::is($value,'url'))
        {
            return $value;
        }
        if(!$value){
            return $value;
        }
        return config('pro_img').$value;
    }
    public function getAvatarurlAttr($value)
    {
        if (Validate::is($value,'url'))
        {
            return $value;
        }
        if(!$value){
            return $value;
        }
        return config('pro_img').$value;
    }
    public function getLogoAttr($value)
    {
        if(Validate::is($value,'url')) return $value;
        //$path = config('IMG');
        if(!$value){
            return $value;
        }
        return config('pro_img').$value;//config('save_protocol').config('save_url').$path['clinic_logo'].ltrim($value,'/');
    }
    public function gettHumbnailAttr($value)
    {
        if(Validate::is($value,'url')) return $value;
        //$path = config('IMG');
        if(!$value){
            return $value;
        }
        return config('pro_img').$value;//config('save_protocol').config('save_url').$path['clinic_logo'].ltrim($value,'/');
    }
    public function getCompletionTimeAttr($value)
    {
//        $value = '';
        return date('Y-m-d H:i:s',$value);
    }
    public function getPaytimeAttr($value)
    {
//        $value = '';
        return date('Y-m-d H:i:s',$value);
    }
}
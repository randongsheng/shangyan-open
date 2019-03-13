<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/26
 * Time: 15:35
 */

namespace app\admin\validate;

use think\Validate;
class Examvalidate extends Validate
{
    protected $rule = [
        /**
         * 添加测试标题
         */
        'title' => 'require',
        'description'=>'require',
        'topic'=>'require',
        'content'=>'require',
        'know'=>'require',
        'picurl'=>'require',
        'biaoti'=>'require',
        /**
         * 添加测试问题
         */
        'titleid'=>'require',
        'question'=>'require',
        'json'=>'require',
        'number'=>'require',
    ];
    protected  $message = [
        'title.require' => '测试题不能为空',
        'description.require'=>'描述不能为空',
        'topic.require'=>'话题不能为空',
        'content.require'=>'简介不能为空',
        'know.require'=>'须知不能为空',
        'picurl.require'=>'缩略图不能为空',
        'biaoti.require'=>'标题不能为空',
        /**
         * 添加测试问题
         */
        'titleid'=>'测试id不能为空',
        'question'=>'题干不能为空',
        'json'=>'json不能为空',
        'number'=>'number不能为空',
    ];
    /**
     * @var array 用在场景
     */
    protected  $scene = [
        'addtitle' =>['title','description','topic','content','know','picurl','biaoti'],
        'addquestion'=>['titleid','question','weidu','json','number'],
    ];
}
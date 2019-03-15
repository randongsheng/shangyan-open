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
use think\Db;

/**
 * 站内消息
 */
class InnerMail extends Model
{
	protected $pk = 'id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_inner_mail';
    
    public function addInnerMail($theme,$target_role,$mail_content,$to_uid,$type=0)
    {
        $role = ['普通用户','倾听师','咨询师','商户'];
        $target_user = '';
        if (is_numeric($target_role)) {
            $target_user = $role[$target_role];
            if ($to_uid){
                if ($target_role == 3){
                    $to_username = Db::name('clinic')->where(['id'=>$to_uid])->value('clinic_name');
                }elseif($target_role == 0){
                    $to_username = Db::name('user')->where(['id'=>$to_uid,'role'=>0])->value('nickname');
                }else{
                    $to_username = Db::name('userfield')->where(['uid'=>$to_uid])->value('realname');
                }
                if (!$to_username){
                    return false;
                }
                $target_user = $target_user."=>".$to_username;
            }
        }
        if ($target_role == 1){
            $target_role = ',1,2,';
        }else{
            $target_role = ','.$target_role.',';
        }
        $data = [
            'theme'=>$theme,
            'target_user'=>$target_user,
            'target_role'=>$target_role,
            'mail_content'=>$mail_content,
            'send_time'=>date('Y-m-d H:i:s'),
            'to_uid'=>$to_uid,
            'type'=>$type
        ];
        if($this->save($data)){
            return $this->id;
        }else{
            return false;
        }
    }
}
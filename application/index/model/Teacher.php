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
use app\index\service\TeacherCertificate as Certificate;
use app\index\model\Topic;

/**
 * 老师
 */
class Teacher extends Model
{
	protected $pk = 'teacher_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_teacher';

    /**
     * 修改老师审核状态
     */
    public function editData($teacherId,$editData)
    {
        $nowTime = time();
    	$teacher = Teacher::get($teacherId);
    	foreach ($teacher as $key => $value) {
            $teacher->$key = $value;
        }
        $teacher->update_at = $nowTime;
        if($teacher->save()){
            return $teacher->teacher_id;
        }else{
            return false;
        }
    }

    /**
     * 生日转年龄
     */
    public function getBirthdayAttr($value)
    {
        if(empty($value)) return $value;
        return get_age($value);
    }

    /**
     * 身份
     */
    public function getTeacherRoleAttr($value)
    {
        $roleDe = [0=>'倾听师',1=>'咨询师',2=>'咨询师（仅视频）',3=>'咨询师（仅当面）'];
        if(!array_key_exists($value, $roleDe)){
            return '未识别';
        }
        return $roleDe[$value];
    }

    /**
     * 注册时间
     */
    public function getCreateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 专业认证
     */
    public function getCertificateAttr($value,$data)
    {
        $certificate = new Certificate;
        return implode('#',$certificate->getTitle($data['uid']));
    }

    /**
     * 倾听标签
     */
    public function getListentopicAttr($value)
    {
        $topic = new Topic;
        return $topic->getText($value);
    }

    /**
     * 咨询标签
     */
    public function getZixuntopicAttr($value)
    {
        $topic = new Topic;
        return $topic->getText($value);
    }

    /**
     * 性别
     */
    public function getSexAttr($value)
    {
        $sexDe = [0=>'未知',1=>'男',2=>'女'];
        return $sexDe[$value];
    }

    /**
     * 状态
     */
    public function getServerstatusAttr($value)
    {
        $statusDe = [1=>'电话服务中',2=>'视频服务中',3=>'面对面服务中',4=>'离线',5=>'空闲'];
        if(!array_key_exists($value, $statusDe)){
            return '离线';
        }
        return $statusDe[$value];
    }

    /**
     * 审核过程
     */
    public function getInfoStatusAttr($value)
    {
        $statusDe = [-1=>'被驳回',0=>'草稿',1=>'待审核',2=>'已通过'];
        if(!array_key_exists($value, $statusDe)){
            return '未填写';
        }
        return $statusDe[$value];
    }

    /**
     * 老师评论
     */
    public function getComments($teacherId)
    {
        $order = new Order;
        // 咨询评价
        $consultOrders = $order->alias('o')
        ->join('sy_ordermore om','om.orderid=o.orderid','RIGHT')
        ->join('sy_zixuncomment zc','zc.moreid=om.id')
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where('o.serverpersonid','in',implode(',',$teacherId));
            }else{
                $query->where('o.serverpersonid',$teacherId);
            }
        })
        ->field(['o.orderid','o.serverpersonid','om.id as consult_id','zc.content','zc.score','zc.addtime as create_at'])
        ->select();
        // 倾听评价
        $listenOrders = $order->alias('o')
        ->join('sy_listenrecord ol','ol.orderid=o.orderid','RIGHT')
        ->join('sy_listencomment lc','lc.recordid=ol.id')
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where('o.serverpersonid','in',implode(',',$teacherId));
            }else{
                $query->where('o.serverpersonid',$teacherId);
            }
        })
        ->field(['o.orderid','o.serverpersonid','ol.id as listen_id','lc.content','lc.score','lc.createtime as create_at'])
        ->select();
        if(!$listenOrders){
            return [];
        }
        $comments = array_merge($consultOrders,$listenOrders);
        array_multisort(array_column($comments, 'create_at'),SORT_DESC,$comments );
        return $comments;
    }
}
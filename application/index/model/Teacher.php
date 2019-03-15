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
use think\Validate;
use think\Db;

/**
 * 老师
 */
class Teacher extends Model
{
	protected $pk = 'teacher_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_teacher';

    /**
     * 创建数据
     */
    public function createTeacher($insertData)
    {
        $nowTime = time();
        foreach ($insertData as $k => $v) {
            $this->$k = $v;
        }
        $this->create_at = $nowTime;
        if($this->save()){
            return $this->teacher_id;
        }else{
            return false;
        }
    }

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
     * 出生年月
     */
    public function getTeacherBirthdayAttr($value,$data)
    {
        return $data['birthday'];
    }

    /**
     * 截止时间
     */
    public function getEndAtAttr($value,$data)
    {
        if(!$data['update_at']) return date('Y-m-d H:i:s',$data['create_at']);
        return date('Y-m-d H:i:s', $data['update_at']);
    }

    /**
     * 老师上身照
     */
    public function getTeacherPhotoAttr($value)
    {
        if(Validate::is($value,'url') || empty($value)) return $value;
        return config('save_protocol').rtrim(config('save_url'),'/').'/'.ltrim($value,'/');
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
     * 转换时间格式
     */
    public function getGrowthAtAttr($value)
    {
        if(!$value) return $value;
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转换时间格式
     */
    public function getUpdateAtAttr($value)
    {
        if(!$value) return $value;
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转换时间格式
     */
    public function getCooperAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 转换时间格式
     */
    public function getAdoptAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 性别
     */
    public function getSexAttr($value)
    {
        $sexDe = [0=>'未知',1=>'男',2=>'女'];
        if(!array_key_exists($value, $sexDe)){
            return '未知';
        }
        return $sexDe[$value];
    }

    /**
     * 标签
     */
    public function getConsultLabelAttr($value)
    {
        if(!$value) return [];
        $topicArr = explode(',', trim($value,','));
        $de = [];
        for ($i=0; $i < count($topicArr); $i++) { 
            $de[] = ['id'=>$topicArr[$i],'title'=>Topic::where('id',$topicArr[$i])->value('title')];
        }
        return $de;
    }

    /**
     * 标签
     */
    public function getListenLabelAttr($value)
    {
        if(!$value) return [];
        $topicArr = explode(',', trim($value,','));
        $de = [];
        for ($i=0; $i < count($topicArr); $i++) { 
            $de[] = ['id'=>$topicArr[$i],'title'=>Topic::where('id',$topicArr[$i])->value('title')];
        }
        return $de;
    }

    /**
     * 专业认证
     */
    public function getCertificateAttr($value,$data)
    {
        $certificate = new Certificate;
        return implode('#',$certificate->getTitle($data['teacher_id']));
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
     * 时间
     */
    public function getListenTimeSumAttr($value)
    {
        return time_to_date($value);
    }

    /**
     * 时间
     */
    public function getConsultTimeConAttr($value)
    {
        return time_to_date($value);
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
        $statusDe = [-2=>'修改待审核',-1=>'被驳回',0=>'草稿',1=>'待审核',2=>'已通过'];
        if(!array_key_exists($value, $statusDe)){
            return '未填写';
        }
        return $statusDe[$value];
    }

    /**
     * 老师评论
     */
    public function getComments($teacherId,$limit=false)
    {
        $order = new Order;
        if($limit){
            // 咨询评价
            $consultOrders = $order->alias('o')
            ->join('sy_ordermore om','om.orderid=o.orderid','RIGHT')
            ->join('sy_zixuncomment zc','zc.moreid=om.id')
            ->join('sy_userfield uf','uf.uid=o.serverpersonid','LEFT')
            ->join('sy_user uu','uu.id=o.uid','LEFT')
            ->where(function($query)use($teacherId){
                if(is_array($teacherId)){
                    $query->where('o.serverpersonid','in',implode(',',$teacherId));
                }else{
                    $query->where('o.serverpersonid',$teacherId);
                }
            })
            ->field(['o.orderid','o.serverpersonid','zc.content','zc.score','zc.addtime as create_at','uu.avatarurl','uu.nickname','uf.realname'])
            ->select();
            // 倾听评价
            $listenOrders = $order->alias('o')
            ->join('sy_listenrecord ol','ol.orderid=o.orderid','RIGHT')
            ->join('sy_listencomment lc','lc.recordid=ol.id')
            ->join('sy_userfield uf','uf.uid=o.serverpersonid','LEFT')
            ->join('sy_user uu','uu.id=o.uid','LEFT')
            ->where(function($query)use($teacherId){
                if(is_array($teacherId)){
                    $query->where('o.serverpersonid','in',implode(',',$teacherId));
                }else{
                    $query->where('o.serverpersonid',$teacherId);
                }
            })
            ->field(['o.orderid','o.serverpersonid','lc.content','lc.score','lc.createtime as create_at','uu.avatarurl','uu.nickname','uf.realname'])
            ->select();
            if(!$listenOrders && !$consultOrders){
                return [];
            }
            $comments = array_merge($consultOrders,$listenOrders);
            array_multisort(array_column($comments, 'create_at'),SORT_DESC,$comments );
            return $comments;
        }else{
            // 咨询评价
            $comments = $order->alias('o')
            ->join('sy_ordermore om','om.orderid=o.orderid','LEFT')
            ->join('sy_listenrecord ol','ol.orderid=o.orderid','LEFT')
            ->join('sy_zixuncomment zc','zc.moreid=om.id','LEFT')
            ->join('sy_listencomment lc','lc.recordid=ol.id','LEFT')
            ->join('sy_userfield uf','uf.uid=o.serverpersonid','LEFT')
            ->join('sy_user uu','uu.id=o.uid','LEFT')
            ->where(function($query)use($teacherId){
                if(is_array($teacherId)){
                    $query->where('zc.serverpersonid','in',implode(',',$teacherId))
                    ->whereOr('lc.serverpersonid','in',implode(',', $teacherId));
                }else{
                    $query->where('zc.serverpersonid',$teacherId)->whereOr('lc.serverpersonid',$teacherId);
                }
            })
            ->field(['o.orderid','o.serverpersonid','zc.content','zc.score','zc.addtime as create_at','uu.avatarurl','uu.nickname','uf.realname'])
            ->paginate(20);
            return $comments;
        }
        
    }

    /**
     * 老师评分 满分10
     */
    public function getScore($teacherId,$sum=false)
    {
        // 1-4差评
        $listenBad = Db::name('listencomment')
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where(['serverpersonid'=>['in',implode(',', $teacherId)]]);
            }else{
                $query->where('serverpersonid',$teacherId);
            }
            $query->where('score','between','1,4');
        })
        ->sum('score');
        $consultBad = Db::name('zixuncomment')
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where(['serverpersonid'=>['in',implode(',', $teacherId)]]);
            }else{
                $query->where('serverpersonid',$teacherId);
            }
            $query->where('score','between','1,4');
        })
        ->sum('score');
        $bad = $listenBad+$consultBad;
        // 5-8中评
        $listenPass = Db::name('listencomment')
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where(['serverpersonid'=>['in',implode(',', $teacherId)]]);
            }else{
                $query->where('serverpersonid',$teacherId);
            }
            $query->where('score','between','5,8');
        })
        ->sum('score');
        $consultPass = Db::name('zixuncomment')
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where(['serverpersonid'=>['in',implode(',', $teacherId)]]);
            }else{
                $query->where('serverpersonid',$teacherId);
            }
            $query->where('score','between','5,8');
        })
        ->sum('score');
        $pass = $listenPass+$consultPass;
        // 8分以上为好评
        $listenGood = Db::name('listencomment')
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where(['serverpersonid'=>['in',implode(',', $teacherId)]]);
            }else{
                $query->where('serverpersonid',$teacherId);
            }
            $query->where('score','>','8');
        })
        ->sum('score');
        $consultGood = Db::name('zixuncomment')
        ->where(function($query)use($teacherId){
            if(is_array($teacherId)){
                $query->where(['serverpersonid'=>['in',implode(',', $teacherId)]]);
            }else{
                $query->where('serverpersonid',$teacherId);
            }
            $query->where('score','>','8');
        })
        ->sum('score');
        $good = $listenGood+$consultGood;
        if($sum){
            $total = $good+$pass+$bad;
            if($total<=0){
                return 100;
            }
            return round(($good/$total)*100,2);
        }else{
            return ['good'=>$good,'pass'=>$pass,'bad'=>$bad];
        }
    }
}
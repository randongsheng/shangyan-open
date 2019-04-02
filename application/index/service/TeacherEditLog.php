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
use app\index\model\Teacher;
use think\Validate;

/**
 * teacher专业信息修改待审核
 */
class TeacherEditLog extends Model
{
	protected $pk = 'log_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_teacher_edit_log';

    /**
     * 时间转换
     */
    public function getCreateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 时间转换
     */
    public function getUpdateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 专业信息修改通过审核
     */
    public function adopt($teacherId)
    {
        $nowTime = time();

        $teacher = new Teacher;
        $certificate = new TeacherCertificate;
        $train = new TeacherTrain;
        $supervise = new TeacherSupervise;
        $education = new TeacherEducation;
        $userfield = new UserField;

        $teacher->startTrans();
        $certificate->startTrans();
        $supervise->startTrans();
        $education->startTrans();
        $train->startTrans();
        $userfield->startTrans();

        $logs = $this->where(['teacher_id'=>$teacherId,'status'=>0])->select();
        $teacherData = Teacher::where(['teacher_id'=>$teacherId])->find();
        foreach ($logs as $k => $v) {
            switch ($v['model']) {
                case '0':
                    $info = json_decode($v['edit_content'],true);
                    $result = $teacher->where(['teacher_id'=>$teacherId])->update([
                        'video_price'=>$info['video_price'],
                        'f2f_price'=>$info['f2f_price'],
                    ]);
                    if(!empty($teacherData->uid)){
                        $userRes = $userfield->where(['uid'=>$teacherData->uid])->update([
                            'video_price'=>$info['video_price'],
                            'f2f_price'=>$info['f2f_price'],
                        ]);
                        if(!$userRes){
                            $teacher->rollBack();
                            $certificate->rollBack();
                            $supervise->rollBack();
                            $education->rollBack();
                            $train->rollBack();
                            $userfield->rollBack();
                            return false;
                        }
                    }
                    if(!$result){
                        $teacher->rollBack();
                        $certificate->rollBack();
                        $supervise->rollBack();
                        $education->rollBack();
                        $train->rollBack();
                        $userfield->rollBack();
                        return false;
                    }
                    break;

                case '1':
                    $info = json_decode($v['edit_content'],true);
                    if(empty($info)){
                        $certificate->where(['teacher_id'=>$teacherId])->delete();
                        break;
                    }
                    $result = $certificate->allowField(true)->saveAll($info);
                    if(!$result){
                        $teacher->rollBack();
                        $certificate->rollBack();
                        $supervise->rollBack();
                        $education->rollBack();
                        $train->rollBack();
                        $userfield->rollBack();
                        return false;
                    }
                    $certificate->where(['certificate_id'=>['not in',array_column(collection($result)->toArray(),'certificate_id')],'teacher_id'=>$teacherId])->delete();
                    break;

                case '2':
                    $info = json_decode($v['edit_content'],true);
                    if(empty($info)){
                        $train->where(['teacher_id'=>$teacherId])->delete();
                        break;
                    }
                    $result = $train->saveAll($info);
                    if(!$result){
                        $teacher->rollBack();
                        $certificate->rollBack();
                        $supervise->rollBack();
                        $education->rollBack();
                        $train->rollBack();
                        $userfield->rollBack();
                        return false;
                    }
                    $train->where(['train_id'=>['not in',array_column(collection($result)->toArray(),'train_id')],'teacher_id'=>$teacherId])->delete();
                    break;

                case '3':
                    $info = json_decode($v['edit_content'],true);
                    $result = $teacher->where(['teacher_id'=>$teacherId])->update([
                        'consult_number'=>$info['consult_number'],
                        'consult_duration'=>$info['consult_duration'],
                        'listen_number'=>$info['listen_number'],
                        'listen_duration'=>$info['listen_duration'],
                        'team_consult_number'=>$info['team_consult_number'],
                        'team_consult_duration'=>$info['team_consult_duration'],
                    ]);
                    if(!$result){
                        $teacher->rollBack();
                        $certificate->rollBack();
                        $supervise->rollBack();
                        $education->rollBack();
                        $train->rollBack();
                        $userfield->rollBack();
                        return false;
                    }
                    break;

                case '4':
                    $info = json_decode($v['edit_content'],true);
                    $result = $teacher->where(['teacher_id'=>$teacherId])->update([
                        'team_growth_duration'=>$info['team_growth_duration'],
                        'growth_duration'=>$info['growth_duration'],
                        'growth_at'=>$info['growth_at'],
                    ]);
                    if(!$result){
                        $teacher->rollBack();
                        $certificate->rollBack();
                        $supervise->rollBack();
                        $education->rollBack();
                        $train->rollBack();
                        $userfield->rollBack();
                        return false;
                    }
                    break;

                case '5':
                    $info = json_decode($v['edit_content'],true);
                    if(empty($info)){
                        $supervise->where(['teacher_id'=>$teacherId])->delete();
                        break;
                    }
                    $result = $supervise->saveAll($info);
                    if(!$result){
                        $teacher->rollBack();
                        $certificate->rollBack();
                        $supervise->rollBack();
                        $education->rollBack();
                        $train->rollBack();
                        $userfield->rollBack();
                        return false;
                    }
                    $supervise->where(['supervise_id'=>['not in',array_column(collection($result)->toArray(),'supervise_id')],'teacher_id'=>$teacherId])->delete();
                    break;

                case '6':
                    $info = json_decode($v['edit_content'],true);
                    if(empty($info)){
                        $education->where(['teacher_id'=>$teacherId])->delete();
                        break;
                    }
                    $result = $education->allowField(true)->saveAll($info);
                    if(!$result){
                        $teacher->rollBack();
                        $certificate->rollBack();
                        $supervise->rollBack();
                        $education->rollBack();
                        $train->rollBack();
                        $userfield->rollBack();
                        return false;
                    }
                    $education->where(['education_id'=>['not in',array_column(collection($result)->toArray(),'education_id')],'teacher_id'=>$teacherId])->delete();
                    break;
                
                default:
                    # code...
                    break;
            }
        }
        $result = $this->where(['teacher_id'=>$teacherId,'status'=>0])->update(['status'=>1]);
        $teacherResult = $teacher->where(['teacher_id'=>$teacherId])->update(['info_status'=>2]);
        if($result && $teacherResult){
            $teacher->commit();
            $certificate->commit();
            $supervise->commit();
            $education->commit();
            $train->commit();
            $userfield->commit();
            return true;
        }else{
            $teacher->rollBack();
            $certificate->rollBack();
            $supervise->rollBack();
            $education->rollBack();
            $train->rollBack();
            $userfield->rollBack();
            return false;
        }
    }

    /**
     * 获取
     */
    public function getLogData($teacherId)
    {
        $data = $this->where(['teacher_id'=>$teacherId,'status'=>0])->select();
        $resultData = [
            'info'=>[],
            'certificate'=>[],
            'train'=>[],
            'education'=>[],
            'supervise'=>[]
        ];
        foreach ($data as $k => $v) {
            switch ($v['model']) {
                case '0':// 价格咨询
                    $info = json_decode($v['edit_content'],true);
                    $resultData['info']['video_price'] = $info['video_price'];
                    $resultData['info']['f2f_price'] = $info['f2f_price'];
                    break;

                case '1':// 资质证书
                    $info = json_decode($v['edit_content'],true);
                    for ($i=0; $i < count($info); $i++) { 
                        if(!empty($info[$i]['certificate_photo']) && !Validate::is($info[$i]['certificate_photo'],'url')){
                            $info[$i]['certificate_photo'] = config('save_protocol').config('save_url').ltrim($info[$i]['certificate_photo'],'/');
                        }
                    }
                    $resultData['certificate'] = $info;
                    break;

                case '2':
                    $info = json_decode($v['edit_content'],true);
                    for ($i=0; $i < count($info); $i++) { 
                        if(!empty($info[$i]['train_photo']) && !Validate::is($info[$i]['train_photo'],'url')){
                            $info[$i]['train_photo'] = config('save_protocol').config('save_url').ltrim($info[$i]['train_photo'],'/');
                        }
                    }
                    $resultData['train'] = $info;
                    break;

                case '3':
                    $info = json_decode($v['edit_content'],true);
                    $resultData['info']['consult_number'] = $info['consult_number'];
                    $resultData['info']['consult_duration'] = $info['consult_duration'];
                    $resultData['info']['listen_number'] = $info['listen_number'];
                    $resultData['info']['listen_duration'] = $info['listen_duration'];
                    $resultData['info']['team_consult_number'] = $info['team_consult_number'];
                    $resultData['info']['team_consult_duration'] = $info['team_consult_duration'];
                    break;

                case '4':
                    $info = json_decode($v['edit_content'],true);
                    $resultData['info']['team_growth_duration'] = $info['team_growth_duration'];
                    $resultData['info']['growth_duration'] = $info['growth_duration'];
                    $resultData['info']['growth_at'] = date('Y-m-d H:i:s',$info['growth_at']);
                    break;

                case '5':
                    $supervise = json_decode($v['edit_content'],true);
                    $resultData['supervise'] = $supervise;
                    $resultData['info']['supervise_duration'] = array_sum(array_column($supervise,'supervise_duration'));
                    break;

                case '6':
                    $info = json_decode($v['edit_content'],true);
                    for ($i=0; $i < count($info); $i++) { 
                        if(!empty($info[$i]['education_photo']) && !Validate::is($info[$i]['education_photo'],'url')){
                            $info[$i]['education_photo'] = config('save_protocol').config('save_url').ltrim($info[$i]['education_photo'],'/');
                        }
                    }
                    $resultData['education'] = $info;
                    break;
                
                default:
                    break;
            }
        }
        return $resultData;
    }
}
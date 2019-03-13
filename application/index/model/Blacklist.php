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

/**
 * 黑名单
 */
class Blacklist extends Model
{
	protected $pk = 'blacklist_id';
	// 设置当前模型对应的完整数据表名称
    protected $table = 'sy_blacklist';

    /**
     * 黑名单添加
     */
    public function insertData($insertData,$client,$clientId)
    {
    	$nowTime = time();
    	switch ($client) {
    		case 'clinic':
    			$clinic = Clinic::find($clientId);
                if(!in_array($clinic->getData('status'),[2,-8])){
                    return ['success'=>false,'message'=>'当前状态不能修改'];
                }
                $clinic->status = -7;
                $clinic->run_status = 2;
    			if(!$clinic->save()){
    				return ['success'=>false,'message'=>'机构或已经加入黑名单'];
    			}
                $t = 2;
    			break;
    		case 'user':
    			$user = User::find($clientId);
                if($user->getData('status')<0){
                    return ['success'=>false,'message'=>'当前用户状态不能加入黑名单'];
                }
    			$user->status = -1;
    			if(!$user->save()){
    				return ['success'=>false,'message'=>'用户或已经加入黑名单'];
    			}
                $t = 0;
    			break;
    		case 'teacher':
    			$teacher = Teacher::find($clientId);
                if($teacher->getData('info_status')!=2){
                    return ['success'=>false,'message'=>'老师当前状态不能加入黑名单'];
                }
    			$teacher->status = -1;
    			if(!$teacher->save()){
    				return ['success'=>false,'message'=>'老师或已经加入黑名单'];
    			}
                $t = 1;
    			break;
    		
    		default:
    			return ['success'=>false,'message'=>'不存在的客户类型'];
    	}
    	foreach ($insertData as $key => $value) {
    		$createData[$key] = $value;
    	}
        $createData['create_at'] = $nowTime;
        $createData['valid_is'] = 1;
    	if($this->save($createData)){
            return ['success'=>true,'message'=>'保存成功','id'=>$this->blacklist_id];
    	}else{
    		return ['success'=>false,'message'=>'保存出错'];
    	}
    }

    /**
     * 修改
     */
    public function editData($blackId,$editData)
    {
    	$nowTime = time();
    	$black = $this->find($blackId);
    	foreach ($editData as $key => $value) {
    		$black->$key = $value;
    	}
    	$black->update_at = $nowTime;
    	if($black->save()){
    		return $black->blacklist_id;
    	}else{
    		return false;
    	}
    }

    /**
     * 取消黑名单
     */
    public function cancel($clientId,$type)
    {
    	switch ($type) {
    		case 'clinic':
    			$clinic = Clinic::find($clientId);
    			$clinic->status = 2;
                $clinic->run_status = 0;
    			if(!$clinic->save()){
    				return false;
    			}
    			$t = 2;
    			break;

    		case 'user':
    			$user = User::find($clientId);
    			$user->status = 1;
    			if(!$user->save()){
    				return false;
    			}
    			$t = 0;
    			break;

    		case 'teacher':
    			$teacher = Teacher::find($clientId);
    			$teacher->status = 1;
    			if(!$teacher->save()){
    				return false;
    			}
    			$t = 1;
    			break;
    		default:
    			return false;
    	}
    	$result = $this->where(['client_id'=>$clientId,'type'=>$t])->update(['valid_is'=>0]);
        if($result){
            return true;
        }else{
            return false;
        }
    }

    public function getReasonAttr($value)
    {
        $reasonDe = [0=>'理由1',1=>"理由二",2=>'理由二',3=>'理由二',4=>'理由二',5=>'其他'];
        return $reasonDe[$value];
    }

    public function getTypeAttr($value)
    {
        $typeDe = [0=>'用户',1=>'老师',2=>'机构'];
        return $typeDe[$value];
    }

    public function getCreateAtAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
}
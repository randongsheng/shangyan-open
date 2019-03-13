<?php
namespace app\admin\controller;
use think\Db;
use app\admin\controller;
class Innermail extends Common
{
	public function addInnerMail(){
		$theme = input('post.theme');
		$target_role = input('post.target_role');
        $mail_content = input('post.mail_content');
        $to_uid = input('post.to_uid');
        $to_uid = $to_uid?$to_uid:0;
        if (!isset($target_role)){
            sendJson(-1,'请选择角色');
        }
		$role = ['普通用户','倾听师','咨询师','商户'];
		$target_user = '';
		if (is_numeric($target_role)) {
			$target_user = $role[$target_role];
			if ($to_uid){
			    if ($target_role == 3){
			        $to_username = \db('clinic')->where(['id'=>$to_uid])->value('clinic_name');
                }elseif($target_role == 0){
                    $to_username = \db('user')->where(['id'=>$to_uid,'role'=>0])->value('nickname');
                }else{
                    $to_username = \db('userfield')->where(['uid'=>$to_uid])->value('realname');
                }
                if (!$to_username){
                    sendJson(-1,'没找到要发送的用户,请确认');
                }
                $target_user = $target_user."=>".$to_username;
            }
		}else{
            if ($to_uid){
                sendJson(-1,'多选角色时,不能发给单一用户');
            }
			$targetArr = explode(',', $target_role);
			foreach ($targetArr as $key => $value) {
				$user[]= $role[$value];
			}
			$target_user = implode('#', $user);
		}
		$target_role = ','.$target_role.',';

		$data = [
			'theme'=>$theme,
			'target_user'=>$target_user,
			'target_role'=>$target_role,
			'mail_content'=>$mail_content,
			'send_time'=>date('Y-m-d H:i:s'),
            'to_uid'=>$to_uid
		];
		db('inner_mail')->insert($data);

		sendJson(1,'推送站内信');

	}
	//获得站内信列表
	public function getInnerMailList(){
		$role = input('post.role');
		$uid = input('post.uid');
		$where = [];
		$where['target_role'] = ['like','%,'.$role.',%'];
		$where['mail_status'] = 0;
        $where['to_uid'] = [['=',0],['=',$uid],'or'];
        $where['type'] = 0;
		//获取角色能够收到的所有信息
		$count = db('inner_mail')->field('id,theme,target_user,mail_content,send_time')->where($where)->count();
		$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page',1));
    	$page = $page<=0?1:$page;
    	//print_r($totalpages);
    	$data['page'] = ['totalpages'=>$totalpages,'page'=>$page];
		$list = db('inner_mail')->field('id,theme,target_user,mail_content,send_time')->where($where)->page($page,$pageSize)->select();
		echo db('inner_mail')->getLastSql();
		//echo db('inner_mail')->getLastSql();
		
		foreach ($list as $key => $value) {
			$is_read = db('inner_mail_read')->where(['uid'=>$uid,'mail_id'=>$value['id'],'role'=>$role])->count();
			// echo db('inner_mail_read')->getLastSql();
			// print_r($is_read);
			if ($is_read == 0) {
				$list[$key]['is_read'] = 0;
			}else{
				$list[$key]['is_read'] = 1;
			}
		}
		$data['list'] = $list;
		sendJson(1,'站内信列表',$data);
		print_r($list);
	}
	//读取站内信
	public function readInnerMail(){
		$mail_id = input('post.mail_id');
		$role = input('post.role');
		$uid = input('post.uid');
		//读取站内信内容
		$where['id'] = $mail_id;
		$where['target_role'] = ['like','%,'.$role.',%'];
		$mail = db('inner_mail')->where($where)->find();
		if (!$mail) {
			sendJson(-1,'没有权限读取该站内信');
		}
		$is_read = db('inner_mail_read')->where(['uid'=>$uid,'id'=>$mail_id,'role'=>$role])->count();
		if ($is_read == 0) {
			//如果没有读过,就添加到已读表中
			$data = [
				'role'=>$role,
				'uid'=>$uid,
				'mail_id'=>$mail_id,
				'read_time'=>date('Y-m-d H:i:s')
			];
			db('inner_mail_read')->insert($data);
		}
		//读取站内信内容
		$where['id'] = $mail_id;
		$where['target_role'] = ['like','%,'.$role.',%'];
		$mail = db('inner_mail')->where($where)->find();
		sendJson(1,'读取站内信内容',$mail);
	}
	/**
     * 后台获取站内信列表
     */
    public function backGetInnerMailList(){
        $where = [];
        $where['mail_status'] = 0;
        $where['type'] = 0;
        //获取角色能够收到的所有信息
        $count = db('inner_mail')->field('id,theme,target_user,mail_content,send_time')->where($where)->count();
        $pageSize = 10;
        $totalpages = ceil($count/$pageSize);
        $page = ceil(input('post.page',1));
        $page = $page<=0?1:$page;
        //print_r($totalpages);
        $data['page'] = ['pagesize'=>$pageSize,'page'=>$page,'count'=>$count];
        $list = db('inner_mail')->field('id,theme,target_user,mail_content,send_time')->where($where)->page($page,$pageSize)->order('id','desc')->select();
        $data['list'] = $list;
        sendJson(1,'后台站内信列表',$data);
    }
    /**
     * 后台读取站内信
     */
    public function backReadInnerMail(){
        $mail_id = input('post.mail_id');
        //读取站内信内容
        $where['id'] = $mail_id;
        $mail = db('inner_mail')->where($where)->find();
        sendJson(1,'后台读取站内信内容',$mail);
    }
}
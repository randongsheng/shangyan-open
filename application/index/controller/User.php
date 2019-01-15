<?php
namespace app\index\controller;
use think\Request;
use app\index\model\Admin;
use app\index\model\User as UserModel;
use app\index\model\Teacher;
use app\index\model\Clinic;
use app\index\model\Blacklist;
use think\Session;
use think\Db;

class User extends Base
{
	/**
	 * 用户查询
	 */
	public function queryUsers()
	{
		$request = Request::instance();
		// 用户ID 昵称 性别 是否测试过 是否倾诉过 是否咨询过 注册时间[today|yesterday] 开始时间(筛选注册) 结束时间(筛选注册)
		$post = $request->only(['user_id','nickname','sex','if_test','if_listen','if_consult','regster_time','regster_start_time','regster_end_time','label_id']);
		$nowTime = time();
		$userWhere = [];
		$user = new UserModel;
		if(!empty($post['user_id'])){
			$userWhere['u.id'] = $post['user_id'];
		}
		if(!empty($post['nickname'])){
			$userWhere['u.nickname'] = ['like','%'.$post['nickname'].'%'];
		}
		if(!empty($post['sex'])){
			$userWhere['u.gender'] = $post['sex'];
		}
		// 查找标签ID（表结构被迫）
		if(!empty($post['label_id'])){
			$userWhere['u.topic'] = ['like','%,'.trim($post['label_id']).',%'];
		}
		// 不能同时筛选三个条件（需要些10种不同sql）
		if(!empty($post['if_test'])||!empty($post['if_listen'])||!empty($post['if_consult'])){
			$userWhere['u.id'] = ['in',
			function($query)use($post){
				if(!empty($post['if_test'])&&$post['if_test']==1){
					// 是否测试过
					$query->table('sy_exam_result')->alias('e')->where('e.uid=u.id')->field('uid');
				}else if(!empty($post['if_listen'])&&$post['if_listen']==1){
					// 是否倾诉过
					$query->table('sy_order')->alias('o')->where('o.uid=u.id')->where('o.type',1)->field('uid');
				}else if(!empty($post['if_consult'])&&$post['if_consult']==1){
					// 是否咨询过
					$query->table('sy_order')->alias('o')->where('o.uid=u.id')->where('o.type',2)->field('uid');
				}
			}];
		}
		// 昨天今天注册用户
		if(!empty($post['regster_time']) && $post['regster_time']=='today'){
			$todayFirst = strtotime(date('Ymd 00:00:00',$nowTime));
			$todayEnd = strtotime(date('Ymd 23:59:59',$nowTime));
			$post['regster_start_time'] = $todayFirst;
			$post['regster_end_time'] = $todayEnd;
		}else if(!empty($post['regster_time']) && $post['regster_time']=='yesterday'){
			$yesterdayFirst = strtotime('yesterday');
			$yesterdayEnd = strtotime(date('Ymd 23:59:59',$yesterdayFirst));
			$post['regster_start_time'] = $yesterdayFirst;
			$post['regster_end_time'] = $yesterdayEnd;
		}
		$userWhere['role'] = 0;
		// 测试订单统计
		$examResultSql = Db::table('sy_examorder')
		->alias('e')
	    ->where('u.id=e.uid')
	    ->field('count(*)')
	    ->buildSql();
	    // 倾听订单统计
	    $listenSql = Db::table('sy_order')
		->alias('o')
	    ->where('u.id=o.uid')
	    ->where('o.type',1)
	    ->field('count(*)')
	    ->buildSql();
	    // 咨询订单统计
	    $consultSql = Db::table('sy_order')
		->alias('o')
	    ->where('u.id=o.uid')
	    ->where('o.type',2)
	    ->field('count(*)')
	    ->buildSql();
	    // 用户
		$users = $user
		->alias('u')
		->where($userWhere)
		->where(function($query)use($post){
			if(!empty($post['regster_start_time'])&&!empty($post['regster_start_time'])){
				$query->where('u.regtime','between',[$post['regster_start_time'],$post['regster_start_time']]);
			}
		})
		->fieldRaw('id,role,status,nickname,mobile,level,regtime,age,gender,avatarurl,'.$examResultSql.' as test_con,'.$listenSql.' as listen_con,'.$consultSql.' as consult_con')
		->order('u.regtime','desc')
		->paginate(20);
		if($users){
			return $this->message(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$users]);
		}else{
			return $this->message(['success'=>false,'code'=>'013','message'=>'竟然还没有用户注册！']);
		}
	}

	/**
	 * 用户统计
	 */
	public function statistics()
	{
		$nowTime = time();
		$dayFirst = strtotime(date('Ymd 00:00:00',$nowTime));
		$dayEnd = strtotime(date('Ymd 23:59:59',$nowTime));
		$yesterDayFirst = strtotime(date('Ymd 00:00:00',$nowTime-(60*60*24)));
		$yesterDayEnd = strtotime(date('Ymd 23:59:59',$nowTime-(60*60*24)));
		$user = new UserModel;
		$blacklist = new Blacklist;
		// 所有用户
		$usercount = $user->where(['role'=>0])->count();
		// 今日注册用户
		$daynowreg = $user->where(['role'=>0])->whereTime('regtime','between',[$dayFirst,$dayEnd])->count();
		// 昨天注册用户
		$yesterReg = $user->where(['role'=>0])->whereTime('regtime','between',[$yesterDayFirst,$yesterDayEnd])->count();
		// 活跃用户
		$actives = $user->where(['role'=>0])->whereTime('last_login_time','between',[$dayFirst,$dayEnd])->count();
		// 黑名单
		$black = $blacklist->where(['type'=>0,'valid_is'=>1])->count();
		// 返回结果
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>compact('usercount','daynowreg','yesterReg','actives','black')]);
	}

	/**
	 * 用户详情
	 */
	public function details()
	{
		$userId = input('param.user_id');
		$user = new UserModel;
		$userData = $user->where('id',$userId)->field('password',true)->find();
		if($userData){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$userData]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'您查看的用户不存在或已经注销']);
		}
	}

	/**
	 * 用户评论列表
	 */
	public function getUserComment(){
        $uid = input('param.user_id');
        $list = Db::name('comment c')
        ->field('c.id,articleid,a.title articletitle,unick,c.content,c.createtime,u.avatarurl')
        ->join('articles a','c.articleid = a.id','LEFT')
        ->join('user u','c.uid = u.id')
        ->where(['uid'=>$uid,'replyid'=>0])
        ->select();
        if(!$list){
        	return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>[]]);
        }
        foreach ($list as $key => $value) {
            list($list[$key]['soncount'],$list[$key]['son']) = $this->getSonComment($value['id']);
            $list[$key]['fabulous'] = db('fabulous')->where(['type'=>2,'id'=>$value['id']])->count();
        }
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$list]);        
    }

    /**
     * 获取子评论
     */
    public function getSonComment($fatherid)
    {
        $son = Db::name('comment')
        ->where(['replyid'=>$fatherid])
        ->select();
        $soncount = count($son);
        return [$son,$soncount];
    }

    /**
     * 文章点赞列表
     */
    public function getUserFabulous()
    {
        $uid = input('param.user_id');
        $list = Db::name('fabulous f')->join('articles a','f.id = a.id')->where(['uid'=>$uid,'f.status'=>1,'f.type'=>1])->select();
        return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$list]);
    }

    /**
     * 文章收藏
     */
    public function getUserCollection()
    {
        $uid = input('param.user_id');
        $list = Db::name('collentiom c')->join('articles a','c.articleid = a.id')->where(['uid'=>$uid])->select();
        return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$list]);
    }

	/**
	 * down excel
	 */
	public function downExcel()
	{
		$users = $this->queryUsers();
		if(!$users['success']){
			return $this->message($users);
		}
		$data = $users['data'];
		//引用phpexcel
		$excel = new \PHPExcel();
		$name = '尚言心理_截至'.date('m月d日H时').'用户数据';
		//针对中文名转码
	 	iconv('UTF-8', 'gb2312', $name);
		//表头,名称可自定义
	    $header= ['ID','昵称','性别','年龄','等级','手机','测试订单','倾听订单','咨询订单'];
	    $excel->setActiveSheetIndex(0);
	    //设置表名
	    $excel->getActiveSheet()->setTitle($name);
	    $excel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(18);
	    // $excel->getActiveSheet()->getColumnDimension('B')->setWidth(80);
	 	//列坐标
	    $letter = ['A','B','C','D','E','F','G','H','I'];
		//生成表头
		for($i=0;$i<count($header);$i++){
	        //设置表头值
	        $excel->getActiveSheet()->setCellValue("$letter[$i]1",$header[$i]);
	        //设置表头字体样式
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFont()->setName('宋体');
	        //设置表头字体大小
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFont()->setSize(14);
	        //设置表头字体是否加粗
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFont()->setBold(true);
	        //设置表头文字水平居中
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	        //设置文字上下居中
	        $excel->getActiveSheet()->getStyle($letter[$i])->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
	        //设置单元格背景色
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFill()->getStartColor()->setARGB('FFFFFFFF');
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFill()->getStartColor()->setARGB('FF6DBA43');
	        //设置字体颜色
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFont()->getColor()->setARGB('FFFFFFFF');
	    }
		
	    //写入数据
	    foreach($data as $k=>$v){
			//从第二行开始写入数据（第一行为表头）
			$excel->getActiveSheet()->setCellValue('A'.($k+2),$v['id']);
			$excel->getActiveSheet()->setCellValue('B'.($k+2),$v['nickname']);
			$excel->getActiveSheet()->setCellValue('C'.($k+2),$v['gender']);
			$excel->getActiveSheet()->setCellValue('D'.($k+2),$v['age']);
			$excel->getActiveSheet()->setCellValue('E'.($k+2),$v['level']);
			$excel->getActiveSheet()->setCellValue('F'.($k+2),$v['mobile']);
			$excel->getActiveSheet()->setCellValue('G'.($k+2),$v['test_con']);
			$excel->getActiveSheet()->setCellValue('H'.($k+2),$v['listen_con']);
			$excel->getActiveSheet()->setCellValue('I'.($k+2),$v['consult_con']);
		}
		
		//设置单元格边框
		$excel->getActiveSheet()->getStyle("A1:E".(count($data)+1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
		
		//清理缓冲区，避免中文乱码
		ob_end_clean();
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$name.'.xlsx"');
		header('Cache-Control: max-age=0');
		
		//导出数据
		$res_excel = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$res_excel->save('php://output');
	}

	/**
	 * 加入黑名单
	 */
	public function blacklistAction()
	{
		$request = Request::instance();
		$post = $request->only(['black_reason','user_id','black_reason_other']);
		$vali = $this->validate($post,'BlacklistValidate.user');
		if( $vali !== true ){
			return json(['success'=>false,'code'=>'002','message'=>$vali]);
		}
		$insertData = [];
		$blacklist = new Blacklist;
		if(!empty($post['black_reason_other'])){
			$insertData['reason_other'] = $post['black_reason_other'];
		}
		$insertData['client_id'] = $post['user_id'];
		$insertData['reason'] = $post['black_reason'];
		$result = $blacklist->insertData($insertData,'user',$post['user_id']);
		if($result['success']){
			return json(['success'=>true,'code'=>'000','message'=>'成功加入黑名单']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>$result['message']]);
		}
	}

	/**
	 * 取消黑名单
	 */
	public function blacklistClean()
	{
		$userId = input('param.user_id');
		if(empty($userId)){
			return json(['success'=>false,'code'=>'002','message'=>'用户ID不能为空']);
		}
		$blacklist = new Blacklist;
		$result = $blacklist->cancel($userId,'user');
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'取消成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'撤出黑名单出错，请稍后重试']);
		}
	}

	/**
	 * 获取黑名单列表
	 */
	public function blacklist()
	{
		$request = Request::instance();
		$post = $request->only(['attr','in_time','key_words']);
		$user = new UserModel;
		$teacher = new Teacher;
		$clinic = new Clinic;
		$where = [];
		if(!empty($post['attr'])){
			$where['type'] = $post['attr'];
		}
		if(!empty($post['in_time'])){
			$startTime = strtotime(date('Y-m-d 00:00:00',strtotime($post['in_time'])));
			$endTime = strtotime(date('Y-m-d 23:59:59',strtotime($post['in_time'])));
			$where['create_at'] = ['between',[$startTime,$endTime]];
		}
		if(!empty($post['key_words'])){
			// $where['reason_other'] = 
		}
		$where['valid_is'] = 1;
		$blacklist = Blacklist::
		where($where)
		->order('create_at','desc')
		->paginate(15);
		$blacklist->each(function($item)use($user,$teacher,$clinic){
			switch ($item->getData('type')) {
				case '0':
					$item->name = $user->where(['id'=>$item->client_id])->value('nickname');
					break;
				case '1':
					$item->name = $teacher->where(['teacher_id'=>$item->client_id])->value('teacher_name');
					break;
				case '2':
					$item->name = $clinic->where(['id'=>$item->client_id])->value('clinic_name');
					break;
				default:
					# code...
					break;
			}
		});
		return json(['success'=>true,'code'=>'000','message'=>'查询完成','data'=>$blacklist]);
	}

	/**
	 * 黑名单统计
	 */
	public function blacklistCount()
	{
		$blacklist = new Blacklist;
		// 所有黑名单
		$all = $blacklist->where(['valid_is'=>1])->count();
		// 用户
		$user = $blacklist->where(['valid_is'=>1,'type'=>0])->count();
		// 老师
		$teacher = $blacklist->where(['valid_is'=>1,'type'=>1])->count();
		// 商户
		$clinic = $blacklist->where(['valid_is'=>1,'type'=>2])->count();
		return json(['success'=>true,'code'=>'000','message'=>'统计完成','data'=>compact('all','user','teacher','clinic')]);
	}
}
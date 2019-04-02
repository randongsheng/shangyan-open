<?php
namespace app\index\controller;
use think\Request;
use think\Session;
use app\index\model\User;
use app\index\service\UserField;
use app\index\model\Teacher as TeacherModel;
use app\index\model\Clinic;
use app\index\service\TeacherCertificate;
use app\index\service\TeacherEducation;
use app\index\service\TeacherSupervise;
use app\index\service\TeacherTrain;
use app\index\service\OrderConsult;
use app\index\service\OrderListen;
use app\index\service\TeacherRelevantRecord;
use app\index\service\TeacherEditLog;
use app\index\model\Topic;
use app\index\model\Order;
use app\index\model\Blacklist;
use app\index\service\InnerMail;
use think\Db;
use think\cache\driver\Redis;

/**
 * 老师管理
 */
class Teacher extends Base
{
	/**
	 * 所有老师
	 */
	public function teachers()
	{
		$request = Request::instance();
		$post = $request->only(['teacher_id','teacher_name','teacher_sex','teacher_status','teacher_service','teacher_service_status']);
		$item = input('param.item',20);
		$teacherWhere = [];
		// 老师ID
		if(!empty($post['teacher_id'])){
			$teacherWhere['t.teacher_id'] = $post['teacher_id'];
		}
		if(!empty($post['teacher_name'])){
			$teacherWhere['t.teacher_name'] = ['like','%'.$post['teacher_name'].'%'];
		}
		if(isset($post['teacher_sex'])&&$post['teacher_sex']!=''){
			// $sexDe = ['未知'=>0,'男'=>1,'女'=>2];
			$teacherWhere['t.sex'] = $post['teacher_sex'];
		}
		if(isset($post['teacher_status'])&&$post['teacher_status']!=''){
			if($post['teacher_status']==1){
				$teacherWhere['t.info_status'] = ['in','-2,1'];
			}else{
				$teacherWhere['t.info_status'] = $post['teacher_status'];
			}
		}
		if(isset($post['teacher_service'])&&$post['teacher_service']!=''){
			switch ($post['teacher_service']) {
				case 'listen':
					$teacherWhere['t.teacher_role'] = '0';
					break;
				case 'consult':
					$teacherWhere['t.teacher_role'] = ['in','1,2,3'];
					break;
			}
		}
		if(isset($post['teacher_service_status'])&&$post['teacher_service_status']!=''){
			$teacherWhere['u.serverstatus'] = $post['teacher_service_status'];
		}
		$teacher = new TeacherModel;
		$teachers = $teacher->alias('t')
		->join('sy_userfield uf','uf.uid=t.uid','LEFT')
		->join('sy_user u','t.uid=u.id','LEFT')
		->join('sy_clinic c','c.id=t.clinic_id','LEFT')
		->where($teacherWhere)
		->field([
			't.teacher_photo','t.teacher_id','t.teacher_name','t.sex','t.birthday','u.level','t.teacher_tel','c.clinic_name','t.teacher_role','u.serverstatus','t.info_status','t.create_at'
		])
		->order('t.create_at','desc')
		->paginate($item);
		if($teachers){
			return $this->message(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$teachers]);
		}else{
			return $this->message(['success'=>false,'code'=>'013','message'=>'查询出错，请稍后重试']);
		}
	}

	/**
	 * 老师导出
	 */
	public function download()
	{
		$teachers = $this->teachers();
		if(!$teachers['success']){
			return $this->message($teachers);
		}
		$data = $teachers['data'];
		// 引用phpexcel
		$excel = new \PHPExcel();
		$name = '尚言心理_截至'.date('m月d日H时').'用户数据';
		// 针对中文名转码
	 	iconv('UTF-8', 'gb2312', $name);
		// 表头,名称可自定义
	    $header= ['ID','姓名','性别','年龄','等级','手机','所属商户','服务','状态'];
	    $excel->setActiveSheetIndex(0);
	    // 设置表名
	    $excel->getActiveSheet()->setTitle($name);
	    $excel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(18);
	    // $excel->getActiveSheet()->getColumnDimension('B')->setWidth(80);
	 	// 列坐标
	    $letter = ['A','B','C','D','E','F','G','H','I'];
		// 生成表头
		for($i=0;$i<count($header);$i++){
	        // 设置表头值
	        $excel->getActiveSheet()->setCellValue("$letter[$i]1",$header[$i]);
	        // 设置表头字体样式
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFont()->setName('宋体');
	        // 设置表头字体大小
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFont()->setSize(14);
	        // 设置表头字体是否加粗
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFont()->setBold(true);
	        // 设置表头文字水平居中
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	        // 设置文字上下居中
	        $excel->getActiveSheet()->getStyle($letter[$i])->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
	        // 设置单元格背景色
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFill()->getStartColor()->setARGB('FFFFFFFF');
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFill()->getStartColor()->setARGB('FF6DBA43');
	        // 设置字体颜色
	        $excel->getActiveSheet()->getStyle("$letter[$i]1")->getFont()->getColor()->setARGB('FFFFFFFF');
	    }
		
	    // 写入数据
	    foreach($data as $k=>$v){
			// 从第二行开始写入数据（第一行为表头）
			$excel->getActiveSheet()->setCellValue('A'.($k+2),$v['teacher_id']);
			$excel->getActiveSheet()->setCellValue('B'.($k+2),$v['teacher_name']);
			$excel->getActiveSheet()->setCellValue('C'.($k+2),$v['sex']);
			$excel->getActiveSheet()->setCellValue('D'.($k+2),$v['birthday']);
			$excel->getActiveSheet()->setCellValue('E'.($k+2),$v['level']);
			$excel->getActiveSheet()->setCellValue('F'.($k+2),$v['teacher_tel']);
			$excel->getActiveSheet()->setCellValue('G'.($k+2),$v['clinic_name']);
			$excel->getActiveSheet()->setCellValue('H'.($k+2),$v['teacher_role']);
			$excel->getActiveSheet()->setCellValue('I'.($k+2),$v['serverstatus']);
		}
		
		// 设置单元格边框
		$excel->getActiveSheet()->getStyle("A1:E".(count($data)+1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
		
		// 清理缓冲区，避免中文乱码
		ob_end_clean();
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$name.'.xlsx"');
		header('Cache-Control: max-age=0');
		
		// 导出数据
		$res_excel = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$res_excel->save('php://output');
	}

	/**
	 * 统计老师
	 */
	public function teacherCount()
	{
		$user = new User;
		$teacher = new TeacherModel;
		$blacklist = new Blacklist;
		// 全部老师
		$all = $user->where(['role'=>['between','1,2']])->count();
		// 咨询师
		$consult = $user->where(['role'=>2])->count();
		// 倾听师
		$listen = $user->where(['role'=>1])->count();
		// 待审
		$trial = $teacher->where(['info_status'=>['in','-2,1']])->count();
		// 黑名单
		$black = $blacklist->where(['type'=>1,'valid_is'=>1])->count();
		return json(['success'=>true,'code'=>'000','message'=>'统计完成','data'=>compact('all','consult','listen','trial','black')]);
	}

	/**
	 * 老师详情
	 */
	public function details()
	{
		$teacherId = input('param.teacher_id');
		$details = input('param.details',0);
		$teacherWhere = [];
		$certificate = new TeacherCertificate;
		$education = new TeacherEducation;
		$supervise = new TeacherSupervise;
		$train = new TeacherTrain;
		$teacher = new TeacherModel;
		$editlog = new TeacherEditLog;
		$topic = new Topic;
		$user = new User;
		$teacherWhere['t.teacher_id'] = $teacherId;
		if($details==0){
			$listenOrder = Db::name('order')->alias('o')->where('o.type',1)->where('o.serverpersonid=t.uid')->field('count(*)')->buildSql();
			$listenTime = Db::name('order')->alias('o')->where('o.type',1)->where('o.serverpersonid=t.uid')->field('sum(o.alltime)')->buildSql();
			$consultOrder = Db::name('order')->alias('o')->where('o.type',2)->where('o.serverpersonid=t.uid')->field('count(*)')->buildSql();
			$consultTime = Db::name('order')->alias('o')
			->join('sy_ordermore om','om.orderid=o.orderid')
			->where('o.type',2)
			->where('o.serverpersonid=t.uid')
			->field('sum(om.timelong)')
			->buildSql();
			$userData = $teacher->alias('t')
			->join('sy_userfield uf','uf.uid=t.uid','LEFT')
			->join('sy_user u','t.uid=u.id','LEFT')
			->where($teacherWhere)
			->field([
				't.teacher_name','u.level','t.sex','t.birthday','t.teacher_id','t.create_at','t.teacher_tel','uf.listentopic','uf.zixuntopic','u.serverstatus',$listenOrder.' as listen_con',$listenTime.' as listen_time_sum',$consultOrder.' as consult_con',$consultTime.' as consult_time_con','t.uid','null as certificate','t.info_status','t.teacher_photo','t.listen_label','t.consult_label','t.team_consult_number','t.team_consult_duration'
			])
			->find();
			if(empty($userData)){
				return json(['success'=>false,'code'=>'013','message'=>'没有这个老师或已经注销']);
			}
		}else{
			$userData['info'] = $teacher->alias('t')
			->join('sy_userfield uf','uf.uid=t.uid','LEFT')
			->join('sy_user u','t.uid=u.id','LEFT')
			->where($teacherWhere)
			->field([
				't.teacher_role','t.teacher_tel','u.level','u.serverstatus','u.avatarurl',
				'u.status','u.nickname','t.sex','t.city','t.province','t.area','u.country','uf.*',
				'u.age','t.update_at','t.create_at','t.profile','t.consult_number','t.consult_duration',
				't.listen_number','t.listen_duration','t.growth_duration','null as end_at','t.cooper_at',
				't.adopt_at','t.teacher_name','t.teacher_photo','null as teacher_birthday','t.birthday',
				't.enter_date','t.listen_label','t.consult_label','t.video_price','t.f2f_price',
				't.growth_at','t.team_consult_number','t.team_consult_duration','t.info_status'
			])
			->find();
			if(empty($userData['info'])){
				return json(['success'=>false,'code'=>'013','message'=>'没有这个老师或已经注销']);
			}
			$userData['certificate'] = $certificate->where(['teacher_id'=>$teacherId])->order('create_at','desc')->select();
			$userData['education'] = $education->where(['teacher_id'=>$teacherId])->order('create_at','desc')->select();
			$userData['supervise'] = $supervise->where(['teacher_id'=>$teacherId])->order('create_at','desc')->select();
			$userData['train'] = $train->where(['teacher_id'=>$teacherId])->order('create_at','desc')->select();

			// 倾诉服务
			$listens = Order::alias('o')
			->where(['o.serverpersonid'=>$userData['info']->uid])
			->column('o.alltime');
			// 倾诉多少例
			$userData['info']->listen_total = count($listens);
			// 倾诉总时长
			$userData['info']->listen_time = time_to_date(array_sum($listens));
			// 咨询服务
			$consultVideo = Order::alias('o')
			->join('sy_ordermore om','om.orderid=o.orderid')
			->where(['o.serverpersonid'=>$userData['info']->uid,'om.mode'=>1])
			->column('om.timelong');
			$consultFtf = Order::alias('o')
			->join('sy_ordermore om','om.orderid=o.orderid')
			->where(['o.serverpersonid'=>$userData['info']->uid,'om.mode'=>2])
			->column('om.timelong');
			$userData['info']->listen_start_time = Db::name('system')->where(['business_module'=>'listen','name'=>'starting_time'])->value('value');
			$userData['info']->listen_start_price = Db::name('system')->where(['business_module'=>'listen','name'=>'starting_price'])->value('value');
			$userData['info']->consult_video_num = count($consultVideo);
			$consultVideoTime = array_sum($consultVideo);
			$userData['info']->consult_video_time = time_to_date($consultVideoTime);
			$userData['info']->consult_ftf_num = count($consultFtf);
			$consultFtfTime = array_sum($consultFtf);
			$userData['info']->consult_ftf_time = time_to_date($consultFtfTime);
			$userData['info']->consult_all_time = time_to_date($consultFtfTime+$consultVideoTime);
			$userData['info']->consult_all_num = ($userData['info']->consult_video_num+$userData['info']->consult_ftf_num);
			$userData['info']->praise_rate = $teacher->getScore($userData['info']->uid,true);
			$userData['info']->supervise_duration = array_sum(array_column(collection($userData['supervise'])->toArray(),'supervise_duration'));
			$userData['info']->level = empty($userData['info']->level)||$userData['info']->level==0?1:$userData['info']->level;
			$system = Db::name('system')->where('business_module','listen')->select();
			$spriceConfig = Db::name('system')->where(['business_module'=>'consultation','name'=>'lv'.$userData['info']->level.'_sprice'])->value('value');
			$epriceConfig = Db::name('system')->where(['business_module'=>'consultation','name'=>'lv'.$userData['info']->level.'_eprice'])->value('value');
			// 起步时间
			$userData['info']->starting_time = $system[0]['value'];
			// 起步价
			$userData['info']->starting_price = $system[1]['value'];
			// 递增时间
			$userData['info']->inc_time = $system[2]['value'];
			// 递增价格
			$userData['info']->inc_price = $system[3]['value'];
			$spriceName = 'lv_sprice';
			$epriceName = 'lv_eprice';
			$userData['info']->$spriceName = $spriceConfig;
			$userData['info']->$epriceName = $epriceConfig;
			$userData['edit_log'] = $editlog->getLogData($teacherId);
		}
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$userData]);
	}

	/**
	 * 老师访客
	 */
	public function visitors()
	{
		$teacherId = input('param.teacher_id');
		$item = input('param.item',20);
		$order = new Order;
		$teacher = new TeacherModel;
		$uid = $teacher->where(['teacher_id'=>$teacherId])->value('uid');
		if(empty($uid)){
			return json(['success'=>true,'code'=>'000','message'=>'暂时还没有来访客户！','data'=>[]]);
		}
		$users = $order->alias('o')->join('sy_user u','o.uid=u.id')
		->where(['o.serverpersonid'=>$uid])
		->field(['u.avatarurl','u.id'])
		->group('o.uid')
		->order('o.createtime','desc')
		->paginate($item);
		if($users){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$users]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'暂时还没有来访客户！']);
		}
	}

	/**
	 * 老师动态
	 */
	public function trends()
	{
		$request = Request::instance();
		$teacherId = input('param.teacher_id');
		$user = new User;
		$teacher = new TeacherModel;
		$relevant = new TeacherRelevantRecord;
		$consoltSql = Db::name('ordermore')->alias('om')->where('om.orderid=o.orderid')->field('count(*)')->buildSql();
		// 老师评价
		$uId = $teacher->where('teacher_id',$teacherId)->value('uid');
		if(empty($uId)){
			$comments = [];
		}else{
			$comments = $teacher->getComments($uId);
		}
		// 倾听订单
		$listenOrders = $user->alias('u')
				->join('sy_order o','o.serverpersonid=u.id','LEFT')
				->join('sy_listenrecord ol','ol.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where(['u.id'=>$uId,'o.type'=>1,'o.status'=>['>',1]])
				->where(['o.status'=>['<>',3]])
				->field([
					'ol.stime','ol.etime','o.content',
					'o.orderid','o.alltime','o.sytime',
					'o.type','uf.realname','u.avatarurl',
					'uu.gender','uu.nickname','o.topic'
				])
				->order('o.createtime','desc')
				->limit(10)
				->select();
		// 咨询订单
		$consultOrders = $user->alias('u')
				->join('sy_order o','o.serverpersonid=u.id','LEFT')
				->join('sy_ordermore om','om.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where(['u.id'=>$uId,'o.type'=>2,'o.status'=>['>',1]])
				->where(['o.status'=>['<>',3]])
				->field([
					'o.content','o.orderid','om.starttime',
					'om.endtime','o.alltime','o.sytime','om.number',
					'o.type',$consoltSql.' as consult_con','uf.realname',
					'u.avatarurl','uu.gender','uu.nickname','o.topic',
					'om.stime','om.etime'
				])
				->order('o.createtime','desc')
				->limit(10)
				->select();

		// 合作记录
		$relevantData = $relevant->getAllTeacherRele($teacherId);
		$teacherData = [
			'listen_orders'=>$listenOrders,
			'consult_orders'=>$consultOrders,
			'comments'=>$comments,
			'relevant'=>$relevantData,
		];
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$teacherData]);
	}

	/**
	 * 老师订单动态
	 */
	public function trendsOrders()
	{
		$request = Request::instance();
		$teacherId = input('param.teacher_id');
		$type = input('param.type',1);
		$user = new User;
		$teacher = new TeacherModel;
		$uId = $teacher->where('teacher_id',$teacherId)->value('uid');
		$consoltSql = Db::name('ordermore')->alias('om')->where('om.orderid=o.orderid')->field('count(*)')->buildSql();
		switch ($type) {
			case 1:
				$teacherData = $user->alias('u')
				->join('sy_order o','o.serverpersonid=u.id','LEFT')
				->join('sy_listenrecord ol','ol.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where([ 'u.id'=>$uId,'o.type'=>1,'o.status'=>['>',1] ])
				->where([ 'o.status'=>['<>',3] ])
				->field([
					'ol.stime','ol.etime','o.content',
					'o.orderid','o.alltime','o.sytime',
					'o.type','uf.realname','u.avatarurl',
					'uu.gender','uu.nickname','o.topic'
				])
				->order('o.createtime','desc')
				->paginate(20);
				break;

			case 2:
				$teacherData = $user->alias('u')
				->join('sy_order o','o.serverpersonid=u.id','LEFT')
				->join('sy_ordermore om','om.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where([ 'u.id'=>$uId,'o.type'=>2,'o.status'=>['>',1] ])
				->where([ 'o.status'=>['<>',3] ])
				->field([
					'o.content','o.orderid','om.starttime',
					'om.endtime','o.alltime','o.sytime','om.number',
					'o.type',$consoltSql.' as consult_con','uf.realname',
					'u.avatarurl','uu.gender','uu.nickname','o.topic'
				])
				->order( 'o.createtime', 'desc' )
				->paginate(20);
				break;
			
			default:
				$teacherData = [];
				break;
		}
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$teacherData]);
	}

	/**
	 * 来访者评价
	 */
	public function evaluate()
	{
		$teacherId = input('param.teacher_id');
		$teacher = new TeacherModel;
		$uId = $teacher->where('teacher_id',$teacherId)->value('uid');
		if(empty($uId)){
			return json(['success'=>true,'code'=>'000','data'=>[]]);
		}else{
			$comments = $teacher->getComments($uId);
			return json(['success'=>true,'code'=>'000','data'=>$comments]);
		}
	}

	/**
	 * 老师审核
	 */
	public function examine()
	{
		$request = Request::instance();
		$teacherId = input('param.teacher_id');
		$apply = input('param.apply');
		$reason = input('param.reason','请仔细填写');
		$applyDe = ['reject'=>0,'adopt'=>1,'adopt_up'=>2];
		$innermail = new InnerMail;
		$nowTime = time();
		if(!array_key_exists($apply, $applyDe)){
			return json(['success'=>false,'code'=>'002','message'=>$apply.'参数错误']);
		}
		$relevant = new TeacherRelevantRecord;
		$teacherData = TeacherModel::get($teacherId);
		if($teacherData->getData('info_status')!=1){
			return json(['success'=>false,'code'=>'007','message'=>'当前状态不能修改']);
		}
		$releData = [
			'teacher_id'=>$teacherData->teacher_id,
			'uid'=>$teacherData->uid,
			'clinic_id'=>$teacherData->clinic_id
		];
		switch ($applyDe[$apply]) {
			case 0:
				$teacherData->info_status = -1;
				// 发送给机构
				$innermail->addInnerMail(
					$teacherData->teacher_name.'老师的资料审核未通过！',
					3,
					$teacherData->teacher_name.'老师的申请未能通过审核，原因如下：'.$reason,
					$teacherData->clinic_id,
					5
				);
				if(!empty($teacherData->uid)){
					$innermail->addInnerMail(
						$teacherData->teacher_name.'老师的资料审核未通过！',
						1,
						$teacherData->teacher_name.'老师的申请未能通过审核，原因如下：'.$reason,
						$teacherData->uid,
						5
					);
				}
				break;
			
			case 1:
			case 2:
				$teacherData->info_status = 2;
				$teacherData->status = 1;
				$teacherData->adopt_at = $nowTime;
				$relevant->addRelevant($releData);
				$innermail->addInnerMail(
					$teacherData->teacher_name.'老师已通过审核！',
					3,
					'恭喜您，'.$teacherData->teacher_name.'老师资料已审核通过，即日起便可以在尚言心理平台接单了！',
					$teacherData->clinic_id,
					5
				);
				if(!empty($teacherData->uid)){
					$innermail->addInnerMail(
						$teacherData->teacher_name.'老师已通过审核！',
						1,
						'恭喜您，'.$teacherData->teacher_name.'老师资料已审核通过，即日起便可以在尚言心理平台接单了！',
						$teacherData->uid,
						5
					);
				}
				break;

			default:
				return json(['success'=>false,'code'=>'002','message'=>$apply.'参数错误']);
		}
		$teacherData->cooper_at = $nowTime;
		$teacherData->update_at = $nowTime;
		if($teacherData->save()){
			return json(['success'=>true,'code'=>'000','message'=>'状态已更新！']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'状态更新出错。']);
		}
	}

	/**
	 * 加入黑名单
	 */
	public function blacklistAction()
	{
		$request = Request::instance();
		$post = $request->only(['black_reason','teacher_id','black_reason_other']);
		$vali = $this->validate($post,'BlacklistValidate.teacher');
		$teacher = new TeacherModel;
		if( $vali !== true ){
			return json(['success'=>false,'code'=>'002','message'=>$vali]);
		}
		$teacherData = $teacher->where(['teacher_id'=>$post['teacher_id']])->field(['teacher_id','uid'])->find();
		if(!$teacherData){
			return json(['success'=>false,'code'=>'013','message'=>'该老师的信息格式不正确！']);
		}
		$insertData = [];
		$blacklist = new Blacklist;
		/*if($blacklist->where(['client_id'=>$post['teacher_id'],'type'=>1])->find()){
			return json(['success'=>false,'code'=>'013','messae'=>'这个老师已经加入黑名单了']);
		}*/
		if(!empty($post['black_reason_other'])){
			$insertData['reason_other'] = $post['black_reason_other'];
		}
		$insertData['client_id'] = $teacherData->teacher_id;
		$insertData['uid'] = $teacherData->uid;
		$insertData['reason'] = $post['black_reason'];
		$insertData['type'] = 1;
		$result = $blacklist->insertData($insertData,'teacher',$teacherData->teacher_id);
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
		$teacherId = input('param.teacher_id');
		if(empty($teacherId)){
			return json(['success'=>false,'code'=>'002','message'=>'用户ID不能为空']);
		}
		$blacklist = new Blacklist;
		$teacher = new TeacherModel;
		$teacherData = $teacher->where(['teacher_id'=>$teacherId])->field(['teacher_id'])->find();
		$result = $blacklist->cancel($teacherData->teacher_id,'teacher');
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'取消成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'撤出黑名单出错，请稍后重试']);
		}
	}

	/**
	 * 老师合作记录
	 */
	public function relevantRecord()
	{
		$teacherId = input('param.teacher_id');
		$relevant = new TeacherRelevantRecord;
		$teacherData = $relevant->getAllTeacherRele($teacherId);
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$teacherData]);
	}

	/**
	 * 老师修改信息审核
	 */
	public function teacherEditInfoExamine()
	{
		$teacherId = input('param.teacher_id');
		// 0 驳回。1 通过
		$action = input('param.action');
		$reason = input('param.reason','请仔细填写');
		$nowTime = time();
		$editlog = new TeacherEditLog;
		$teacher = new TeacherModel;
		$innermail = new InnerMail;
		$teacherData = $teacher->where(['teacher_id'=>$teacherId])->find();
		if(!$teacherData){
			return json(['success'=>false,'code'=>'012','message'=>'该老师不存在！']);
		}
		if($action==0){
			$editlog->startTrans();
			$teacher->startTrans();
			$result = $editlog->where(['teacher_id'=>$teacherId,'status'=>0])->update(['status'=>-1,'update_at'=>$nowTime]);
			$teacherResult = $teacher->where(['teacher_id'=>$teacherId])->update(['info_status'=>2,'update_at'=>$nowTime]);
			if($result && $teacherResult){
				$editlog->commit();
				$teacher->commit();
				$innermail->addInnerMail(
					$teacherData->teacher_name.'老师的资料审核未通过！',
					3,
					$teacherData->teacher_name.'老师的申请未能通过审核，原因如下：'.$reason,
					$teacherData->clinic_id,
					5
				);
				if(!empty($teacherData->uid)){
					$innermail->addInnerMail(
						$teacherData->teacher_name.'老师的资料审核未通过！',
						1,
						$teacherData->teacher_name.'老师的申请未能通过审核，原因如下：'.$reason,
						$teacherData->uid,
						5
					);
				}
				return json(['success'=>true,'code'=>'000','message'=>'操作成功！']);
			}else{
				$editlog->rollBack();
				$teacher->rollBack();
				return json(['success'=>false,'code'=>'006','message'=>'操作出错，请稍后重试！']);
			}
		}else if($action==1){
			$result = $editlog->adopt($teacherId);
			if($result){
				$innermail->addInnerMail(
					$teacherData->teacher_name.'老师修改信息已通过审核！',
					3,
					'恭喜您，'.$teacherData->teacher_name.'老师修改的资料已审核通过并生效了！',
					$teacherData->clinic_id,
					5
				);
				if(!empty($teacherData->uid)){
					$innermail->addInnerMail(
						$teacherData->teacher_name.'老师修改信息已通过审核！',
						1,
						$teacherData->teacher_name.'老师修改的资料已审核通过并生效了！',
						$teacherData->uid,
						5
					);
				}
				return json(['success'=>true,'code'=>'000','message'=>'操作成功！']);
			}else{
				return json(['success'=>false,'code'=>'006','message'=>'操作出错，请稍后重试！']);
			}
		}else{
			return json(['success'=>false,'code'=>'012','message'=>'没有预定义的参数！']);
		}
	}

	/**
	 * 添加老师
	 */
	public function addTeacher()
	{
		$request = Request::instance();
		$post = $request->only(['teacher_name','sex','birthday','enter_date','city','tel','teacher_photo_no']);
		$adminId = Session::get('admin_id');
		$redis = new Redis;
		$imgPrefix = 'teacher_info_'.$adminId.'_';
		// 验证参数是否符合规则
		$vali = $this->validate($post, 'TeacherValidate.info');
		if( $vali !== true ){ // 返回错误的验证结果
			return json(['success'=>false,'code'=>'002','message'=>$vali]);
		}
		
		if(!$redis->get2($imgPrefix.$post['teacher_photo_no'])){
			return json(['success'=>false,'code'=>'002','message'=>'上身照图片已过期，请重新上传！']);
		}
		$teacher = new TeacherModel;
		$user = new User;
		$insertData = [];
		$teacher_id = $teacher->where('teacher_tel',$post['tel'])->value('teacher_id');
		if($teacher_id) {
			return json(['success'=>false,'code'=>'004','message'=>'该手机号已经入驻平台。']);
		}
		$citys = $post['city'];
		if(empty($citys[0])){
			return json(['success'=>false,'code'=>'002','message'=>'请填写所在省份']);
		}
		if(empty($citys[1])){
			return json(['success'=>false,'code'=>'002','message'=>'请填写所在市']);
		}
		$userTel = $user->where(['mobile'=>$post['tel'],'app'=>2])->value('id');
		if($userTel){
			$insertData['uid'] = $userTel;
		}
		$insertData['teacher_name'] = $post['teacher_name'];
		$insertData['sex'] = $post['sex'];
		$insertData['birthday'] = $post['birthday'];
		$insertData['enter_date'] = $post['enter_date'];
		$insertData['province'] = $citys[0];
		$insertData['city'] = $citys[1];
		$insertData['area'] = $citys[2];
		$insertData['teacher_tel'] = $post['tel'];
		$insertData['clinic_id'] = 0;
		$insertData['info_status'] = 0;
		$insertData['status'] = 0;
		$insertData['teacher_photo'] = $redis->get2($imgPrefix.$post['teacher_photo_no']);
		$result = $teacher->createTeacher($insertData);
		if($result){
			$system = Db::name('system')->where('business_module','listen')->select();
			$spriceConfig = Db::name('system')->where(['business_module'=>'consultation','name'=>'lv1_sprice'])->value('value');
			$epriceConfig = Db::name('system')->where(['business_module'=>'consultation','name'=>'lv1_eprice'])->value('value');
			return json([
				'success'=>true,
				'code'=>"000",
				'message'=>'个人信息已保存',
				'data'=>[
					'teacher_id'=>$result,
					// 起步时间
					'starting_time'=>$system[0]['value'],
					// 起步价
					'starting_price'=>$system[1]['value'],
					// 递增时间
					'inc_time'=>$system[2]['value'],
					// 递增价格
					'inc_price'=>$system[3]['value'],
					'lv1_sprice'=>$spriceConfig,
					'lv1_eprice'=>$epriceConfig,
				],
			]);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后再试']);
		}
	}


	/**
	 * 老师角色
	 */
	public function teacherRole()
	{
		$request = Request::instance();
		$clinic = new Clinic;
		$post = $request->only(['type','option_f2f','option_video','teacher_id','video_price','f2f_price']);
		if(!isset($post['type'])){
			return json(['success'=>false,'code'=>'002','message'=>'老师角色必填']);
		}
		$spriceConfig = Db::name('system')->where(['business_module'=>'consultation','name'=>'lv1_sprice'])->value('value');
		$epriceConfig = Db::name('system')->where(['business_module'=>'consultation','name'=>'lv1_eprice'])->value('value');
		$editData = [];
		if($post['type']==1 && @$post['option_f2f']==0 && @$post['option_video']==0){
			return json(['success'=>false,'code'=>'012','message'=>'请勾选工作类型']);
		}else if($post['type']==1 && @$post['option_f2f']==1 && @$post['option_video']==1){
			$editData['teacher_role'] = 1;
			if($spriceConfig>$post['video_price']){
				return json(['success'=>false,'code'=>"007",'message'=>'价格不能低于'.$spriceConfig.'元']);
			}
			if($epriceConfig<$post['video_price']){
				return json(['success'=>false,'code'=>"007",'message'=>'价格不能高于'.$epriceConfig.'元']);
			}
			if($spriceConfig>$post['f2f_price']){
				return json(['success'=>false,'code'=>"007",'message'=>'价格不能低于'.$spriceConfig.'元']);
			}
			if($epriceConfig<$post['f2f_price']){
				return json(['success'=>false,'code'=>"007",'message'=>'价格不能高于'.$epriceConfig.'元']);
			}
			$editData['video_price'] = $post['video_price'];
			$editData['f2f_price'] = $post['f2f_price'];
		}else if($post['type']==1 && @$post['option_video']==1 && @$post['option_f2f']==0){
			$editData['teacher_role'] = 2;
			if($spriceConfig>$post['video_price']){
				return json(['success'=>false,'code'=>"007",'message'=>'价格不能低于'.$spriceConfig.'元']);
			}
			if($epriceConfig<$post['video_price']){
				return json(['success'=>false,'code'=>"007",'message'=>'价格不能高于'.$epriceConfig.'元']);
			}
			$editData['video_price'] = $post['video_price'];
		}else if($post['type']==1 && @$post['option_video']==0 && @$post['option_f2f']==1){
			$editData['teacher_role'] = 3;
			if($spriceConfig>$post['f2f_price']){
				return json(['success'=>false,'code'=>"007",'message'=>'价格不能低于'.$spriceConfig.'元']);
			}
			if($epriceConfig<$post['f2f_price']){
				return json(['success'=>false,'code'=>"007",'message'=>'价格不能高于'.$epriceConfig.'元']);
			}
			$editData['f2f_price'] = $post['f2f_price'];
		}else if($post['type']==0){
			$editData['teacher_role'] = 0;
		}else{
			return json(['success'=>false,'code'=>'012','message'=>'请勾选工作类型']);
		}
		$teacher = new TeacherModel;
		$result = $teacher->editData($post['teacher_id'],$editData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'您的身份已确定，请前往下一步。']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'信息保存出错，请稍后重试']);
		}
	}

	/**
	 * 老师个人简介
	 */
	public function personalProfile()
	{
		$request = Request::instance();
		$post = $request->only(['teacher_id','profile']);
		if(empty($post['profile'])){
			return json(['success'=>false,'code'=>'002','message'=>'请填写简介信息']);
		}
		$editData = [
			'profile'=>$post['profile']
		];
		$teacher = new Teacher;
		$result = $teacher->updateTeacher($post['teacher_id'],$editData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'简介信息已保存']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'信息保存出错，请稍后重试']);
		}
	}

	/**
	 * 填写老师基本信息 
	 */
	public function insertTeacher()
	{
		$request = Request::instance();
		$redis = new Redis;
		$clinicId = config('test_id');
		$imgPrefix = 'teacher_info_'.$clinicId.'_';
		// 过滤参数
		$post = $request->only([
			'teacher_id','listen_label','consult_label',
			'consult_number','consult_duration','listen_number',
			'listen_duration','growth_duration',
			'certificate','train','supervise','education',
			'team_consult_duration','team_consult_number',
			'team_growth_duration','clinic_id',
		]);
		// 验证参数是否符合规则
		$vali = $this->validate($post, 'TeacherValidate.major');
		if( $vali !== true ){ // 返回错误的验证结果
			return json(['success'=>false,'code'=>'002','message'=>$vali]);
		}
		$date = date('Ymd').'/';
		$nowTime = time();
		// 示例化模型
		$teacher = new TeacherModel;
		$certificate = new TeacherCertificate;
		$train = new TeacherTrain;
		$supervise = new TeacherSupervise;
		$education = new TeacherEducation;
		$userfield = new UserField;
		$teacherData = $teacher->where('teacher_id',$post['teacher_id'])->field('clinic_id,teacher_id,uid')->find();
		// 安全验证
		if($clinicId != $teacherData['clinic_id']){
			return json(['success'=>false,'code'=>'002','message'=>'您不能修改这位老师的资料']);
		}
		// 处理请求参数
		$listenLabel = implode(',', $post['listen_label']);
		$consultLabel = implode(',', $post['consult_label']);
		// 格式化
		// 专业信息杂项
		$teacherEditData = [ 
			'listen_label'=>$listenLabel,
			'consult_label'=>$consultLabel,
			'consult_number'=>@$post['consult_number'],
			'consult_duration'=>@$post['consult_duration'],
			'listen_number'=>@$post['listen_number'],
			'listen_duration'=>@$post['listen_duration'],
			'growth_duration'=>@$post['growth_duration'],
			'team_consult_number'=>@$post['team_consult_number'],
			'team_consult_duration'=>@$post['team_consult_duration'],
			'team_growth_duration'=>@$post['team_growth_duration'],
			'clinic_id'=>@$post['clinic_id'],
			'growth_at'=>@$nowTime,
			'info_status'=>1,
			'status'=>0,
		];
		if(!empty($post['certificate'])){
			// 老师证书
			$certificateInsertData = [];
			$certificatePostData = $post['certificate'];
			for ($i=0; $i < count($certificatePostData); $i++) { 
				$vali = $this->validate($certificatePostData[$i], 'TeacherValidate.certificate');
				if( $vali !== true ){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$vali]);
				}
				$certificateInsertDataTwo = [];
				// 安全验证
				if(empty($certificatePostData[$i]['certificate_id'])){
					if(!$redis->get2($imgPrefix.$certificatePostData[$i]['certificate_photo_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'资质证书图片为空或已过期，请重新上传！']);
					}
					$certificateInsertDataTwo['certificate_photo'] = $redis->get2($imgPrefix.$certificatePostData[$i]['certificate_photo_no']);
					$certificateInsertDataTwo['create_at'] = $nowTime;
				}else if(isset($certificatePostData[$i]['certificate_photo_no'])){
					$certificateInsertDataTwo['certificate_photo'] = $redis->get2($imgPrefix.$certificatePostData[$i]['certificate_photo_no']);
					$certificateInsertDataTwo['certificate_id'] = $certificatePostData[$i]['certificate_id'];
					$certificateInsertDataTwo['update_at'] = $nowTime;
				}else{
					$certificateInsertDataTwo['certificate_id'] = $certificatePostData[$i]['certificate_id'];
					$certificateInsertDataTwo['update_at'] = $nowTime;
				}
				$certificateInsertDataTwo['certificate_name'] = $certificatePostData[$i]['certificate_name'];
				$certificateInsertDataTwo['certificate_no'] = $certificatePostData[$i]['certificate_no'];
				$certificateInsertDataTwo['award_date'] = $certificatePostData[$i]['award_date'];
				$certificateInsertDataTwo['teacher_id'] = $post['teacher_id'];
				$certificateInsertData[] = $certificateInsertDataTwo;
			}
		}else{
			return json(['success'=>false,'code'=>'002','message'=>'请填写资质证书后提交！']);
		}
		
		if(!empty($post['train'])){
			// 培训
			$trainData = [];
			$trainPostData = $post['train'];
			for ($i=0; $i < count($trainPostData); $i++) { 
				$vali = $this->validate($trainPostData[$i], 'TeacherValidate.train');
				if( $vali !== true ){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$vali]);
				}
				$trainDataTwo = [];
				if(empty($trainPostData[$i]['train_id'])){
					if(!$redis->get2($imgPrefix.$trainPostData[$i]['train_photo_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'培训证明图片为空或已过期，请重新上传！']);
					}
					$trainDataTwo['train_photo'] = $redis->get2($imgPrefix.$trainPostData[$i]['train_photo_no']);
					$trainDataTwo['create_at'] = $nowTime;
				}else if(isset($trainPostData[$i]['train_photo_no'])){
					$trainDataTwo['train_photo'] = $redis->get2($imgPrefix.$trainPostData[$i]['train_photo_no']);
					$trainDataTwo['train_id'] = $trainPostData[$i]['train_id'];
					$trainDataTwo['update_at'] = $nowTime;
				}else{
					$trainDataTwo['train_id'] = $trainPostData[$i]['train_id'];
					$trainDataTwo['update_at'] = $nowTime;
				}
				
				$trainDataTwo['train_mechanism'] = $trainPostData[$i]['train_mechanism'];
				$trainDataTwo['train_start_time'] = $trainPostData[$i]['train_start_time'];
				$trainDataTwo['train_end_time'] = $trainPostData[$i]['train_end_time'];
				$trainDataTwo['train_course'] = $trainPostData[$i]['train_course'];
				$trainDataTwo['teacher_id'] = $post['teacher_id'];
				$trainData[] = $trainDataTwo;
			}
		}

		if(!empty($post['supervise'])){
			// 督导
			$superviseData = [];
			$supervisePostData = $post['supervise'];
			for ($i=0; $i < count($supervisePostData); $i++) { 
				$vali = $this->validate($supervisePostData[$i], 'TeacherValidate.supervise');
				if( $vali !== true ){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$vali]);
				}
				$superviseDataTwo = [];
				if(empty($supervisePostData[$i]['supervise_id'])){
					$superviseDataTwo['create_at'] = $nowTime;
				}else{
					$superviseDataTwo['supervise_id'] = $supervisePostData[$i]['supervise_id'];
					$superviseDataTwo['update_at'] = $nowTime;
				}
				$superviseDataTwo['supervise_mode'] = $supervisePostData[$i]['supervise_mode'];
				$superviseDataTwo['supervise_name'] = $supervisePostData[$i]['supervise_name'];
				$superviseDataTwo['supervise_tel'] = @$supervisePostData[$i]['supervise_tel'];
				$superviseDataTwo['supervise_duration'] = $supervisePostData[$i]['supervise_duration'];
				$superviseDataTwo['teacher_id'] = $post['teacher_id'];
				$superviseData[] = $superviseDataTwo;
			}
		}

		if(!empty($post['education'])){
			// 学历
			$eduData = [];
			$eduPostData = $post['education'];
			for ($i=0; $i < count($eduPostData); $i++) { 
				$vali = $this->validate($eduPostData[$i], 'TeacherValidate.education');
				if( $vali !== true ){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$vali]);
				}
				$eduDataTwo = [];
				if(empty($eduPostData[$i]['education_id'])){
					if(!$redis->get2($imgPrefix.$eduPostData[$i]['education_photo_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'学历证明图片为空或已过期，请重新上传！']);
					}
					$eduDataTwo['education_photo'] = $redis->get2($imgPrefix.$eduPostData[$i]['education_photo_no']);
					$eduDataTwo['create_at'] = $nowTime;
				}else if(isset($eduPostData[$i]['education_photo_no'])){
					$eduDataTwo['education_photo'] = $redis->get2($imgPrefix.$eduPostData[$i]['education_photo_no']);
					$eduDataTwo['education_id'] = $eduPostData[$i]['education_id'];
					$eduDataTwo['update_at'] = $nowTime;
				}else{
					$eduDataTwo['education_id'] = $eduPostData[$i]['education_id'];
					$eduDataTwo['update_at'] = $nowTime;
				}
				$eduDataTwo['school'] = $eduPostData[$i]['school'];
				$eduDataTwo['start_time'] = $eduPostData[$i]['start_time'];
				$eduDataTwo['end_time'] = $eduPostData[$i]['end_time'];
				$eduDataTwo['major'] = $eduPostData[$i]['major'];
				$eduDataTwo['unified_if'] = $eduPostData[$i]['unified_if'];
				$eduDataTwo['education_level'] = $eduPostData[$i]['education_level'];
				$eduDataTwo['teacher_id'] = $post['teacher_id'];
				$eduData[] = $eduDataTwo;
			}
		}
		// 启动事务
		$teacher->startTrans();
		$certificate->startTrans();
		$train->startTrans();
		$supervise->startTrans();
		$education->startTrans();
		try{// 执行保存
			// 专业信息杂项
			$result = $teacher->updateTeacher($post['teacher_id'],$teacherEditData);
			if(!$result){
				$teacher->rollBack();
				return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后重试！(1)']);
			}
			// 老师资质信息
			$result = $certificate->saveAll($certificateInsertData);
			if(!$result){
				$teacher->rollBack();
				$certificate->rollBack();
				return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后重试！(2)']);
			}

			if(isset($trainData)){
				// 培训经历
				$result = $train->saveAll($trainData);
				if(!$result){
					$teacher->rollBack();
					$certificate->rollBack();
					$train->rollBack();
					return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后重试！(3)']);
				}
			}
			
			if(isset($superviseData)){
				// 督导
				$result = $supervise->saveAll($superviseData);
				if(!$result){
					$teacher->rollBack();
					$certificate->rollBack();
					$train->rollBack();
					$supervise->rollBack();
					return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后重试！(4)']);
				}
			}
			
			if(isset($eduData)){
				// 学历
				$result = $education->saveAll($eduData);
				if(!$result){
					$teacher->rollBack();
					$certificate->rollBack();
					$train->rollBack();
					$supervise->rollBack();
					$education->rollBack();
					return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后重试！(5)']);
				}
			}
			
			// 保存到 userfield
			if(!empty($teacherData['uid'])){
				$content = json_encode($this->getContent($teacherData['uid']));
				$userfield->editData($teacherData['uid'],['content'=>$content]);
			}
		    // 提交事务
		    $teacher->commit();
		    $certificate->commit();
		    $train->commit();
		    $supervise->commit();
		    $education->commit();
		    return json(['success'=>true,'code'=>'000','message'=>'保存成功，请耐心等待审核通过！']);
		} catch (\Exception $e) {
		    // 回滚事务
		    $teacher->rollBack();
		    $certificate->rollBack();
		    $train->rollBack();
		    $supervise->rollBack();
		    $education->rollBack();
		    return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后重试！(6)','errorMsg'=>$e->getMessage()]);
		}
	}

	/**
	 * 获取老师所有附加信息
	 */
	public function getContent($uid=false)
	{
		// 教育经历
		$teacher_education = TeacherEducation::where(['uid'=>$uid])->field('*,null as edu')->select();
		// 资质证书
		$teacher_certificate = TeacherCertificate::where(['uid'=>$uid])->select();
		// 督导经历
		$teacher_supervise = TeacherSupervise::where(['uid'=>$uid])->select();
		// 培训经历
		$teacher_train = TeacherTrain::where(['uid'=>$uid])->select();
		// 个案时长等信息
		$teacher = TeacherModel::where(['uid'=>$uid])->field(['consult_number','consult_duration','growth_duration'])->find();
		// 个案经历
		return [
			'teacher_education'=>$teacher_education,
			'teacher_certificate'=>$teacher_certificate,
			'teacher_supervise'=>$teacher_supervise,
			'teacher_train'=>$teacher_train,
			// 个案数量
			'consult_number'=>$teacher['consult_number'],
			// 总时长
			'consult_duration'=>$teacher['consult_duration'],
			// 个人成长时长
			'growth_duration'=>$teacher['growth_duration'],
			// 督导总时长
			'supervisetime'=>array_sum(array_column($teacher_supervise,'supervise_duration'))
		];
	}

	/**
	 * 老师信息图片上传
	 */
	public function uploadImgs()
	{
		$name = input('post.name');
		$adminId = Session::get('admin_id');
		$redis = new Redis;
		$response = [];
	    // 图片对应地址
	    $imgPath = [
	        'certificate_photo'=>config('IMG')['teacher_certificate'],
	        'train_photo'=>config('IMG')['teacher_train'],
	        'education_photo'=>config('IMG')['teacher_education'],
	        'teacher_photo'=>config('IMG')['teacher_body'],
	    ];
		// 执行上传
		$filename = put_oss($name, $imgPath);
		if(!$filename['success']){
			return json($filename);
		}
		// 随机字符串返回
		$str = generate_rand(15,true);
		// 文件地址存储到redis
		$redis->set2('teacher_info_'.$adminId.'_'.$str,$filename['filename']);
		// 设置有效时间2小时
		$redis->expireAt('teacher_info_'.$adminId.'_'.$str,time()+60*60+2);
		return json(['success'=>true,'code'=>'000','message'=>'上传完成','data'=>['filename'=>$filename['filename'],'no'=>$str]]);
	}

	/**
	 * 老师信息修改上传图片
	 */
	public function editImg()
	{
		$name = input('post.name');
		$adminId = Session::get('admin_id');
		$redis = new Redis;
		$response = [];
		// 前端拼接地址
		$response['path'] = config('IMGPRESENT');
	    // 图片对应地址
	    $imgPath = [
	        'certificate_photo'=>config('IMG')['teacher_certificate'],
	        'train_photo'=>config('IMG')['teacher_train'],
	        'education_photo'=>config('IMG')['teacher_education'],
	        'teacher_photo'=>config('IMG')['teacher_body'],
	    ];
		// 执行上传
		$filename = put_oss($name, $imgPath);
		if(!$filename['success']){
			return json($filename);
		}
		// 随机字符串返回
		$str = generate_rand(15,true);
		// 文件地址存储到redis
		$redis->set2('teacher_editinfo_'.$adminId.'_'.$str,$filename['filename']);
		// 设置有效时间2小时
		$redis->expireAt('teacher_editinfo_'.$adminId.'_'.$str,time()+60*60+2);
		return json(['success'=>true,'code'=>'000','message'=>'上传完成','data'=>['filename'=>$filename['filename'],'no'=>$str]]);
	}


	/**
	 * 专业信息
	 */
	public function durationInfo()
	{
		$request = Request::instance();
		$post = $request->only([
			'teacher_id','consult_number','consult_duration','listen_number',
			'listen_duration','growth_duration','type','team_consult_number',
			'team_consult_duration','team_growth_duration'
		]);
		$nowTime = time();
		if(empty($post['type'])){
			return json(['success'=>false,'code'=>'007','message'=>'type不能为空']);
		}
		if(!in_array($post['type'], ['consult','growth'])){
			return json(['success'=>false,'code'=>'002','message'=>'该字段不在修改范围内！']);
		}
		
		if($post['type']=='consult'){
			// 验证参数是否符合规则
			$vali = $this->validate($post, 'TeacherValidate.major_consult_eidt');
			if( $vali !== true ){ // 返回错误的验证结果
				return json(['success'=>false,'code'=>'002','message'=>$vali]);
			}
			$editData = [
				'consult_number'=>$post['consult_number'],
				'consult_duration'=>$post['consult_duration'],
				'listen_number'=>$post['listen_number'],
				'listen_duration'=>$post['listen_duration'],
				'team_consult_number'=>$post['team_consult_number'],
				'team_consult_duration'=>$post['team_consult_duration'],
			];

		}else if($post['type']=='growth'){
			// 验证参数是否符合规则
			$vali = $this->validate($post, 'TeacherValidate.major_growth_eidt');
			if( $vali !== true ){ // 返回错误的验证结果
				return json(['success'=>false,'code'=>'002','message'=>$vali]);
			}
			$editData = [
				'team_growth_duration'=>$post['team_growth_duration'],
				'growth_duration'=>$post['growth_duration'],
				'growth_at'=>$nowTime,
			];
		}else{
			return json(['success'=>false,'code'=>'007','message'=>'该字段不在修改范围内！']);
		}
		$teacher = new TeacherModel;
		$result = $teacher->editData($post['teacher_id'],$editData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'添加成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后再试。']);
		}
	}

	/**
	 * 专业资质
	 */
	public function teacherCertificate()
	{
		$request = Request::instance();
		@$post = $request->param()['certificate'];
		$certificate = new TeacherCertificate;
		$nowTime = time();
		$redis = new Redis;
		$teacher = new TeacherModel;
		$adminId = Session::get('admin_id');
		$imgPrefix = 'teacher_info_'.$adminId.'_';
		$teacherId = input('param.teacher_id');
		$certificateEditData = [];
		if(empty($post)){
			return json(['success'=>false,'code'=>'002','message'=>'请上传参数合集后提交']);
		}
		for ($i=0; $i < count($post); $i++) { 
			$certificateData = [];
			// 验证参数是否符合规则
			$vali = $this->validate($post[$i], 'TeacherValidate.certificate_eidt');
			if( $vali !== true ){ // 返回错误的验证结果
				return json(['success'=>false,'code'=>'002','message'=>$vali]);
			}

			if(empty($post[$i]['certificate_id'])){
				if(!$redis->get2($imgPrefix.$post[$i]['certificate_photo_no'])){
					return json(['success'=>false,'code'=>'002','message'=>'资质证书图片已过期，请重新上传！']);
				}
				$certificateData['certificate_photo'] = $redis->get2($imgPrefix.$post[$i]['certificate_photo_no']);
				$certificateData['create_at'] = $nowTime;
				$certificateData['teacher_id'] = $post[$i]['teacher_id'];
				$certificateData['uid'] = $teacher->where(['teacher_id'=>$post[$i]['teacher_id']])->value('uid');
			}else{
				if(!empty($post[$i]['certificate_photo_no'])){
					if(!$redis->get2($imgPrefix.$post[$i]['certificate_photo_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'资质证书图片已过期，请重新上传！']);
					}
					$certificateData['certificate_photo'] = $redis->get2($imgPrefix.$post[$i]['certificate_photo_no']);
				}
				$certificateData['certificate_id'] = $post[$i]['certificate_id'];
				$certificateData['update_at'] = $nowTime;
			}
			$certificateData['certificate_name'] = $post[$i]['certificate_name'];
			$certificateData['certificate_no'] = $post[$i]['certificate_no'];
			$certificateData['award_date'] = $post[$i]['award_date'];
			$certificateEditData[] = $certificateData;
			$teacherId = $post[$i]['teacher_id'];
		}
		$result = $certificate->saveAll($certificateEditData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'保存成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后再试。']);
		}
	}

	/**
	 * 删除专业信息
	 */
	public function delMajorInfo()
	{
		$request = Request::instance();
		$post = $request->only(['info_id','type','teacher_id']);
		$vali = $this->validate($post,'TeacherValidate.del_info');
		if($vali!==true){
			return json(['success'=>false,'code'=>'007','message'=>$vali]);
		}
		$certificate = new TeacherCertificate;
		$train = new TeacherTrain;
		$supervise = new TeacherSupervise;
		$education = new TeacherEducation;
		switch (strtolower($post['type'])) {
			case 'certificate':
				$result = $certificate->where(['certificate_id'=>['in',implode(',',$post['info_id'])],'teacher_id'=>$post['teacher_id']])->delete();
				break;
			case 'train':
				$result = $train->where(['train_id'=>['in',implode(',',$post['info_id'])],'teacher_id'=>$post['teacher_id']])->delete();
				break;
			case 'supervise':
				$result = $supervise->where(['supervise_id'=>['in',implode(',',$post['info_id'])],'teacher_id'=>$post['teacher_id']])->delete();
				break;
			case 'education':
				$result = $education->where(['education_id'=>['in',implode(',',$post['info_id'])],'teacher_id'=>$post['teacher_id']])->delete();
				break;
			
			default:
				return json(['success'=>false,'code'=>'014','message'=>'没有预定义的参数！']);
		}
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'删除成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'该信息不存在或已经删除！']);
		}
	}

	/**
	 * 培训经历
	 */
	public function teacherTrain()
	{
		$request = Request::instance();
		@$post = $request->param()['train'];
		$train = new TeacherTrain;
		$redis = new Redis;
		$teacher = new TeacherModel;
		$nowTime = time();
		$adminId = Session::get('admin_id');
		$imgPrefix = 'teacher_info_'.$adminId.'_';
		$teacherId = input('param.teacher_id');
		$trainEditData = [];
		if(empty($post)){
			return json(['success'=>false,'code'=>'002','message'=>'请上传参数合集后提交']);
		}
		for ($i=0; $i < count($post); $i++) {
			$trainData = [];
			// 验证参数是否符合规则
			$vali = $this->validate($post[$i], 'TeacherValidate.train_edit');
			if( $vali !== true ){ // 返回错误的验证结果
				return json(['success'=>false,'code'=>'002','message'=>$vali]);
			}
			if(empty($post[$i]['train_id'])){
				if(!$redis->get2($imgPrefix.$post[$i]['train_photo_no'])){
					return json(['success'=>false,'code'=>'002','message'=>'培训证明图片已过期，请重新上传！']);
				}
				$trainData['train_photo'] = $redis->get2($imgPrefix.$post[$i]['train_photo_no']);
				$trainData['teacher_id'] = $post[$i]['teacher_id'];
				$trainData['uid'] = $teacher->where(['teacher_id'=>$post[$i]['teacher_id']])->value('uid');
				$trainData['create_at'] = $nowTime;
			}else{
				if(!empty($post[$i]['train_photo_no'])){
					if(!$redis->get2($imgPrefix.$post[$i]['train_photo_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'培训证明图片已过期，请重新上传！']);
					}
					$trainData['train_photo'] = $redis->get2($imgPrefix.$post[$i]['train_photo_no']);
				}
				$trainData['train_id'] = $post[$i]['train_id'];
				$trainData['update_at'] = $nowTime;
			}
			$trainData['train_mechanism'] = $post[$i]['train_mechanism'];
			$trainData['train_start_time'] = $post[$i]['train_start_time'];
			$trainData['train_end_time'] = $post[$i]['train_end_time'];
			$trainData['train_course'] = $post[$i]['train_course'];
			$teacherId = $post[$i]['teacher_id'];
			$trainEditData[] = $trainData;
		}
		$result = $train->saveAll($trainEditData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'添加操作提交成功，需等待审核通过']);  
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后再试。']);
		}
	}

	/**
	 * 督导经历
	 */
	public function teacherSupervise()
	{
		$request = Request::instance();
		@$post = $request->param()['supervise'];
		$nowTime = time(); 
		$supervise = new TeacherSupervise;
		$teacher = new TeacherModel;
		$superviseEditData = [];
		$teacherId = input('param.teacher_id');
		if(empty($post)){
			return json(['success'=>false,'code'=>'002','message'=>'请上传参数合集后提交']);
		}
		for ($i=0; $i < count($post); $i++) { 
			$superviseData = [];
			// 验证参数是否符合规则
			$vali = $this->validate($post[$i], 'TeacherValidate.supervise_edit');
			if( $vali !== true ){ // 返回错误的验证结果
				return json(['success'=>false,'code'=>'002','message'=>$vali]);
			}
			if(empty($post[$i]['supervise_id'])){
				$superviseData['create_at'] = $nowTime;
				$superviseData['teacher_id'] = $post[$i]['teacher_id'];
				$superviseData['uid'] = $teacher->where(['teacher_id'=>$post[$i]['teacher_id']])->value('uid');
			}else{
				$superviseData['supervise_id'] = $post[$i]['supervise_id'];
				$superviseData['update_at'] = $nowTime;
			}
			$superviseData['supervise_mode'] = $post[$i]['supervise_mode'];
			$superviseData['supervise_name'] = $post[$i]['supervise_name'];
			$superviseData['supervise_tel'] = @$post[$i]['supervise_tel'];
			$superviseData['supervise_duration'] = $post[$i]['supervise_duration'];
			$superviseEditData[] = $superviseData;
			$teacherId = $post[$i]['teacher_id'];
		}
		$result = $supervise->saveAll($superviseEditData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'添加成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后再试。']);
		}
	}

	/**
	 * 受教经历 
	 */
	public function teacherEducation()
	{
		$request = Request::instance();
		$education = new TeacherEducation;
		$redis = new Redis;
		$nowTime = time();
		$adminId = Session::get('admin_id');
		$imgPrefix = 'teacher_info_'.$adminId.'_';
		@$post = $request->param()['education'];
		$eduInsertData = [];
		$teacherId = input('param.teacher_id');
		if(empty($post)){
			return json(['success'=>false,'code'=>'002','message'=>'请上传参数合集后提交']);
		}
		for ($i=0; $i < count($post); $i++) {
			$editData = [];
			// 验证参数是否符合规则
			$vali = $this->validate($post[$i], 'TeacherValidate.education_edit');
			if( $vali !== true ){ // 返回错误的验证结果
				return json(['success'=>false,'code'=>'002','message'=>$vali]);
			}
			if(empty($post[$i]['education_id'])){
				if(!$redis->get2($imgPrefix.$post[$i]['education_photo_no'])){
					return json(['success'=>false,'code'=>'002','message'=>'学历证明图片已过期，请重新上传！']);
				}
				$editData['teacher_id'] = $post[$i]['teacher_id'];
				$editData['education_photo'] = $redis->get2($imgPrefix.$post[$i]['education_photo_no']);
				$editData['create_at'] = $nowTime;
			}else{
				if(!empty($post[$i]['education_photo_no'])){
					if(!$redis->get2($imgPrefix.$post[$i]['education_photo_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'学历证明图片已过期，请重新上传！']);
					}
					$editData['education_photo'] = $redis->get2($imgPrefix.$post[$i]['education_photo_no']);
				}
				$editData['education_id'] = $post[$i]['education_id'];
				$editData['update_at'] = $nowTime;
			}
			$editData['school'] = $post[$i]['school'];
			$editData['start_time'] = $post[$i]['start_time'];
			$editData['end_time'] = $post[$i]['end_time'];
			$editData['major'] = $post[$i]['major'];
			$editData['unified_if'] = $post[$i]['unified_if'];
			$editData['education_level'] = $post[$i]['education_level'];
			$teacherId = $post[$i]['teacher_id'];
			$eduInsertData[] = $editData;
		}
		$result = $education->saveAll($eduInsertData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'添加成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后再试。']);
		}
	}
}
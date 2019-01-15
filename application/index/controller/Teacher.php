<?php
namespace app\index\controller;
use think\Request;
use think\Session;
use app\index\model\User;
use app\index\service\UserField;
use app\index\model\Teacher as TeacherModel;
use app\index\service\TeacherCertificate;
use app\index\service\TeacherEducation;
use app\index\service\TeacherSupervise;
use app\index\service\TeacherTrain;
use app\index\service\OrderConsult;
use app\index\service\OrderListen;
use app\index\service\TeacherRelevantRecord;
use app\index\model\Topic;
use app\index\model\Order;
use app\index\model\Blacklist;
use think\Db;

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
		if(!empty($post['teacher_sex'])){
			// $sexDe = ['未知'=>0,'男'=>1,'女'=>2];
			$teacherWhere['t.sex'] = $post['teacher_sex'];
		}
		if(!empty($post['teacher_status'])){
			$teacherWhere['t.info_status'] = $post['teacher_status'];
		}
		if(!empty($post['teacher_service'])){
			$serviceDe = ['listen'=>1,'consult'=>2];
			if($serviceDe[$post['teacher_service']]==1){
				$teacherWhere['t.teacher_role'] = 0;
			}else if($serviceDe[$post['teacher_service']]==2){
				$teacherWhere['t.teacher_role'] = ['>',0];
			}
			
		}
		if(!empty($post['teacher_service_status'])){
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
		$trial = $teacher->where(['info_status'=>1])->count();
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
		$topic = new Topic;
		$user = new User;
		$teacherWhere['t.teacher_id'] = $teacherId;
		if($details==0){
			$listenOrder = Db::name('order')->alias('o')->where('o.type',1)->where('o.serverpersonid=t.uid')->field('count(*)')->buildSql();
			$listenTime = Db::name('order')->alias('o')->where('o.type',1)->where('o.serverpersonid=t.uid')->field('sum(o.alltime)')->buildSql();
			$consultOrder = Db::name('order')->alias('o')->where('o.type',2)->where('o.serverpersonid=t.uid')->field('count(*)')->buildSql();
			$consultTime = Db::name('order')->alias('o')->where('o.type',2)->where('o.serverpersonid=t.uid')->field('sum(o.alltime)')->buildSql();
			$userData = $teacher->alias('t')
			->join('sy_userfield uf','uf.uid=t.uid','LEFT')
			->join('sy_user u','t.uid=u.id','LEFT')
			->where($teacherWhere)
			->field([
				't.teacher_name','u.level','t.sex','t.birthday','t.teacher_id','t.create_at','t.teacher_tel','uf.listentopic','uf.zixuntopic','u.serverstatus',$listenOrder.' as listen_con',$listenTime.' as listen_time_sum',$consultOrder.' as consult_con',$consultTime.' as consult_time_con','t.uid','null as certificate','t.info_status'
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
				't.teacher_name','u.level','t.sex','t.birthday','t.teacher_id','t.create_at','t.teacher_tel','uf.listentopic','uf.zixuntopic','u.serverstatus','t.uid','t.info_status','t.teacher_role'
			])
			->find();
			if(empty($userData['info'])){
				return json(['success'=>false,'code'=>'013','message'=>'没有这个老师或已经注销']);
			}
			$userData['certificate'] = $certificate->where(['uid'=>$userData['info']->uid])->order('create_at','desc')->select();
			$userData['education'] = $education->where(['uid'=>$userData['info']->uid])->order('create_at','desc')->select();
			$userData['supervise'] = $supervise->where(['uid'=>$userData['info']->uid])->order('create_at','desc')->select();
			$userData['train'] = $train->where(['uid'=>$userData['info']->uid])->order('create_at','desc')->select();
		}
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$userData]);
	}

	/**
	 * 老师访客
	 */
	public function visitors()
	{
		$teacherId = input('param.uid');
		$item = input('param.item',20);
		$order = new Order;
		$users = $order->alias('o')->join('sy_user u','o.uid=u.id')
		->where(['o.serverpersonid'=>$teacherId])
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
		$teacherId = input('param.uid');
		$user = new User;
		$consoltSql = Db::name('ordermore')->alias('om')->where('om.orderid=o.orderid')->field('count(*)')->buildSql();
		$teacherData = $user->alias('u')
		->join('sy_order o','o.serverpersonid=u.id','LEFT')
		->join('sy_ordermore om','om.orderid=o.orderid','LEFT')
		->join('sy_listenrecord ol','ol.orderid=o.orderid','LEFT')
		->join('sy_userfield uf','o.serverpersonid=uf.uid')
		->where(['u.id'=>$teacherId])
		->field([
			'ol.stime','ol.etime','o.content',
			'o.orderid','om.starttime','om.endtime',
			'o.alltime','o.sytime','om.number',
			'o.type',$consoltSql.' as consult_con','uf.realname'
		])
		->order('o.createtime','desc')
		->paginate(15);
		if($teacherData){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$teacherData]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'查询出错，请稍后重试']);
		}
	}

	/**
	 * 来访者评价
	 */
	public function evaluate()
	{
		$teacherId = input('param.uid');
		$teacher = new TeacherModel;
		$comments = $teacher->getComments($teacherId);
		return json(['success'=>true,'code'=>'000','data'=>$comments]);
	}

	/**
	 * 老师审核
	 */
	public function examine()
	{
		$request = Request::instance();
		$teacherId = input('param.teacher_id');
		$apply = input('param.apply');
		$applyDe = ['reject'=>0,'adopt'=>1,'adopt_up'=>2];
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
				break;
			
			case 1:
				$teacherData->info_status = 2;
				$teacherData->adopt_at = $nowTime;
				$relevant->addRelevant($releData);
				break;

			case 2:
				$teacherData->info_status = 2;
				$teacherData->status = 1;
				$teacherData->adopt_at = $nowTime;
				$relevant->addRelevant($releData);
				break;

			default:
				return json(['success'=>false,'code'=>'002','message'=>$apply.'参数错误']);
		}
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
		$teacherData = $teacher->where(['teacher_id'=>$post['teacher_id']])->field(['teacher_id'])->find();
		if(!$teacherData){
			return json(['success'=>false,'code'=>'013','message'=>'该老师的信息格式不正确！']);
		}
		$insertData = [];
		$blacklist = new Blacklist;
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
}
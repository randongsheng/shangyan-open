<?php
namespace app\index\controller;
use think\Request;
use think\Session;
use think\Db;
use think\cache\driver\Redis;
use app\index\model\Clinic as ClinicModel;
use app\index\service\ClinicRule;
use app\index\model\Order;
use think\Validate;
use app\index\service\ClinicTrends;
use app\index\service\ClinicDeposit;
use app\index\service\ClinicBond;
use app\index\service\ClinicClosure;
use app\index\service\ClinicBankCard;
use app\index\service\ClinicRelated;
use app\index\service\TeacherRelevantRecord;
use app\index\model\Blacklist;
use app\index\service\InnerMail;
use mailer\tp5\Mailer;

class Clinic extends Base
{
	/**
	 * 所有商户
	 */
	public function all()
	{
		$request = Request::instance();
		$post = $request->only(['clinic_id','clinic_name','nature','status','service_type']);
		$clinicWhere = [];
		if(!empty($post['clinic_id'])){
			$clinicWhere['c.clinic_id'] = $post['clinic_id'];
		}

		if(!empty($post['clinic_name'])){
			$clinicWhere['c.clinic_name'] = ['like','%'.$post['clinic_name'].'%'];
		}

		if(!empty($post['nature'])){
			$clinicWhere['c.nature'] = $post['nature'];
		}

		if(isset($post['status']) && $post['status']!=''){
			$clinicWhere['c.status'] = $post['status'];
		}
		
		$teacherCon = Db::name('userfield')->alias('uf')->where('uf.clinicid=c.id')->field('count(*)')->buildSql();
		$clinic = new ClinicModel;
		$clinics = $clinic->alias('c')
		->where($clinicWhere)
		->where(function($query)use($post){
			if(isset($post['service_type']) && $post['service_type']!=''){
				$w = [
					['uf.qingtingstatus'=>1],
					['uf.f2fstatus|uf.videostatus'=>1],
				];
				$query->where(Db::name('userfield')->alias('uf')->where('uf.clinicid=c.id')->where($w[$post['service_type']])->field('count(*)')->buildSql(),'>',0);
			}
		})
		->field([
			'c.id as clinic_id','c.clinic_name','c.nature','c.status',$teacherCon.' as teacher_con','c.logo','c.run_status','c.email'
		])
		->order('create_at','desc')
		->paginate(20);
		if($clinics){
			return json(['success'=>true,'code'=>"000",'message'=>'查询成功','data'=>$clinics]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'还没有机构入驻']);
		}
	}

	/**
	 * 商户详情
	 */
	public function details()
	{
		$clinicId = input('param.clinic_id');
		$details = (bool)input('param.details',0);
		$clinic = new ClinicModel;
		$clinicWhere = [];
		$clinicWhere['c.id'] = $clinicId;
		if($details){
			$related = new ClinicRelated;
			$clinicData = [];
			$clinicData['info'] = $clinic->alias('c')
			->where($clinicWhere)
			->field([
				'c.clinic_name','c.nature','c.operator_tel','c.level','c.logo','c.business_license',
				'c.found_time','c.operator_tel','c.liable_tel','c.liable_name','c.operator_name',
				'c.operator_identity','c.liable_identity','c.city','c.scene_photo','c.run_status','c.address',
				'c.latitude','c.longitude','c.suspend_reason','c.create_at','c.update_at',
				'c.introduce','null as operator_identity_A','null as operator_identity_B',
				'null as liable_identity_A','null as liable_identity_B','null as scene_photo1',
				'null as scene_photo2','null as scene_photo3','c.full_address'
			])
			->find();
			$clinicData['related'] = $related->where(['clinic_id'=>$clinicId])->select();
		}else{
			// 该机构倾听订单总数
			$listenOrderCon = Db::name('order')->where(['clinicid'=>$clinicId,'type'=>1])->field('count(*)')->buildSql();
			// 该机构咨询订单总数
			$consultOrderCon = Db::name('order')->where(['clinicid'=>$clinicId,'type'=>2])->field('count(*)')->buildSql();
			// 该机构的倾听订单总时长
			$listenAllTime = Db::name('order')->where(['clinicid'=>$clinicId,'type'=>1])->field('sum(alltime)')->buildSql();
			// 该机构的咨询订单总时长
			$consultAllTime = Db::name('order')->alias('o')
			->join('sy_ordermore om','o.orderid=om.orderid')
			->where(['o.clinicid'=>$clinicId,'o.type'=>2])
			->field('sum(om.timelong)')
			->buildSql();
			// 该机构收入总额
			$clinicAccount = Db::name('order')->where(['clinicid'=>$clinicId,'status'=>['between','1,2']])->field('sum(ordermoney)')->buildSql();
			// 待付款订单
			$clinicAccountDj = Db::name('order')->where(['clinicid'=>$clinicId,'status'=>0])->field('sum(ordermoney)')->buildSql();
			// 保证金统计
			$chargingCon = Db::name('clinic_deposit')->where(['clinic_id'=>$clinicId,'add_subtract'=>0])->field('sum(charging_money)')->buildSql();
			// 保证金余额
			$rechargeCon = Db::name('clinic_deposit')->where(['clinic_id'=>$clinicId,'add_subtract'=>1])->field('(sum(recharge_money)-'.$chargingCon.')')->buildSql();
			// 咨询中的老师总数
			$consultTeachers = Db::name('user')->alias('u')->join('sy_userfield uf','uf.uid=u.id')
			->where(['uf.clinicid'=>$clinicId,'u.serverstatus'=>['between','2,3']])
			->field('count(*)')->buildSql();
			// 倾听中的老师总数
			$listenTeachers = Db::name('user')->alias('u')->join('sy_userfield uf','uf.uid=u.id')
			->where(['uf.clinicid'=>$clinicId,'u.serverstatus'=>1])->field('count(*)')->buildSql();
			// 空闲中的老师总数
			$kxTeachers = Db::name('userfield')->join('sy_user','sy_user.id=sy_userfield.uid')->where(['sy_userfield.clinicid'=>$clinicId,'sy_user.serverstatus'=>5])->field('count(*)')->buildSql();
			// 离线的老师总数
			$outlineTeachers = Db::name('userfield')->join('sy_user','sy_user.id=sy_userfield.uid')->where(['sy_userfield.clinicid'=>$clinicId,'sy_user.serverstatus'=>4])->field('count(*)')->buildSql();
			$clinicData = $clinic->alias('c')
			->where($clinicWhere)
			->field([
				'c.clinic_name','c.nature','c.operator_tel','c.level'
				,'c.registered_capital','c.id','c.city','c.operator_tel','c.status',
				$listenOrderCon.' as listen_con',$consultOrderCon.' as consult_con'
				,$listenAllTime.' as listen_all_time',$consultAllTime.' as consult_all_time'
				,$clinicAccountDj.' as stay_pay',$clinicAccount.' as pay_con',$rechargeCon.
				' as recharge_con',$chargingCon.' as charging_con',$consultTeachers.' as consult_teachers',
				$listenTeachers.' as listen_teachers',$kxTeachers.' as kx_teachers',$outlineTeachers.' as outline_teachers',
				'c.logo','c.create_at','c.tel','c.found_time','null as listen_label','null as consult_label'
				,'c.id as clinic_id','c.full_address'
			])
			->find();
		}

		if($clinicData){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$clinicData]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'该机构不存在']);
		}
	}

	/**
	 * 商户统计
	 */
	public function clinicCount()
	{
		$blacklist = new Blacklist;
		$clinic = new ClinicModel;
		// 所有商户
		$all = $clinic->count();
		// 个人商户
		$personal = $clinic->where(['nature'=>1])->count();
		// 企业商户
		$enterprise = $clinic->where(['nature'=>2])->count();
		// 待审
		$pending = $clinic->where([
			'status'=>['between',[-2,1]],
		])->count();
		$black = $blacklist->where(['type'=>2,'valid_is'=>1])->count();
		return json([
			'success'=>true,
			'code'=>'000',
			'message'=>'统计完成',
			'data'=>compact('all','personal','enterprise','pending','black'),
		]);
	}

	/**
	 * 机构来访用户
	 */
	public function visitors()
	{
		$clinicId = input('param.clinic_id');
		$preNum = input('param.num',20);
		$order = new Order;
		$users = $order->alias('o')->join('sy_user u','o.uid=u.id')
		->where(['o.clinicid'=>$clinicId])
		->field(['u.avatarurl','u.id'])
		->group('o.uid')
		->paginate($preNum);
		if($users){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$users]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'暂时还没有来访客户！']);
		}
	}

	/**
	 * 获取评论
	 */
	public function evaluate()
	{
		$clinicId = input('param.clinic_id');
		$clinic = new ClinicModel;
		$clinicData = $clinic->getComments($clinicId);
		return json(['success'=>true,'code'=>'000','data'=>$clinicData]);
	}

	/**
	 * 机构动态
	 */
	public function clinicTrends()
	{
		$request = Request::instance();
		$clinicId = input('param.clinic_id');
		$clinic = new ClinicModel;
		$relevant = new TeacherRelevantRecord;
		// 全部订单
		$consoltSql = Db::name('ordermore')->alias('om')->where('om.orderid=o.orderid')->field('count(*)')->buildSql();
		$listenOrderData = $clinic->alias('c')
				->join('sy_order o','o.clinicid=c.id','LEFT')
				->join('sy_listenrecord ol','ol.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user u','u.id=o.serverpersonid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where(['c.id'=>$clinicId,'o.type'=>1])
				->field([
					'ol.stime','ol.etime','o.content',
					'o.orderid','o.alltime','o.sytime',
					'o.type','uf.realname','u.avatarurl',
					'uu.gender','uu.nickname','o.topic'
				])
				->order('o.createtime','desc')
				->limit(10)
				->select();
		$consultOrderData = $clinic->alias('c')
				->join('sy_order o','o.clinicid=c.id','LEFT')
				->join('sy_ordermore om','om.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user u','u.id=o.serverpersonid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where(['c.id'=>$clinicId,'o.type'=>2])
				->field([
					'o.content','o.orderid','om.starttime',
					'om.endtime','o.alltime','o.sytime',
					'om.number','o.type',$consoltSql.' as consult_con',
					'uf.realname','u.avatarurl','uu.gender','uu.nickname','o.topic','om.stime','om.etime'
				])
				->order('o.createtime','desc')
				->limit(10)
				->select();
		// 评论
		$commentData = $clinic->getComments($clinicId,10);
		// 机构合作记录
		$recordData = $relevant->getAllClinicRele($clinicId,10);
		$clinicData = [
			'listen_orders'=>$listenOrderData,
			'consult_orders'=>$consultOrderData,
			'comments'=>$commentData,
			'relevant'=>$recordData,
		];
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$clinicData]);
	}

	/**
	 * 机构动态（订单）
	 */
	public function trendsOrders()
	{
		$request = Request::instance();
		$clinicId = input('param.clinic_id');
		$type = input('param.type',0);
		$clinic = new ClinicModel;
		$consoltSql = Db::name('ordermore')->alias('om')->where('om.orderid=o.orderid')->field('count(*)')->buildSql();
		switch ($type) {
			case 0:// 全部
				$clinicData = $clinic->alias('c')
				->join('sy_order o','o.clinicid=c.id','LEFT')
				->join('sy_ordermore om','om.orderid=o.orderid','LEFT')
				->join('sy_listenrecord ol','ol.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user u','u.id=o.serverpersonid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where(['c.id'=>$clinicId])
				->field(['ol.stime','ol.etime','o.content','o.orderid','om.starttime','om.endtime','o.alltime','o.sytime','om.number','o.type',$consoltSql.' as consult_con','uf.realname','u.avatarurl','uu.gender','uu.nickname','o.topic'])
				->order('o.createtime','desc')
				->paginate(20);
				break;

			case 1:// 倾听
				$clinicData = $clinic->alias('c')
				->join('sy_order o','o.clinicid=c.id','LEFT')
				->join('sy_listenrecord ol','ol.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user u','u.id=o.serverpersonid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where(['c.id'=>$clinicId,'o.type'=>1])
				->field(['ol.stime','ol.etime','o.content','o.orderid','o.alltime','o.sytime','o.type','uf.realname','u.avatarurl','uu.gender','uu.nickname','o.topic'])
				->order('o.createtime','desc')
				->paginate(20);
				break;
			case 2:// 咨询
				$clinicData = $clinic->alias('c')
				->join('sy_order o','o.clinicid=c.id','LEFT')
				->join('sy_ordermore om','om.orderid=o.orderid','LEFT')
				->join('sy_userfield uf','o.serverpersonid=uf.uid','LEFT')
				->join('sy_user u','u.id=o.serverpersonid','LEFT')
				->join('sy_user uu','uu.id=o.uid','LEFT')
				->where(['c.id'=>$clinicId,'o.type'=>2])
				->field(['o.content','o.orderid','om.starttime','om.endtime','o.alltime','o.sytime','om.number','o.type',$consoltSql.' as consult_con','uf.realname','u.avatarurl','uu.gender','uu.nickname','o.topic','om.stime','om.etime'])
				->order('o.createtime','desc')
				->paginate(20);
			    break;
			
			default:
				$clinicData = [];
				break;
		}
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$clinicData]);
	}

	/**
	 * 添加规则/导航
	 * @notes 此接口同一时间仅允许请求一次（避免并发）
	 */
	public function addRule()
	{
		$request = Request::instance();
		// module下必须有一条规则 moduleid可不传，程序且认为新建module
		$post = $request->only(['rule_title','rule_content','rule_module_name','rule_module_id']);
		$vali = $this->validate($post,'ClinicValidate.rule');
		if($vali!==true){
			return json(['success'=>false,'code'=>'002','message'=>$vali]);
		}
		$rule = new ClinicRule;
		$result = $rule->createRule([
			'title'=>$post['rule_title'],
			'content'=>$post['rule_content'],
			'module'=>empty($post['rule_module_id'])?'':$post['rule_module_id'],
			'module_name'=>empty($post['rule_module_name'])?'':$post['rule_module_name']
		]);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'保存成功！']);
		}else{
			return json(['success'=>false,'code'=>'007','message'=>'保存出错，请稍后再试！']);
		}
	}

	/**
	 * 获取规则
	 */
	public function getRule()
	{
		$rule = new ClinicRule;
		$moduleId = input('module');
		if(empty($moduleId)){
			$moduleId = $rule->order('module','asc')->value('module');
		}
		$rules = $rule->where(function($query)use($moduleId){
			if($moduleId!=0){
				$query->where('module',$moduleId);
			}
		})->order('create_at','desc')->paginate(15);
		// key：module模块ID value：module模块名称
		$module = array_column(collection($rule->field(['module_name','module'])->select())->toArray(),'module_name','module');
		if($rules){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>['rules'=>$rules,'module'=>$module]]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'暂时未填写任何规则']);
		}
	}

	/**
	 * 修改规则状态
	 */
	public function editRule()
	{
		$request = Request::instance();
		$post = $request->only(['rule_title','rule_content','rule_state']);
		$ruleId = input('param.rule_id');
		$rule = new ClinicRule;
		$ruleData = $rule->find($ruleId);
		$editData = [];
		$stateDe = ['feiqi'=>0,'fabu'=>1];
		if(!empty($post['rule_state'])&&isset($stateDe[$post['rule_state']])){
			$editData['state'] = $stateDe[$post['rule_state']];
		}
		if(!empty($post['rule_title'])&&$post['rule_title']!=$ruleData->title){
			$editData['title'] = $post['rule_title'];
		}
		if(!empty($post['rule_content'])&&$post['rule_content']!=$ruleData->content){
			$editData['content'] = $post['rule_content'];
		}
		if(empty($editData)){
			return json(['success'=>false,'code'=>'000','message'=>'您没有做任何修改！']);
		}
		$result = $rule->editRule($ruleId,$editData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'修改成功']);
		}else{
			return json(['success'=>false,'code'=>'007','message'=>'修改出错，请稍后再试！']);
		}
	}

	/**
	 * 动态发布
	 */
	public function sendNews()
	{
		$request = Request::instance();
		$post = $request->only(['title','content']);
		$status = input('param.status',1);
		$trends = new ClinicTrends;
		$createData = [
			'title'=>trim($post['title']),
			'content'=>trim($post['content'])
		];
		$statusDe = ['send'=>1,'deaft'=>0];
		$statusText = ['send'=>'发布','deaft'=>'保存'];
		$statusEn = ['send','deaft'];
		if(!in_array($status, $statusEn)){
			return json(['success'=>false,'code'=>"006",'message'=>'没有预定义的参数']);
		}
		$createData['status'] = $statusDe[$status];
		$result = $trends->createTrends($createData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>$statusText[$status].'成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>$statusText[$status].'出错，请稍后重试']);
		}
	}

	/**
	 * 修改动态文章的状态
	 */
	public function editStatus()
	{
		$request = Request::instance();
		$post = $request->only(['status','trends_id']);
		$nowTime = time();
		$statusDe = ['send'=>1,'deaft'=>0];
		$statusText = ['send'=>'发布','deaft'=>'保存'];
		$trends = ClinicTrends::get($post['trends_id']);
		if(!$trends){
			return json(['success'=>false,'code'=>'013','message'=>'该文章可能已经被隐藏或删除']);
		}
		$trends->status = $statusDe[$post['status']];
		$trends->update_at = $nowTime;
		if($trends->save()){
			return json(['success'=>true,'code'=>'000','message'=>$statusText[$post['status']].'成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>$statusText[$post['status']].'出错，请稍后再试']);
		}
	}

	/**
	 * 修改动态内容
	 */
	public function editTrends()
	{
		$request = Request::instance();
		$post = $request->only(['title','content','trends_id']);
		$nowTime = time();
		$trends = ClinicTrends::get($post['trends_id']);
		if(!$trends){
			return json(['success'=>false,'code'=>'013','message'=>'该文章可能已经被隐藏或删除']);
		}
		$trends->title = $post['title'];
		$trends->content = $post['content'];
		$trends->update_at = $nowTime;
		if($trends->save()){
			return json(['success'=>true,'code'=>'000','message'=>'保存完成']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'保存出错，请稍后再试']);
		}
	}

	/**
	 * 查询动态
	 */
	public function trends()
	{
		$trends = ClinicTrends::order('create_at','desc')->paginate(20);
		if($trends){
			return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$trends]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'查询出错，请稍后重试']);
		}
	}

	/**
	 * 所有机构保证金统计
	 */
	public function depositCount()
	{
		$deposit = new ClinicDeposit;
		$closure = new ClinicClosure;
		$payTotal = $deposit->where(['add_subtract'=>1])->sum('recharge_money');
		$chargTotal = $deposit->where(['add_subtract'=>0])->sum('charging_money');
		$cancelTotal = $closure->where(['progress_status'=>2])->sum('progress_status');
		$balance = $payTotal-($chargTotal+$cancelTotal);
		return json(['success'=>true,'code'=>'000','message'=>'查询完成','data'=>[
			'pay_total'=>$payTotal,
			'charg_total'=>$chargTotal,
			'cancel_total'=>$cancelTotal,
			'balance'=>$balance,
		]]);
	}

	/**
	 * 机构保证金
	 */
	public function deposit()
	{
		$request = Request::instance();
		$post = $request->only(['clinic_id','clinic_name']);
		$clinic = new ClinicModel;
		$deposit = new ClinicDeposit;
		// 缴纳金额
		$payTotal = $deposit->alias('cd')->where('c.id=cd.clinic_id')->where(['cd.add_subtract'=>1])->field('sum(cd.recharge_money)')->buildSql();
		// 扣除
		$depositTotal = $deposit->alias('cd')->where('c.id=cd.clinic_id')->where(['cd.add_subtract'=>0])->field('sum(cd.charging_money)')->buildSql();
		$clinics = $clinic->alias('c')
		->join('sy_clinic_closure cc','cc.clinic_id=c.id','LEFT')
		->join('sy_clinic_deposit cd','cd.clinic_id=c.id')
		->where(function($query)use($post){
			if(!empty($post['clinic_id'])){
				$query->where(['c.id'=>$post['clinic_id']]);
			}
			if(!empty($post['clinic_name'])){
				$query->where(['c.clinic_name'=>['like','%'.$post['clinic_name'].'%']]);
			}
		})
		->field(['c.clinic_name','c.id',$payTotal.' as pay_total',$depositTotal.' as deposit_total','cc.deposit_money as close_deposit_money','('.$payTotal.'-'.$depositTotal.') as balance'])
		->group('c.id')
		->paginate(15);
		if($clinics){
			return json(['success'=>true,'code'=>'000','message'=>'查询完成','data'=>$clinics]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'还没有机构缴纳保证金']);
		}
	}

	/**
	 * 保证金交易记录
	 */
	public function depositDetails()
	{
		$request = Request::instance();
		$payordeduct = input('param.payordeduct');
		$clinicId = input('param.clinic_id');
		$bond = new ClinicBond;
		$deposit = new ClinicDeposit;
		$closure = new ClinicClosure;
		$bankcard = new ClinicBankCard;
		$bondWhere = [];
		$paySum = $deposit->where(['add_subtract'=>1,'clinic_id'=>$clinicId])->sum('recharge_money');
		$charSum = $deposit->where(['add_subtract'=>0,'clinic_id'=>$clinicId])->sum('charging_money');
		$balance = $paySum-$charSum;
		$cancelTotal = $closure->where(['clinic_id'=>$clinicId,'progress_status'=>2])->value('deposit_money');
		$card = $bankcard->where(['clinic_id'=>$clinicId])->find();
		$card->card_bank = bankInfo($card->card_number);
		if(!empty($payordeduct) && $payordeduct=='pay'){ // 充值记录
			$bondWhere['de.clinic_id'] = $clinicId;
			$bondWhere['o.pay_state'] = 1;
			$bondWhere['de.add_subtract'] = 1;
			$queryData = $deposit->alias('de')
			->join('sy_clinic_bond_order o','de.bonds_id=o.bond_id','LEFT')
			->where($bondWhere)
			->field([
				'de.deposit_id',
				'de.create_at deposit_create_time',
				'o.create_at order_create_time',
				'o.order_number',
				'de.add_subtract',
				'de.charging_money',
				'o.pay_state',
				'o.sum_money',
				'o.update_at pay_time',
				'de.sketch',
				'null as self_account',
				'null as he_account'
			])
			->paginate(15);
		}else if(!empty($payordeduct) && $payordeduct=='deduct'){ // 扣款记录
			$bondWhere['de.clinic_id'] = $clinicId;
			$bondWhere['de.add_subtract'] = 0;
			$queryData = $deposit->alias('de')
			->join('sy_clinic_bond_order o','de.bonds_id=o.bond_id','LEFT')
			->where($bondWhere)
			->field([
				'de.deposit_id',
				'de.create_at deposit_create_time',
				'o.create_at order_create_time',
				'o.order_number',
				'de.add_subtract',
				'de.charging_money',
				'o.pay_state',
				'o.sum_money',
				'o.update_at pay_time',
				'de.sketch',
				'null as self_account',
				'null as he_account'
			])
			->paginate(15);
		}else{ // 全部
			$bondWhere['de.clinic_id'] = $clinicId;
			$queryData = $deposit->alias('de')
			->join('sy_clinic_bond_order o','o.bond_id=de.bonds_id','LEFT')
			->where($bondWhere)
			->field([
				'de.deposit_id',
				'de.create_at deposit_create_time',
				'o.create_at order_create_time',
				'o.order_number',
				'de.add_subtract',
				'de.charging_money',
				'o.pay_state',
				'o.sum_money',
				'o.update_at pay_time',
				'de.sketch',
				'null as self_account',
				'null as he_account'
			])
			->paginate(15);
		}
		if($queryData){
			return json([
				'success'=>true,
				'code'=>'000',
				'message'=>'查询成功',
				'data'=>[
					'query'=>$queryData,
					'balance'=>$balance,
					'standard'=>config('deoisit'),
					'char_total'=>$charSum,
					'cancel_total'=>$cancelTotal,
					'bankcard'=>$card,
				]
			]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'查询出错']);
		}
	}

	/**
	 * 保证金详细
	 */
	/*public function depositDetails()
	{
		$request = Request::instance();
		$payordeduct = input('param.payordeduct');
		$clinicId = input('param.clinic_id');
		$bond = new ClinicBond;
		$deposit = new ClinicDeposit;
		$closure = new ClinicClosure;
		$bankcard = new ClinicBankCard;
		$bondWhere = [];
		$paySum = $deposit->where(['add_subtract'=>1,'clinic_id'=>$clinicId])->sum('recharge_money');
		$charSum = $deposit->where(['add_subtract'=>0,'clinic_id'=>$clinicId])->sum('charging_money');
		$balance = $paySum-$charSum;
		$cancelTotal = $closure->where(['clinic_id'=>$clinicId,'progress_status'=>2])->value('deposit_money');
		$card = $bankcard->where(['clinic_id'=>$clinicId])->find();
		$card->card_bank = bankInfo($card->card_number);
		if(!empty($payordeduct) && $payordeduct=='pay'){ // 充值记录
			$bondWhere['clinic_id'] = $clinicId;
			$bondWhere['pay_state'] = 1;
			$queryData = $bond
			->where($bondWhere)
			->field([
				'create_at order_create_time',
				'order_number',
				'pay_state',
				'sum_money',
				'update_at pay_time',
			])
			->paginate(15);
		}else if(!empty($payordeduct) && $payordeduct=='deduct'){ // 扣款记录
			$bondWhere['de.clinic_id'] = $clinicId;
			$bondWhere['de.add_subtract'] = 0;
			$queryData = $deposit->alias('de')
			->join('sy_clinic_bond_order o','de.bonds_id=o.bond_id','LEFT')
			->where($bondWhere)
			->field([
				'de.create_at deposit_create_time',
				'o.create_at order_create_time',
				'o.order_number',
				'de.add_subtract',
				'de.charging_money',
				'o.pay_state',
				'o.sum_money',
				'o.update_at pay_time',
				'de.sketch',
			])
			->paginate(15);
		}else{ // 全部
			$bondWhere['de.clinic_id'] = $clinicId;
			$queryData = $deposit->alias('de')
			->join('sy_clinic_bond_order o','o.bond_id=de.bonds_id','LEFT')
			->where($bondWhere)
			->field([
				'de.create_at deposit_create_time',
				'o.create_at order_create_time',
				'o.order_number',
				'de.add_subtract',
				'de.charging_money',
				'o.pay_state',
				'o.sum_money',
				'o.update_at pay_time',
				'de.sketch',
			])
			->paginate(15);
		}
		if($queryData){
			return json([
				'success'=>true,
				'code'=>'000',
				'message'=>'查询成功',
				'data'=>[
					'query'=>$queryData,
					'bankcard'=>$card,
					'pay_total'=>$paySum,
					'char_total'=>$charSum,
					'balance'=>$balance,
					'cancel_total'=>$cancelTotal,
				]
			]);
		}else{
			return json(['success'=>false,'code'=>'013','message'=>'查询出错']);
		}
	}*/

	/**
	 * 关停申请列
	 */
	public function closureList()
	{
		$clinicId = input('param.clinic_id');
		$progress = input('param.progress');
		$startApply = input('param.apply_start_date');
		$endApply = input('param.apply_end_date');
		$clinicName = input('param.clinic_name');

		$closure = new ClinicClosure;
		$closures = $closure->alias('cc')
		->join('sy_clinic c','c.id=cc.clinic_id')
		->where(function($query)use($progress,$clinicName,$clinicId,$startApply,$endApply){
			if(!empty($progress)){
				$query->where(['cc.progress_status'=>$progress]);
			}
			if(!empty($clinicName)){
				$query->where(['c.clinic_name'=>['like','%'.$clinicName.'%']]);
			}
			if(!empty($clinicId)){
				$query->where(['cc.clinic_id'=>$clinicId]);
			}
			if(!empty($startApply) && !empty($endApply)){
				$query->where('c.create_at','between time',[$startApply,$endApply]);
			}
		})
		->field(['c.clinic_name','cc.*'])
		->paginate(15);
		return json(['success'=>true,'code'=>'000','message'=>'查询完成','data'=>$closures]);
	}

	/**
	 * 关停申请详情
	 */
	public function closureDetails()
	{
		$closureId = input('param.closure_id');
		$closure = new ClinicClosure;
		$card = new ClinicBankCard;
		$deposit = new ClinicDeposit;
		// 申请信息
		$closureData = $closure->alias('cc')
		->join('sy_clinic c','c.id=cc.clinic_id')
		->where(['closure_id'=>$closureId])
		->field(['c.clinic_name','cc.*'])
		->find();
		// 银行卡信息
		$bankCard = $card->where(['card_id'=>$closureData['card_id']])->find();
		if($bankCard){
			$bankCard->card_bank = bankInfo($bankCard->card_number);
		}
		// 保证金余额
		$balance = $deposit->getBalance($closureData['clinic_id']);
		// 总共缴纳过的金额
		$payTotal = $deposit->where(['clinic_id'=>$closureData['clinic_id']])->sum('recharge_money');
		// 保证金扣除
		$deductionTotal = $deposit->where(['clinic_id'=>$closureData['clinic_id']])->sum('charging_money');
		// 注销回退金额
		$cancelTotal = $closureData['deposit_money'];
		return json([
			'success'=>true,
			'code'=>'000',
			'message'=>'查询完成',
			'data'=>[
				'closure_data'=>$closureData,
				'bank_card'=>$bankCard,
				'balance'=>$balance,
				'pay_total'=>$payTotal,
				'deduction_total'=>$deductionTotal,
				'cancel_total'=>$cancelTotal,
			]
		]);
	}

	/**
	 * 关停通过审核
	 */
	public function editClosureStatus()
	{
		$clinicId = input('param.clinic_id');
		// 0 驳回  1 通过
		$action = input('param.action');
		$statusDe = [0=>-1,1=>2];
		$closure = new ClinicClosure;
		$clinic = new ClinicModel;
		$deposit = new ClinicDeposit;
		$nowTime = time();
		
		$closureData = $closure->where(['clinic_id'=>$clinicId,'progress_status'=>['<',2]])->find();
		if(!$closureData){
			return json(['success'=>false,'code'=>'006','message'=>'没有查询到申请记录！']);
		}
		$closureData->startTrans();
		$clinic->startTrans();
		if($closureData->getData('progress_status')!=1){
			return json(['success'=>false,'code'=>'007','message'=>"当前状态不能不能操作"]);
		}
		if($action==1){
			$deposit->startTrans();
			$closureData->progress_status = 2;
			$clinicRes = $clinic->editData($closureData->clinic_id,['status'=>-6,'run_status'=>2]);
			$deduRes = $deposit->deduction($closureData->clinic_id,$closureData->deposit_money,'机构注销扣除余额');
			$closureData->update_at = $nowTime;
			if($closureData->save() && $clinicRes && $deduRes){
				$closureData->commit();
				$clinic->commit();
				$deposit->commit();
				return json(['success'=>true,'code'=>'000','message'=>'操作成功']);
			}else{
				$closureData->rollback();
				$clinic->rollback();
				$deposit->rollback();
				return json(['success'=>false,'code'=>"006",'message'=>"操作失败，请稍后重试！"]);
			}
		}else if($action==0){
			$closureData->progress_status = -1;
			$closureData->update_at = $nowTime;
			$clinicRes = $clinic->editData($closureData->clinic_id,['status'=>2]);
			if($closureData->save() && $clinicRes){
				$closureData->commit();
				$clinic->commit();
				return json(['success'=>true,'code'=>'000','message'=>'操作成功']);
			}else{
				$closureData->rollback();
				$clinic->rollback();
				return json(['success'=>false,'code'=>"006",'message'=>"操作失败，请稍后重试！"]);
			}
		}else{
			return json(['success'=>false,'code'=>'012','message'=>"没有预定义的参数"]);
		}
	}

	/**
	 * 机构通过审核
	 */
	public function examine()
	{
		// 通过 驳回
		$examine = input('param.examine');
		$clinicId = input('param.clinic_id');
		$reason = input('param.reason','请仔细填写');
		$clinic = new ClinicModel;
		$innermail = new InnerMail;
		if($clinic->where('id',$clinicId)->value('apply_schedule')!=2){
			return json(['success'=>false,'code'=>"012",'message'=>'当前状态不能修改']);
		}
		if($examine==0){
			$result = $clinic->refuse($clinicId);
			$innermail->addInnerMail(
				'资料审核未通过！',
				3,
				'您的帐号申请未能通过审核，原因如下：'.$reason,
				$clinicId,
				4
			);
		}else if($examine==1){
			$result = $clinic->adopt($clinicId,true);
			$innermail->addInnerMail(
				'恭喜审核通过！',
				3,
				'审核通过！请添加老师，继续您的尚言心理旅程吧～',
				$clinicId,
				4
			);
		}else{
			return json(['success'=>false,'code'=>"012",'message'=>'为识别']);
		}
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'操作成功']);
		}else{
			return json(['success'=>false,'code'=>"006",'message'=>"通过出错，请稍后重试！"]);
		}
	}

	/**
	 * 加入黑名单
	 */
	public function blacklistAction()
	{
		$request = Request::instance();
		$post = $request->only(['black_reason','clinic_id','black_reason_other']);
		$vali = $this->validate($post,'BlacklistValidate.clinic');
		if( $vali !== true ){
			return json(['success'=>false,'code'=>'002','message'=>$vali]);
		}
		$insertData = [];
		$blacklist = new Blacklist;
		if(!empty($post['black_reason_other'])){
			$insertData['reason_other'] = $post['black_reason_other'];
		}
		$insertData['client_id'] = $post['clinic_id'];
		$insertData['reason'] = $post['black_reason'];
		$insertData['type'] = 2;
		$result = $blacklist->insertData($insertData,'clinic',$post['clinic_id']);
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
		$clinicId = input('param.clinic_id');
		if(empty($clinicId)){
			return json(['success'=>false,'code'=>'002','message'=>'机构ID不能为空']);
		}
		$blacklist = new Blacklist;
		$result = $blacklist->cancel($clinicId,'clinic');
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'取消成功']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'撤出黑名单出错，请稍后重试']);
		}
	}

	/**
	 * 机构上下架
	 */
	public function shelf()
	{
		$clinicId = input('param.clinic_id');
		// up down
		$type = input('param.type');
		$vali = $this->validate(['clinic_id'=>$clinicId,'type'=>$type],'ClinicValidate.shelf');
		if($vali!==true){
			return json(['success'=>false,'code'=>"002",'message'=>$vali]);
		}
		$clinic = new ClinicModel;
		$result = $clinic->shelf($clinicId,$type);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'修改完成']);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'无法修改，请确保该机构的审核已通过且保证金余额大于等于'.config('deoisit')]);
		}
	}

	/**
	 * 获取机构合作记录
	 */
	public function relevantRecord()
	{
		$clinicId = input('param.clinic_id');
		$relevant = new TeacherRelevantRecord;
		$clinicData = $relevant->getAllClinicRele($clinicId);
		return json(['success'=>true,'code'=>'000','message'=>'查询成功','data'=>$clinicData]);
	}

	/**
	 * 添加机构账号
	 */
	public function createAccount()
	{
		$request = Request::instance();
		
		$param = ['number_email', 'password', 'repassword'];
		// $param[] = 'ycode';
		// 获取请求参数 
		$post = $request->only( $param );
		// 验证参数是否符合规则 
		$vali = $this->validate($post, 'ClinicValidate.account');
		if( $vali !== true ){ // 返回错误的验证结果
			return json(['success'=>false,'code'=>'002','message'=>$vali]);
		}
		$clinic = new ClinicModel;
		if($clinic->where('email',$post['number_email'])->find()){
			return json(['success'=>false,'code'=>'004','message'=>'该邮箱已经注册。']);
		}
		$createData = [
			'email'=>$post['number_email'],
			'password'=>md5($post['password']),
		];
		$result = $clinic->createData($createData);
		if($result){
			return json(['success'=>true,'code'=>'000','message'=>'添加完成','clinic_id'=>$result]);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'修改出错，请稍后重试！']);
		}
	}

	/**
	 * 机构基本信息图片上传
	 */
	public function uploadImgs()
	{
		$name = input('post.name');
		$clinicId = Session::get('admin_id');
		$path = date('Ymd').'/';
		$redis = new Redis;
		$response = [];
		// 前端拼接地址
		$response['path'] = config('IMGPRESENT');
	    // 图片对应地址
	    $imgPath = [
	        'logo'=>config('IMG')['clinic_logo'],
	        'business_license'=>config('IMG')['clinic_business_license'],
	        'operator_identity_A'=>config('IMG')['clinic_identity'],
	        'operator_identity_B'=>config('IMG')['clinic_identity'],
	        'liable_identity_A'=>config('IMG')['clinic_identity'],
	        'liable_identity_B'=>config('IMG')['clinic_identity'],
	        'scene_photo'=>config('IMG')['clinic_scene'],
	        'related_photo'=>config('IMG')['clinic_related'],
	    ];
		// 执行上传
		$filename = put_oss($name, $imgPath);
		if(!$filename['success']){
			return json($filename);
		}
		// 随机字符串返回
		$str = generate_rand(15,true);
		// 文件地址存储到redis
		$redis->set2('clinic_info_'.$clinicId.'_'.$str,$filename['filename']);
		// 设置有效时间2小时
		$redis->expireAt('clinic_info_'.$clinicId.'_'.$str,time()+60*60+2);
		
		return json(['success'=>true,'code'=>'000','message'=>'上传完成','data'=>['filename'=>$filename['filename'],'no'=>$str]]);
	}

	/**
	 * 修改上传图片
	 */
	public function editImg()
	{
		$name = input('post.name');
		$clinicId = config('test_id');
		$path = date('Ymd').'/';
		$redis = new Redis;
		$response = [];
		// 前端拼接地址
		$response['path'] = config('IMGPRESENT');
		// 图片对应地址
	    $imgPath = [
	        'logo'=>config('IMG')['clinic_logo'],
	        'business_license'=>config('IMG')['clinic_business_license'],
	        'operator_identity_A'=>config('IMG')['clinic_identity'],
	        'operator_identity_B'=>config('IMG')['clinic_identity'],
	        'liable_identity_A'=>config('IMG')['clinic_identity'],
	        'liable_identity_B'=>config('IMG')['clinic_identity'],
	        'scene_photo'=>config('IMG')['clinic_scene'],
	        'related_photo'=>config('IMG')['clinic_related'],
	    ];
		// 执行上传
		$filename = put_oss($name, $imgPath);
		if(!$filename['success']){
			return json($filename);
		}
		// 随机字符串返回
		$str = generate_rand(15,true);
		// 文件地址存储到redis
		$redis->set2('clinic_editimg_'.$clinicId.'_'.$str,$filename['filename']);
		// 设置有效时间2小时
		$redis->expireAt('clinic_editimg_'.$clinicId.'_'.$str,time()+60*60+2);
		
		return json([
			'success'=>true,
			'code'=>'000',
			'message'=>'上传完成',
			'data'=>['filename'=>$filename['filename'],'no'=>$str]
		]);
	}

	/**
	 * 已关停机构申请重新开启
	 */
	public function reOpenClinic()
	{
		$clinicId = input('param.clinic_id');
		// 0 拒绝。1 开启
		$action = input('param.action');
		$nowTime = time();
		$statusDe = [0=>-7,1=>2];
		$clinic = new ClinicModel;
		$clinicData = $clinic->get($clinicId);
		if($clinicData->getData('status')!=-8){
			return json(['success'=>false,'code'=>'012','message'=>'您当前状态不可也无需执行此操作']);
		}
		if(!array_key_exists($action, $statusDe)){
			return json(['success'=>false,'code'=>'012','message'=>'没有预定义的参数']);
		}
		if($action==0){
			$insertData = [];
			$blacklist = new Blacklist;
			$insertData['reason_other'] = '已关停被拒绝重新开启';
			$insertData['client_id'] = $clinicId;
			$insertData['reason'] = 5;
			$insertData['type'] = 2;
			$result = $blacklist->insertData($insertData,'clinic',$clinicId);
			if($result['success']){
				return json(['success'=>true,'code'=>'000','message'=>'操作成功！']);
			}else{
				return json($result);
			}
		}else{
			$clinicData->status = 2;
			$clinicData->update_at = $nowTime;
			if($clinicData->save()){
				return json(['success'=>true,'code'=>'000','message'=>'操作成功！']);
			}else{
				return json(['success'=>false,'code'=>'006','messaeg'=>'操作出错，请稍后再试！']);
			}
		}
	}

	/**
	 * 录入机构信息
	 */
	public function clinicInfoInsert()
	{
		$request = Request::instance();
		$redis = new Redis;
		$name = [
			// 基本信息 1个人/2企业
			'clinic_name','logo_no','business_license_no','found_time','nature','introduce',
			// 运营人信息
			'operator_name','operator_identity_A_no','operator_identity_B_no','operator_ycode','operator_tel',
			// 法人信息
			'liable_name','liable_identity_A_no','liable_identity_B_no','liable_tel','liable_ycode',
			// 场地信息
			'address','full_address','latitude','longitude','scene_photo_no','related','city','clinic_id','email'
		];
		// BIG BUG 、、、
		@$params = $request->param()['clinic'];
		$fileParam = [
			'logo'=>'logo_no',
			'business_license'=>'business_license_no',
			'operator_identity'=>['operator_identity_A_no','operator_identity_B_no'],
			'liable_identity'=>['liable_identity_A_no','liable_identity_B_no'],
			'scene_photo'=>'scene_photo_no'
		];
		$fieldText = [
			'logo'=>'logo',
			'business_license'=>'营业执照',
			'operator_identity'=>'运营人身份证图片信息',
			'liable_identity'=>'法人（负责人）身份证图片信息',
			'scene_photo'=>'场地图片信息'
		];
		$post = [];
        foreach ($name as $key) {
            if (isset($params[$key])) {
                $post[$key] = $params[$key];
            }
        }
		$clinic = new ClinicModel;
		$clinicAdd = new ClinicRelated;
		$nowTime = time();
		
		$adminId = Session::get('admin_id');
		$infoType = input('post.model');
		$response = [];
		$response['path'] = config('IMGPRESENT');
		$imgKey = 'clinic_info_'.$adminId.'_';
		$valiMethod = $post['nature']==1?'clinic_info_all_2':'clinic_info_all';
		$vali = $this->validate($post, 'ClinicValidate.'.$valiMethod);
		if( $vali !== true){ // 返回错误的验证结果
			return json(['success'=>false,'code'=>'002','message'=>$vali]);
		}
		if(empty($post['clinic_id'])){
			$queryData = $clinic->where(['email'=>$post['email']])->find();
		}else{
			$queryData = $clinic->get($post['clinic_id']);
		}
		$insertData = [];
		if(($queryData->getData('status')==0&&$queryData['apply_schedule']==1) || ($queryData->getData('status')==-1&&$queryData['apply_schedule']==1)){
			foreach ($fileParam as $key => $value) {
				if($key=='liable_identity' && $post['nature']==1){
					continue;
				}
				if(is_array($value)){
					$fieldData = explode(',',$queryData->getData($key));
					if(@$redis->get2($imgKey.$post[$value[0]])) {
						$fieldData[0] = $redis->get2($imgKey.$post[$value[0]]);
					}
					if(@$redis->get2($imgKey.$post[$value[1]])) {
						$fieldData[1] = $redis->get2($imgKey.$post[$value[1]]);
					}
					$fieldData = implode(',', $fieldData);
					$insertData[$key] = $fieldData;
				}else{
					$fieldData = '';
					if ($key == 'scene_photo') {
						if(empty($post[$value])){
							continue;
						}
						$scenefield = explode(',',$queryData->getData($key));
						// 目前是三个
						for ($i=0; $i < 3; $i++) {
							if(isset($post[$value][$i])){
								$scenefield[$i] = $redis->get2($imgKey.$post[$value][$i]);
							}
						}
						$insertData[$key] = implode(',', $scenefield);
					}
					if(@$redis->get2($imgKey.$post[$value])) {
						$fieldData = $redis->get2($imgKey.$post[$value]);
						$insertData[$key] = $fieldData;
					}
				}
			}
		}else{
			foreach ($fileParam as $key => $value) {
				if(is_array($value)){
					$fieldData = explode(',',$queryData->getData($key));
					if(!isset($value[0]) || !isset($value[1])){
						return json(['success'=>false,'code'=>'002','message'=>'请上传'.$fieldText[$key].'图片']);
					}
					if(!$redis->get2($imgKey.$post[$value[0]]) || !$redis->get2($imgKey.$post[$value[1]])){
						return json(['success'=>false,'code'=>'002','message'=>'您上传的'.$fieldText[$key].'已过期']);
					}
					$fieldData[0] = $redis->get2($imgKey.$post[$value[0]]);
					$fieldData[1] = $redis->get2($imgKey.$post[$value[1]]);
					$insertData[$key] = implode(',', $fieldData);
				}else{
					if(empty($post[$value])){
						return json(['success'=>false,'code'=>'002','message'=>'请上传'.$fieldText[$key].'图片']);
					}
					if ($key == 'scene_photo') {
						$scenefield = explode(',',$queryData->getData($key));
						// 目前是三个
						for ($i=0; $i < 3; $i++) {
							if(isset($post[$value][$i])){
								if(!$redis->get2($imgKey.$post[$value][$i])){
									return json(['success'=>false,'code'=>'002','message'=>'您上传的'.$fieldText[$key].'已过期']);
								}
								$scenefield[$i] = $redis->get2($imgKey.$post[$value][$i]);
							}
						}
						$insertData[$key] = implode(',', $scenefield);
						continue;
					}
					if(!$redis->get2($imgKey.$post[$value])) {
						return json(['success'=>false,'code'=>'002','message'=>'您上传的'.$fieldText[$key].'已过期']);
					}
					$insertData[$key] = $redis->get2($imgKey.$post[$value]);
				}
			}
		}
		$insertData['clinic_name'] = trim($post['clinic_name']);
		$insertData['found_time'] = @trim($post['found_time']);
		$insertData['introduce'] = trim($post['introduce']);
		$insertData['operator_name'] = trim($post['operator_name']);
		$insertData['operator_tel'] = trim($post['operator_tel']);
		$insertData['liable_name'] = @trim($post['liable_name']);
		$insertData['liable_tel'] = @trim($post['liable_tel']);
		$insertData['address'] = trim($post['address']);
		$insertData['full_address'] = trim($post['full_address']);
		$insertData['city'] = trim($post['city']);
		$insertData['latitude'] = trim($post['latitude']);
		$insertData['longitude'] = trim($post['longitude']);
		$insertData['nature'] = trim($post['nature']);
		$insertData['apply_schedule'] = 3;
		$insertData['status'] = 2;
		$insertData['run_status'] = 0;
		if(!empty($post['related'])){
			// 附加信息
			$relatedData = [];
			foreach ($post['related'] as $value) {
				$k = [];
				$relatedVali = $this->validate($value, 'ClinicValidate.related');
				if( $relatedVali !== true){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$relatedVali]);
				}
				if(!empty($value['related_id'])){
					$k['related_id'] = trim($value['related_id']);
					if(!empty($value['related_photo_no'])){
						$k['related_photo'] = $redis->get2($imgKey.$value['related_photo_no']);
					}
				}else{
					$k['related_photo'] = $redis->get2($imgKey.$value['related_photo_no']);
					$k['clinic_id'] = $queryData['id'];
				}
				$k['related_name'] = trim($value['related_name']);
				$k['related_desc'] = trim($value['related_desc']);
				$k['related_link'] = trim($value['related_link']);
				$k['create_at'] = $nowTime;
				$relatedData[] = $k;
			}
			$resultRelated = $clinicAdd->saveAll($relatedData);
			if(!$resultRelated){
				return json(['success'=>false,'code'=>'000','message'=>'信息保存出错，请稍后重试']);
			}
		}
		// 写入数据
		$result = $clinic->editData($queryData['id'],$insertData);
		if($result){
			$response['success'] = true;
			$response['code'] = '000';
			$response['message'] = '保存成功！';
			return json($response);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'数据保存出错，请稍后重试！']);
		}
	}

	/**
	 * 填写入驻信息
	 *
	 * @param model/[clinic(机构基本)][operator(运营人员信息)][liable(法人信息)][scene(场地信息)][related(其他信息)]
	 *
	 * @return String
	 */
	public function applyInfo()
	{
		$request = Request::instance();
		$clinicId = input('param.clinic_id');
		$adminId = Session::get('admin_id');
		$redis = new Redis;
		$clinic = new ClinicModel;
		$infoType = input('post.model');
		$response = [];
		$response['path'] = config('IMGPRESENT');
		$clinicData = $clinic->where('id',$clinicId)->field('password',true)->find();
		if(!$clinicData){
			return json(['success'=>false,'code'=>'007','message'=>'没有查询到您的机构']);
		}
		$editImg = 'clinic_info_'.$adminId.'_';
		switch ($infoType) {
			// 诊所基本资料
			case 'clinic':
				$post = $request->only([
					'clinic_name','logo_no','business_license_no','found_time'
				]);

				// 验证参数是否符合规则
				$vali = $this->validate($post, 'ClinicValidate.clinic');
				if( $vali !== true){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$vali]);
				}
				$insertData = [];
				$path = date('Ymd').'/';
				if(!empty($post['logo_no'])){
					if(!$redis->get2($editImg.$post['logo_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'logo图片已过期，请重新上传']);
					}
					$insertData['logo'] = $redis->get2($editImg.$post['logo_no']);
				}
				if(!empty($post['business_license_no'])){
					if(!$redis->get2($editImg.$post['business_license_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'营业执照图片已过期，请重新上传']);
					}
					$insertData['business_license'] = $redis->get2($editImg.$post['business_license_no']);
				}
				
				$insertData['clinic_name'] = trim($post['clinic_name']);
				$insertData['found_time'] = trim($post['found_time']);
				break;
			// 运营人员信息
			case 'operator':
				$post = $request->only([
					'operator_name','operator_identity_A_no','operator_identity_B_no',
					'operator_ycode','operator_tel'
				]);
				// 验证参数是否符合规则
				$vali = $this->validate($post, 'ClinicValidate.operator');
				if( $vali !== true ){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$vali]);
				}

				if($clinicData->operator_tel!=$post['operator_tel']){
					$tools = new Tools;
					$checkTime = $tools->checkTime($post['operator_tel']);
					if ($checkTime) {
						//没过期,检验验证码
						$check = Db::name('smscode')->where(['mobile'=>$post['operator_tel'],'code'=>$post['operator_ycode']])->find();
						if (!$check){
							return json(['success'=>false,'code'=>'009','message'=>'验证码不正确']);
						}
					}else{
						return json(['success'=>false,'code'=>'009','message'=>'验证码已过期，请重新获取']);
					}
				}

				$insertData = [];
				
				$identityArr = explode(',',$clinicData->operator_identity);
				if(!empty($post['operator_identity_A_no'])){
					if(!$redis->get2($editImg.$post['operator_identity_A_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'身份证正面图片已过期，请重新上传']);
					}
					$insertData['operator_identity'] = $redis->get2($editImg.$post['operator_identity_A_no']).','.$identityArr[1];
					$identityArr[0] = $redis->get2($editImg.$post['operator_identity_A_no']);
				}
				if(!empty($post['operator_identity_B_no'])){
					if(!$redis->get2($editImg.$post['operator_identity_B_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'身份证反面图片已过期，请重新上传']);
					}
					$insertData['operator_identity'] = $identityArr[0].','.$redis->get2($editImg.$post['operator_identity_B_no']);
				}
				$insertData['operator_name'] = trim($post['operator_name']);
				$insertData['operator_tel'] = $post['operator_tel'];
				break;

			case 'liable':// 法人信息
				$post = $request->only([
					'liable_name','liable_identity_A_no','liable_identity_B_no',
			        'liable_tel','liable_ycode'
				]);
				// 验证参数是否符合规则
				$vali = $this->validate($post, 'ClinicValidate.liable');
				if( $vali !== true ){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$vali]);
				}

				if($clinicData->liable_tel!=$post['liable_tel']){
					$tools = new Tools;
					$checkTime = $tools->checkTime($post['liable_tel']);
					if ($checkTime) {
						//没过期,检验验证码
						$check = Db::name('smscode')->where(['mobile'=>$post['liable_tel'],'code'=>$post['liable_ycode']])->find();
						if (!$check){
							return json(['success'=>false,'code'=>'009','message'=>'验证码不正确']);
						}
					}else{
						return json(['success'=>false,'code'=>'009','message'=>'验证码已过期，请重新获取']);
					}
				}

				$insertData = [];

				$identityArr = explode(',',$clinicData->liable_identity);

				if(!empty($post['liable_identity_A_no'])){
					if(!$redis->get2($editImg.$post['liable_identity_A_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'身份证正面图片已过期，请重新上传']);
					}
					$insertData['liable_identity'] = $redis->get2($editImg.$post['liable_identity_A_no']).','.$identityArr[1];
					$identityArr[0] = $redis->get2($editImg.$post['liable_identity_A_no']);
				}

				if(!empty($post['liable_identity_B_no'])){
					if(!$redis->get2($editImg.$post['liable_identity_A_no'])){
						return json(['success'=>false,'code'=>'002','message'=>'身份证反面图片已过期，请重新上传']);
					}
					$insertData['liable_identity'] = $identityArr[0].','.$redis->get2($editImg.$post['liable_identity_B_no']);
					$identityArr[1] = $redis->get2($editImg.$post['liable_identity_B_no']);
				}
				
				$insertData['liable_name'] = trim($post['liable_name']);
				$insertData['liable_tel'] = trim($post['liable_tel']);

				break;

			case 'scene':// 场地信息
				$post = $request->only([
					'address','latitude','longitude','scene_photo_no','full_address','city'
				]);
				// 验证参数是否符合规则
				$vali = $this->validate($post, 'ClinicValidate.scene');
				if( $vali !== true ){ // 返回错误的验证结果
					return json(['success'=>false,'code'=>'002','message'=>$vali]);
				}
				$path = date('Ymd').'/';
				$filenames = '';
				$yScene = explode(',',$clinicData->getData('scene_photo'));
				for ($i=0; $i < count($post['scene_photo_no']); $i++) { 
					if(!empty($post['scene_photo_no'][$i])){
						if(!$redis->get2($editImg.$post['scene_photo_no'][$i])){
							return json(['success'=>false,'code'=>'002','message'=>'场地图片已过期，请重新上传']);
						}
						$yScene[$i] = $redis->get2($editImg.$post['scene_photo_no'][$i]);
					}
				}
				$filenames = implode(',', $yScene);
				$insertData = [
					'address' => trim($post['address']),
					'full_address' => trim($post['full_address']),
					'city' => trim($post['city']),
					'latitude' => trim($post['latitude']),
					'longitude' => trim($post['longitude']),
					'scene_photo' => $filenames,
				];
				break;

			case 'related':// 其他信息添加或修改
				@$relateds = $request->param()['related'];
				$relatedData = [];
				$clinicAdd = new ClinicRelated;
				foreach ($relateds as $value) {
					$relatedVali = $this->validate($value, 'ClinicValidate.related');
					if( $relatedVali !== true){ // 返回错误的验证结果
						return json(['success'=>false,'code'=>'002','message'=>$relatedVali]);
					}
					$editData = [];
					if(!empty($value['related_id'])){
						$editData['related_id'] = $value['related_id'];
						if(!empty($value['related_photo_no'])){
							if(!$redis->get2($editImg.$value['related_photo_no'])){
								return json(['success'=>false,'code'=>'002','message'=>'资料图片已过期，请重新上传']);
							}
							$editData['related_photo'] = $redis->get2($editImg.$value['related_photo_no']);
						}
						if($clinicId!=$clinicAdd->where('related_id',$value['related_id'])->value('clinic_id')){
							return json(['success'=>false,'code'=>'007','message'=>'不可修改改信息！']);
						}
					}else{
						if(empty($value['related_photo_no'])){
							return json(['success'=>false,'code'=>'002','message'=>'资料图片不能为空']);
						}
						$editData['related_photo'] = $redis->get2($editImg.$value['related_photo_no']);
					}
					$editData['clinic_id'] = $clinicId;
					$editData['related_name'] = trim($value['related_name']);
					$editData['related_desc'] = trim($value['related_desc']);
					$editData['related_link'] = trim($value['related_link']);
					$relatedData[] = $editData;
				}
				
				$resultRelated = $clinicAdd->saveAll($relatedData);
				if($resultRelated){
					return json([
						'success'=>true,
						'code'=>'000',
						'message'=>'保存成功'
					]);
				}else{
					return json(['success'=>false,'code'=>"006",'message'=>'抱歉，保存失败请稍后重试！']);
				}
			default:
				return json(['success'=>false,'code'=>'007','message'=>'没有该模块的存储位置']);
		}
		// 写入数据
		$result = $clinic->editData($clinicId,$insertData);
		if($result){
			$response['success'] = true;
			$response['code'] = '000';
			$response['message'] = '保存成功！';
			return json($response);
		}else{
			return json(['success'=>false,'code'=>'006','message'=>'数据保存出错，请稍后重试！']);
		}
	}
}	
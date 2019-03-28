<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
class Businessaccount extends Common
{
	//商户业务账列表
	public function accountList(){
		//查询条件
		$data = input('post.');
		$where = [];
		$_where = [];
		//默认时间段
		if (isset($data['time'])) {
			$time = $data['time'];
			$now = date('Y-m-d H:i:s');
			switch ($time)
			{
			case 'today':
			  $data['stime'] = date('Y-m-d');
			  $data['etime'] = $now;
			  break;  
			case 'yesterday':
			  $data['stime'] = date('Y-m-d',strtotime('-1 days'));
			  $data['etime'] = $now;
			  break;
			case 'beforeyesterday':
			  $data['stime'] = date('Y-m-d',strtotime('-2 days'));
			  $data['etime'] = $now;
			  break;
			case 'beforesevenday':
			  $data['stime'] = date('Y-m-d',strtotime('-7 days'));
			  $data['etime'] = $now;
			  break;
			case 'beforethirtyday':
			  $data['stime'] = date('Y-m-d',strtotime('-30 days'));
			  $data['etime'] = $now;
			  break;  
			// default:

			}
		}
		//必须有时间段
		if (isset($data['stime'])&&isset($data['etime'])) {
			# code...
			$stime = strtotime($data['stime']);
			if (!$stime){
                $stime = strtotime(date('Y-m').'-01');
            }
			$etime = strtotime($data['etime']);
            if (!$etime){
                $etime = time();
            }
			//以入账时间为条件(即支付时间)
			$where['paytime'] = ['between',$stime.','.$etime];
		}else{
			sendJson(-1,'时间必须填写');
		}
		if (isset($data['id'])&&!empty($data['id'])) {
    		//商户id
    		# code...
    		$_where['id'] = $data['id'];
    	}elseif (isset($data['clinic_name'])&&!empty($data['clinic_name'])) {
    		//商户名称
    		# code...
    		$clinic_name = $data['clinic_name'];
    		$_where['clinic_name'] = ['like',['%'.$clinic_name,$clinic_name.'%','%'.$clinic_name.'%'],'OR'];
    	}
    	$_where['status']= ['not in',[-1,0,1]];
    	//分页
    	//总数
    	$count = db('clinic')->field('id,clinic_name')->where($_where)->count();
    	//总页数
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page/d',1));
    	$page = $page<=0?1:$page;
    	//print_r($totalpages);
    	
		//获取所有机构列表
		$clinic = db('clinic')->field('id,clinic_name')->where($_where)->page($page,$pageSize)->select();
		//echo db('clinic')->getLastSql();
		//总收入流水
		$t_income = 0;
		//总技术服务费
		$t_service_charge = 0;
		//总退款
		$t_refund_amount = 0;
		//总已经结算金额
		$t_settled = 0;
		//总未结算金额
		$t_unsettled = 0;
		$bakwhere = $where;
		foreach ($clinic as $key => $value) {
			#获取商户的总收入流水
			$where = $bakwhere;
			$where['completion_time'] = ['<>',0];
			$where['clinicid'] = $value['id'];
			$income = db('order')->where($where)->sum('ordermoney');
            //print_r($where);
			$t_income +=$income;
			//echo db('order')->getLastSql();
			//技术服务费
			$service_charge = db('order')->where($where)->sum('service_charge');
			$t_service_charge +=$service_charge;
			//退费金额
			$refund_amount = db('order')->where($where)->sum('refund_amount');
			$t_refund_amount +=$refund_amount;
			//获取已经结算的金额
			$where['financial_status'] = 1;
			$settled = db('order')->where($where)->sum('real_income');
            //echo \db('order')->getLastSql();
			$t_settled +=$settled;
			//获取未结算
			$where['financial_status'] = 0;
			$unsettled = db('order')->where($where)->sum('real_income');

			$t_unsettled +=$unsettled;


			$clinic[$key]['income'] = $income;
			$clinic[$key]['service_charge'] = $service_charge;
			$clinic[$key]['refund_amount'] = $refund_amount;
			$clinic[$key]['settled'] = $settled;
			$clinic[$key]['unsettled'] = $unsettled;
		}
		$list['total'] = ['t_income'=>number_format($t_income,2,".",""),
						  't_service_charge'=>number_format($t_service_charge,2,".",""),
						  't_refund_amount'=>number_format($t_refund_amount,2,".",""),
						  't_settled'=>number_format($t_settled,2,".",""),
						  't_unsettled'=>number_format($t_unsettled,2,".","")];
		$list['page'] = ['pagesize'=>$pageSize,'page'=>$page,'count'=>$count];
		$list['list'] = $clinic;
		$list['all_money'] = $this->getGeneralLedger();
		// print_r($list);
		sendJson(1,'业务账',$list);
		// $list = db('clinic c')->field('c.id,clinic_name,sum(ordermoney) income,sum(service_charge) service_charge,sum(refund_amount) refund_amount')->join('order o','c.id=o.clinicid','LEFT')->group('clinic_name')->select();
		// foreach ($list as $key => $value) {
		// 	//计算已经结算的金额订单金额与退款金额
		// 	$settled = db('order')->field('sum(ordermoney)')->where(['clinicid'=>$value['id'],'financial_status'=>1])->sum('ordermoney');
		// }
		// print_r($list);
	}
	//账单详情
	public function accountInfo(){
		$data = input('post.');
		//默认时间段
		if ($data['time']) {
			$time = $data['time'];
			$now = date('Y-m-d H:i:s');;
			switch ($time)
			{
			case 'today':
			  $data['stime'] = date('Y-m-d');
			  $data['etime'] = $now;
			  break;  
			case 'yesterday':
			  $data['stime'] = date('Y-m-d',strtotime('-1 days'));
			  $data['etime'] = $now;
			  break;
			case 'beforeyesterday':
			  $data['stime'] = date('Y-m-d',strtotime('-2 days'));
			  $data['etime'] = $now;
			  break;
			case 'beforesevenday':
			  $data['stime'] = date('Y-m-d',strtotime('-7 days'));
			  $data['etime'] = $now;
			  break;
			case 'beforethirtyday':
			  $data['stime'] = date('Y-m-d',strtotime('-30 days'));
			  $data['etime'] = $now;
			  break;  
			// default:
			}
		}
		$where = [];
		$where['paytime'] = ['<>',0];
		//必须有时间段
		if (isset($data['stime'])&&isset($data['etime'])&&isset($data['id'])) {
			# code...
			$stime = strtotime($data['stime']);
			$etime = strtotime($data['etime']);
			$where['clinicid'] = $data['id'];
			$where['paytime'] = ['between',$stime.','.$etime];
		}else{
			sendJson(-1,'时间与商户id必须填写');
		}
//		print_r($where);
		$count = db('order')->field('orderid,completion_time,type,ordermoney,service_charge,refund_amount,real_income')->where($where)->count();
		//总页数
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page/d',1));
    	$page = $page<=0?1:$page;
    	print_r($where);
		$order = db('order')->field('orderid,completion_time,paytime,type,ordermoney,service_charge,refund_amount,real_income,status')->where($where)->page($page,$pageSize)->select();

		//总应得
		$t_real_income = 0;
		//总用户付款
		$t_ordermoney = 0;
		//总技术服务费
		$t_service_charge = 0;
		//总
		$t_refund_amount = 0;
		foreach ($order as $key => $value) {
			# code...
			//总应得
			$t_real_income += $value['real_income'];
			//总用户付款
			$t_ordermoney += $value['ordermoney'];
			//总技术服务费
			$t_service_charge += $value['service_charge'];
			//总
			$t_refund_amount += $value['refund_amount'];
		}
		$list['total'] = ['t_real_income'=>number_format($t_real_income,2,".",""),
						  't_service_charge'=>number_format($t_service_charge,2,".",""),
						  't_refund_amount'=>number_format($t_refund_amount,2,".",""),
						  't_ordermoney'=>number_format($t_ordermoney,2,".","")];
		$list['page'] = ['pagesize'=>$pageSize,'page'=>$page,'count'=>$count];
		$list['list'] = $order;

		//print_r($list);
		sendJson(1,'业务账详情',$list);
	}
	//获取商户支付信息
	public function getBankcard(){
		$clinic_id = input('post.clinic_id');
		if (!$clinic_id){
		    sendJson(-1,'clinic_id不能为空');
        }
		$bankcard = db('clinic_bankcard')->where(['clinic_id'=>$clinic_id])->find();
		//print_r($bankcard);
        if (!$bankcard){
            sendJson(-1,'商户没有绑定银行卡');
        }
		$bankcard['clinic_name'] = db('clinic')->where(['id'=>$clinic_id])->value('clinic_name');
		$bankcard['bank_name'] = $this->bankInfo($bankcard['card_number']);
		sendJson(1,'商户支付信息',$bankcard);
		//print_r($bankcard);
	}
	//编辑商户账户信息
	public function editBankcard(){
		$clinic_id = input('post.clinic_id');
		$data = input('post.');
		$update = [];
		if (!empty($data['card_number'])&&!empty($data['card_holder'])&&!empty($data['card_tel'])) {
			# code...
			$update = [
				'card_number'=>input('post.card_number'),
				'card_holder'=>input('post.card_holder'),
				'card_tel'=>input('post.card_tel'),
				'update_at'=>time()
			];
		}	
			db('clinic_bankcard')->where(['clinic_id'=>$clinic_id])->update($update);
			sendJson(1,'修改商户支付信息');
	}
	/**
	 * 根据卡号获取所属银行
	 */
	public function bankInfo($card) {  
	    //$bankList = json_decode(file_get_contents('F:\phpstudy\PHPTutorial\WWW\tp5.0\public\static\BankName.json'),true);
	    $bankList = json_decode(file_get_contents('/home/b.shangyanxinli.com/public/static/BankName.json'),true);

	    $card_8 = substr($card, 0, 8);  
	    if (isset($bankList[$card_8])) {  
	        return $bankList[$card_8];
	    }  
	    $card_6 = substr($card, 0, 6);  
	    if (isset($bankList[$card_6])) {  
	        return $bankList[$card_6];
	    }  
	    $card_5 = substr($card, 0, 5);  
	    if (isset($bankList[$card_5])) {  
	        return $bankList[$card_5];
	    }  
	    $card_4 = substr($card, 0, 4);  
	    if (isset($bankList[$card_4])) {  
	        return $bankList[$card_4];
	    }
	}
	//获取平台累计金额数据(获取总账)
	public function getGeneralLedger(){
		$data = [];
		//累计已经结算收入
		$data['all_settled']= db('order')->where(['financial_status'=>1,'paytime'=>['<>',0]])->sum('real_income');
		//当前待结算收入
		$data['all_settlement']= db('order')->where(['financial_status'=>0,'paytime'=>['<>',0]])->sum('real_income');
		//累计收入流水
		$data['all_income']= db('order')->where(['paytime'=>['<>',0]])->sum('ordermoney');
		//累计技术服务费
		$data['all_service_charge']= db('order')->where(['paytime'=>['<>',0]])->sum('service_charge');
		//累计退款
		$data['all_refund_amount']= db('order')->where(['paytime'=>['<>',0]])->sum('refund_amount');
		return $data;
	}
	//生成结算周期表
	public function createSettleCycle(){
		//获取上个月的Ym如201811
		$date = date('Ym', strtotime(date('Y-m-01') . ' -1 month'));
		$num = db('settle_cycle')->where(['settle_id'=>['like',$date.'%']])->count();
		$num++;
		$settle_id = date('Ym', strtotime(date('Y-m-01') . ' -1 month')).sprintf("%02d",$num);
			$start = strtotime(date('Y-m-01') . ' -1 month');
			$settle_start = date('Y-m-d', $start);
			$end = strtotime(date('Y-m-01') . ' -1 day');
			$settle_end = date('Y-m-d', $end);
			//商户数量
			$where = [];
			$where['settle_time'] = ['between',$start.','.$end];
			$where['financial_status'] = 0;
			$merchant_num = db('order')->where($where)->group('clinicid')->count();
			//结算金额
			$settle_amount = db('order')->where($where)->sum('real_income');
			//添加到结算周期表的数据
			$settlecycle = [
				'settle_id'=>$settle_id,
				'settle_start'=>$settle_start,
				'settle_end'=>$settle_end,
				'merchant_num'=>$merchant_num,
				'settle_amount'=>$settle_amount,
			];
			db('settle_cycle')->insert($settlecycle);
			$settlecycleinfo = $this->getSettleCycleInfo($settle_id,$where);
			db('settle_cycle_info')->insertAll($settlecycleinfo);
			$this->getSettleCycleClinicInfo($settle_id,$where);
			sendJson(1,'结算周期已经生成');
		
		//print_r($settlecycle);
	}
	//获得结算周期详情数据
	public function getSettleCycleInfo($settle_id,$where){
		$clinicids = db('order')->where($where)->group('clinicid')->column('clinicid');
		//echo db('order')->getLastSql();
		$data = [];
		foreach ($clinicids as $key => $clinic_id) {
			$clinic_name = db('clinic')->where(['id'=>$clinic_id])->value('clinic_name');
			$where['clinicid'] = $clinic_id;
			$settle_amount = db('order')->where($where)->sum('real_income');
			$data[$key] = [
				'settle_id'=>$settle_id,
				'clinic_id'=>$clinic_id,
				'clinic_name'=>$clinic_name,
				'settle_amount'=>$settle_amount,
			];
		}
		return $data;
	}
	//结算周期内商户明细
	public function getSettleCycleClinicInfo($settle_id,$where){
		$clinicids = db('order')->where($where)->group('clinicid')->column('clinicid');
		foreach ($clinicids as $clinic_id) {
			$where['clinicid'] = $clinic_id;
			$data = db('order')->field('type,clinicid clinic_id,orderid,paytime,completion_time,ordermoney,service_charge,refund_amount,real_income')->where($where)->select();
			echo db('order')->getLastsql();
			foreach ($data as $k => $v) {
				$data[$k]['settle_time'] = time(); 
				$data[$k]['settle_id'] = $settle_id; 
			}
			db('settle_clinic_info')->insertAll($data);
			db('order')->where($where)->update(['financial_status'=>1]);
		}
	}
	//结算单列表
	public function settleList(){
		$settle_id = input('post.settle_id');
		$settle_time = input('post.settle_time');
		$where = [];
		if(isset($settle_time)&&!empty($settle_time)){
		    $settle_time = preg_replace('/-+/i','',$settle_time);
            $settle_time = substr($settle_time,0,5);
			$where['settle_id'] = ['like',$settle_time.'%'];
		}
		if(isset($settle_id)&&!empty($settle_id)){
			$where['settle_id'] = $settle_id;
		}
		
		$count = db('settle_cycle')->where($where)->count();
		//总页数
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page/d',1));
    	$page = $page<=0?1:$page;
		$list = db('settle_cycle')->where($where)->page($page,$pageSize)->order('id','desc')->select();
		$data['page'] = ['pagesize'=>$pageSize,'page'=>$page,'count'=>$count];
		$data['list'] = $list;
		sendJson(1,'结算单列表',$data);
	}
	//结算单详情
	public function settleInfo(){
		$settle_id = input('post.settle_id');
		$settle = db('settle_cycle')->where(['settle_id'=>$settle_id])->find();
		$clinic_id = input('post.clinic_id');
		$clinic_name = input('post.clinic_name');
		$where = [];
		$where['settle_id'] = $settle_id;
		if (isset($clinic_name)&&!empty($clinic_name)) {
			$where['clinic_name'] = ['like',['%'.$clinic_name,$clinic_name.'%','%'.$clinic_name.'%'],'OR'];
		}
		if (isset($clinic_id)&&!empty($clinic_id)) {
			$where['clinic_id'] = $clinic_id;
		}
		$count = db('settle_cycle_info')->where($where)->count();
		//总页数
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page/d',1));
    	$page = $page<=0?1:$page;
		$list = db('settle_cycle_info')->where($where)->page($page,$pageSize)->select();
		$data['page'] = ['pagesize'=>$pageSize,'page'=>$page,'count'=>$count];
		$data['list'] = $list;
		$data['settle'] = $settle;
		sendJson(1,'结算单详情',$data);
	}
	//商户结算明细
	public function settleClinicInfo(){
		$infoid = input('post.infoid');
		if (!$infoid)
        {
            sendJson(-1,'infoid不能为空');
        }
		//$settle_id = input('post.settle_id');
		$info = db('settle_cycle_info')->where(['id'=>$infoid])->find();
		if (!$info)
        {
            sendJson(-1,'没有找到该条记录');
        }
		$settle_id = $info['settle_id'];
		$clinic_id = $info['clinic_id'];
		$settle = db('settle_cycle')->where(['settle_id'=>$settle_id])->find();
		//商户银行信息
		$bankcard = db('clinic_bankcard')->where(['clinic_id'=>$clinic_id])->find();
       if (!$bankcard)
       {
           sendJson(-1,'该商户没有银行卡信息');
       }
		//print_r($bankcard);
		$bankcard['clinic_name'] = db('clinic')->where(['id'=>$clinic_id])->value('clinic_name');
		$bankcard['bank_name'] = $this->bankInfo($bankcard['card_number']);
		$where = ['settle_id'=>$settle_id,'clinic_id'=>$clinic_id];
		$count = db('settle_clinic_info')->where($where)->count();
		//总页数
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page/d',1));
    	$page = $page<=0?1:$page;
		$list = db('settle_clinic_info')->where($where)->page($page,$pageSize)->select();
		//结算金额,机构实收
		$settle_amount = db('settle_clinic_info')->where($where)->sum('real_income');
		//收入流水,订单金额
		$ordermoney = db('settle_clinic_info')->where($where)->sum('ordermoney');
		//技术服务费
		$service_charge = db('settle_clinic_info')->where($where)->sum('service_charge');
		//退款
		$refund_amount = db('settle_clinic_info')->where($where)->sum('refund_amount');
		$data = [
			'settle'=>$settle,
			'bankcard'=>$bankcard,
			'page'=>['pagesize'=>$pageSize,'page'=>$page,'count'=>$count],
			'list'=>$list,
			'settle_amount'=>$settle_amount,
			'ordermoney'=>$ordermoney,
			'service_charge'=>$service_charge,
			'refund_amount'=>$refund_amount
		];
		sendJson(1,'商户结算明细',$data);
//		print_r($orderlist);
	}
	/**
     * 确定结算
     */
    public function sureSettle()
    {
        $settle_id = input('post.settle_id');
        $settle_status = \db('settle_cycle')->where(['settle_id'=>$settle_id])->value('settle_status');
        if ($settle_status == 1)
        {
            sendJson(-1,'该结算单已经结算过了');
        }
        \db('settle_cycle')->where(['settle_id'=>$settle_id])->update(['settle_status'=>1]);
        sendJson(1,'该结算单结算成功');
	}
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;
use app\admin\model\CouponsModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;

class Coupons extends Common
{


    //所有劵
        public function couponsList()
    {

        $request=Request::instance();
//        $page=$request->post('page',1);
        $limit=$request->post('limit',10);
        $couponType=$request->post('couponType',null);
        $validEndTime=$request->post('validEndTime',null);

           //查询条件
        $where=array();
        $where['flag']=1;
        $where['examine']=1;

        if(!empty($couponType)){
            $where['couponType']=$couponType;
        }

        //判断是否为机构的劵
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
            $where['ad_id']=session('admin_id');
        }

        //已过期

        if(!empty($validEndTime)){

            $where['validEndTime']=array('lt',$validEndTime);
        }




        $data= CouponsModel::where($where)->order('create_at','desc')->paginate($limit);



        if($data){
            return json(['code'=>'000','message'=>'成功','data'=>$data]);
        }
           else{
               return json(['code'=>'000','message'=>'空','data'=>array()]);
           }


    }


    /**
     * 添加优惠券

     * ];
     */
    public function addCoupons( ){
         $param=input('post.');



        if(empty($param['couponName'])||empty($param['couponMoney'])||empty($param['couponRange'])||empty(session('admin_id'))||empty(session('role_id'))){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        if(session('role_id')==8){//为机构
        $param['is_jigou']=1;
        }
        else{
            $param['is_jigou']=2;//为平台发放
        }

        //插入后台发送者ID
        $param['ad_id']=session('admin_id');

        try{

            $param['create_at']=time();

            $request=Request::instance();

            $param['url']= $request->domain().'/index/coupons/register';

           if($getId=CouponsModel::insert($param))
           {

                   $content='审核消息!优惠券:'.$param['couponName'].'请审核!';//如果回复内容为空,自定义回复

               //rece_id=* 为超级管理员接收 ..发送站内信通知审核情况
               if(!\db('self_mail')->insert(array('send_id'=>$param['ad_id'],'rece_id'=>'*','content'=>$content,'create_at'=>time()))){


                   return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);
               }
               //添加操作日志
               $this->add_log($getId,'添加优惠券:'.$param['couponName'],$param['ad_id']);
               return json(['code'=>'000','message'=>'成功','data'=>array()]);
           }

        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }



    /**
     *
     *停止发放优惠券
     */
    public function delCoupons()
    {



        $param=input('post.');



        if(empty($param['couponId'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }



        $where=array();
        $where['id']=$param['couponId'];
        //判断是否为机构的劵
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
            $where['ad_id']=session('admin_id');
        }

          $couponsInfo=CouponsModel::where($where)->find();

        if(!$couponsInfo){
            return json(['code'=>'006','message'=>'错误的优惠券!','data'=>array()]);
        }



        if(CouponsModel::where($where)->update(['flag'=>2])){
                //添加操作日志
            $this->add_log($param['couponId'],'停止发放优惠券:'.$couponsInfo['couponName'],session('admin_id'));
            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'已经停止!','data'=>array()]);
        }

    }








}
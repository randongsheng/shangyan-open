<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;

use app\admin\model\TeacherModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;

class Teacher extends Common
{


    public function test()
    {

       $redis=new \Redis();
       $redis->set('15733118589',54122);
    }
    //所有老师
        public function teacherList()
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
     * 添加老师

     * ];
     */
    public function addTeacher(Request $request){
         $param=input('post.');


        if(empty($param['phone'])||empty($param['name'])||empty($param['id_card'])||empty(session('admin_id'))||empty(session('role_id'))){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        if(session('role_id')!=Env::get('jigou.jigou')){//为机构

            return json(['code'=>'002','message'=>'请机构角色添加哦!','data'=>array()]);
        }

        //插入后台发送者ID
        $param['ad_id']=session('admin_id');

        try{

            $param['create_at']=time();



           if($getId=TeacherModel::insertGetId($param))
           {


               //添加操作日志
               $this->add_log($getId,'添加优惠券:'.$param['couponName'],$param['ad_id']);
               return json(['code'=>'000','message'=>'成功','data'=>array()]);
           }

        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }



   







}
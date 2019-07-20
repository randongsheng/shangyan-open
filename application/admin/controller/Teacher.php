<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;

use app\admin\model\TeacherCertificateModel;
use app\admin\model\TeacherModel;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;


class Teacher extends Common
{



    //所有老师
        public function teacherList()
    {

        $request=Request::instance();

        $limit=$request->post('limit',10);
        $name=$request->post('name',null);


           //查询条件
        $where=array();
        $where['flag']=1;


        if(!empty($name)){
            $where['name']=array('like','%'.$name.'%');
        }

        //判断所属机构
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
            $where['ad_id']=session('admin_id');
        }



        $data= TeacherModel::where($where)->field('id,name,login_time')->order('create_at','desc')->paginate($limit);



        if($data){

            foreach ($data as $k=>$v){
                $data[$k]['order_course']=rand(15,99999);//本周订单课程
                $data[$k]['order_qing']=rand(15,99999);//本周订单轻咨询
                $data[$k]['order_zi']=rand(15,99999);//本周订单咨询
                $data[$k]['evaluate']=rand(15,99).'%';//好评度
            }
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


        if(empty($param['phone'])||empty($param['name'])||empty($param['id_card'])||empty($param['code'])||empty(session('admin_id'))||empty(session('role_id'))){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        if(session('role_id')!=Env::get('jigou.jigou')){//为机构

            return json(['code'=>'002','message'=>'请机构角色添加哦!','data'=>array()]);
        }

        //插入后台发送者ID
        $param['ad_id']=session('admin_id');

         $redis=new Redis();
         $mop=$redis->get(config('redis_type.teacher_add').$param['phone']);
         //判断验证码
        if(empty($mop)){
            return json(['code'=>'006','message'=>'验证码超时!','data'=>array()]);
        }
        if($mop!=$param['code']){
            return json(['code'=>'006','message'=>'验证码错误!','data'=>array()]);
        }

               Db::startTrans();

        try{

            $zhuan=$param['zhuan'];//专业证书
            $pei=$param['pei'];//培训证书

            $param['create_at']=time();
            //关联信息

            unset($param['code'],$param['zhuan'],$param['pei']);

           if($getId=TeacherModel::insertGetId($param))
           {

               if($zhuan){//添加证书
                   foreach ($zhuan as $k=>$v){
                 TeacherCertificateModel::insert(array('teacher_id'=>$getId,'cata_name'=>$v['cata_name'],'cata_number'=>$v['cata_number'],'type'=>1,'img'=>$v['img'],'create_at'=>time()));
                   }
               }
               if($pei){//添加培训证书
                   foreach ($pei as $ki=>$vi){
                       TeacherCertificateModel::insert(array('teacher_id'=>$getId,'type'=>3,'img'=>$vi,'create_at'=>time()));
                   }
               }

               //添加操作日志
               $this->add_log($getId,'添加老师:'.$param['name'],$param['ad_id']);

               Db::commit();
               return json(['code'=>'000','message'=>'成功','data'=>array()]);
           }

        }catch(\PDOException $e){
            Db::rollback();
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }












}
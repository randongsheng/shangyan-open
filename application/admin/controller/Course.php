<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;
use app\admin\model\CouponsModel;
use app\admin\model\CourseListModel;
use app\admin\model\CourseModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;

class Course extends Common
{


    //所有课程
    public function courseList()
    {



           //查询条件
        $where=array();
        $where['flag']=1;

        if(!empty($_POST['keyWord'])){
            $where['title']=array('like','%'.$_POST['keyWord'].'%');
        }

        //判断是否为机构的课程
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
            $where['ad_id']=session('admin_id');
        }




        $data= CourseModel::where($where)->order('create_at','desc')->select();


        if($data){
            return json(['code'=>'000','message'=>'成功','data'=>$data]);
        }
           else{
               return json(['code'=>'000','message'=>'空','data'=>array()]);
           }


    }


    /**
     * 添加课程

     * ];
     */
    public function addCourse( ){
         $param=input('post.');



        if(empty($param['title'])||empty($param['money'])||empty($param['thumb'])||empty($param['sue'])||empty($param['examine'])||empty($param['content'])||empty(session('admin_id'))||empty(session('role_id'))){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }
        if($param['examine']!=2&&$param['examine']!=4){//判断除了待审核,草稿之外的情况
            return json(['code'=>'006','message'=>'警告,危险操作!','data'=>array()]);
        }

        //插入后台发送者ID
        $param['ad_id']=session('admin_id');


              //课时情况
      if(!empty($param['list'])){
          $list=$param['list'];
          //去除课时信息
          unset($param['list']);
      }



      DB::startTrans();

        try{

            $param['create_at']=time();


           if(CourseModel::insert($param))
           {


               //加入课时
                   if(!empty($list)){
                   foreach ($list as $k=>$v){
                       $v['create_at']=time();
                     CourseListModel::insert($v);
                   }
                   }

                if($param['examine']==2){
                    $content='审核消息!课程:'.$param['title'].'请审核!';//如果回复内容为空,自定义回复

                    //rece_id=* 为超级管理员接收 ..发送站内信通知审核情况
                    if(!\db('self_mail')->insert(array('send_id'=>$param['ad_id'],'rece_id'=>'*','content'=>$content,'create_at'=>time()))){


                        return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);
                    }
                }

                DB::commit();

               return json(['code'=>'000','message'=>'成功','data'=>array()]);
           }

        }catch(\PDOException $e){
            DB::rollback();
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }



    /**
     *
     *提交审核
     */
    public function exCourse()
    {



        $param=input('post.');



        if(empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }



        $where=array();
        $where['id']=$param['id'];
        //判断是否为机构的劵
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
            $where['ad_id']=session('admin_id');
        }


          $courseInfo=CourseModel::where($where)->find();

        if(!$courseInfo||$courseInfo['examine']!=4){
            return json(['code'=>'006','message'=>'没有此课程!','data'=>array()]);
        }



        if(CourseModel::where($where)->update(['examine'=>2])){

            $content='审核消息!课程:'.$courseInfo['title'].'请审核!';//如果回复内容为空,自定义回复

            //rece_id=* 为超级管理员接收 ..发送站内信通知审核情况
            if(!\db('self_mail')->insert(array('send_id'=>$courseInfo['ad_id'],'rece_id'=>'*','content'=>$content,'create_at'=>time()))){


                return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);
            }

            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'提交审核失败!','data'=>array()]);
        }

    }




    /**
     *
     *课程上下架
     */
    public function topCourse()
    {



        $param=input('post.');



        if(empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }



        $where=array();
        $where['id']=$param['id'];
        //判断是否为机构的劵
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
            $where['ad_id']=session('admin_id');
        }


        $courseInfo=CourseModel::where($where)->find();

        if(!$courseInfo){
            return json(['code'=>'006','message'=>'没有此课程!','data'=>array()]);
        }


        if($courseInfo['examine']==1||$courseInfo['examine']==6){
              $result=CourseModel::where($where)->update(['examine'=>5]);
        }elseif ($courseInfo['examine']==5){
            $result=CourseModel::where($where)->update(['examine'=>6]);
        }
        else{
            return json(['code'=>'006','message'=>'错误的课程状态!','data'=>array()]);
        }



        if($result){


            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'失败!','data'=>array()]);
        }

    }


    
    
    /**
     * 编辑课程并提交审核
     */

    public function editCourse()
    {
        $param=input('post.');



        if(empty($param['id'])||empty(session('admin_id'))||empty(session('role_id'))){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        $courseInfo=CourseModel::where('id',$param['id'])->find();

        if(!$courseInfo){
            return json(['code'=>'006','message'=>'没有此课程!','data'=>array()]);
        }

        //插入后台发送者ID
        $param['ad_id']=session('admin_id');


        //修改课程情况
        $edit_course=$param['edit_course'];
        //增加课时情况
        $add_list=$param['add_course_list'];
        //修改课时情况
        $edit_list=$param['edit_course_list'];

        try{

            $param['create_at']=time();


            if(\db('course_edit_log')->insert($param))
            {


                    $content='审核消息!课程:'.$courseInfo['title'].'.请求修改!请审核!';//如果回复内容为空,自定义回复

                    //rece_id=* 为超级管理员接收 ..发送站内信通知审核情况
                    if(!\db('self_mail')->insert(array('send_id'=>$param['ad_id'],'rece_id'=>'*','content'=>$content,'create_at'=>time()))){


                        return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);

                }



                return json(['code'=>'000','message'=>'成功','data'=>array()]);
            }

        }catch(\PDOException $e){

            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }

}
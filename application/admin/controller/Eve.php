<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;
use app\admin\model\AdminModel;
use app\admin\model\CouponsModel;
use app\admin\model\CourseListModel;
use app\admin\model\CourseModel;
use app\admin\model\EveModel;
use app\admin\model\TeacherModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;

/**
 * Class Eve
 * @package app\admin\controller 每日三分钟控制器   liuxu
 */

class Eve extends Common
{


    //所有每日三分钟
    public function eveList()
    {

        $request=Request::instance();

        $limit=$request->post('limit',10);
        $keyWord=$request->post('keyWord',null);

           //查询条件
        $where=array();
        $where['flag']=1;


        if(!empty($keyWord)){
            $where['title']=array('like','%'.$keyWord.'%');
        }
        //判断是否为机构的课程
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
            $where['ad_id']=session('admin_id');
        }

        $data= EveModel::where($where)->order('create_at','desc')->paginate($limit);



        if($data){
            foreach ($data as $k=>$v){
                  if($v['range']==1){//老师
                      $data[$k]['teacher']=TeacherModel::field('name,id')->where('id',$v['range_id'])->find();
                  }elseif ($v['range']==2){//机构
                      $data[$k]['admin']=AdminModel::field('name,admin_id')->where('admin_id',$v['range_id'])->find();
                  }
                  elseif ($v['range']==3){//课程
                      $data[$k]['course']=CourseModel::field('title,id')->where('id',$v['range_id'])->find();


                  }
                $data[$k]['dayHot']=rand(15,99999);//日活跃
                $data[$k]['evaluate']=rand(15,99).'%';//好评度
            }
            return json(['code'=>'000','message'=>'成功','data'=>$data]);
        }
           else{
               return json(['code'=>'000','message'=>'空','data'=>array()]);
           }


    }


    /*
 * 每日三分钟详情
 */

    public function eveInfo(Request $request)
    {


        $courseId=$request->post('eveId',null);



        if(empty($courseId)){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }



        $where=array();
        $where['id']=$courseId;
        $where['flag']=1;



        $courseInfo=EveModel::where($where)->find();



        if($courseInfo){

            if($courseInfo['range']==1){//老师
                $courseInfo['teacher']=TeacherModel::field('name,id')->where('id',$courseInfo['range_id'])->find();
            }elseif ($courseInfo['range']==2){//机构
                $courseInfo['admin']=AdminModel::field('name,admin_id')->where('admin_id',$courseInfo['range_id'])->find();
            }
            elseif ($courseInfo['range']==3){//课程
                $courseInfo['course']=CourseModel::field('title,id')->where('id',$courseInfo['range_id'])->find();


            }

            return json(['code'=>'000','message'=>'成功!','data'=>$courseInfo]);
        }
        else{
            return json(['code'=>'006','message'=>'该课程有误!','data'=>array()]);

        }


    }



    /**
     * 添加每日三分钟

     * ];
     */
    public function addEve( ){
         $param=input('post.');




        if(empty($param['title'])||empty($param['thumb'])||empty($param['examine'])||empty($param['sound'])||empty(session('admin_id'))||empty(session('role_id'))){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }
        if($param['examine']!=2&&$param['examine']!=4){//判断除了待审核,草稿之外的情况
            return json(['code'=>'006','message'=>'警告,危险操作!','data'=>array()]);
        }

        //插入后台发送者ID
        $param['ad_id']=session('admin_id');

        try{


            $param['create_at']=time();


           if($getId=EveModel::insertGetId($param))

           {

                if($param['examine']==2){
                    $content='审核消息!每日三分钟:'.$param['title'].'请审核!';//如果回复内容为空,自定义回复

                    //rece_id=* 为超级管理员接收 ..发送站内信通知审核情况
                    if(!\db('self_mail')->insert(array('send_id'=>$param['ad_id'],'rece_id'=>'*','content'=>$content,'create_at'=>time()))){


                        return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);
                    }
                }


               //添加操作日志
               $this->add_log($getId,'添加每日三分钟:'.$param['title'],$param['ad_id']);
               return json(['code'=>'000','message'=>'成功','data'=>array()]);
           }

        }catch(\PDOException $e){

            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }



    /**
     *
     *提交审核
     */
    public function exEve()
    {



        $param=input('post.');



        if(empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }



        $where=array();
        $where['id']=$param['id'];
        //判断是否为机构的
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
            $where['ad_id']=session('admin_id');
        }


          $courseInfo=EveModel::where($where)->find();

        if(!$courseInfo||($courseInfo['examine']!=4&&$courseInfo['examine']!=3)){
            return json(['code'=>'006','message'=>'没有此课程!','data'=>array()]);
        }



        if(EveModel::where($where)->update(['examine'=>2])){

            $content='审核消息!每日三分钟:'.$courseInfo['title'].'请审核!';//如果回复内容为空,自定义回复

            //rece_id=* 为超级管理员接收 ..发送站内信通知审核情况
            if(!\db('self_mail')->insert(array('send_id'=>$courseInfo['ad_id'],'rece_id'=>'*','content'=>$content,'create_at'=>time()))){


                return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);
            }
            //添加操作日志
            $this->add_log($param['id'],'提交审核,每日三分钟:'.$courseInfo['title'],session('admin_id'));
            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'提交审核失败!','data'=>array()]);
        }

    }




    /**
     *
     *每日三分钟上下架
     */
    public function topEve()
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


        $courseInfo=EveModel::where($where)->find();

        if(!$courseInfo){
            return json(['code'=>'006','message'=>'没有此课程!','data'=>array()]);
        }


        if($courseInfo['examine']==1||$courseInfo['examine']==6){
              $result=EveModel::where($where)->update(['examine'=>5]);
            //添加操作日志
            $this->add_log($param['id'],'上架,每日三分钟:'.$courseInfo['title'],session('admin_id'));
        }elseif ($courseInfo['examine']==5){
            $result=EveModel::where($where)->update(['examine'=>6]);
            //添加操作日志
            $this->add_log($param['id'],'下架,每日三分钟:'.$courseInfo['title'],session('admin_id'));
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

    public function editEve()
    {
        $param=input('post.');



        if(empty($param['eve_id'])||empty(session('admin_id'))||empty(session('role_id'))){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        $courseInfo=EveModel::where('id',$param['eve_id'])->find();

        if(!$courseInfo){
            return json(['code'=>'006','message'=>'没有此课程!','data'=>array()]);
        }

        //插入后台发送者ID
        $param['ad_id']=session('admin_id');


        //修改课程情况
//        $edit_course=$param['edit_eve'];



        try{

            $param['create_at']=time();


            if(\db('eve_edit_log')->insert($param))
            {


                    $content='审核消息!每日三分钟:'.$courseInfo['title'].'.请求修改!请审核!';//如果回复内容为空,自定义回复

                    //rece_id=* 为超级管理员接收 ..发送站内信通知审核情况
                    if(!\db('self_mail')->insert(array('send_id'=>$param['ad_id'],'rece_id'=>'*','content'=>$content,'create_at'=>time()))){


                        return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);

                }


                //添加操作日志
                $this->add_log($param['eve_id'],'提交编辑,每日三分钟:'.$courseInfo['title'],session('admin_id'));
                return json(['code'=>'000','message'=>'成功','data'=>array()]);
            }

        }catch(\PDOException $e){

            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }

}
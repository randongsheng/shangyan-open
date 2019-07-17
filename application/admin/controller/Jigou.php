<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;
use app\admin\model\AdminJigouModel;
use app\admin\model\AdminModel;
use app\admin\model\ArticleModel;
use app\admin\model\CouponsModel;
use app\admin\model\CourseEditModel;
use app\admin\model\CourseListModel;
use app\admin\model\CourseModel;
use app\admin\model\EveEditModel;
use app\admin\model\EveModel;
use app\admin\model\NoticeModel;
use app\admin\model\SelfmailModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;

class Jigou extends Common
{


    /**
     * 机构列表
     */
    public function jigouList(Request $request)
    {



        $name= $request->post('name',null);
       $where=array();

       if(!empty($name)){
           $where['name']=array('like','%'.$name.'%');
       }

        $where['examine']=1;

         $where['role_id']=Env::get('jigou.jigou');


        $data= AdminModel::field('password',true)->where($where)->select();


              if($data){
                  return json(['code'=>'000','message'=>'成功','data'=>$data]);
              }

        return json(['code'=>'000','message'=>'空','data'=>array()]);

    }



    /**
     * 添加机构

     * ];
     */
    public function addJigou( ){
        $param=input('post.');



        if(empty($param['name'])||empty($param['tel'])||empty($param['business_license'])||empty($param['acc_number'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }


        //判断账号是否重复
        $where=array();
        $where['tel']=$param['tel'];


        if(AdminModel::where($where)->find()){

            return json(['code'=>'006','message'=>'账号已存在!','data'=>array()]);
        }


        try{
            $secret = rand(1000,9999);
            $password=md5('123456'.$secret);


            $info=array(//添加后台用户主表信息
                'name'=>$param['name'],
                'tel'=>$param['tel'],
                'role_id'=>Env::get('jigou.jigou'),
                'secret'=>$secret,
                'password'=>$password,
                'examine'=>2,
                'ad_id'=>session('admin_id'),
                'create_at'=>time()

                );
            $getId=AdminModel::insertGetId($info);
               $jigouName=$param['name'];//新增的名称
            unset($param['name'],$param['tel']);//去除多余的
            $param['relate_id']=$getId;

            AdminJigouModel::insert($param);//添加附表

            $content='新添机构:'.$jigouName.'请审核!';//如果回复内容为空,自定义回复

            //rece_id=* 为超级管理员接收 ..发送站内信通知审核情况
            if(!SelfmailModel::insert(array('send_id'=>session('admin_id'),'rece_id'=>'*','content'=>$content,'create_at'=>time()))){

                return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);
            }

            $this->add_log($getId,'添加机构待审核:'.$jigouName, session('admin_id'));
            return json(['code'=>'000','message'=>'成功','data'=>array()]);



        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }


    /*
     * 暂停开启机构
     */

    public function stopJigou(Request $request)
    {


        $admin_id= $request->post('admin_id',null);




        if(empty($admin_id)){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }




        //是否有机构用户
        $secret=AdminModel::where(['admin_id'=>$admin_id,'role_id'=>Env::get('jigou.jigou')])->find();
        if(!$secret){
            return json(['code'=>'006','message'=>'非法操作!','data'=>array()]);
        }



          if($secret['flag']==1){//暂停机构
              $this->add_log($admin_id,'机构暂停:'.$secret['name'], session('admin_id'));
              $result=AdminModel::where('admin_id',$admin_id)->update(['flag'=>2]);

          }
          else{
              $this->add_log($admin_id,'机构开启:'.$secret['name'], session('admin_id'));
              $result=AdminModel::where('admin_id',$admin_id)->update(['flag'=>1]);//开启机构
          }

        if($result){

            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'失败!','data'=>array()]);
        }


    }



    /*
     * 机构详情
     *
     */


    public function jigouInfo(Request $request)
    {

          $admin_id=$request->post('admin_id',null);
        if(empty($admin_id)){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }

        $data= AdminModel::field('password',true)->where('admin_id',$admin_id)->with('jigou')->find();

        if($data){

            return json(['code'=>'000','message'=>'成功!','data'=>$data]);
        }
        else{
            return json(['code'=>'006','message'=>'空!','data'=>array()]);
        }

    }


}
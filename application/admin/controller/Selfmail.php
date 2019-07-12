<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;
use app\admin\model\SelfmailModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;

class Selfmail extends Common
{
    public function index(){

    }


    //站内信列表
    public function mailList()
    {


          if(empty($_POST['id'])){
              return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
          }

           //查询条件
        $where=array();
        $where['flag']=1;
        $where['examine']=1;
        $where['admin_id']=($_POST['id']);


        $ad= db('admin')->field('name')->where($where)->find();


        if($ad){
            if(session('rule_shang')!=Env::get('rule_super.rule_shang')) {
                $data = SelfmailModel::where('rece_id', $_POST['id'])->order('create_at', 'desc')->select();
            }
            else{
                $data = SelfmailModel::where('rece_id', '*')->order('create_at', 'desc')->select();//超级管理员ID  为*保护
            }
                  if($data){
                      return json(['code'=>'000','message'=>'成功','data'=>$data]);
                  }else{
                      return json(['code'=>'000','message'=>'空','data'=>array()]);
                  }

        }
           else{
               return json(['code'=>'006','message'=>'错误的用户','data'=>array()]);
           }


    }


    /**
     *
  站内信详情
     * ];
     */
    public function mailInfo( ){

        if(empty($_POST['id'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        //查询条件
        $where=array();

        $where['id']=($_POST['id']);


        $data= SelfmailModel::where($where)->find();


        if($data){

            if($data['is_read']==2){
               if(!SelfmailModel::where($where)->update(array('is_read'=>1))){
                   return json(['code'=>'006','message'=>'更改已读状态失败!','data'=>array()]);
               }

            }
            return json(['code'=>'000','message'=>'成功!','data'=>$data]);
        }
        else{
            return json(['code'=>'006','message'=>'未找到!','data'=>array()]);
        }

    }











}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;

class Auth extends Common
{
    public function index(){

    }




    //所有节点列表
    public function nodeList()
    {


        //检查超级管理员总权限
        $this->chek_super();

       $data= db('node')->select();

        return json(['code'=>'000','message'=>'成功','data'=>$data]);
    }


    /**
     * 添加节点
     * $param = [
     *      'module_name'=>模块名称,
     *      'control_name'=>控制器名称,
     *      'action_name'=>方法名称,
     *      'is_menu'=>是否是菜单项 1不是 2是,
     *      'typeid'=>父级节点id,
     * ];
     */
    public function addNode( ){

        //检查超级管理员总权限
        $this->chek_super();

         $param=input('post.');



        if(empty($param['node_name'])||empty($param['module_name'])||empty($param['control_name'])||empty($param['action_name'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        try{
//            array_shift($param);

            \db('node')->insert($param);


            return json(['code'=>'000','message'=>'成功','data'=>array()]);


           // return msg(1, '', '添加节点成功');
        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }

    /**
     * 编辑节点
     */
    public function editNode()
    {

        //检查超级管理员总权限
        $this->chek_super();

        $param = input('post.');



        if(empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }
        try{

            $id=$param['id'];
                  //去除节点主键
            unset($param['id']);

            \db('node')->where('id',$id)->update($param);


            return json(['code'=>'000','message'=>'成功','data'=>array()]);



        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }


    }

    /**
     * 删除节点
     */
    public function delNode()
    {

        //检查超级管理员总权限
        $this->chek_super();

        $param = input('post.');



        if(empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }
        try{



            \db('node')->where('id',$param['id'])->delete();


            return json(['code'=>'000','message'=>'成功','data'=>array()]);



        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }

    }
  
    
    
    
    /**
     * 权限角色列表
     */

    public function roleList()
    {
        //检查超级管理员总权限
        $this->chek_super();


        $where['role_name']=array('neq','超级管理员');
        $data= db('role')->where($where)->select();

        return json(['code'=>'000','message'=>'成功','data'=>$data]);
        
    }
    

    /**
     * 权限角色添加
     */

    public function addRole( ){


        //检查超级管理员总权限
        $this->chek_super();

        $param=input('post.');



        if(empty($param['role_name'])||empty($param['rule'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        try{


            \db('role')->insert($param);


            return json(['code'=>'000','message'=>'成功','data'=>array()]);


            // return msg(1, '', '添加节点成功');
        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }


    /**
     * 权限角色修改
     */

    public function editRole( ){


        //检查超级管理员总权限
        $this->chek_super();

        $param = input('post.');



        if(empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }
        try{

            $id=$param['id'];
            //去除节点主键
            unset($param['id']);

            \db('role')->where('id',$id)->update($param);


            return json(['code'=>'000','message'=>'成功','data'=>array()]);



        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }

    }



    /**
     * 权限角色删除
     */

    public function delRole( ){


        //检查超级管理员总权限
        $this->chek_super();

        $param = input('post.');



        if(empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }
        try{



            \db('role')->where('id',$param['id'])->delete();


            return json(['code'=>'000','message'=>'成功','data'=>array()]);



        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }

    }



    private function chek_super()
    {
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')){

            return json(['code'=>'006','message'=>'口令出错！','data'=>array()]);
        }
    }



}
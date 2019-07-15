<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 14:16
 */
namespace app\admin\controller;
use app\admin\model\AdminModel;
use app\admin\model\ArticleModel;
use app\admin\model\CouponsModel;
use app\admin\model\CourseEditModel;
use app\admin\model\CourseListModel;
use app\admin\model\CourseModel;
use app\admin\model\EveEditModel;
use app\admin\model\EveModel;
use app\admin\model\NoticeModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Env;

class Staff extends Common
{
    public function index(){

    }


    //所有后台用户
    public function staffList()
    {



//        $key_word=$_POST['key_word'];

        //检查超级管理员总权限
        $this->chek_super();

        $where['name']=array('neq','超级管理员');
        $where['flag']=1;
        $where['examine']=1;

        //分组ID
        if(!empty($_POST['group_id'])){
            $where['group']=$_POST['group_id'];
        }

        $data= AdminModel::field('password',true)->where($where)->select();


        return json(['code'=>'000','message'=>'成功','data'=>$data]);
    }


    /**
     * 添加后台用户

     * ];
     */
    public function addStaff( ){
         $param=input('post.');

             //检查超级管理员总权限
          $this->chek_super();

        if(empty($param['name'])||empty($param['tel'])||empty($param['role_id'])){
            return json(['code'=>'002','message'=>'缺少参数','data'=>array()]);
        }

        //判断账号是否重复
          $where=array();
           $where['tel']=$param['tel'];
           if(!empty($param['email'])){
               $where['email']=$param['email'];
           }


        if(\db('admin')->whereOr($where)->find()){

            return json(['code'=>'006','message'=>'邮箱或者账号已存在!','data'=>array()]);
        }


        try{
            $param["secret"] = rand(1000,9999);
            $param['password']=md5('123456'.$param["secret"]);
            $param['examine']=1;//超级管理员添加的用户直接审核通过

            $param['create_at']=time();

            \db('admin')->insert($param);


            return json(['code'=>'000','message'=>'成功','data'=>array()]);



        }catch(\PDOException $e){
            return json(['code'=>'006','message'=>'失败','data'=>array()]);

        }
    }

    /**
     * 编辑用户
     */
    public function editStaff()
    {

        //检查超级管理员总权限
        $this->chek_super();

        $param=input('post.');




        if(empty($param['admin_id'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }
        //判断是否非法操作
        if(!empty($param['tel'])||!empty($param['email'])){
            return json(['code'=>'006','message'=>'非法操作!','data'=>array()]);
        }

        //是否有用户
         $secret= \db('admin')->where('admin_id',$param['admin_id'])->value('secret');
           if(!$secret){
               return json(['code'=>'006','message'=>'非法操作!','data'=>array()]);
           }

           $admin_id=$param['admin_id'];

           unset($param['admin_id']);

           //修改密码
           if(!empty($param['password'])){
               $param['password']=md5($param['password'].$secret);
           }


        if(\db('admin')->where('admin_id',$admin_id)->update($param)){

            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'并未作出修改!','data'=>array()]);
        }




    }

    /**
     * 删除后台用户
     */
    public function delStaff()
    {


        //检查超级管理员总权限
        $this->chek_super();

        $param=input('post.');




        if(empty($param['admin_id'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }

        $admin_id=$param['admin_id'];


        //是否有用户
        $secret= \db('admin')->where('admin_id',$admin_id)->value('secret');
        if(!$secret){
            return json(['code'=>'006','message'=>'非法操作!','data'=>array()]);
        }




        if(\db('admin')->where('admin_id',$admin_id)->update(['flag'=>2])){

            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'已经删除!','data'=>array()]);
        }

    }


    /*
     * 后台用户修改自己密码(信息)
     */
    public function editPw()
    {

        $param=input('post.');


         $save=array();

          if(empty($param['admin_id'])){
              return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
          }

        //判断是否非法操作
        if(!empty($param['tel'])||!empty($param['email'])||!empty($param['role_id'])||!empty($param['job_number'])||!empty($param['group'])){
            return json(['code'=>'006','message'=>'非法操作!','data'=>array()]);
        }

        //是否有用户
        $secret= \db('admin')->where('admin_id',$param['admin_id'])->find();
        if(!$secret){
            return json(['code'=>'006','message'=>'非法操作!','data'=>array()]);
        }

        //如果修改密码
        if(!empty($param['password'])&&!empty($param['password_new'])) {
            if (md5($param['password'] . $secret['secret']) != $secret['password']) {
                return json(['code'=>'006','message'=>'原密码错误!','data'=>array()]);
            }

            $save['password']=md5($param['password_new'] . $secret['secret']);

        }

        $admin_id=$param['admin_id'];


         if(!empty($param['name'])){
             $save['name']=$param['name'];
         }




        if(\db('admin')->where('admin_id',$admin_id)->update($save)){

            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'并未作出修改!','data'=>array()]);
        }




    }
    


    /**
     * 分组列表
     */

    public function groupList()
    {
        //检查超级管理员总权限
        $this->chek_super();

        $where['flag']=1;
        $data= db('group')->where($where)->select();


        return json(['code'=>'000','message'=>'成功','data'=>$data]);


    }

    /**
     * 增加分组
     */

    public function addGroup()
    {
        //检查超级管理员总权限
        $this->chek_super();

        $param=input('post.');

        if(empty($param['group_name'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }



        if(\db('group')->insert($param)){

            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'失败!','data'=>array()]);
        }



    }




    /**
     * 修改分组
     */

    public function editGroup()
    {
        //检查超级管理员总权限
        $this->chek_super();

        $param=input('post.');




        if(empty($param['group_name'])||empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }



        $id=$param['id'];

        unset($param['id']);


        if(\db('group')->where('id',$id)->update($param)){

            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'并未作出修改!','data'=>array()]);
        }


    }



    /**
     * 删除分组
     */

    public function delGroup()
    {
        //检查超级管理员总权限
        $this->chek_super();

        $param=input('post.');




        if(empty($param['id'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }



        if(\db('group')->where('id',$param['id'])->update(array('flag'=>2))){

            return json(['code'=>'000','message'=>'成功!','data'=>array()]);
        }
        else{
            return json(['code'=>'006','message'=>'删除失败!','data'=>array()]);
        }


    }


    //审核模块列表

    public function examineList()
    {

        $this->chek_super();


        //输入值
        $num=!empty($_POST['num'])?$_POST['num']:10;

        $where=array();

        //必要查询条件

        $where['flag']=1;
        $where['examine']=2;

        //文章待审核列表

        $articles=ArticleModel::field('content',true)->where($where)->limit($num)->order('create_at','desc')->select();
        $articleNumber=!empty($articles)?count($articles):0;

        //课程待审核列表

        $courses=CourseModel::where($where)->limit($num)->order('create_at','desc')->select();
        $courseNumber=!empty($courses)?count($courses):0;

        //课程改动待审核

        $courses_edit=CourseEditModel::where($where)->limit($num)->order('create_at','desc')->select();
        $courses_editNumber=!empty($courses_edit)?count($courses_edit):0;

        //每日三分钟课程

        $everyThree=EveModel::where($where)->limit($num)->order('create_at','desc')->select();
        $everyThreeNumber=!empty($everyThree)?count($everyThree):0;

        //每日三分钟改动待审核

        $everyThree_edit=EveEditModel::where($where)->limit($num)->order('create_at','desc')->select();
        $everyThree_editNumber=!empty($everyThree_edit)?count($everyThree_edit):0;

        //机构审核

        $jigou=AdminModel::where($where)->limit($num)->order('create_at','desc')->select();
        $jigouNumber=!empty($jigou)?count($jigou):0;

        //测试题审核

//        $ceshi=\db('admin')->where($where)->limit($num)->order('create_at','desc')->select();
//        $ceshiNumber=!empty($jigou)?count($jigou):0;


        //优惠券审核

        $coupons=CouponsModel::where($where)->limit($num)->order('create_at','desc')->select();
        $couponsNumber=!empty($coupons)?count($coupons):0;

        //公告审核

        $notice=NoticeModel::where($where)->limit($num)->order('create_at','desc')->select();
        $noticeNumber=!empty($notice)?count($notice):0;


           //各个审核数量
        $eveNumber=array('articleNum'=>$articleNumber,'courseNum'=>$courseNumber,'eveNumber'=>$everyThreeNumber,'jigouNumber'=>$jigouNumber,
            'couponsNumber'=>$couponsNumber,'noticeNumber'=>$noticeNumber,'course_editNumber'=>$courses_editNumber,'everyThree_editNumber'=>$everyThree_editNumber);

        //各个审核数据

        $eveData=array('article'=>$articles,'course'=>$courses,'eve'=>$everyThree,'jigou'=>$jigou,'coupons'=>$coupons,'notice'=>$notice,'course_edit'=>$courses_edit,'everyThree_edit'=>$everyThree_edit);

            return json(['code'=>'000','message'=>'成功!','data'=>array('num'=>$eveNumber,'data'=>$eveData)]);


    }



    /**
     *
     *审核
     */
    public function examineAny()
    {

        //超级管理员才有权限
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')){

            return json(['code'=>'006','message'=>'口令出错！','data'=>array()]);
        }


        $param=input('post.');



        if(empty($param['couponId'])&&empty($param['articleId'])&&empty($param['courseId'])&&empty($param['eveId'])
            &&empty($param['jigouId'])&&empty($param['noticeId'])&&empty($param['course_edit_id'])&&empty($param['eve_edit_id'])){
            return json(['code'=>'002','message'=>'缺少参数!','data'=>array()]);
        }


        if(empty($param['examine'])){
            return json(['code'=>'002','message'=>'缺少状态参数!','data'=>array()]);
        }
        $examine=$param['examine'];//审核状态 1通过  3驳回

        $content=!empty($param['content'])?$param['content']:'';//回复内容


        //首先处理公告
        if(!empty($param['noticeId'])){



           return json( $this->exNotice($param['noticeId'],$examine,$content));

        }

        //处理优惠券
        elseif(!empty($param['couponId'])){



            return json( $this->exCurr('coupons',$param['couponId'],$examine,$content,'couponName','优惠券'));//表名->ID->状态->审核内容->关键字段


        }

        //文章
        elseif(!empty($param['articleId'])){



            return json( $this->exCurr('articles',$param['articleId'],$examine,$content,'title','文章'));//表名->ID->状态->审核内容->关键字段


        }
        //课程
        elseif(!empty($param['courseId'])){



            return json( $this->exCurr('course',$param['courseId'],$examine,$content,'title','课程'));//表名->ID->状态->审核内容->关键字段


        }


        //课程改动
        elseif(!empty($param['course_edit_id'])){



            return json( $this->exCourseEdit($param['course_edit_id'],$examine,$content,'课程内容修改'));//ID->状态->审核内容->关键字段


        }


        //每日三分钟
        elseif(!empty($param['eveId'])){



            return json( $this->exCurr('eve',$param['eveId'],$examine,$content,'title','每日三分钟'));//表名->ID->状态->审核内容->关键字段


        }

        //每日三分钟改动审核
        elseif(!empty($param['eve_edit_id'])){



            return json( $this->exEveEdit($param['eve_edit_id'],$examine,$content,'每日三分钟内容修改'));//ID->状态->审核内容->关键字段


        }

        //机构
        elseif(!empty($param['jigouId'])){


            $noticeInfo=\db('admin')->where('admin_id',$param['jigouId'])->find();//查询的信息

                   if(!$noticeInfo){
                       return json(['code'=>'006','message'=>'数据出错!','data'=>array()]);
                   }

            $result= \db('admin')->where('admin_id',$param['jigouId'])->update(['examine'=>$examine]);

            if($result){
                if($examine==1){//审核成功
                    $content=!empty($content)?$content:'请查收!机构:'.$noticeInfo['name'].'.审核成功!';//如果回复内容为空,自定义回复
                }else{
                    $content=!empty($content)?$content:'请查收!机构:'.$noticeInfo['name'].'.审核被驳回!';//如果回复内容为空,自定义回复
                }

                //send_id=* 为超级管理员发送 ..发送站内信通知审核情况
                if(\db('self_mail')->insert(array('send_id'=>'*','rece_id'=>$noticeInfo['ad_id'],'content'=>$content,'create_at'=>time()))){


                    return json( ['code'=>'000','message'=>'审核成功!','data'=>array()]);
                }
                else{
                    return json( ['code'=>'006','message'=>'站内信发送失败!','data'=>array()]);
                }

            }
            else{
                return json( ['code'=>'006','message'=>'审核出错!','data'=>array()]);
            }





        }
        else{
            return json( ['code'=>'006','message'=>'未有此审核条目!','data'=>array()]);
        }


    }



  //检查超级管理员权限
    private function chek_super()
    {
        if(session('rule_shang')!=Env::get('rule_super.rule_shang')){

            return json(['code'=>'006','message'=>'口令出错！','data'=>array()]);
        }
    }


    //公告审核

    private function exNotice($nopo,$ex,$con){

        $noticeId=$nopo;
        $examine=$ex;
        $content=$con;

        $noticeInfo=\db('notice')->where('id',$noticeId)->find();//公告信息

        if(!$noticeInfo){
            return ['code'=>'006','message'=>'数据出错!','data'=>array()];
        }


            $result= \db('notice')->where('id',$noticeId)->update(['examine'=>$examine]);

            if($result){
                if($examine==1){//审核成功
                    $content=!empty($content)?$content:'请查收!公告:'.$noticeInfo['title'].'.审核成功!';//如果回复内容为空,自定义回复
                }else{
                    $content=!empty($content)?$content:'请查收!公告:'.$noticeInfo['title'].'.审核被驳回!';//如果回复内容为空,自定义回复
                }

                     //send_id=* 为超级管理员发送 ..发送站内信通知审核情况
                if(\db('self_mail')->insert(array('send_id'=>'*','rece_id'=>$noticeInfo['ad_id'],'content'=>$content,'create_at'=>time()))){


                    if($noticeInfo['type']==4&&$examine==1){//如果是面向后台公告,站内信形式 继续发送其他用户
                        $no_where['role_id']='8';//固定机构权限ID
                      $ad_list=\db('admin')->where($no_where)->select();
                      foreach ($ad_list as $k=>$v){
                         if( !\db('self_mail')->insert(array('send_id'=>'*','rece_id'=>$v['admin_id'],'content'=>$noticeInfo['content'],'create_at'=>time()))){
                             return ['code'=>'000','message'=>'发送公告失败!','data'=>array()];
                         }
                      }
                    }
                 return ['code'=>'000','message'=>'审核成功!','data'=>array()];
                }
                else{
                    return ['code'=>'006','message'=>'站内信发送失败!','data'=>array()];
                }

            }
            else{
                return ['code'=>'006','message'=>'审核出错!','data'=>array()];
            }

}






    //通用审核,优惠券,文章等

    private function exCurr($tab,$iid,$ex,$con,$pri,$ti){

         $table=$tab;//表名
        $id=$iid;//ID
        $examine=$ex;//审核状态
        $content=$con;//审核内容
        $pxi=$pri;//关键字段
        $tishi=$ti;//提示信息前缀

        $noticeInfo=\db($table)->where('id',$id)->find();//查询的信息
        if(!$noticeInfo){
            return ['code'=>'006','message'=>'数据出错!','data'=>array()];
        }



        $result= \db($table)->where('id',$id)->update(['examine'=>$examine]);

        if($result){
            if($examine==1){//审核成功
                $content=!empty($content)?$content:'请查收!'.$tishi.':'.$noticeInfo[$pxi].'.审核成功!';//如果回复内容为空,自定义回复
            }else{
                $content=!empty($content)?$content:'请查收!'.$tishi.':'.$noticeInfo[$pxi].'.审核被驳回!';//如果回复内容为空,自定义回复
            }

            //send_id=* 为超级管理员发送 ..发送站内信通知审核情况
            if(\db('self_mail')->insert(array('send_id'=>'*','rece_id'=>$noticeInfo['ad_id'],'content'=>$content,'create_at'=>time()))){


                return ['code'=>'000','message'=>'审核成功!','data'=>array()];
            }
            else{
                return ['code'=>'006','message'=>'站内信发送失败!','data'=>array()];
            }

        }
        else{
            return ['code'=>'006','message'=>'审核出错!','data'=>array()];
        }

    }






    //课程修改审核
    private function exCourseEdit($iid,$ex,$con,$ti){


        $id=$iid;//ID
        $examine=$ex;//审核状态
        $content=$con;//审核内容
        $tishi=$ti;//提示信息前缀

        $noticeInfo=CourseEditModel::where('id',$id)->find();//查询的信息

        if(!$noticeInfo){
            return ['code'=>'006','message'=>'数据出错!','data'=>array()];
        }

          //课程信息
          $couInfo=CourseModel::where('id',$noticeInfo['course_id'])->find();


        $result= CourseEditModel::where('id',$id)->update(['examine'=>$examine]);


        if($result){
            if($examine==1){//审核成功
                $content=!empty($content)?$content:'请查收!'.$tishi.':'.$couInfo['title'].'.审核成功!';//如果回复内容为空,自定义回复

                //去处理更新操作
                if($noticeInfo['edit_course']!=''){//课程更新
                    $upCour=json_decode($noticeInfo['edit_course'],true);
                    if(!CourseModel::where('id',$noticeInfo['course_id'])->update($upCour)){
                        return ['code'=>'006','message'=>'审核成功,课程修改失败!','data'=>array()];
                    }

                }

                if($noticeInfo['edit_course_list']!=''){//课时修改
                    $editList=json_decode($noticeInfo['edit_course_list'],true);

                    foreach ($editList as $k=>$v){
                        $mid=$v['id'];
                        unset($v['id']);//去除课时修改的主键ID
                        if(!CourseListModel::where('id',$mid)->update($v)){
                            return ['code'=>'006','message'=>'审核成功,课程修改失败!','data'=>array()];
                        }
                    }

                }
                if($noticeInfo['add_course_list']!=''){//课时增加
                    $addList=json_decode($noticeInfo['add_course_list'],true);

                    foreach ($addList as $ki=>$vi){
                             $vi['course_id']=$noticeInfo['course_id'];
                             $vi['create_at']=time();
                        if(!CourseListModel::insert($vi)){
                            return ['code'=>'006','message'=>'审核成功,课时增加失败!','data'=>array()];
                        }
                    }


                }

            }else{
                $content=!empty($content)?$content:'请查收!'.$tishi.':'.$couInfo['title'].'.审核被驳回!';//如果回复内容为空,自定义回复
            }

            //send_id=* 为超级管理员发送 ..发送站内信通知审核情况
            if(\db('self_mail')->insert(array('send_id'=>'*','rece_id'=>$noticeInfo['ad_id'],'content'=>$content,'create_at'=>time()))){


                return ['code'=>'000','message'=>'审核成功!','data'=>array()];
            }
            else{
                return ['code'=>'006','message'=>'站内信发送失败!','data'=>array()];
            }

        }
        else{
            return ['code'=>'006','message'=>'审核出错!','data'=>array()];
        }

    }




    //每日三分钟修改审核
    private function exEveEdit($iid,$ex,$con,$ti){


        $id=$iid;//ID
        $examine=$ex;//审核状态
        $content=$con;//审核内容
        $tishi=$ti;//提示信息前缀

        $noticeInfo=EveEditModel::where('id',$id)->find();//查询的信息

        if(!$noticeInfo){
            return ['code'=>'006','message'=>'数据出错!','data'=>array()];
        }

        //每日三分钟信息
        $couInfo=EveModel::where('id',$noticeInfo['eve_id'])->find();


        $result= EveEditModel::where('id',$id)->update(['examine'=>$examine]);


        if($result){
            if($examine==1){//审核成功
                $content=!empty($content)?$content:'请查收!'.$tishi.':'.$couInfo['title'].'.审核成功!';//如果回复内容为空,自定义回复

                //去处理更新操作
                if($noticeInfo['edit_eve']!=''){//课程更新
                    $upCour=json_decode($noticeInfo['edit_eve'],true);
                    if(!EveModel::where('id',$noticeInfo['eve_id'])->update($upCour)){
                        return ['code'=>'006','message'=>'审核成功,课程修改失败!','data'=>array()];
                    }

                }

            }else{
                $content=!empty($content)?$content:'请查收!'.$tishi.':'.$couInfo['title'].'.审核被驳回!';//如果回复内容为空,自定义回复
            }

            //send_id=* 为超级管理员发送 ..发送站内信通知审核情况
            if(\db('self_mail')->insert(array('send_id'=>'*','rece_id'=>$noticeInfo['ad_id'],'content'=>$content,'create_at'=>time()))){


                return ['code'=>'000','message'=>'审核成功!','data'=>array()];
            }
            else{
                return ['code'=>'006','message'=>'站内信发送失败!','data'=>array()];
            }

        }
        else{
            return ['code'=>'006','message'=>'审核出错!','data'=>array()];
        }

    }




}
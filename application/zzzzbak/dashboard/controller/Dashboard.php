<?php
namespace app\dashboard\controller;
use think\Db;
class Dashboard
{
    public function index()
    {

        return 'index';
    }
    //审核统计
    public function reviewed()
    {
    	$clinic = db('clinic')->where(['status'=>0])->count();
        $teacher = db('user')->where(['status'=>1])->count();
        $article = db('articles')->where(['status'=>0])->count();
        //$comment = 
        $data = ['clinic'=>$clinic,'teacher'=>$teacher,'article'=>$article];
        // print_r($article);
        sendJson(1,'审计统计',$data);
    }
    //用户图表
    public function user(){
        $hours = $this->getTime('hour');
        $newuser['hours'] = $this->getNewUser($hours);
        $liveuser['hours'] = $this->getLiveUser($hours);
        $day = $this->getTime('day');
        $newuser['day'] = $this->getNewUser($day);
        $liveuser['day'] = $this->getLiveUser($day);
        $week = $this->getTime('week');
        $newuser['week'] = $this->getNewUser($week);
        $liveuser['week'] = $this->getLiveUser($week);
        $month = $this->getTime('month');
        $newuser['month'] = $this->getNewUser($month);
        $liveuser['month'] = $this->getLiveUser($month);
        $data = ['newuser'=>$newuser,'liveuser'=>$liveuser];
        sendJson(1,'用户图表',$data);
    }
    //交易图表
    public function transaction(){
        $hours = $this->getTime('hour');
        $getMoney['hours'] = $this->getMoney($hours);
        $day = $this->getTime('day');
        $getMoney['day'] = $this->getMoney($day);
        $week = $this->getTime('week');
        $getMoney['week'] = $this->getMoney($week);
        $month = $this->getTime('month');
        $getMoney['month'] = $this->getMoney($month);
        print_r($getMoney);
        sendJson(1,'交易图表',$getMoney);
        //$liveuser['hours'] = $this->getLiveUser($hours);
    }
    //排行榜
    public function top(){
        $topTime = $this->getTopTime();
        //咨询中心排行榜
        $getClinicOrderMoney = $this->getClinicOrderMoney($topTime);
        //倾听师排行
        $getListenMoney = $this->getTeacherMoney($topTime,1);
        //咨询师排行
        $getZixunMoney = $this->getTeacherMoney($topTime,2);
        //文章排行榜
        $getArticlesClick = $this->getArticlesClick($topTime);
        //测试排行榜
        $getExamNum = $this->getExamNum($topTime);
        //话题排行榜
        $getTopicFollow = $this->getTopicFollow();
        // print_r($getTopicFollow);
        $data = ['clinic'=>$getClinicOrderMoney,'listen'=>$getListenMoney,'zixun'=>$getZixunMoney,'articles'=>$getArticlesClick,'exam'=>$getExamNum,'topic'=>$getTopicFollow];
        sendJson(1,'排行榜',$data);
        
    }
    //咨询中心下单排行榜
    public function getClinicOrderMoney($data){
        //天排行
        foreach ($data as $key => $value) {
            $where = [];
            $where['o.createtime'] = ['between',$value['stime'].','.$value['etime']];
            $where['o.status'] = 1;
            $top[$key] = Db::name('order o')->field(['clinic_name',"sum(ordermoney) money",'nature'])->join('clinic c','o.clinicid=c.id')->where($where)->order('money desc')->group('clinicid')->limit(10)->select();
            //echo Db::name('order o')->getLastSql();
        }
        //print_r($top);
        return $top;
    }
    //老师排行榜
    //$type 1为倾听师,2为咨询师
    public function getTeacherMoney($data,$type){
        foreach ($data as $key => $value) {
            $where = [];
            $where['o.createtime'] = ['between',$value['stime'].','.$value['etime']];
            $where['o.status'] = 1;
            $where['o.type'] = $type;//倾听订单
            $top[$key] = Db::name('order o')->field(['realname',"sum(ordermoney) money",'gender'])->join('userfield f','o.serverpersonid=f.uid')->join('user u','o.serverpersonid=u.id')->where($where)->order('money desc')->group('serverpersonid')->limit(10)->select();
            //echo Db::name('order o')->getLastSql();
        }
        //print_r($top);
        return $top;
    }
    //文章点击量排行榜
    public function getArticlesClick($data){
        foreach ($data as $key => $value) {
            $where = [];
            $where['add_time'] = ['between',$value['stime'].','.$value['etime']];
            $where['status'] = 1;
            $top[$key] = Db::name('articles')->field(['title',"clicknum"])->where($where)->order('clicknum desc')->limit(10)->select();
            //echo Db::name('order o')->getLastSql();
        }
        //print_r($top);
        return $top;
    }
    //测试排行榜
    public function getExamNum($data){
        foreach ($data as $key => $value) {
            $where = [];
            $where['r.examtime'] = ['between',$value['stime'].','.$value['etime']];
            $where['t.status'] = 1;
            $top[$key] = Db::name('exam_result r')->join('examtitle t','r.examtitle=t.id')->field(['title',"count(r.id) num"])->where($where)->group('r.examtitle')->order('num desc')->limit(10)->select();
            //echo Db::name('order o')->getLastSql();
        }
        //print_r($top);
        return $top;
    }
    //关注话题排行
    public function getTopicFollow(){
        $topic = Db::name('topic')->where('status=1')->select();
        $topicfollow = [];
        foreach ($topic as $key => $value) {
            $topicid = $value['id'];
            $title[$key] = $value['title'];
            $num[$key] = Db::name('user')->where('topic','like','%'.$topicid.'%')->count();
        }
        //不打乱键值排序
        arsort($num);
        foreach ($num as $key => $value) {
            $topicfollow[] = ['title'=>$title[$key],'num'=>$value];
        }
        return $topicfollow;
    }
    //获取排行榜时间
    public function getTopTime(){
        $topTime = [];
        $timestamp = time();
        $yesterday = strtotime(date('Y-m-d'))-86400;
        $beforeday = $yesterday - 86400;
        $topTime['day'] = ['stime'=>$beforeday,'etime'=>$yesterday];
        $sweek = strtotime(date('Y-m-d', strtotime("last week Monday", $timestamp)));
        $eweek = strtotime(date('Y-m-d', strtotime("last week Sunday", $timestamp))) + 24 * 3600 - 1;
        $topTime['week'] = ['stime'=>$sweek,'etime'=>$eweek];
        $smonth = mktime(0, 0, 0, date('m') - 1, 1, date('Y'));
        $emonth = mktime(23, 59, 59, date('m') - 1, date('t', $smonth), date('Y'));
        $topTime['month'] = ['stime'=>$smonth,'etime'=>$emonth];
        //print_r($topTime);
        return $topTime;
    }

    //时间段的订单金额
    public function getMoney($data){
        $transaction = [];
        foreach ($data as $key => $value) {
            $listenWhere = [];
            $zixunWhere = [];
            $examWhere = [];
            $listenWhere['createtime'] = ['between',$value['etime'].','.$value['stime']];
            $listenWhere['status'] = 1;
            $listenNum = Db::name('order')->where($listenWhere)->sum('ordermoney');
            $zixunWhere['createtime'] = ['between',$value['etime'].','.$value['stime']];
            $zixunWhere['status'] = 2;
            $zixunNum = Db::name('order')->where($zixunWhere)->sum('ordermoney');
            $examWhere['createtime'] = ['between',$value['etime'].','.$value['stime']];
            $examWhere['status'] = 1;
            $examNum = Db::name('examorder')->where($examWhere)->sum('money');
            $money[] = ['time'=>date('Y-m-d H:0:0',$value['etime']),'num'=>['listen'=>$listenNum,'zixun'=>$zixunNum,'exam'=>$zixunNum]];
        }
        return $money;
    }
    //计算起始时间
    public function getTime($type='day'){
        $data = [];
        $day = date('Y-m-d');
        $now = date('Y-m-d H:0:0');
        for ($i=1; $i < 8; $i++) { 
            switch ($type) {
                case 'hour':
                $stime =strtotime($now);
                $atime =date('Y-m-d H:0:0',strtotime(date('Y-m-d H:0:0',strtotime("-$i hours"))));
                $etime =strtotime($atime);
                break;
                case 'day':
                    $stime = strtotime($day);//ate('Y-m-d',strtotime($day));
                    $atime = date('Y-m-d',strtotime(date('Y-m-d',strtotime("-$i day"))));
                    $etime = strtotime($atime);
                break;
                case 'week':
                    $stime =strtotime($day);
                    $atime =date('Y-m-d',strtotime(date('Y-m-d',strtotime("-$i week"))));
                    $etime =strtotime($atime);
                break;
                case 'month':
                    $stime =strtotime($day);
                    $atime =date('Y-m',strtotime(date('Y-m',strtotime("-$i month"))));
                    $etime =strtotime($atime);
                break;
                default:
                    # code...
                break;
                }
                $day = $atime;
                $now = $atime;
                $time = ['stime'=>$stime,'etime'=>$etime];
                $data[] = $time;
            }
            return $data;
        }
    //获取时间段内的新增用户
        public function getNewUser($data){
            $newuser = [];
            foreach ($data as $key => $value) {
            //print_r($value);
                $num = db('user')->where('regtime','between',$value['etime'].','.$value['stime'])->count();
                $newuser[] = ['time'=>date('Y-m-d H:0:0',$value['etime']),'num'=>$num];
            }
            return $newuser;
        }
    //活跃用户
        public function getLiveUser($data){
            $liveuser = [];
            foreach ($data as $key => $value) {
                $num = db('user')->where('last_login_time','between',$value['etime'].','.$value['stime'])->count();
                $liveuser[] = ['time'=>date('Y-m-d',$value['stime']),'num'=>$num];
            }
            return $liveuser;
        }
    }

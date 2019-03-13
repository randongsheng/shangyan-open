<?php
namespace app\admin\controller;
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
    	$clinic = db('clinic')->where(['status'=>1])->count();
        $teacher = db('teacher')->where(['info_status'=>1])->count();
        $article = db('articles')->where(['status'=>0])->count();
        //$comment = 
        $data = ['clinic'=>$clinic,'teacher'=>$teacher,'article'=>$article];
        // print_r($article);
        sendJson(1,'审计统计',$data);
    }
    //用户图表
    public function user(){
        $time = input('post.time');//time 为 hour.day,week,month
        $type = input('post.type');//类型 newuser新用户,liveuser活跃用户
        $data = [];
        if ($type == 'newuser')
        {

            $timeArr = $this->getTime($time);
            $data = $this->getNewUser($timeArr,$time);
//            print_r($data);
        }
        if($type == 'liveuser')
        {
            $timeArr = $this->getTime($time);
            $data = $this->getLiveUser($timeArr,$time);
//            print_r($data);
        }

        sendJson(1,'用户图表',$data);
    }
    //交易图表
    public function transaction(){
        $time = input('post.time');//time 为 hour.day,week,month
        $timeArr = $this->getTime($time);
//        print_r($timeArr);
        $data = $this->getMoney($timeArr,$time);
        //print_r($data);
//        $day = $this->getTime('day');
//        $getMoney['day'] = $this->getMoney($day);
//        $week = $this->getTime('week');
//        $getMoney['week'] = $this->getMoney($week);
//        $month = $this->getTime('month');
//        $getMoney['month'] = $this->getMoney($month);
//        print_r($getMoney);
        sendJson(1,'交易图表',$data);
        //$liveuser['hours'] = $this->getLiveUser($hours);
    }
    //排行榜
    public function top(){
        $time = input('post.time');
        $type = input('post.type');
        $topTime = $this->getTopTime($time);
        $data = [];
//        print_r($topTime);
        switch ($type)
        {
            case 'clinic':
                $data = $this->getClinicOrderMoney($topTime);
                break;
            case 'listen':
                $data = $this->getTeacherMoney($topTime,1);
                break;
            case 'zixun':
                $data = $this->getTeacherMoney($topTime,2);
                break;
            case 'article':
                $data = $this->getArticlesClick($topTime);
                break;
            case 'exam':
                $data = $this->getExamNum($topTime);
                break;
            case 'topic':
                $data = $this->getTopicFollow($topTime);
                break;
        }

        sendJson(1,'排行榜',$data);
        
    }

    /**
     * 所有的top排行榜
     */
    public function allTop()
    {
        $topTime = $this->getTopTime('day');
        $data = [];



                $data['clinic'] = $this->getClinicOrderMoney($topTime);


                $data['listen'] = $this->getTeacherMoney($topTime,1);


                $data['zixun'] = $this->getTeacherMoney($topTime,2);


                $data['article'] = $this->getArticlesClick($topTime);


                $data['exam'] = $this->getExamNum($topTime);


                $data['topic'] = $this->getTopicFollow($topTime);


        sendJson(1,'排行榜',$data);
    }
    //咨询中心下单排行榜
    public function getClinicOrderMoney($data){
        //天排行

            $where = [];
            $where['o.paytime'] = ['between',$data['stime'].','.$data['etime']];
            $top = Db::name('order o')->field(['clinic_name',"sum(ordermoney) money",'nature'])->join('clinic c','o.clinicid=c.id','RIGHT')->where($where)->order('money desc')->group('clinicid')->limit(10)->select();
            //echo  Db::name('order')->getLastSql();
            //echo Db::name('order o')->getLastSql();

        //print_r($top);
        return $top;
    }
    //老师排行榜
    //$type 1为倾听师,2为咨询师
    public function getTeacherMoney($data,$type){
            $where = [];
            $where['o.paytime'] = ['between',$data['stime'].','.$data['etime']];
            $where['o.type'] = $type;//倾听订单
            $top = Db::name('order o')->field(['realname',"sum(ordermoney) money",'gender'])->join('userfield f','o.serverpersonid=f.uid')->join('user u','o.serverpersonid=u.id')->where($where)->order('money desc')->group('serverpersonid')->limit(10)->select();
            //echo Db::name('order o')->getLastSql();

        //print_r($top);
        return $top;
    }
    //文章点击量排行榜
    public function getArticlesClick($data){

            $where = [];
            $where['add_time'] = ['between',$data['stime'].','.$data['etime']];
            $where['status'] = 3;
            $where['clicknum'] = ['<>',0];
            $top = Db::name('articles')->field(['title',"clicknum"])->where($where)->order('clicknum desc')->limit(10)->select();
            //echo Db::name('order o')->getLastSql();

        //print_r($top);
        return $top;
    }
    //测试排行榜
    public function getExamNum($data){

            $where = [];
            $where['r.examtime'] = ['between',$data['stime'].','.$data['etime']];
            $where['t.status'] = 1;
            $top = Db::name('exam_result r')->join('examtitle t','r.examtitle=t.id')->field(['title',"count(r.id) num"])->where($where)->group('r.examtitle')->order('num desc')->limit(10)->select();
            //echo Db::name('order o')->getLastSql();

        //print_r($top);
        return $top;
    }
    //关注话题排行
    public function getTopicFollow($data){
        $topic = Db::name('topic')->where('status=1')->select();
        $topicfollow = [];
        foreach ($topic as $key => $value) {
            $topicid = $value['id'];
            $title[$key] = $value['title'];
            $where['topic'] = ['like','%'.$topicid.'%'];
            $where['follow_topic_time'] = ['between',$data['stime'].','.$data['etime']];
            $num[$key] = Db::name('user')->where($where)->count();
        }
        //不打乱键值排序
        arsort($num);
        foreach ($num as $key => $value) {
            $topicfollow[] = ['title'=>$title[$key],'num'=>$value];
        }
        return array_slice($topicfollow, 0, 10);
    }
    //获取排行榜时间
    //time //day/week/month
    public function getTopTime($time){
        $topTime = [];
        switch ($time)
        {
            case 'day':
                $stime = time() - 86400;
                break;
            case 'week':
                $stime = time() - 86400*7;
                break;
            case 'month':
                $stime = time() - 86400*30;
                break;
        }
        return ['stime'=>$stime,'etime'=>time()];
//        $timestamp = time();
//        $yesterday = strtotime(date('Y-m-d'))-86400;
//        $beforeday = $yesterday - 86400;
//        $topTime['day'] = ['stime'=>$beforeday,'etime'=>$yesterday];
//        $sweek = strtotime(date('Y-m-d', strtotime("last week Monday", $timestamp)));
//        $eweek = strtotime(date('Y-m-d', strtotime("last week Sunday", $timestamp))) + 24 * 3600 - 1;
//        $topTime['week'] = ['stime'=>$sweek,'etime'=>$eweek];
//        $smonth = mktime(0, 0, 0, date('m') - 1, 1, date('Y'));
//        $emonth = mktime(23, 59, 59, date('m') - 1, date('t', $smonth), date('Y'));
//        $topTime['month'] = ['stime'=>$smonth,'etime'=>$emonth];
//        //print_r($topTime);
//        return $topTime;
    }

    //时间段的订单金额
    public function getMoney($data,$time){
        $x = [];
        $y1 = [];//倾听交易
        $y2 = [];//咨询交易
        $y3 = [];//测试交易
        $y4 = [];//课程交易
        if($time == 'hour')
        {
            foreach ($data['today'] as $k => $v)
            {
            $x[] = $k.':00';//date('H:i',$v['stime']);
            $y1[] = Db::name('order')->where(['paytime'=>['between',$v['stime'].','.$v['etime']],'type'=>1])->sum('ordermoney');
            $y2[] = Db::name('order')->where(['paytime'=>['between',$v['stime'].','.$v['etime']],'type'=>2])->sum('ordermoney');
            $y3[] = Db::name('examorder')->where(['createtime'=>['between',$v['stime'].','.$v['etime']]])->sum('money');
            $y4[] = 0;
            }
        }else{
            foreach ($data as $key => $value) {
                $listenWhere = [];
                $zixunWhere = [];
                $examWhere = [];
                $x[] = date('Y-m-d',$value['stime']);
                $listenWhere['paytime'] = ['between',$value['stime'].','.$value['etime']];
                $listenWhere['type'] = 1;
                $y1[] = Db::name('order')->where($listenWhere)->sum('ordermoney');
                //echo Db::name('order')->getLastSql();
                $zixunWhere['paytime'] = ['between',$value['stime'].','.$value['etime']];
                $zixunWhere['type'] = 2;
                $y2[] = Db::name('order')->where($zixunWhere)->sum('ordermoney');
                $examWhere['createtime'] = ['between',$value['stime'].','.$value['etime']];
                $y3[] = Db::name('examorder')->where($examWhere)->sum('money');
                $y4[] = 0;
            }
        }

        return ['x'=>$x,'y1'=>$y1,'y2'=>$y2,'y3'=>$y3,'y4'=>$y4];
    }
    //计算起始时间
    public function getTime($type='day'){


            if($type == 'hour'){
                //获取今天和明天的时间数据
                $today = date('Y-m-d');

                $todayArr = [];
                $yesterday = date('Y-m-d',strtotime("-1 day"));

                $yesterdayArr = [];
                for ($j = 0 ; $j <= 24 ;$j++)
                {
//                    print_r(strtotime($today.' '.$j.'0:0'));
//                    die;
                    $todayArr[$j]['stime'] = strtotime($today.' '.$j.':0:0');
                    //print_r(strtotime($today.' '.$j.'0:0'));
                    $todayArr[$j]['etime'] = $todayArr[$j]['stime'] + 3600;
                    $yesterdayArr[$j]['stime'] = strtotime($yesterday.' '.$j.':0:0');
                    $yesterdayArr[$j]['etime'] = $yesterdayArr[$j]['stime'] + 3600;

                }
                    return ['yesterday'=>$yesterdayArr,'today'=>$todayArr];
                }else{
                    $data = [];
                    $day = date('Y-m-d');
    //        $now = date('Y-m-d H:0:0');
                    for ($i=8; $i > 0; $i--) {
                        switch ($type) {
                            case 'day':
                                $atime = date('Y-m-d',strtotime(date('Y-m-d',strtotime("-$i day"))));
                                $stime = strtotime($atime);
                                $etime = $stime + 3600*24;//ate('Y-m-d',strtotime($day));
                                break;
                            case 'week':
                                $atime =date('Y-m-d',strtotime(date('Y-m-d',strtotime("-$i week"))));
                                $stime =strtotime($atime);
                                $etime =$stime + 3600*24*7;


                                break;
                            case 'month':
                                $atime =date('Y-m',strtotime(date('Y-m',strtotime("-$i month"))));
                                $stime =strtotime($atime);
                                $up = $i - 1;
                                $etime =strtotime(date('Y-m',strtotime("-$up month")));


                                break;
                            default:
                                # code...
                                break;
                        }

    //                    $day = $atime;
    //                    $now = $atime;
                        $time = ['stime'=>$stime,'etime'=>$etime];
                        $data[] = $time;
                    }
                    return $data;
                }

        }
    //获取时间段内的新增用户
    //$time = hour,day,week,month 当$time == hour时$data为二维数组
        public function getNewUser($data,$time){
            $y = [];
            $x = [];
            if ($time == 'hour')
            {
                foreach ($data['yesterday'] as $k=>$v)
                {
                    $x[] = $k.':00';//date('H:i',$v['stime']);
                    $y['yesterday'][] = db('user')->where('regtime','between',$v['stime'].','.$v['etime'])->count();
                    //echo db('user')->getLastSql();

                }
                foreach ($data['today'] as $k=>$v)
                {
                    $y['today'][] = db('user')->where('regtime','between',$v['stime'].','.$v['etime'])->count();
                    //echo db('user')->getLastSql();

                }
                return ['x'=>$x,'y'=>$y];
            }else{
                foreach ($data as $key => $value) {
                    //print_r($value);
                    $y[] = db('user')->where('regtime','between',$value['stime'].','.$value['etime'])->count();
                    //echo db('user')->getLastSql();
                    $x[] = date('Y-m-d',$value['stime']);
                }
                return ['x'=>$x,'y'=>$y];
            }

        }
    //活跃用户
        public function getLiveUser($data,$time){
            $liveuser = [];
            $y = [];
            $x = [];
            if ($time == 'hour')
            {
                foreach ($data['yesterday'] as $k=>$v)
                {
                    $x[] = $k.':00';//date('H:i',$v['stime']);
                    $y['yesterday'][] = db('user')->where('last_login_time','between',$v['stime'].','.$v['etime'])->count();
                    //echo db('user')->getLastSql();

                }
                foreach ($data['today'] as $k=>$v)
                {
                    $y['today'][] = db('user')->where('last_login_time','between',$v['stime'].','.$v['etime'])->count();
                    //echo db('user')->getLastSql();

                }
                return ['x'=>$x,'y'=>$y];
            }else{
                foreach ($data as $key => $value) {
                    $y[] = db('user')->where('last_login_time','between',$value['stime'].','.$value['etime'])->count();
                    $x[] = date('Y-m-d',$value['stime']);
                }
                return ['x'=>$x,'y'=>$y];
            }

        }
        //获取所有话题
        public function getTopic(){
            $topics = \db('topic')->field('id,title')->where(['status'=>1])->select();
            sendJson(1,'所有话题',$topics);
        }
    }

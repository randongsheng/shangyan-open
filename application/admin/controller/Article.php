<?php
namespace app\admin\controller;
use app\admin\model\ArticleModel;
use think\Db;
use app\admin\controller;
class Article extends Common
{
	public function articleList(){
		$data = input('post.');
		$where = [];
    	// $where['type'] = 1;
    	// $where['o.status'] = ['NEQ',3];
    	if (isset($data['id'])&&!empty($data['id'])) {
    		//文章id
    		# code...
    		$where['id'] = $data['id'];
    	}elseif (isset($data['update_at'])&&!empty($data['update_at'])) {
    		//更新时间
    		# code...
    		$stime = strtotime($data['update_at']);
    		$etime = $stime+86399;
    		$where['update_at'] = ['between',[$stime,$etime]];
    	}elseif (isset($data['status'])&&!empty($data['status'])) {
    		//订单状态
    		# code...
    		$where['status'] = $data['status'];
    	}elseif (isset($data['author_name'])&&!empty($data['author_name'])) {
    		//作者姓名
    		# code...
    		$author = $data['author_name'];
    		$where['author_name'] = ['like',['%'.$author,$author.'%','%'.$author.'%'],'OR'];;
    	}elseif (isset($data['title'])&&!empty($data['title'])) {
    		//文章标题
    		# code...
    		$title = $data['title'];
    		$where['title'] = ['like',['%'.$title,$title.'%','%'.$title.'%'],'OR'];
    	}elseif (isset($data['clinic_name'])&&!empty($data['clinic_name'])) {
    		//机构名称
    		# code...
    		$clinic_name = $data['clinic_name'];
    		$where['clinic_name'] = ['like',['%'.$clinic_name,$clinic_name.'%','%'.$clinic_name.'%'],'OR'];
    	}elseif (isset($data['keywords'])&&!empty($data['keywords'])) {
    		# code...
    		# 标签查询
    		$keywords = $data['keywords'];
//    		$_where1['keywords'] = ['like',['%,'.$keywords.',%']];
//    		$topicids = db('topic')->where($_where1)->column('id');
//
//    		$arr = [];
//    		foreach ($topicids as $topicid) {
//    			# code...
//    			$arr[] = '%,'.$topicid.',%';
//    		}
    		$where['keywords'] = ['like',"%,".$keywords.",%",'OR'];
    	}
    	//$where['status'] = ['<>',7];
        $neq = 'status <> 7';
    	$count = db('articles a')->field('id,title,description,author_name,clinic_name,update_at,keywords,status')->where($where)->where($neq)->count();
    	$pageSize = 10;
    	$totalpages = ceil($count/$pageSize);
    	$page = ceil(input('post.page',1));
    	$page = $page<=0?1:$page;
    	//print_r($totalpages);
    	$data['page'] = ['pagesize'=>$pageSize,'page'=>$page,'count'=>$count];
    	$list = db('articles a')->field('id,title,description,author_name,clinic_name,update_at,keywords,status')->page($page,$pageSize)->where($where)->where($neq)->order('id desc')->select();
//        echo db('articles a')->getLastSql();
    	foreach ($list as $k => $v) {
	            $list[$k]['topic'] = $this->getTopicStr($v['keywords']);
    	}
    	
    	$data['list'] = $list;
    	
    	sendJson(1,'文章列表',$data);
	}
	//添加文章
		//添加文章
    /*
    	title标题,description描述,thumbnail标题图片,content内容,add_time
     */
    public function addArticle(){

    	$saveType = input('post.savetype');//保存类型,1草稿,2待审核 
    	$title = input('post.title');
    	$content = input('post.content');
    	$description = input('post.description');
    	if (!$description) {
    	    sendJson(-1,'描述必须填写');
//    		//把接收到的的HTML实体转换为字符
//            $html_string = htmlspecialchars_decode($content);
//            //将空格替换成空
//            $str = str_replace(" ","",$html_string);
//            //函数剥去字符串中的HTML、XML以及PHP的标签,获取纯文本内容
//            $contents = strip_tags($str);
//            //返回字符串中的前60字符串长度的字符
//            $description = mb_substr($contents,0,32,"utf-8");
    	}
    	if(!$title||!$content){
    		sendJson(-1,'标题和内容不能为空');
    	}
    	$keywords = ','.input('post.keywords').',';
    	$author_name = input('post.author_name','尚小言');
    	$author = input('post.author',0);
    	$thumbnail = input('post.thumbnail');
        $picurl = input('post.picurl');
    	$data = [
    		'title'=>$title,
    		'content'=>$content,
    		'description'=>$description,
    		'add_time'=>time(),
    		'author_name'=>$author_name,
    		'author'=>$author,
    		'clinic_name'=>'尚言心理',
    		'keywords'=>$keywords,
    		'thumbnail'=>$thumbnail,
            'picurl'=>$picurl,
    		'status'=>$saveType
    	];        
    	$res = db('articles')->insert($data);
    	if($res){
    		sendJson(1,'添加成功');
    	}else{
    		sendJson(-1,'添加失败');
    	}
    }
    //获取文章内容
    public function getArticle(){
    	$id = input('post.id');
    	$articleObj = new ArticleModel();
    	$article = $articleObj->where(['id'=>$id])->find();
    	$topic = trim($article['keywords'],',');
        $topic = explode(',',$topic);
        foreach ($topic as $key => $value) {
            $topic[$key] = (int)$value;
        }
        $article['topic'] = $topic;
//        print_r($article);
//        var_dump($article);
//        die;
        $article['keywords'] = $this->getTopicStr($article['keywords']);
    	sendJson(1,'获取文章内容',$article);
    }
    //修改文章
    public function editArticle(){
    	$id = input('post.id');
    	$saveType = input('post.savetype');//保存类型,1草稿,2待审核 
    	$title = input('post.title');
    	$content = input('post.content');
    	$description = input('post.description');
    	if (!$description) {
    	    sendJson(-1,'描述必须填写');
//    		//把接收到的的HTML实体转换为字符
//            $html_string = htmlspecialchars_decode($content);
//            //将空格替换成空
//            $str = str_replace(" ","",$html_string);
//            //函数剥去字符串中的HTML、XML以及PHP的标签,获取纯文本内容
//            $contents = strip_tags($str);
//            //返回字符串中的前60字符串长度的字符
//            $description = mb_substr($contents,0,32,"utf-8");
    	}
    	if(!$title||!$content){
    		sendJson(-1,'标题和内容不能为空');
    	}
    	$keywords = ','.input('post.keywords').',';
    	$author_name = input('post.author_name','尚小言');
    	$author = input('post.author',0);
    	$thumbnail = input('post.thumbnail');
        $picurl = input('post.picurl');
    	$data = [
    		'title'=>$title,
    		'content'=>$content,
    		'description'=>$description,
    		'update_at'=>time(),
    		'author_name'=>$author_name,
    		'author'=>$author,
    		'clinic_name'=>'尚言心理',
    		'keywords'=>$keywords,
    		'thumbnail'=>$thumbnail,
            'picurl'=>$picurl,
    		'status'=>$saveType
    	];        
    	$res = db('articles')->where(['id'=>$id])->update($data);
    	if($res){
    		sendJson(1,'修改成功');
    	}else{
    		sendJson(-1,'修改失败');
    	}
    }
    //修改状态
    public function updateStatus(){
//    	$id = input('post.id');
//    	$status = input('post.status');
//    	$res = db('articles')->where(['id'=>$id])->update(['status'=>$status]);
//    	if($res){
//    		sendJson(1,'修改成功');
//    	}else{
//    		sendJson(-1,'修改失败');
//    	}
    }
    //文章评论
    //获取评论内容
    public function getComment(){
        //获取文章id
        $id = input('id');
        $page = input('post.page');
        $pageSize = 5;
        $limit = $pageSize*$page;
        // $a = $this->getserverperson(9);
        // print_r($a);
        // die;
        $data = [];
        $count = db('comment')->where(['articleid'=>$id,'replyid'=>0])->order('id desc')->count();
        $pageSize = 10;
        $totalpages = ceil($count/$pageSize);
        $page = ceil(input('post.page',1));
        $page = $page<=0?1:$page;
        $list = db('comment')->where(['articleid'=>$id,'replyid'=>0])->order('id desc')->page($page,$pageSize)->select();
        foreach ($list as $key => $value) {
            $clinicid = db('userfield')->where(['uid'=>$value['uid']])->find()['clinicid'];
            $list[$key]['avatarurl'] = db('user')->where(['id'=>$value['uid']])->find()['avatarurl'];
            $list[$key]['clinicname'] = db('clinic')->where(['id'=>$clinicid])->find()['clinic_name'];
            $list[$key]['son'] = db('comment')->where(['replyid'=>$value['id']])->order('id desc')->select();
            $sonmum = count($list[$key]['son']);
            $list[$key]['sonnum'] = $sonmum;
        }

        $data['page'] = ['pagesize'=>$pageSize,'page'=>$page,'count'=>$count];
        $data['list'] = $list;
        sendJson(1,'成功',$data);
    }
    //删除评论
    public function delComment(){
    	$id = input('post.id');
    	db('comment')->where(['id'=>$id])->delete();
    	sendJson(1,'删除成功');
    }
    //删除文章
    public function delArticle(){
        $id = input('post.id');
        db('articles')->where(['id'=>$id])->update(['status'=>7,'update_at'=>time()]);
        sendJson(1,'删除文章成功');
    }
    //文章上架
    public function upArticle(){
        $id = input('post.id');
        $status = db('articles')->where(['id'=>$id])->value('status');
        if ($status != 2 &&$status != 4){
            sendJson(-1,'文章状态不为待上架或下架');
        }
        db('articles')->where(['id'=>$id])->update(['status'=>3,'update_at'=>time()]);
        sendJson(1,'文章上架');
    }
    //文章下架
    public function downArticle(){
        $id = input('post.id');
        $status = db('articles')->where(['id'=>$id])->value('status');
        if ($status != 3){
            sendJson(-1,'文章状态不为上架');
        }
        db('articles')->where(['id'=>$id])->update(['status'=>4]);
        sendJson(1,'文章下架');
    }
}
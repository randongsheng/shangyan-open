<?php
namespace app\index\controller;
use think\Request;
use app\index\model\Article as ArticleModel;
use think\Session;
use think\Validate;
use think\Db;

class Article extends Base
{
	/**
	 * 文章列表
	 */
	public function articles()
	{
		$request = Request::instance();
		$article = new ArticleModel;
		$articleWhere = [];
		$post = $request->only(['teacher_id','order_id','label_id','update_time','title','author_name','clinic_name']);
		if(!empty($post['teacher_id'])){

		}

		if(!empty($post['order_id'])){

		}

		if(!empty($post['label_id'])){
			$articleWhere['keywords'] = ['like','%,'.$post['label_id'].',%'];
		}

		if(!empty($post['title'])){
			$articleWhere['title'] = ['like','%'.$post['title'].'%'];
		}

		if(!empty($post['author_name'])){
			$articleWhere['author_name'] = ['like','%'.$post['author_name'].'%'];
		}

		if(!empty($post['clinic_name'])){
			$articleWhere['clinic_name'] = ['like','%'.$post['clinic_name'].'%'];
		}

		if(!empty($post['update_time'])){
			$startTime = strtotime(date('Y-m-d 00:00:00',strtotime($post['update_time'])));
			$endTime = strtotime(date('Y-m-d 23:59:59',strtotime($post['update_time'])));
			$articleWhere['update_at'] = ['bewteen',[$startTime,$endTime]];
		}
		
		$articles = $article
		->where($articleWhere)
		->field(['author','author_name','clinic_name','title','id','description','add_time','update_at','keywords'])
		->order('add_time','desc')
		->paginate(15);

		return json(['success'=>true,'code'=>'000','message'=>'查询完成','data'=>$articles]);
	}

	/**
	 * 测试话题排序
	 */
	public function test()
	{
        // 文章阅读数
        $readnum = Db::name('articles')->alias('a')->where('a.keywords like concat("%,",t.id,",%")')->field('sum(a.clicknum)')->buildSql();
        // 文章评论数
        $commentnum = Db::name('comment')->alias('ac')
        ->where('a.id=ac.articleid')
        ->field('count(*)')
        ->buildSql();
        // 文章收藏数
        $collentiomnum = Db::name('collentiom')->alias('cl')->where('a.id=cl.articleid')->field('count(*)')->buildSql();
        // 文章点赞数
        $fabulousnum = Db::name('fabulous')->alias('af')->where('af.id=a.id')->where(['type'=>1])->field('count(*)')->buildSql();
        // 测试次数
        $examtimes = Db::name('exam_result')->alias('er')->where('er.examtitle=e.id')->field('count(*)')->buildSql();
        // 倾听订单数
        $listennum = Db::name('order')->alias('o')
        ->where('o.topic like concat("%,",t.id,",%")')
        ->where(['type'=>1,'status'=>['<>',3]])
        ->field('count(*)')
        ->buildSql();
        // 咨询订单数
        $consultationnum = Db::name('order')->alias('o')
        ->where('o.topic like concat("%,",t.id,",%")')
        ->where(['type'=>2,'status'=>['<>',3]])
        ->field('count(*)')
        ->buildSql();
        // 标签结果
        $topics = Db::name('topic')->alias('t')
        ->join('sy_articles a','a.keywords like concat("%,",t.id,",%")','LEFT')
        ->join('sy_examtitle e','e.topic like concat("%,",t.id,",%")','LEFT')
        ->where(['t.status'=>1])
        ->field(['('.$readnum.'*0.5+'.$commentnum.'*10+'.$collentiomnum.'*10+'.$fabulousnum.'*5+'.$examtimes.'*0.5+'.$listennum.'+'.$consultationnum.'*10) as article_sort'])
        ->select();
        return json($topics);
	}

	/**
	 * 文章排序
	 */
	public function articleTest()
	{
		$topics = $this->test();
		$article = new ArticleModel;
		$articles = $artcile->field(['*','null article_sort'])->select();
		return json($articles);
	}
}
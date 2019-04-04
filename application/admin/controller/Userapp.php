<?php
namespace app\admin\controller;
//use think\Controller;
use app\admin\model\BannerModel;
use think\Db;
use app\admin\controller;
class Userapp extends Common
{
    public function index()
    {

        return 'index';
    }
    /*
    *启动页
    * 
    * 
    * 
    * 
     */
    //app启动页
    public function bootPage(){
        $banner = new BannerModel();
    	$list = $banner->where(['position'=>3])->order('sort asc')->select();
    	sendJson(1,'启动页列表',$list);
    }
    //添加启动页
    public function addBootPage(){
    	$count = Db::name('banner')->where(['position'=>3])->count();
    	if($count>4){
    		sendJson(-1,'启动页不能超过5个');
    	}
    	$data = input('post.');
    	$res = $this->addBanner($data,3);
    	if ($res) {
    		sendJson(1,'添加启动页成功');
    	}else{
    		sendJson(-1,'添加启动页失败');
    	}
    	
    }
    //获得启动页
    public function getBootPage(){
    	$id = input('post.id');
    	$banner = new BannerModel();
    	$bootPage = $banner->where(['id'=>$id])->find();
    	if ($bootPage) {
    		sendJson(1,'获取成功',$bootPage);
    	}else{
    		sendJson(-1,'没有获取到');
    	}

    }
    //修改启动页
    public function updateBootPage(){
    	$data = input('post.');
    	$res = $this->updateBanner($data);
		if ($res) {
    		sendJson(1,'修改成功');
    	}else{
    		sendJson(-1,'修改失败');
    	}

    }
    /*
    *banner管理
    * 
    * index
     */
    //获取首页banner列表
    public function getIndeBanner()
    {
    	$this->getBanner(1);
    }
    //添加首页banner
    public function addIndexBanner(){
    	$count = Db::name('banner')->where(['position'=>1])->count();
    	if($count>9){
    		sendJson(-1,'banner不能超过10个');
    	}
    	$data = input('post.');
    	$res = $this->addBanner($data,1);
    	if ($res) {
    		sendJson(1,'添加首页banner成功');
    	}else{
    		sendJson(-1,'添加首页banner失败');
    	}
    }
    //保存修改
    public function saveBanner(){
    	$data = input('post.');
    	$id = input('post.id');
    	if (!$id) {
    		sendJson(-1,'没有获取到id');
    	}
    	$res = Db::name('banner')->where(['id'=>$id])->update($data);
    	//print_r($res);
    	if ($res) {
    		sendJson(1,'修改成功');
    	}
    }
    //获取心理测试首页banner
    public function getExamBanner(){
    	$this->getBanner(2);
    }
    //添加心理测试首页banner
    public function addExamBanner(){
    	$count = Db::name('banner')->where(['position'=>2])->count();
    	if($count>9){
    		sendJson(-1,'banner不能超过10个');
    	}
    	$data = input('post.');
    	$res = $this->addBanner($data,2);
    	if ($res) {
    		sendJson(1,'添加心理测试首页banner成功');
    	}else{
    		sendJson(-1,'添加心理测试首页banner失败');
    	}
    }
    //排序向上移动
    public function setUp(){
    	$id = input('post.id');//banner的id
    	$banner = Db::name('banner')->where(['id'=>$id])->find();
    	$sort = $banner['sort'];
    	//获取该banner上一位的id
    	$where['position'] = $banner['position'];
    	$where['sort'] = ['<',$sort];
    	$upbanner = Db::name('banner')->where($where)->order('sort desc')->find();
    	//如果排序前面有banner则交换
    	if ($upbanner) {
    		$upid = $upbanner['id'];
	    	$upsort = $upbanner['sort'];
	    	//两条banner进行sort交换
	    	Db::name('banner')->where(['id'=>$id])->update(['sort'=>$upsort]);
	    	Db::name('banner')->where(['id'=>$upid])->update(['sort'=>$sort]);
    	}
    	if ($upbanner){
    	    sendJson(1,'向上成功');
        }else{
            sendJson(-1,'已经最高了');
        }
    	//print_r($upbanner);
    }
    //排序向下移动
    public function setDown(){
    	$id = input('post.id');//banner的id
    	$banner = Db::name('banner')->where(['id'=>$id])->find();
    	$sort = $banner['sort'];
    	//获取该banner上一位的id
    	$where['position'] = $banner['position'];
    	$where['sort'] = ['>',$sort];
    	$downbanner = Db::name('banner')->where($where)->order('sort asc')->find();
    	//如果排序前面有banner则交换
    	if ($downbanner) {
    		$upid = $downbanner['id'];
	    	$upsort = $downbanner['sort'];
	    	//两条banner进行sort交换
	    	Db::name('banner')->where(['id'=>$id])->update(['sort'=>$upsort]);
	    	Db::name('banner')->where(['id'=>$upid])->update(['sort'=>$sort]);
    	}
        if ($downbanner){
            sendJson(1,'向下成功');
        }else{
            sendJson(-1,'已经最低了');
        }
    	//print_r($downbanner);
    }
    //获取所有banner
    public function getBanner($position){
        $banner = new BannerModel();
    	$list = $banner->where(['position'=>$position])->order('sort asc')->select();
    	sendJson(1,'banner列表',$list);
    }

    //添加所有banner
    //$data 插入数据
    //$position 位置,位置,1,用户端首页banner,2测试频道banner,3app启动页
    public function addBanner($data,$position){
        //实例化User验证器类
        $validate = new \app\admin\validate\Banner; //validate('User');
        //数据验证
        $checkData = $validate->check($data);
        //验证通过$checkData =1,不通过用getErro()获取错误
        if (!$checkData){
            sendJson(-1,$validate->getError()) ;
        }
//    	if (!isset($data['picurl'])||!isset($data['linkurl'])||!isset($data['type'])||!isset($data['objid'])) {
//    		sendJson(-1,'数据错误,请确认');
//    	}
    	if (isset($data['sort'])) {
    		$sort = $data['sort'];
    		//如果有sort有空位则不用后续加1
    		$have = Db::name('banner')->where(['sort'=>$sort,'position'=>$position])->find();
    		if($have){
    			$where['sort'] = ['>=',$sort];
	    		$where['position'] = $position;
	    		$down = Db::name('banner')->where($where)->order('sort asc')->column('id');
	    		foreach ($down as $value) {
	    			Db::name('banner')->where('id',$value)->setInc('sort');
	    			//echo Db::name('banner')->getLastSql();
    			}
    		}
    	}
    	$data['update_time'] = time();
    	$data['position'] = $position;
    	$res = Db::name('banner')->insert($data);
    	return $res?true:false;
    }
    //修改所有banner
    public function updateBanner($data){
    	if (!isset($data['picurl'])||!isset($data['linkurl'])||!isset($data['type'])||!isset($data['objid'])||!isset($data['id'])) {
    		sendJson(-1,'数据错误,请确认');
    	}
    	$data['update_time'] = time();
    	$res = Db::name('banner')->where(['id'=>$data['id']])->update($data);
    	return $res?true:false;
    }

    /*
    *内容推荐管理
    *
    * 
     */
   	//获取推荐商户
   	public function getPushClinic(){
   		$list = Db::name('clinic')->field('id,clinic_name,update_at,sort')->where(['push'=>1])->order('sort desc')->select();
   		sendJson(1,'商户列表',$list);
   	}
   	//获取推荐老师
   	public function getPushTeacher(){
   		$list = Db::name('user u')->field('u.id,realname,gender,sort,push_date,c.clinic_name')->join('userfield f','u.id=f.uid')->join("clinic c","c.id = f.clinicid")->where(['push'=>1])->order('sort desc')->select();
   		sendJson(1,'老师列表',$list);
   	}
   	//获取推荐测试
   	public function getPushExam(){
   		$list = Db::name('examtitle')->field('id,title,topic,sort,update_at')->where(['push'=>1])->order('sort desc')->select();
   		foreach ($list as $key => $value) {
   			$topic = trim($value['topic'],',');
   			$topicarr = [];
   			$topic = explode(",", $topic);
   			foreach ($topic as $v) {
   				$topicarr[] = Db::name('topic')->where(['id'=>$v])->value('title');
   			}
   			$list[$key]['topic'] = implode('#',$topicarr);
   		}
   		sendJson(1,'测试列表',$list);
   	}
   	//获取推荐文章
   	public function getPushArticles(){
   		$list = Db::name('articles')->field('id,title,author,keywords,sort,update_at')->where(['push'=>1])->order('sort desc')->select();
   		foreach ($list as $key => $value) {
   			$topic = trim($value['keywords'],',');
   			$topicarr = [];
   			$topic = explode(",", $topic);
   			foreach ($topic as $v) {
   				$topicarr[] = Db::name('topic')->where(['id'=>$v])->value('title');
   			}
   			$list[$key]['topic'] = implode('#',$topicarr);
   			$list[$key]['author'] = Db::name('userfield')->where(['uid'=>$value['author']])->value('realname');
   		}
   		sendJson(1,'文章列表',$list);
   	}
   	//推荐话题
   	public function getPushHotWord(){
   		$list = Db::name('hotword')->field('id,hotword,update_at,sort')->where(['push'=>1])->order('sort desc')->select();
   		sendJson(1,'话题列表列表',$list);
   	}
   	//设置推荐排序
   	public function setSort(){
   		$id = input('post.id');
   		$type = input('post.type');
   		$sort = input('post.sort');
   		switch ($type) {
   			case 'clinic':
   				Db::name('clinic')->where(['id'=>$id])->update(['sort'=>$sort]);
   				break;
   			case 'teacher':
   				Db::name('user')->where(['id'=>$id])->update(['sort'=>$sort]);
   				break;
   			case 'exam':
   				Db::name('examtitle')->where(['id'=>$id])->update(['sort'=>$sort,'update_at'=>time()]);
   				break;
   			case 'articles':
   				Db::name('articles')->where(['id'=>$id])->update(['sort'=>$sort,'update_at'=>time()]);
   				break;
   			case 'hotword':
   				Db::name('hotword')->where(['id'=>$id])->update(['sort'=>$sort,'update_at'=>time()]);
   				break;
   			default:
   				sendJson(-1,'type错误');
   				break;
   		}
   		sendJson(1,'更新排序');
   	}
   	//添加推荐
   	public function addPush(){
   		$id = input('post.id');
   		$type = input('post.type');
   		$sort = input('post.sort',0);
   		switch ($type) {
   			case 'clinic':
   				Db::name('clinic')->where(['id'=>$id])->update(['sort'=>$sort,'push'=>1]);
   				break;
   			case 'teacher':
   				Db::name('user')->where(['id'=>$id])->update(['sort'=>$sort,'push'=>1,'push_date'=>date("Y-m-d H:i:s")]);
   				break;
   			case 'exam':
   				Db::name('examtitle')->where(['id'=>$id])->update(['sort'=>$sort,'update_at'=>time(),'push'=>1]);
   				break;
   			case 'articles':
   				Db::name('articles')->where(['id'=>$id])->update(['sort'=>$sort,'update_at'=>time(),'push'=>1]);
   				break;
   			default:
   				sendJson(-1,'type错误');
   				break;
   		}
   		sendJson(1,'添加成功');
   	}
    //添加热词
    public function addHotWord(){
      $hotword = input('post.hotword');
      $sort = input('post.sort',0);
      Db::name('hotword')->insert(['hotword'=>$hotword,'sort'=>$sort,'update_at'=>time(),'push'=>1]);
      sendJson(1,'添加成功');
    }
    //删除推荐
    public function delPush(){
      $id = input('post.id');
      $type = input('post.type');
      switch ($type) {
        case 'clinic':
          Db::name('clinic')->where(['id'=>$id])->update(['push'=>0]);
          break;
        case 'teacher':
          Db::name('user')->where(['id'=>$id])->update(['push'=>0]);
          break;
        case 'exam':
          Db::name('examtitle')->where(['id'=>$id])->update(['push'=>0,'update_at'=>time()]);
          break;
        case 'articles':
          Db::name('articles')->where(['id'=>$id])->update(['push'=>0,'update_at'=>time()]);
          break;
        case 'hotword':
          Db::name('hotword')->where(['id'=>$id])->update(['push'=>0,'update_at'=>time()]);
          break;
        default:
          sendJson(-1,'type错误');
          break;
      }
      sendJson(1,'删除推荐');
    }
    //删除banner
    public function delBanner(){
      $id = input('id');
      db('banner')->where(['id'=>$id])->delete();
      sendJson(1,'删除banner');
    }
}
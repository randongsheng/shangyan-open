<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Loader;
class Uicomment extends Controller
{
	public function test(){
		Loader::import("PhpServerSdk.TimRestApi",EXTEND_PATH);
		$api = new \TimRestAPI();
		$sdkappid = 1400163119;
		$identifier = 'admin';
		$api->init($sdkappid, $identifier);
		$tool_path = 'F:\phpstudy\PHPTutorial\WWW\tp5.0\extend\PhpServerSdk\signature\windows-signature64.exe';
		$protected_key_path = '';
		$sig = $api->generate_user_sig($identifier, 86400, 'private.pem', $tool_path);
		print_r($sig);
	}

    /**
     * 用户评论列表
     */
	public function getUserComment(){
        $uid = input('post.uid');
        $list = db('comment c')->field('c.id,articleid,a.title articletitle,unick,c.content,c.createtime,u.avatarurl')->join('articles a','c.articleid = a.id','LEFT')->join('user u','c.uid = u.id')->where(['uid'=>$uid,'replyid'=>0])->select();
        foreach ($list as $key => $value) {
            list($list[$key]['soncount'],$list[$key]['son']) = $this->getSonComment($value['id']);
            $list[$key]['fabulous'] = db('fabulous')->where(['type'=>2,'id'=>$value['id']])->count();
        }

        print_r($list);
    }

    /**
     * @param $fatherid 父评论id
     * 获取子评论
     */
    public function getSonComment($fatherid)
    {
        $son = \db('comment')->where(['replyid'=>$fatherid])->select();
        $soncount = count($son);
        return [$son,$soncount];
    }
    /**
     *文章点赞列表
     */
    public function getUserFabulous()
    {
        $uid = input('post.uid');
        $list = \db('fabulous f')->join('articles a','f.id = a.id')->where(['uid'=>$uid,'f.status'=>1,'f.type'=>1])->select();
        print_r($list);
    }
    /**
     * 文章收藏
     */
    public function getUserCollentiom()
    {
        $uid = input('post.uid');
        $list = \db('collentiom c')->join('articles a','c.articleid = a.id')->where(['uid'=>$uid])->select();
        print_r($list);
    }
}
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
class Auth extends Common
{
    public function index(){

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
    public function addNode(){
        $param = input('post.');
        try{

            \db('node')->insert($param);
            sendJson(1,'添加节点成功');
           // return msg(1, '', '添加节点成功');
        }catch(PDOException $e){
            sendJson(-1,'节点添加错误',$e->getMessage());
            //return msg(-2, '', $e->getMessage());
        }
    }

    /**
     * 编辑节点
     */
    public function editNode()
    {
        $param = input('post.');

        $node = new NodeModel();
        $flag = $node->editNode($param);
        $this->removRoleCache();
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }

    /**
     * 删除节点
     */
    public function delNode()
    {
        $id = input('param.id');

        $role = new NodeModel();
        $flag = $role->delNode($id);
        $this->removRoleCache();
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }
    /**
     * 节点列表
     */
    public function getNodeList()
    {
        
    }
}
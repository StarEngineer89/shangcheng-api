<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\agent\AgentServices;
use app\services\room\RoomServices;
use app\services\user\UserServices;
use think\facade\Log;

/**
 * 代理类
 * Class Agent
 * @package app\controller\admin
 */
class Agent
{
    /**
     * @var AgentServices
     */
    #[Inject]
    protected AgentServices $services;
    /**
     * 获取房间信息
     * @return mixed
     */
    public function getAgentList(Request $request)
    {
        [$keyword, $status] = $request->postMore([
            ['keyword',''],
            [['status','d'],0]
        ],true);
        $data = $this->services->getlist($keyword, $status);
        return app('json')->success($data);
    }
    
    
    /**
     * 添加机器人
     * @return mixed
     */
    public function addAgent(Request $request)
    {
        $data = $request->postMore([
             [['id','d'],0],
             ['account',''],
             ['password',''],
             ['nickname',''],
        ]);
        if(!$data['account']||!$data['nickname']){
            return app('json')->fail('缺少参数');
        }
        if(!$data['id']&&!$data['password']){
            return app('json')->fail('请输入密码');
        }
        
        $admin_id = $request->adminId();
        
        
        $res = $this->services->editAgent($admin_id, $data);
        return app('json')->success("添加成功");
    }
    
    /**
     * 删除
     * @return mixed
     */
    public function delAgent(Request $request)
    {
        [$id] = $request->postMore([
            [['id','d'],0]
        ], true);
        if(!$id) return app('json')->fail('缺少参数');
        $admin_id = $request->adminId();
        
        $res = $this->services->delAgent($admin_id, $id);
        return app('json')->success('删除成功');
    }
    
    
    
    /**
     * 修改状态
     * @return mixed
     */
    public function setAgentStatus(Request $request)
    {
        [$id,$status] = $request->postMore([
            [['id','d'],0],
            [['status','d'],0],
        ], true);
        if(!intval($id)) return app('json')->fail('缺少参数');
        if(!in_array($status,[0,1])) return app('json')->fail('参数错误');
        $status = $status==1?1:0;
        $admin_id = $request->adminId();
        $res = $this->services->setStatus($admin_id, $id, $status);
        return app('json')->success("修改成功");
    }
    

}
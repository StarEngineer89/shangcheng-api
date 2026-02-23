<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\room\RoomServices;
use app\services\user\UserServices;
use think\facade\Log;

/**
 * 房间类
 * Class Room
 * @package app\controller\admin
 */
class Room
{
    /**
     * @var RoomServices
     */
    #[Inject]
    protected RoomServices $services;
    /**
     * 获取房间信息
     * @return mixed
     */
    public function getRoomList(Request $request)
    {
        [$status, $keyword] = $request->postMore([
            [['status','d'],0],
            ['keyword','']
        ],true);
        $data = $this->services->getlist($keyword, $status);
        return app('json')->success($data);
    }
    
    
    /**
     * 添加房间
     * @return mixed
     */
    public function addRoom(Request $request)
    {
        $data = $request->postMore([
             [['id','d'],0],
             ['account',''],
             ['password',''],
             ['room_name',''],
             ['room_num',''],
             ['enddate',''],
        ]);
        if(!$data['account']||!$data['room_name']||!$data['room_num']||!$data['enddate']){
            return app('json')->fail('缺少参数');
        }
        if(!$data['id']&&!$data['password']){
            return app('json')->fail('请输入密码');
        }
        if($data['password']&&!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,20}$/',$data['password'])){
            return app('json')->fail('密码请输入6-20位包含大小写字母及数字组合');
        }
        if(!preg_match('/^\d{6}$/', $data['room_num'])){
            return app('json')->fail('请输入六位数房间号');
        }
        if(!strtotime($data['enddate'])||strtotime($data['enddate'])<time()+3600){
            return app('json')->fail('请输入正确的日期时间');
        }
        
        $admin_id = $request->adminId();
        
        
        $res = $this->services->editRoom($admin_id, $data);
        return app('json')->success("添加成功");
    }
    

}
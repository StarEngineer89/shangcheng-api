<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\user\UserServices;
use app\services\room\RoomServices;
use app\services\room\RoomUserServices;
use app\services\admin\AdminAclogServices;
use app\services\user\UserMoneylogServices;
use app\services\admin\AdminLoginlogServices;
use app\services\cai\CaiPeriodServices;
use app\services\order\OrderServices;
use app\services\room\RoomRedServices;

/**
 * 用户报表相关
 * Class Report
 * @package app\controller\admin
 */
class Report
{
    /**
     * @var OrderServices
     */
    #[Inject]
    protected OrderServices $services;
    
    
    /**
     * 获取总资金记录
     * @return mixed
     */
    public function moneylog(Request $request)
    {
        [$startdate, $enddate, $type, $cai_id, $period, $usertype, $keyword] = $request->postMore([
            ['startdate', ''],
            ['enddate', ''],
            [['type', 'd'], 0],
            [['cai_id', 'd'], 0],
            [['period', 'd'], 0],
            [['usertype', 'd'], 0],
            ['keyword', ''],
        ], true);
        if(!strtotime($startdate)||!strtotime($enddate)){
            return app('json')->fail('缺少参数');
        }
        
        $usertype = $usertype==1?1:2;
        
        $startTime = strtotime($startdate);
        $endTime = strtotime($enddate);
        
        if($startTime>$endTime){
             $start = $startdate;
             $startdate = $enddate;
             $enddate = $start;
        }
        
        if($keyword){
             $UserServices = app()->make(UserServices::class);
             $RoomUserServices = app()->make(RoomUserServices::class);
             $user_ids = $UserServices->searchKeyword((string)$keyword);
             $keyword = $RoomUserServices->searchKeyword($user_ids, $keyword);
        }
        
        if($startTime<strtotime('-15days',strtotime(date('y-m-d'))) || $startTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        if($endTime<strtotime('-15days',strtotime(date('y-m-d'))) || $endTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        
        $data = app()->make(UserMoneylogServices::class)->getMoneylogList(0, $keyword, $startdate, $enddate, $type, $usertype, $cai_id, $period);
        return app('json')->success($data);
    }
    
    
    
    /**
     * 获取用户报表
     * @return mixed
     */
    public function user(Request $request)
    {
        [$startdate, $enddate, $sorttype, $cai_id, $period, $usertype, $status, $keyword] = $request->postMore([
            ['startdate', ''],
            ['enddate', ''],
            [['sorttype', 'd'], 0],
            [['cai_id', 'd'], 0],
            [['period', 'd'], 0],
            [['usertype', 'd'], 0],
            [['status', 'd'], 0],
            ['keyword', ''],
        ], true);
        if(!strtotime($startdate)||!strtotime($enddate)){
            return app('json')->fail('缺少参数');
        }
        if(!in_array($sorttype,[0,1,2,3,4])) return app('json')->fail('参数错误');
        $usertype = $usertype==2?2:1;
        $status = $status==2?2:1;
        
        $startTime = strtotime($startdate);
        $endTime = strtotime($enddate);
        
        if($startTime>$endTime){
             $start = $startdate;
             $startdate = $enddate;
             $enddate = $start;
        }
        
        if($keyword){
             $UserServices = app()->make(UserServices::class);
             $RoomUserServices = app()->make(RoomUserServices::class);
             $user_ids = $UserServices->searchKeyword((string)$keyword);
             $keyword = $RoomUserServices->searchKeyword($user_ids, $keyword);
        }
        
        if($startTime<strtotime('-15days',strtotime(date('y-m-d'))) || $startTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        if($endTime<strtotime('-15days',strtotime(date('y-m-d'))) || $endTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        $data = $this->services->getUserReport(0, $keyword, $startdate, $enddate, $usertype, $usertype, $cai_id, $period, $status);
        return app('json')->success($data);
    }
    
    /**
     * 获取用户注单报表
     * @return mixed
     */
    public function userBet(Request $request)
    {
        [$startdate, $enddate, $cai_id, $period, $user_id] = $request->postMore([
            ['startdate', ''],
            ['enddate', ''],
            [['cai_id', 'd'], 0],
            [['period', 'd'], 0],
            [['user_id', 'd'], 0]
        ], true);
        if(!strtotime($startdate)||!strtotime($enddate) || !$user_id){
            return app('json')->fail('缺少参数');
        }
        
        $startTime = strtotime($startdate);
        $endTime = strtotime($enddate);
        
        if($startTime>$endTime){
             $start = $startdate;
             $startdate = $enddate;
             $enddate = $start;
        }
        
        if($startTime<strtotime('-15days',strtotime(date('y-m-d'))) || $startTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        if($endTime<strtotime('-15days',strtotime(date('y-m-d'))) || $endTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        
        $data = $this->services->getUserBetReport(0, $startdate, $enddate, $user_id, $cai_id, $period);
        return app('json')->success($data);
    }
    
    
     /**
     * 获取分类报表
     * @return mixed
     */
    public function cate(Request $request)
    {
        [$startdate, $enddate, $sorttype, $cai_id, $period, $usertype, $status, $keyword] = $request->postMore([
            ['startdate', ''],
            ['enddate', ''],
            [['sorttype', 'd'], 0],
            [['cai_id', 'd'], 0],
            [['period', 'd'], 0],
            [['usertype', 'd'], 0],
            [['status', 'd'], 0],
            ['keyword', ''],
        ], true);
        if(!strtotime($startdate)||!strtotime($enddate)){
            return app('json')->fail('缺少参数');
        }
        if(!in_array($sorttype,[0,1,2,3,4])) return app('json')->fail('参数错误');
        $usertype = $usertype==2?2:1;
        $status = $status==2?2:1;
        
        $startTime = strtotime($startdate);
        $endTime = strtotime($enddate);
        
        if($startTime>$endTime){
             $start = $startdate;
             $startdate = $enddate;
             $enddate = $start;
        }
        
        if($keyword){
             $UserServices = app()->make(UserServices::class);
             $RoomUserServices = app()->make(RoomUserServices::class);
             $user_ids = $UserServices->searchKeyword((string)$keyword);
             $keyword = $RoomUserServices->searchKeyword($user_ids, $keyword);
        }
        
        if($startTime<strtotime('-15days',strtotime(date('y-m-d'))) || $startTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        if($endTime<strtotime('-15days',strtotime(date('y-m-d'))) || $endTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        
        $data = $this->services->getCateReport(0, $keyword, $startdate, $enddate, $usertype, $usertype, $cai_id, $period, $status);
        return app('json')->success($data);
    }
    
    
    /**
     * 获取未结算报表
     * @return mixed
     */
    public function unResult(Request $request)
    {
        [$cai_id, $period] = $request->postMore([
            [['cai_id', 'd'], 0],
            [['period', 'd'], 0],
        ], true);
        $data = $this->services->getUnResultReport(0, $cai_id, $period);
        return app('json')->success($data);
    }
    
    /**
     * 获取项目报表
     * @return mixed
     */
    public function played(Request $request)
    {
        [$cai_id] = $request->postMore([
            [['cai_id', 'd'], 0],
        ], true);
        if(!$cai_id){
            return app('json')->fail('缺少参数');
        }
        
        
        $CaiPeriodServices = app()->make(CaiPeriodServices::class);
        $caiInfo = $CaiPeriodServices->getTime($cai_id);
        if(!$caiInfo){
            return app('json')->fail('彩种有误');
        }
        
        $data = $this->services->getPlayedReport(0, $cai_id, $caiInfo['next_period']);
        return app('json')->success($data);
    }
 
     /**
     * 获取登录报表
     * @return mixed
     */
    public function loginList(Request $request)
    {
        [$startdate, $enddate, $ip] = $request->postMore([
            ['startdate', ''],
            ['enddate', ''],
            ['ip', ''],
        ], true);
        if(!strtotime($startdate)||!strtotime($enddate)){
            return app('json')->fail('缺少参数');
        }
        
        $startTime = strtotime($startdate);
        $endTime = strtotime($enddate);
        
        if($startTime>$endTime){
             $start = $startdate;
             $startdate = $enddate;
             $enddate = $start;
        }
        
        if($startTime<strtotime('-15days',strtotime(date('y-m-d'))) || $startTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        if($endTime<strtotime('-15days',strtotime(date('y-m-d'))) || $endTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        $admin_id = $request->adminId();
        
        $AdminLoginlogServices = app()->make(AdminLoginlogServices::class);
        $data = $AdminLoginlogServices->getList($admin_id, $startdate, $enddate, $ip);
        return app('json')->success($data);
    }
    
    
    /**
     * 获取操作报表
     * @return mixed
     */
    public function actList(Request $request)
    {
        [$startdate, $enddate, $ip] = $request->postMore([
            ['startdate', ''],
            ['enddate', ''],
            ['ip', ''],
        ], true);
        if(!strtotime($startdate)||!strtotime($enddate)){
            return app('json')->fail('缺少参数');
        }
        
        
        $startTime = strtotime($startdate);
        $endTime = strtotime($enddate);
        
        if($startTime>$endTime){
             $start = $startdate;
             $startdate = $enddate;
             $enddate = $start;
        }
        
        if($startTime<strtotime('-15days',strtotime(date('y-m-d'))) || $startTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        if($endTime<strtotime('-15days',strtotime(date('y-m-d'))) || $endTime> strtotime(date('y-m-d'))){
            return app('json')->fail('参数有误');
        }
        
        $admin_id = $request->adminId();
        
        $AdminAclogServices = app()->make(AdminAclogServices::class);
        $data = $AdminAclogServices->getList($admin_id, $startdate, $enddate, $ip);
        return app('json')->success($data);
    }
    
   
}
<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\room\RoomServices;
use app\services\room\RoomCaiServices;
use app\services\cai\CaiServices;
use app\services\cai\CaiMsgServices;
use app\services\cai\CaiPeriodServices;
use app\services\cai\OddsServices;

/**
 * 彩种相关
 * Class Cai
 * @package app\controller\admin
 */
class Cai
{
    /**
     * @var CaiServices
     */
    #[Inject]
    protected CaiServices $services;
   
    /**
     * 修改房间彩种状态
     * @return mixed
     */
    public function setStatus(Request $request)
    {
        [$cai_id,$status] = $request->postMore([
            'cai_id','status'
        ], true);
        $status = $status==1?1:0;
        if(!intval($cai_id)) return app('json')->fail('缺少参数');
        
        $admin_id = $request->adminId();
        $this->services->setStatus((int)$admin_id, (int)$cai_id, $status);
        return app('json')->success("修改成功");
    }
    
    /**
     * 保存彩种配置
     * @return mixed
     */
    public function saveCai(Request $request)
    {
        $data = $request->postMore([
            'id','is_http','tv_url'
        ]);
        $data['is_http'] = $data['is_http']==1?1:0;
        if(!intval($data['id'])) return app('json')->fail('缺少参数');
        if($data['tv_url']){
            if(!preg_match('/\bhttps?:\/\/[a-z0-9-]+(\.[a-z0-9-]+)+(:\d+)?(\/.*)?(\?.*)?\b/i', $data['tv_url'])){
                return app('json')->fail('请输入正确的网络开奖视频地址');
            }
        }
        $admin_id = $request->adminId();
        $caiServices = app()->make(CaiServices::class);
        $res = $caiServices->saveCai($admin_id, $data);
        return app('json')->success('保存成功');
    }
    
    /**
     * 获取彩种消息配置
     * @return mixed
     */
    public function getMsgConfig(Request $request)
    {
        $caiMsgServices = app()->make(CaiMsgServices::class);
        $data = $caiMsgServices->getMsg(0);
        return app('json')->success($data);
    }
    
    
    /**
     * 保存彩种消息配置
     * @return mixed
     */
    public function saveMsgConfig(Request $request)
    {
        $data = $request->postMore([
            'cai_id','msg_a','msg_a_time','msg_b','msg_c','msg_c_time','msg_d','msg_e','msg_f'
        ]);
        if(!intval($data['cai_id'])) return app('json')->fail('缺少参数');
        $admin_id = $request->adminId();
        $caiMsgServices = app()->make(CaiMsgServices::class);
        $res = $caiMsgServices->saveMsg(0, $data, $admin_id);
        return app('json')->success('保存成功');
    }
    
    
    /**
     * 获取彩种赔率
     * @return mixed
     */
    public function oddslist(Request $request)
    {
        [$cai_id] = $request->postMore([
            [['cai_id','d'],0]
        ],true);
        if(!$cai_id) return app('json')->fail('缺少参数');
 
        $caiServices = app()->make(CaiServices::class);
        $cai = $caiServices->getCaiCacheInfo($cai_id);
        if(!$cai){
            return app('json')->fail('彩种不存在');
        }
        $oddsServices = app()->make(OddsServices::class);
        $data = $oddsServices->getSysList($cai_id);
        return app('json')->success($data);
    }
    
    
    /**
     * 保存彩种赔率
     * @return mixed
     */
    public function saveOdds(Request $request)
    {
        [$cai_id, $oddslist] = $request->postMore([
            [['cai_id','d'],0],
            ['oddslist',[]]
        ],true);
        if(!$cai_id||!$oddslist||!is_array($oddslist)) return app('json')->fail('缺少参数');
        $admin_id = $request->adminId();
        
        $caiServices = app()->make(CaiServices::class);
        $cai = $caiServices->getCaiCacheInfo($cai_id);
        if(!$cai){
            return app('json')->fail('彩种不存在');
        }
        $oddsServices = app()->make(OddsServices::class);
        $data = $oddsServices->saveSysOdds($admin_id, $cai_id, $oddslist);
        return app('json')->success('保存成功');
    }
    
    
     /**
     * 获取彩种开奖记录
     * @return mixed
     */
    public function resultlist(Request $request)
    {
        [$cai_id, $startdate, $enddate, $period] = $request->postMore([
            [['cai_id','d'],0],
            ['startdate', ''],
            ['enddate', ''],
            [['period','d'],0],
        ],true);
        if(!strtotime($startdate)||!strtotime($enddate)||!$cai_id){
            return app('json')->fail('缺少参数');
        }
        
        $caiServices = app()->make(CaiServices::class);
        $cai = $caiServices->getCaiCacheInfo($cai_id);
        if(!$cai){
            return app('json')->fail('彩种不存在');
        }
        
        $startTime = strtotime($startdate);
        $endTime = strtotime($enddate);
        
        if($startTime>$enddate){
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
        
        
        $CaiPeriodServices = app()->make(CaiPeriodServices::class);
        $data = $CaiPeriodServices->getCaiList($cai_id, $startdate, $enddate, $period);
        return app('json')->success($data);
    }


}
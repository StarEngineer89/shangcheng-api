<?php
namespace app\listener\timer;
use core\utils\Cron;
use core\interfaces\ListenerInterface;
use core\services\CacheService;
use app\services\order\OrderServices;
use app\services\merchant\MerchantServices;
use app\services\merchant\MerchantDateServices;
use think\facade\Log;
/**
 * 定时任务
 * Class Create
 * @package app\listener\timer
 */
class SystemTimer extends Cron implements ListenerInterface
{
    protected $event;
    protected $ChatService;
    /**
     * @param $event
     */
    public function handle($event): void
    {
        $this->event = $event;
        try{
            
            ///每10分钟检测订单发货
            $this->setWorkerId($event)->tick(600000, function (){
                   $OrderServices = app()->make(OrderServices::class);
                   $OrderServices->checkOrder();
            });
            
            
            ///每小时检测订单收货
            $this->setWorkerId($event)->tick(3600000, function (){
                   $OrderServices = app()->make(OrderServices::class);
                   $OrderServices->checkshOrder();
            });
            
            ///每10分钟检测订单发货
            $this->setWorkerId($event)->tick(1800000, function (){
                   $MerchantServices = app()->make(MerchantServices::class);
                   $MerchantDateServices = app()->make(MerchantDateServices::class);
                   $mers = $MerchantServices->getmers();
                   foreach ($mers as $mer){
                       $MerchantDateServices->addDate($mer);
                   }
            });
            
        }catch(\Exception $e){
            Log::error('定时任务失败:'.$e->getMessage());
        }
    }
    
    
}
<?php
declare (strict_types=1);
namespace app\services\merchant;

use app\services\BaseServices;
use app\model\MerchantTrafficOrder;
use app\services\merchant\MerchantServices;
use app\services\admin\AdminNotificationsServices;
use core\services\CacheService;

/**
 * Class OrderServices
 * @package app\services
 * @mixin Order
 */
class MerchantTrafficOrderServices extends BaseServices {
    protected function setModel(): string
    {
        return MerchantTrafficOrder::class;
    }

    public function getnewcount()
    {
        return $this->getModel()->where('status', 1)->count();
    }
    
    public function create($merchant_id, $merchant_name, $num, $price, $type, $duration)
    {
        return $this->transaction(function() use($merchant_id, $merchant_name, $num, $price, $type, $duration){
                $MerchantServices = app()->make(MerchantServices::class);
                $res = $MerchantServices->subMoney($merchant_id, $price);
                if(!$res){
                    throw new ApiException('The system is busy, please try again later');
                }
                
                $order = [
                           'merchant_id'=>$merchant_id,
                           'num'=>$num,
                           'price'=>$price,
                           'type'=>$type,
                           'duration'=>$duration,
                           'status'=>0
                        ];
                $res_order = $this->getModel()->insert($order);
                if(!$res_order){
                    throw new ApiException('The system is busy, please try again later');
                }
                $AdminNotificationsServices = app()->make(AdminNotificationsServices::class);
                $msg = "商户【" . $merchant_name . "】购买了流量购买";
                $AdminNotificationsServices->setNotification($msg);
                $this->clearMerchantCache($merchant_id);
                
                return true;
        });
        
    }
    
    /**
     * 获取列表
     * @param $keyword
     */
    public function getlist($mer_id, $mer_name, $start_date, $end_date)
    {
        $where = [];
        $hasWhere = [];
        if($mer_id){
            $where[] = ['id', '=', $mer_id];
        }
        if($mer_name){
            $hasWhere[] = ['mer_name', 'like', "%$mer_name%"];
        }
        if ($start_date) {
            $where[] = ['order_time', '>', $start_date];
        }
        if ($end_date) {
            $where[] = ['order_time', '<', $end_date];
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->getModel()->with(['merchant'])->where($where)->hasWhere('merchant', $hasWhere)->order('id','desc')->page($page, $limit)->select()->toArray();
        $count = $this->getModel()->where($where)->hasWhere('merchant', $hasWhere)->count();
        return compact('list','count');
    }
    
    public function update($id, $update) {
        return $this->getModel()->where('id', $id)->update($update);
    }
    
    /**
     * 清除商户缓存
     */
    public function clearMerchantCache($mid)
    {
        CacheService::delete($this->getCacheKey('merchant_info_'.$mid));
    }
}
<?php
namespace app\controller\api;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\user\UserCollectServices;
use app\services\product\CartServices;
use app\services\product\ProductServices;
use app\services\product\CategoryServices;
use app\services\merchant\MerchantServices;

/**
 * 店铺类
 * Class Store
 * @package app\controller\api
 */
class Store
{
     public function merchant(Request $request)
     {
         [$mer_id] = $request->postMore([
            [['mer_id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $mer_id)){
             return app('json')->fail('Missing parameters');
         }
         $MerchantServices = app()->make(MerchantServices::class);
         $merchant = $MerchantServices->getMer($mer_id);
         if(empty($merchant)){
             return app('json')->fail('Merchant does not exist');
         }
         $merchant = $merchant->toArray();
         $lang = $request->lang();
         
         $uid = $request->uid();
         $data = [
                'id'=>$mer_id,
                'colectmer'=>0,
                'mer_name'=>$merchant['mer_name'],
                'mer_avatar'=>$merchant['mer_avatar'],
                'care_count'=>$merchant['care_count'],
                'store_score'=>$merchant['store_score'],
                'mer_info'=>$merchant['mer_info'],
                'type_id'=>$merchant['type_id'],
                'mer_banner'=>$merchant['mer_banner'],
                'cartnum'=>0
            ];
         if($uid){
             ///是否关注店铺
             $UserCollectServices = app()->make(UserCollectServices::class);
             $data['colectmer'] = $UserCollectServices->collectMer($uid, $mer_id);
         }
         return app('json')->success($data);
     }
     
     public function category(Request $request)
     {
         [$mer_id] = $request->postMore([
            [['mer_id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $mer_id)){
             return app('json')->fail('Missing parameters');
         }
         $MerchantServices = app()->make(MerchantServices::class);
         $merchant = $MerchantServices->getMer($mer_id);
         if(empty($merchant)){
             return app('json')->fail('Merchant does not exist');
         }
         $lang = $request->lang();
         $CategoryServices = app()->make(CategoryServices::class);
         $data = $CategoryServices->mercatelist($lang, $mer_id);
         return app('json')->success($data);
     }
     
}
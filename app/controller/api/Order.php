<?php
namespace app\controller\api;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\order\OrderServices;
use app\services\merchant\MerchantServices;
use app\services\merchant\MerchantTrafficOrderServices;
use think\facade\Log;

/**
 * 订单类
 * Class Order
 * @package app\controller\api
 */
class Order
{
    
     public function createOrder(Request $request)
     {
         [$cart_id, $address_id, $password] = $request->postMore([
            ['cart_id', []],
            [['address_id', 'd'], 0],
            ['password', '']
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $address_id)){
             return app('json')->fail('Missing parameters');
         }
         if(!is_array($cart_id)||empty($cart_id)){
             return app('json')->fail('Missing parameters');
         }
         if(!preg_match('/^\d{6}$/', $password)){
             return app('json')->fail('Missing parameters');
         }
         $lang = $request->lang();
         $user = $request->user();
         if(md5($password)!=$user['withdraw_pwd']){
             return app('json')->fail('Incorrect payment password');
         }
         $OrderServices = app()->make(OrderServices::class);
         $res = $OrderServices->createOrder($lang, $user, $cart_id, $address_id);
         return app('json')->success('success');
     }
     
    public function createTrafficOrder(Request $request)
    {
        [$num, $price, $type, $duration, $password] = $request->postMore([
            [['num', 'd'], 0],
            [['price', 'd'], 0],
            [['type', 'd'], 0],
            [['duration', 'd'], 0],
            ['password', '']
        ], true);
        if(!preg_match('/^[1-9]\d*$/', $num)){
            return app('json')->fail('Missing parameters');
        }
        if(!preg_match('/^[1-9]\d*$/', $type)){
            return app('json')->fail('Missing parameters');
        }
        if(!preg_match('/^[1-9]\d*$/', $duration)){
            return app('json')->fail('Missing parameters');
        }
        if(!preg_match('/^\d{6}$/', $password)){
            return app('json')->fail('Missing parameters');
        }
        $lang = $request->lang();
        $user = $request->user();
        if(md5($password)!=$user['withdraw_pwd']){
            return app('json')->fail('Incorrect payment password');
        }
        $MerchantServices = app()->make(MerchantServices::class);
        $merchant = $MerchantServices->find(['mer_uid' => $user['id']]);
        if (!$merchant || count($merchant) == 0) {
            return app('json')->fail('商户无效。');
        }
        $MerchantTrafficOrderServices = app()->make(MerchantTrafficOrderServices::class);
        $res = $MerchantTrafficOrderServices->create($merchant['id'], $merchant['mer_name'], $num, $price, $type, $duration);
        return app('json')->success('success');
    }
     
}
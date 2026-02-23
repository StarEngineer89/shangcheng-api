<?php
declare (strict_types=1);
namespace app\services\order;

use app\services\BaseServices;
use core\exceptions\ApiException;
use app\services\product\CartServices;
use app\services\product\ProductServices;
use app\services\user\UserAddressServices;
use app\services\user\UserMoneylogServices;
use app\services\user\UserServices;
use app\services\merchant\MerchantServices;
use app\services\merchant\MerchantMoneylogServices;
use app\services\merchant\MerchantWithdrawServices;
use app\services\merchant\MerchantDateServices;
use app\model\Order;
use app\jobs\order\FahuoJob;

/**
 * Class OrderServices
 * @package app\services
 * @mixin Order
 */
class OrderServices extends BaseServices
{
    protected function setModel(): string
    {
        return Order::class;
    }
    
    public function getInfo($lang, $order_id, $mer_id)
    {
        $order = $this->getModel()->where('id', $order_id)->find();
        if(empty($order)){
            throw new ApiException('订单不存在');
        }
        if($order['mer_id']!=$mer_id){
            throw new ApiException('订单不存在');
        }
        $order = $order->toArray();
        $mer = app()->make(MerchantServices::class)->getMer($mer_id);
        $order['addtime'] = date('Y-m-d H:i:s', $order['add_time']);
        $rate = bcdiv((string)$mer['spread_ratio'], '100', 4);
        $rate_cost = bcsub('1', (string)$rate, 4);
        $order['mer_cost'] = bcmul((string)$rate_cost, (string)$order['total'], 2);
        $user = app()->make(UserServices::class)->getUserCacheInfo($order['uid']);
        $account = '';
        if($user['phone']){
           $account = substr($user['phone'], 0, 2).'****'.substr($user['phone'], -3);
        }
        if($user['email']&&!$account){
           $account = substr($user['email'], 0, 2).'**@**'.substr($user['email'], -3);
        }
        $order['account'] = $account;
        $order['avatar'] = $user['avatar'];
        $order['contact_phone'] = substr($order['contact_phone'], 0, 2).'****'.substr($order['contact_phone'], -3);
        return $order;
    }
    
    public function orderCount($uid)
    {
        $data = [];
        $data['all'] = $this->getModel()->where('uid', $uid)->count();
        $data['paid'] = $this->getModel()->where('uid', $uid)->where('status', 0)->count();
        $data['shipping'] = $this->getModel()->where('uid', $uid)->where('status', 1)->count();
        $data['recived'] = $this->getModel()->where('uid', $uid)->where('status', 2)->count();
        $data['refund'] = $this->getModel()->where('uid', $uid)->where('status', 3)->where('refund_status', 0)->count();
        return $data;
    }
    
    public function orderMerCount($mer_id)
    {
        $data = [];
        $data['paid'] = $this->getModel()->where('mer_id', $mer_id)->where('status', 0)->count();
        $data['shipping'] = $this->getModel()->where('mer_id', $mer_id)->where('status', 1)->count();
        $data['recived'] = $this->getModel()->where('mer_id', $mer_id)->where('status', 2)->count();
        $data['refund'] = $this->getModel()->where('mer_id', $mer_id)->where('status', 3)->where('refund_status', 0)->count();
        $today_time = strtotime(date('Y-m-d'));
        $yestoday_time = strtotime('-1day', $today_time);
        $month_time = strtotime(date('Y-m-01'));
        $sevenday_time = strtotime('-7day', $today_time);
        $thirty_time = strtotime('-30day', $today_time);
        
        $data['today_profit'] = $this->getModel()->where('mer_id', $mer_id)->whereIn('status', [2, 4])->where('add_time', '>=', $today_time)->sum('mer_profit');
        $data['yestoday_profit'] = $this->getModel()->where('mer_id', $mer_id)->whereIn('status', [2, 4])->where('add_time', '<', $today_time)->where('add_time', '>=', $yestoday_time)->sum('mer_profit');
        $data['month_profit'] = $this->getModel()->where('mer_id', $mer_id)->whereIn('status', [2, 4])->where('add_time', '>=', $month_time)->sum('mer_profit');
        
        $data['today_order'] = $this->getModel()->where('mer_id', $mer_id)->where('add_time', '>=', $today_time)->count();
        $data['yestoday_order'] = $this->getModel()->where('mer_id', $mer_id)->where('add_time', '<', $today_time)->where('add_time', '>=', $yestoday_time)->count();
        $data['month_order'] = $this->getModel()->where('mer_id', $mer_id)->where('add_time', '>=', $month_time)->count();
        
        $MerchantDateServices = app()->make(MerchantDateServices::class);
        $today = $MerchantDateServices->getModel()->where('uid', $mer_id)->where('add_time', '>=', $today_time)->value('nums');
        $data['today_nums'] = $today?$today:0;
        $seven = $MerchantDateServices->getModel()->where('uid', $mer_id)->where('add_time', '>=', $sevenday_time)->sum('nums');
        $data['seven_nums'] = $seven?$seven:0;
        $thirty = $MerchantDateServices->getModel()->where('uid', $mer_id)->where('add_time', '>=', $thirty_time)->sum('nums');
        $data['thirty_nums'] = $thirty?$thirty:0;
        
        return $data;
    }
    
    public function orderDataProfit($mer_id, $type)
    {
        $today_time = strtotime(date('Y-m-d'));
        $month_time = strtotime(date('Y-m-01'));
        $week_time = strtotime('-7day', $today_time);
        $date = [];
        $profit = [];
        $first_time = $type==1?$week_time:$month_time;
        if($type==1){
          $monday = date('Y-m-d', strtotime('monday this week'));
          $days = floor(($today_time - strtotime($monday)) / 86400) + 1;
        }else{
          $days = floor(($today_time - $month_time) / 86400) + 1;
        }
        for($i = 0;$i<$days;$i++){
           $starttime = strtotime('+'.$i.'day', $first_time);
           $endtime = strtotime('+1day', $starttime);
           $date[] = date('Y-m-d', $starttime);
           $amount = $this->getModel()->where('mer_id', $mer_id)->whereIn('status', [2, 4])->where('add_time', '<', $endtime)->where('add_time', '>=', $starttime)->sum('mer_profit');
           $profit[] = $amount?$amount:0;
        }
        return ['x'=>$date, 'y'=>$profit];
    }
    
    public function orderProfit($mer_id)
    {
        $data = [];
        $data['withdraw'] = app()->make(MerchantWithdrawServices::class)->getModel()->where('uid', $mer_id)->where('status', 0)->sum('amount');
        $data['profit'] = $this->getModel()->where('mer_id', $mer_id)->whereIn('status', [2, 4])->sum('mer_profit');
        return $data;
    }
    
    public function checkOrder()
    {
        $this->getModel()->where('status', 0)->where('is_caigou', 1)->update(['status'=>1]);
    }
    
    public function checkshOrder()
    {
        $time = strtotime('-3days', time());
        $this->getModel()->where('status', 1)->where('is_caigou', 1)->where('caigou_time', '<', $time)->update(['status'=>2]);
    }
    
    public function fahuoorder($order_id)
    {
        $this->getModel()->where('id', $order_id)->where('status', 0)->where('is_caigou', 1)->update(['status'=>1]);
    }
    
    public function createOrder($lang, $user, $cart_ids, $address_id)
    {
        return $this->transaction(function() use($lang, $user, $cart_ids, $address_id){
                $uid = $user['id'];
                $CartServices = app()->make(CartServices::class);
                $list = $CartServices->getModel()->with(['product', 'attr'])->whereIn('id', $cart_ids)->where('uid', $uid)->select()->toArray();
                if(count($list)!=count($cart_ids)){
                    throw new ApiException('Incorrect parameters');
                }
                
                $UserAddressServices = app()->make(UserAddressServices::class);
                $address = $UserAddressServices->getModel()->where('id', $address_id)->where('uid', $uid)->find();
                if(empty($address)){
                    throw new ApiException('Incorrect parameters');
                }
                $address = $address->toArray();
                
                $UserServices = app()->make(UserServices::class);
                $user = $UserServices->getModel()->where('id', $uid)->lock(true)->find();
                if(empty($user)){
                    throw new ApiException('Incorrect parameters');
                }
                $user = $user->toArray();
                $total = 0;
                $orders = [];
                $order_no = getNewOrderId('O');
                $name = 'store_name'.$this->lang[$lang];
                $time = time();
                $pro_ids = [];
                foreach ($list as $item){
                    $product = $item['product'];
                    if(empty($product)){
                        throw new ApiException('Some items are no longer available. Please place your order again');
                    }
                    $pro_ids[] = $product['id'];
                    $price = $product['price'];
                    $image = $product['image'];
                    $cost = $product['cost'];
                    if($item['attr']){
                        $sku = $item['attr'];
                        if($sku['image']){
                          $image = $sku['image'];
                        }
                        $cost = $sku['cost'];
                        $price = $sku['price'];
                    }
                    $amount = bcmul((string)$price, (string)$item['cart_num'], 2);
                    $total = bcadd((string)$total, (string)$amount);
                    $orders[] = [
                           'main_order_no'=>$order_no,
                           'order_no'=>getNewOrderId('O'),
                           'uid'=>$uid,
                           'mer_id'=>$item['mer_id'],
                           'product_id'=>$item['product_id'],
                           'cart_num'=>$item['cart_num'],
                           'sku_id'=>$item['sku_id'],
                           'sku'=>$item['sku'],
                           'price'=>$price,
                           'image'=>$image,
                           'title'=>$product[$name],
                           'cost'=>$cost,
                           'total'=>$amount,
                           'add_time'=>$time,
                           'contact_name'=>$address['name'],
                           'contact_phone'=>$address['phone'],
                           'contact_address'=>$address['street'].','.$address['city'].','.$address['province'].','.$address['country']
                        ];
                }
                if($total>$user['money']){
                    throw new ApiException('Insufficient balance');
                }
                $res_order = $this->getModel()->insertAll($orders);
                if($res_order!=count($orders)){
                    throw new ApiException('The system is busy, please try again later');
                }
                
                $update = [];
                $update['money'] = bcsub((string)$user['money'], (string)$total, 2);
                $res_user = $UserServices->getModel()->where('id', $uid)->update($update);
                if(!$res_user){
                    throw new ApiException('The system is busy, please try again later');
                }
                $UserMoneylogServices = app()->make(UserMoneylogServices::class);
                $res_userlog = $UserMoneylogServices->addMoneylog([
                        'type'=>0,
                        'uid'=>$uid,
                        'state'=>2,
                        'title'=>'Create Order',
                        'amount'=>$total,
                        'money'=>$update['money'],
                        'add_time'=>$time,
                        'remark'=>$order_no
                    ]);
                if(!$res_userlog){
                    throw new ApiException('The system is busy, please try again later');
                }
                
                $ProductServices = app()->make(ProductServices::class);
                $ProductServices->getModel()->whereIn('id', $pro_ids)->inc('sales')->update();
                
                $CartServices->getModel()->whereIn('id', $cart_ids)->delete();
                
                $UserServices->clearUserCache($uid);
                
                return true;
        });
        
    }
    
    
    
    public function getList($lang, $uid, $status)
    {
        [$page, $limit] = $this->getPageValue();
        $where = [];
        $where[] = ['uid', '=', $uid];
        if($status){
            $status = $status - 1;
            $where[] = ['status', '=', $status];
        }
        $list = $this->getModel()->with(['product'])->field('id,order_no,cart_num,add_time,status,total,price,image,title,product_id,refund_status,sku')->where($where)->order('add_time','desc')->page($page, $limit)->select()->toArray();
        $name = 'store_name'.$this->lang[$lang];
        foreach ($list as &$item){
            if($item['product']){
                $item['title'] = $item['product'][$name];
            }
            $item['date_time'] = date('Y-m-d H:i:s', $item['add_time']);
            unset($item['product']);
        }
        return compact('list');
    }
    
    
    public function getMerList($lang, $mer_id, $status, $is_caigou, $keyword = '')
    {
        [$page, $limit] = $this->getPageValue();
        $where = [];
        $where[] = ['mer_id', '=', $mer_id];
        if($status==-1){
            $where[] = ['status', '=', -1];
        }
        if($is_caigou==1){
            $where[] = ['is_caigou', '=', 0];
        }
        if($is_caigou==2){
            $where[] = ['is_caigou', '=', 1];
        }
        if($status>0){
            $status = $status - 1;
            $where[] = ['status', '=', $status];
        }
        $name = 'store_name'.$this->lang[$lang];
        if($keyword){
            if (preg_match('/^O\d{18}$/', $keyword)) {
                $where[] = ['order_no', '=', $keyword];
            }else{
                $where[] = ['title', 'LIKE', "%$keyword%"];
            }
        }
        $list = $this->getModel()->with(['product'])->field('id,order_no,cart_num,add_time,status,total,price,image,title,product_id,refund_status,sku,is_caigou,refund_status')->where($where)->order('add_time','desc')->page($page, $limit)->select()->toArray();
        $mer = app()->make(MerchantServices::class)->getMer($mer_id);
        foreach ($list as &$item){
            if($item['product']){
                $item['title'] = $item['product'][$name];
            }
            $item['date_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $rate = bcdiv((string)$mer['spread_ratio'], '100', 4);
            $rate_cost = bcsub('1', (string)$rate, 4);
            $item['mer_cost'] = bcmul((string)$rate_cost, (string)$item['total'], 2);
            unset($item['product']);
        }
        return compact('list');
    }
    
    public function caigou($mer_id, $id)
    {
        return $this->transaction(function() use($mer_id, $id){
            $order = $this->getModel()->where('id', $id)->find();
            if(empty($order)||$order->mer_id!=$mer_id){
                throw new ApiException('Incorrect parameters');
            }
            if($order->is_caigou==1){
                return true;
            }
            $MerchantServices = app()->make(MerchantServices::class);
            $mer = $MerchantServices->getModel()->where('id', $mer_id)->lock(true)->find();
            $total = $order->total;
            $rate = bcdiv((string)$mer['spread_ratio'], '100', 4);
            $rate_cost = bcsub('1', (string)$rate, 4);
            $mer_cost = bcmul((string)$rate_cost, (string)$total, 2);
            if($mer['mer_money']<$mer_cost){
                throw new ApiException('Insufficient balance');
            }
            $time = time();
            $mer_profit = bcsub((string)$total, (string)$mer_cost, 2);
            $res = $this->getModel()->where('id', $id)->update([
                    'is_caigou'=>1,
                    'caigou_time'=>$time,
                    'rates'=>$mer['spread_ratio'],
                    'mer_cost'=>$mer_cost,
                    'mer_profit'=>$mer_profit
                ]);
            if(!$res){
                throw new ApiException('The system is busy, please try again later');
            }
            $update = [];
            $update['mer_money'] = bcsub((string)$mer['mer_money'], (string)$mer_cost, 2);
            $res_user = $MerchantServices->getModel()->where('id', $mer_id)->update($update);
            if(!$res_user){
                throw new ApiException('The system is busy, please try again later');
            }
            $MerchantMoneylogServices = app()->make(MerchantMoneylogServices::class);
            $res_userlog = $MerchantMoneylogServices->addMoneylog([
                    'type'=>0,
                    'uid'=>$mer_id,
                    'state'=>2,
                    'title'=>'Purchase',
                    'amount'=>$mer_cost,
                    'money'=>$update['mer_money'],
                    'add_time'=>$time,
                    'remark'=>$id
                ]);
            if(!$res_userlog){
                throw new ApiException('The system is busy, please try again later');
            }
            $rand = rand(10, 60);
            FahuoJob::dispatchSece($rand, [$id]);
            return true;
        });
    }
    
    public function refund($uid, $id)
    {
        $order = $this->getModel()->where('id', $id)->find();
        if(empty($order)||$order->uid!=$uid){
            throw new ApiException('Incorrect parameters');
        }
        return $this->getModel()->where('id', $id)->update([
                'status'=>3,
                'refund_time'=>time()
            ]);
    }
    
    public function recived($uid, $id)
    {
        $order = $this->getModel()->where('id', $id)->find();
        if(empty($order)||$order->uid!=$uid){
            throw new ApiException('Incorrect parameters');
        }
        return $this->getModel()->where('id', $id)->update([
                'status'=>2,
                'recived_time'=>time()
            ]);
    }
    
    
    public function setrefund($mer_id, $id, $status)
    {
        return $this->transaction(function() use($mer_id, $id, $status){
            $order = $this->getModel()->where('id', $id)->find();
            if(empty($order)||$order->mer_id!=$mer_id||$order->status!=3){
                throw new ApiException('Incorrect parameters');
            }
            if($status==1){
                $res = $this->getModel()->where('id', $id)->update([
                    'refund_status'=>1
                ]);
                if(!$res){
                    throw new ApiException('The system is busy, please try again later');
                }
                
                $total = $order->total;
                $time = time();
                if($order->is_caigou==1){
                    $MerchantServices = app()->make(MerchantServices::class);
                    $mer = $MerchantServices->getModel()->where('id', $mer_id)->lock(true)->find();
                    $mer_cost = $order->mer_cost;
                    ///给商家退款
                    $update = [];
                    $update['mer_money'] = bcadd((string)$mer['mer_money'], (string)$mer_cost, 2);
                    $res_mer = $MerchantServices->getModel()->where('id', $mer_id)->update($update);
                    if(!$res_mer){
                        throw new ApiException('The system is busy, please try again later');
                    }
                    $MerchantMoneylogServices = app()->make(MerchantMoneylogServices::class);
                    $res_merrlog = $MerchantMoneylogServices->addMoneylog([
                            'type'=>7,
                            'uid'=>$mer_id,
                            'state'=>1,
                            'title'=>'Refund order number '.$order->order_no,
                            'amount'=>$mer_cost,
                            'money'=>$update['mer_money'],
                            'add_time'=>$time,
                            'remark'=>$id
                        ]);
                    if(!$res_merrlog){
                        throw new ApiException('The system is busy, please try again later');
                    }
                }
                
                $uid = $order->uid;
                $UserServices = app()->make(UserServices::class);
                $user = $UserServices->getModel()->where('id', $uid)->lock(true)->find();
                ///给用户退款
                $update = [];
                $update['money'] = bcadd((string)$user['money'], (string)$total, 2);
                $res_user = $UserServices->getModel()->where('id', $uid)->update($update);
                if(!$res_user){
                    throw new ApiException('The system is busy, please try again later');
                }
                $UserMoneylogServices = app()->make(UserMoneylogServices::class);
                $res_userlog = $UserMoneylogServices->addMoneylog([
                        'type'=>7,
                        'uid'=>$uid,
                        'state'=>1,
                        'title'=>'Refund order number '.$order->order_no,
                        'amount'=>$total,
                        'money'=>$update['money'],
                        'add_time'=>$time,
                        'remark'=>$id
                    ]);
                if(!$res_userlog){
                    throw new ApiException('The system is busy, please try again later');
                }
                
                
                return true;
            }else{
                return $this->getModel()->where('id', $id)->update([
                        'refund_status'=>2,
                        'status'=>2
                    ]);
            }
        });
    }
    
}

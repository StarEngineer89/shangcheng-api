<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\model\Cart;
use think\facade\Log;
use core\exceptions\ApiException;

/**
 * Class CartServices
 * @package app\services
 * @mixin Cart
 */
class CartServices extends BaseServices
{
    protected function setModel(): string
    {
        return Cart::class;
    }
    
    
    public function cartnum($uid)
    {
        return $this->getModel()->where('uid', $uid)->where('is_buy', 0)->count();
    }
    
    public function addCart($uid, $pro_id, $sku_id, $cart_num, $is_buy, $mer_id = 0, $sku_str = '')
    {
        $cart = $this->getModel()->where('uid', $uid)->where('product_id', $pro_id)->where('sku_id', $sku_id)->find();
        if(!empty($cart)){
            $cart = $cart->toArray();
            if($cart['sku']!=$sku_str){
                $res = $this->getModel()->create([
                   'uid'=>$uid,
                   'product_id'=>$pro_id,
                   'sku_id'=>$sku_id,
                   'cart_num'=>$cart_num,
                   'mer_id'=>$mer_id,
                   'is_buy'=>$is_buy,
                   'sku'=>$sku_str
                ]);
                if(!$res){
                    throw new ApiException('The system is busy, please try again later');
                }
                return $res->toArray();
            }
            if($cart['cart_num']==$cart_num&&$cart['is_buy']==$is_buy){
                return $cart;
            }
            $update = ['cart_num'=>$cart_num, 'is_buy'=>$is_buy];
            $res = $this->getModel()->where('id', $cart['id'])->update($update);
            if(!$res){
                throw new ApiException('The system is busy, please try again later');
            }
            $cart['cart_num']=$cart_num;
            return $cart;
        }else{
            if($is_buy==1){
                $this->getModel()->where('uid', $uid)->where('is_buy', 1)->delete();
            }
            $res = $this->getModel()->create([
                   'uid'=>$uid,
                   'product_id'=>$pro_id,
                   'sku_id'=>$sku_id,
                   'cart_num'=>$cart_num,
                   'mer_id'=>$mer_id,
                   'is_buy'=>$is_buy,
                   'sku'=>$sku_str
                ]);
            if(!$res){
                throw new ApiException('The system is busy, please try again later');
            }
            return $res->toArray();
        }
    }
    
    
    public function getCart($lang, $uid, $cart_id = [])
    {
        $where = [];
        $where[] = ['uid', '=', $uid];
        if(empty($cart_id)){
           $where[] = ['is_buy', '=', 0];
        }else{
           $where[] = ['id', 'in', $cart_id];
           if(count($cart_id)>1){
              $where[] = ['is_buy', '=', 0];
           }
        }
        $list = $this->getModel()->with(['product', 'mer', 'attr'])->where($where)->order('mer_id', 'desc')->select()->toArray();
        $result = [];
        $name = 'store_name'.$this->lang[$lang];
        foreach ($list  as $item){
            $mId = $item['mer_id'];
            $product = $item['product'];
            if($product){
                if (!isset($result[$mId])) {
                    $result[$mId] = [
                        'mer_id' => $mId,
                        'mer_name'=>$item['mer_name'],
                        'mer_avatar'=>$item['mer_avatar'],
                        'products' => []
                    ];
                }
                if($item['attr']){
                    $sku = $item['attr'];
                    if($sku['image']){
                      $product['image'] = $sku['image'];
                    }
                    $product['price'] = $sku['price'];
                }
                $result[$mId]['products'][] = [
                       'id'=>$product['id'],
                       'cart_id'=>$item['id'],
                       'title'=>$product[$name],
                       'image'=>$product['image'],
                       'price'=>$product['price'],
                       'cart_num'=>$item['cart_num'],
                       'sku'=>$item['sku']
                    ];
            }
        }
        $cartlist = array_values($result);
        return $cartlist;
    }
    
    
    public function delCart($uid, $cart_ids)
    {
        $list = $this->getModel()->whereIn('id', $cart_ids)->where('uid', $uid)->select()->toArray();
        if(count($list)!=count($cart_ids)){
            throw new ApiException('Incorrect parameters');
        }
        return $this->getModel()->whereIn('id', $cart_ids)->where('uid', $uid)->delete();
    }
    
    public function setCartNum($uid, $cart_id, $num)
    {
        $cart = $this->getModel()->whereIn('id', $cart_id)->where('uid', $uid)->find();
        if(empty($cart)){
            throw new ApiException('Incorrect parameters');
        }
        $cart->cart_num = $num;
        $cart->save();
        return true;
    }

}

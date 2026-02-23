<?php
declare (strict_types=1);
namespace app\services\user;

use app\services\BaseServices;
use app\model\UserFav;
use think\facade\Log;

/**
 * Class UserFavServices
 * @package app\services
 * @mixin UserFav
 */
class UserFavServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserFav::class;
    }
    
    public function favCount($uid)
    {
        return $this->getModel()->where('uid', $uid)->count();
    }
    
    public function favProduct($uid, $pro_id)
    {
        return $this->getModel()->where('uid', $uid)->where('product_id', $pro_id)->count();
    }
    
    public function favPro($uid, $pro_id)
    {
        return $this->getModel()->create([
                'uid'=>$uid,
                'product_id'=>$pro_id,
                'add_time'=>time()
            ]);
    }
    
    public function unFavPro($uid, $pro_id)
    {
        return $this->getModel()->where('uid', $uid)->where('product_id', $pro_id)->delete();
    }
    
    public function favDel($uid, $id){
        return $this->getModel()->where('uid', $uid)->where('id', $id)->delete();
    }
    
    public function getFavList($lang, $uid)
    {
        [$page, $limit] = $this->getPageValue();
        $_list = $this->getModel()->with(['product'])->where('uid', $uid)->order('add_time', 'desc')->page($page, $limit)->select()->toArray();
        $list = [];
        $name = 'store_name'.$this->lang[$lang];
        foreach ($_list as $item){
            if($item['product']){
               $product = $item['product'];
               $list[] = [
                      'id'=>$item['id'],
                      'title'=>$product[$name],
                      'image'=>$product['image'],
                      'price'=>$product['price'],
                      'product_id'=>$product['id'],
                   ];
            }
        }
        return compact('list');
    }

}

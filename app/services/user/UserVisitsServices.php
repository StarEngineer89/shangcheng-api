<?php
declare (strict_types=1);
namespace app\services\user;

use app\services\BaseServices;
use app\services\product\ProductServices;
use app\model\UserVisits;
use think\facade\Log;

/**
 * Class UserVisitsServices
 * @package app\services
 * @mixin UserVisits
 */
class UserVisitsServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserVisits::class;
    }
    
    public function addVisits($uid, $product_id, $mer_id = 0)
    {
        $visit = $this->getModel()->where('uid', $uid)->where('product_id', $product_id)->find();
        if(empty($visit)){
            return $this->getModel()->create([
                    'uid'=>$uid,
                    'mer_id'=>$mer_id,
                    'product_id'=>$product_id,
                    'add_time'=>time()
                ]);
        }else{
            return $this->getModel()->where('id', $visit->id)->update(['add_time'=>time()]);
        }
    }
    
    
    public function getList($uid)
    {
        $list = $this->getModel()->with(['product'])->where('uid', $uid)->order('add_time', 'desc')->limit(10)->select()->toArray();
        $nlist = [];
        $delete_id = [];
        foreach ($list as $item){
            if(!empty($item['product'])){
                $nlist[] = [
                       'id'=>$item['product_id'],
                       'image'=>$item['product']['image'],
                    ];
            }else{
                $delete_id[] = $item['id'];
            }
        }
        if($delete_id){
           $this->getModel()->whereIn('id', $delete_id)->delete();
        }
        return $nlist;
    }
    
}

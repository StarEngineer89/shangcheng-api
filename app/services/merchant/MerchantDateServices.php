<?php
declare (strict_types=1);
namespace app\services\merchant;

use app\services\BaseServices;
use app\model\MerchantDate;
use core\exceptions\ApiException;

/**
 * Class MerchantDateServices
 * @package app\services
 * @mixin MerchantDate
 */
class MerchantDateServices extends BaseServices
{
    protected function setModel(): string
    {
        return MerchantDate::class;
    }
    
    
    
    public function addDate($mer)
    {
        $this->transaction(function() use($mer){
            $num = rand($mer['min_nums'], $mer['max_nums']);
            $today = strtotime(date('Y-m-d'));
            $item = $this->getModel()->where('uid', $mer['id'])->where('add_time', '>=', $today)->find();
            if(empty($item)){
                $this->getModel()->create([
                       'uid'=>$mer['id'],
                       'nums'=>$num,
                       'add_time'=>time()
                    ]);
            }else{
                $nums = $num + $item->nums;
                $this->getModel()->where('id',$item->id)->update([
                    'nums'=>$nums,
                    'add_time'=>time()
                ]);
            }
        });
    }

}
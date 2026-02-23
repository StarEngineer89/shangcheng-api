<?php
declare (strict_types=1);
namespace app\services\merchant;

use app\services\BaseServices;
use app\model\MerchantCategory;
use think\facade\Log;

/**
 * Class MerchantCategoryServices
 * @package app\services
 * @mixin MerchantCategory
 */
class MerchantCategoryServices extends BaseServices
{
    protected function setModel(): string
    {
        return MerchantCategory::class;
    }
    
    public function merCategory($lang)
    {
        $name = 'category_name'.$this->lang[$lang];
        $list = $this->getModel()->field("id,{$name} as name")->cache(600)->order('id', 'asc')->select()->toArray();
        return compact('list');
    }
    
    public function catename($lang, $cate_id)
    {
        $name = 'category_name'.$this->lang[$lang];
        return $this->getModel()->where('id', $cate_id)->value($name);
    }

}

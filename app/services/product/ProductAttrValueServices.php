<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\model\ProductAttrValue;
use think\facade\Log;

/**
 * Class ProductAttrValueServices
 * @package app\services
 * @mixin ProductAttrValue
 */
class ProductAttrValueServices extends BaseServices
{
    protected function setModel(): string
    {
        return ProductAttrValue::class;
    }
    
    public function getSku($sku_id)
    {
        return $this->getModel()->where('id', $sku_id)->find();
    }

}

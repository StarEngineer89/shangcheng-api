<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\model\ProductAttr;
use think\facade\Log;

/**
 * Class ProductAttrServices
 * @package app\services
 * @mixin ProductAttr
 */
class ProductAttrServices extends BaseServices
{
    protected function setModel(): string
    {
        return ProductAttr::class;
    }
    
    

}

<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\model\ProductContent;
use think\facade\Log;

/**
 * Class ProductContentServices
 * @package app\services
 * @mixin ProductContent
 */
class ProductContentServices extends BaseServices
{
    protected function setModel(): string
    {
        return ProductContent::class;
    }
    
    

}

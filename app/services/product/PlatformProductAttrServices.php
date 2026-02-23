<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\model\PlatformProductAttr;
use think\facade\Log;

/**
 * Class PlatformProductAttrServices
 * @package app\services
 * @mixin ProductAttr
 */
class PlatformProductAttrServices extends BaseServices
{
    protected function setModel(): string
    {
        return PlatformProductAttr::class;
    }
    
    

}

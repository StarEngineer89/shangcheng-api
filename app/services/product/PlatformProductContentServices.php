<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\model\PlatformProductContent;
use think\facade\Log;

/**
 * Class PlatformProductContentServices
 * @package app\services
 * @mixin PlatformProductContent
 */
class PlatformProductContentServices extends BaseServices
{
    protected function setModel(): string
    {
        return PlatformProductContent::class;
    }
    
    

}

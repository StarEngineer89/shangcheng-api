<?php
namespace app\jobs\order;

use app\services\product\PlatformProductServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

class AddproJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($mer_id, $item)
    {
        $PlatformProductServices = app()->make(PlatformProductServices::class);
		$PlatformProductServices->addPro($mer_id, $item);
		return true;
    }

}
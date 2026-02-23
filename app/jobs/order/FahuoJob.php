<?php
namespace app\jobs\order;

use app\services\order\OrderServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

class FahuoJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($order_id)
    {
        $OrderServices = app()->make(OrderServices::class);
		$OrderServices->fahuoorder($order_id);
		return true;
    }

}

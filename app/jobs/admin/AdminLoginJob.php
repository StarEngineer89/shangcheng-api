<?php
namespace app\jobs\admin;

use app\services\admin\AdminServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

class AdminLoginJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($uid,$ip)
    {

        /** @var AdminServices $AdminServices */
        $AdminServices = app()->make(AdminServices::class);
		$city = $AdminServices->convertIp($ip);
		if ($city) {
		    $userInfo = $AdminServices->loginCity((int)$uid, $city);
		}
		return true;
    }

}

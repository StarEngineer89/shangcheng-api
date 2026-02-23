<?php
namespace app\jobs\user;

use app\services\user\UserServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

class UserLoginJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($uid,$ip)
    {

        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
		$city = $userServices->convertIp($ip);
		if ($city) {
			$userInfo = $userServices->loginCity($uid, $city);
		}
		return true;
    }

}

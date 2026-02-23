<?php
namespace app\jobs\user;

use app\services\user\UserLoginlogServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

class UserLoginlogJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($uid, $time, $ip, $is_fail, $content)
    {
        /** @var UserLoginlogServices $userLoginlogServices */
        $userLoginlogServices = app()->make(UserLoginlogServices::class);
		$userLoginlogServices->setLoginlog($uid, $time, $ip, $is_fail, $content);
		return true;
    }

}

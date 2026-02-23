<?php
namespace app\jobs\admin;

use app\services\admin\AdminLoginlogServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

class AdminLoginlogJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($uid, $time, $ip, $is_fail, $content)
    {
        /** @var AdminLoginlogServices $AdminLoginlogServices */
        $AdminLoginlogServices = app()->make(AdminLoginlogServices::class);
		$AdminLoginlogServices->setLoginlog((int)$uid, (int)$time, (string)$ip, (int)$is_fail, (string)$content);
		return true;
    }

}

<?php
namespace app\jobs\admin;

use app\services\admin\AdminAclogServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

class AdminAclogJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($uid, $time, $ip='', $content='')
    {

        /** @var AdminAclogServices $AdminAclogServices */
        $AdminAclogServices = app()->make(AdminAclogServices::class);
		$AdminAclogServices->setAclog($uid, $time, $ip, $content);
		return true;
    }

}

<?php
namespace app\jobs\user;

use app\services\user\RoomUserloginServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

class UserLoginRoomJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($uid, $room_id, $time, $ip, $from)
    {
        $RoomUserloginServices = app()->make(RoomUserloginServices::class);
		$RoomUserloginServices->setLoginlog($uid, $room_id, $time, $ip, $from);
		return true;
    }

}

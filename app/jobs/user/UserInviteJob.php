<?php
namespace app\jobs\user;

use app\services\user\UserInviteServices;
use core\basic\BaseJobs;
use core\traits\QueueTrait;
use think\facade\Log;

/**
 * 用户推广关系
 * Class UserInviteJob
 * @package app\jobs
 */
class UserInviteJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 记录用户推广关系
     * @param int $uid
     * @param int $inviteUid
     * @return bool
     */
    public function doJob($room_id, $uid, $inviteUid, $time)
    {
        if (!$uid || !$inviteUid || !$room_id || $uid == $inviteUid) {
            return true;
        }
        
        /** @var UserInviteServices $userInviteServices */
        $userInviteServices = app()->make(UserInviteServices::class);
        //记录
        $userInviteServices->setInvite($room_id, $uid, $inviteUid, $time);
        
        return true;
    }
}
<?php
namespace app\listener\user;

use app\jobs\user\UserInviteJob;
use core\interfaces\ListenerInterface;

/**
 * 用户推荐关系绑定事件
 * Class UserInvite
 * @package app\listener
 */
class UserInvite implements ListenerInterface
{
    public function handle($event): void
    {
        [$room_id, $uid, $inviteUid, $time] = $event;
        
        UserInviteJob::dispatch([$room_id, $uid, $inviteUid, $time]);

    }
}

<?php
namespace app\listener\user;

use app\jobs\user\UserLoginRoomJob;
use core\interfaces\ListenerInterface;

/**
 * 用户登录房间日志事件
 * Class UserLoginRoom
 * @package app\listener
 */
class UserLoginRoom implements ListenerInterface
{
    /**
     * 房间登录完成后置事件
     * @param $event
     */
    public function handle($event): void
    {
        [$uid, $room_id, $time, $ip, $from] = $event;
        try {
            UserLoginRoomJob::dispatch([$uid, $room_id, $time, $ip, $from]);
        } catch (\Throwable $e) {
        }
    }

}

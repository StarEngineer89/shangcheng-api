<?php
namespace app\listener\user;

use app\jobs\user\UserLoginlogJob;
use core\interfaces\ListenerInterface;

/**
 * 用户登录日志事件
 * Class UserLoginlog
 * @package app\listener
 */
class UserLoginlog implements ListenerInterface
{
    /**
     * 房间登录完成后置事件
     * @param $event
     */
    public function handle($event): void
    {
        [$uid, $time, $ip, $is_fail, $content] = $event;
        try {
            UserLoginlogJob::dispatch([$uid, $time, $ip, $is_fail, $content]);
        } catch (\Throwable $e) {
        }
    }

}

<?php
namespace app\listener\user;

use app\jobs\user\UserLoginJob;
use core\interfaces\ListenerInterface;

/**
 * 登录完成后置事件
 * Class Login
 * @package app\listener
 */
class UserLogin implements ListenerInterface
{
    /**
     * 登录完成后置事件
     * @param $event
     */
    public function handle($event): void
    {
        [$uid, $ip] = $event;
        try {
            UserLoginJob::dispatch([$uid, $ip]);
        } catch (\Throwable $e) {

        }
    }

}

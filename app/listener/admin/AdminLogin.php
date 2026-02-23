<?php
namespace app\listener\admin;

use app\jobs\admin\AdminLoginJob;
use core\interfaces\ListenerInterface;

/**
 * 管理登录完成后置事件
 * Class AdminLogin
 * @package app\listener
 */
class AdminLogin implements ListenerInterface
{
    /**
     * 登录完成后置事件
     * @param $event
     */
    public function handle($event): void
    {
        [$uid, $ip] = $event;
        try {
            AdminLoginJob::dispatch([$uid, $ip]);
        } catch (\Throwable $e) {

        }
    }

}

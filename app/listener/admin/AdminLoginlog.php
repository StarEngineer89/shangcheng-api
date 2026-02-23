<?php
namespace app\listener\admin;

use app\jobs\admin\AdminLoginlogJob;
use core\interfaces\ListenerInterface;

/**
 * 后台登录日志事件
 * Class AdminLoginlog
 * @package app\listener
 */
class AdminLoginlog implements ListenerInterface
{
    /**
     * 房间登录完成后置事件
     * @param $event
     */
    public function handle($event): void
    {
        [$uid, $time, $ip, $is_fail, $content] = $event;
        try {
            AdminLoginlogJob::dispatch([$uid, $time, $ip, $is_fail, $content]);
        } catch (\Throwable $e) {
        }
    }

}

<?php
namespace app\listener\admin;

use app\jobs\admin\AdminAclogJob;
use core\interfaces\ListenerInterface;

/**
 * 用后台操作后置事件
 * Class AdminAclog
 * @package app\listener
 */
class AdminAclog implements ListenerInterface
{
    /**
     * 用户操作后置事件
     * @param $event
     */
    public function handle($event): void
    {
        [$uid, $content, $ip] = $event;
        try {
            $time = time();
            AdminAclogJob::dispatch([$uid, $time, $ip, $content]);
        } catch (\Throwable $e) {

        }
    }

}

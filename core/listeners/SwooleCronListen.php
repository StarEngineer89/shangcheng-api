<?php
namespace core\listeners;

use core\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * swoole 定时任务
 */
class SwooleCronListen implements ListenerInterface
{

    public function handle($event): void
    {
        try {
            event('crontab');//app/event.php 里面配置事件
        } catch (\Throwable $e) {
            Log::error('监听定时器报错: ' . $e->getMessage());
        }
        
    }
}
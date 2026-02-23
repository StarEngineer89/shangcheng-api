<?php
namespace core\listeners;

use core\interfaces\ListenerInterface;
use core\utils\Start;
use think\swoole\Manager;
use Swoole\Process\Pool;

/**
 * swoole 初始化
 */
class InitSwooleLockListen implements ListenerInterface
{

    public function handle($event): void
    {
        //启动时输出内容
        app()->make(Start::class)->show();
        //增加定时任务进程
        app()->make(Manager::class)->addBatchWorker(1, [$this, 'createCronServer'], 'cron server');
    }

    /**
     * 创建定时任务服务 - 单独启动一个进程做为定时任务执行进程
     * @author Mrbruce
     */
    public function createCronServer(Pool $pool, $workerId)
    {
        event('crontab', $workerId);
    }
}
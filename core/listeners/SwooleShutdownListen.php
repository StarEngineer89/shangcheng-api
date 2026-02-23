<?php
namespace core\listeners;


use core\interfaces\ListenerInterface;
use Swoole\Timer;

/**
 * swoole 停止
 */
class SwooleShutdownListen implements ListenerInterface
{

    public function handle($event): void
    {
        Timer::clearAll();
    }
}

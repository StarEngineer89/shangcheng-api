<?php
namespace core\listeners;


use core\interfaces\ListenerInterface;
use core\services\CacheService;
/**
 * swoole停止监听
 * Class SwooleStopListen
 * @package core\listeners
 */
class SwooleStopListen implements ListenerInterface
{

    /**
     * 事件执行
     * @param $event
     */
    public function handle($event): void
    {
    }
}

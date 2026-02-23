<?php
namespace core\listeners;


use core\interfaces\ListenerInterface;
use core\services\CacheService;
/**
 * swoole启动监听
 * Class SwooleStartListen
 * @package core\listeners
 */
class SwooleStartListen implements ListenerInterface
{

    /**
     * 事件执行
     * @param $event
     */
    public function handle($event): void
    {
    }
}

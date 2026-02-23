<?php
namespace core\traits;

use think\facade\Queue;

/**
 * 快捷加入消息队列
 * Trait QueueTrait
 * @package core\traits
 */
trait QueueTrait
{

    /**
     * 错误次数
     * @var int
     */
    protected static int $errorCount = 3;

    /**
     * @var string
     */
    protected static string $defaultDo = 'doJob';

    /**
     * 列名
     * @return string
     */
    protected static function queueName()
    {
        return null;
    }

    /**
     * 加入队列
     * @param $action
     * @param array $data
     * @param string|null $queueName
     * @return mixed
     */
    public static function dispatch($action = null, array $data = [], string $queueName = null)
    {
        $do = '';
        if (is_array($action)) {
            $data = $action;
        } else if (is_string($action)) {
            $do = $action;
        }

        if (self::queueName()) {
            $queueName = self::queueName();
        }

        if (!$do) {
            $do = self::$defaultDo;
        }

        return self::queuePush($do, $data, null, $queueName);
    }

    /**
     * 延迟加入消息队列
     * @param int $secs
     * @param $action
     * @param array $data
     * @param string|null $queueName
     * @return mixed
     */
    public static function dispatchSece(int $secs, $action = null, array $data = [], string $queueName = null)
    {
        $do = '';

        if (is_array($action)) {
            $data = $action;
        } else if (is_string($action)) {
            $do = $action;
        }

        if (self::queueName()) {
            $queueName = self::queueName();
        }

        if (!$do) {
            $do = self::$defaultDo;
        }

        return self::queuePush($do, $data, $secs, $queueName);
    }

    /**
     * 加入小队列
     * @param string $do
     * @param array $data
     * @param int|null $secs
     * @param string|null $queueName
     * @return mixed
     */
    public static function dispatchDo(string $do, array $data = [], int $secs = null, string $queueName = null)
    {
        if (self::queueName()) {
            $queueName = self::queueName();
        }
        return self::queuePush($do, $data, $secs, $queueName);
    }

    /**
     * 放入消息队列
     * @param string $do
     * @param array $data
     * @param int|null $secs
     * @param string|null $queueName
     * @return mixed|null
     */
    protected static function queuePush(string $do, array $data = [], int $secs = null, string $queueName = null)
    {
        $job = __CLASS__ . '@fire';

        $jobData = [
            'data' => $data,
            'do' => $do,
            'errorCount' => self::$errorCount,
        ];

        if ($secs) {
            $res = Queue::later($secs, $job, $jobData, $queueName);
        } else {
            $res = Queue::push($job, $jobData, $queueName);
        }

        return $res;
    }
}

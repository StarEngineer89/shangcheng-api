<?php
namespace core\services;

use think\facade\Cache as CacheStatic;

/**
 * core 缓存类
 * Class CacheService
 * @package core\services
 * @mixin \Redis
 */
class CacheService
{

    const CACHE_EXPIRE_NAME = 'cache_expire';

    //副屏相关
    const CASHIER_AUX_SCREEN_TAG = 'auxScreen';

    /**
     * 标签名
     * @var string
     */
    protected static $globalCacheName = '_cached_1515146130';


    /**
     * 过期时间
     * @var int
     */
    protected static $expire;

    /**
     * 获取缓存过期时间
     * @param int|null $expire
     * @return int
     */
    protected static function getExpire(int $expire = null): int
    {
        if (self::$expire) {
            return (int)self::$expire;
        }
        $expire = !is_null($expire) ? $expire : CacheStatic::store('redis')->get(self::CACHE_EXPIRE_NAME, 360);
        if (!is_int($expire))
            $expire = (int)$expire;
        return self::$expire = $expire;
    }

    /**
     * 判断缓存是否存在
     * @param string $name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function has(string $name): bool
    {
        try {
            return CacheStatic::has($name);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 写入缓存
     * @param string $name 缓存名称
     * @param mixed $value 缓存值
     * @param int $expire 缓存时间，为0读取系统缓存时间
     * @return bool
     */
    public static function set(string $name, $value, int $expire = null): bool
    {
        try {
            if($expire){
               return self::handler()->set($name, $value, $expire);
            }else{
               return self::handler()->set($name, $value);
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 如果不存在则写入缓存
     * @param string $name
     * @param bool $default
     * @return mixed
     */
    public static function remember(string $name, $default = false, int $expire = null)
    {
        try {
            return self::handler()->remember($name, $default, $expire ?? self::getExpire($expire));
        } catch (\Throwable $e) {
            try {
                if (is_callable($default)) {
                    return $default();
                } else {
                    return $default;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    /**
     * 删除缓存
     * @param string $name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function delete(string $name)
    {
        return CacheStatic::delete($name);
    }
    

    /**
     * 缓存句柄
     *
     * @return \think\cache\TagSet|CacheStatic
     */
    public static function handler(?string $cacheName = null)
    {
        return CacheStatic::tag($cacheName ?: self::$globalCacheName);
    }

    /**
     * 清空缓存池
     * @return bool
     */
    public static function clear()
    {
        return self::handler()->clear();
    }

    /**
     * Redis缓存句柄
     *
     * @return \think\cache\TagSet|CacheStatic
     */
    public static function redisHandler($type = null)
    {
        if ($type) {
            return CacheStatic::store('redis')->tag($type);
        } else {
            return CacheStatic::store('redis');
        }
    }

    /**
     * 放入令牌桶
     * @param string $key
     * @param array $value
     * @param string $type
     * @return bool
     */
    public static function setTokenBucket(string $key, $value, $expire = null, string $type = 'admin')
    {
        try {
            $redisCahce = self::redisHandler($type);
            return $redisCahce->set($key, $value, $expire);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除所有令牌桶
     * @param string $type
     * @return bool
     */
    public static function clearTokenAll(string $type = 'admin')
    {
        try {
            return self::redisHandler($type)->clear();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除令牌桶
     * @param string $type
     * @return bool
     */
    public static function clearToken(string $key)
    {
        try {
            return self::redisHandler()->delete($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 查看令牌是否存在
     * @param string $key
     * @return bool
     */
    public static function hasToken(string $key)
    {
        try {
            return self::redisHandler()->has($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取token令牌桶
     * @param string $key
     * @return mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getTokenBucket(string $key)
    {
        try {
            return self::redisHandler()->get($key, null);
        } catch (\Throwable $e) {
            return null;
        }
    }


    /**
     * 检查锁
     * @param int $uid
     * @param int $timeout
     * @return bool
     */
    public static function setMutex(string $key, int $timeout = 10)
    {
        $curTime = time();
        $readMutexKey = "redis:mutex:{$key}";
        $mutexRes = self::redisHandler()->handler()->setnx($readMutexKey, $curTime + $timeout);
        if ($mutexRes) {
            return true;
        }
        //就算意外退出，下次进来也会检查key，防止死锁
        $time = self::redisHandler()->handler()->get($readMutexKey);
        if ($curTime > $time) {
            self::redisHandler()->handler()->del($readMutexKey);
            return self::redisHandler()->handler()->setnx($readMutexKey, $curTime + $timeout);
        }
        return false;
    }

    /**
     * 删除锁
     * @param $uid
     */
    public static function delMutex(string $key)
    {
        $readMutexKey = "redis:mutex:{$key}";
        self::redisHandler()->handler()->del($readMutexKey);
    }
    
    public static function incrbyAmount($key, $amount)
    {
        $amount = bcmul((string)$amount, '100');
        $result = app()->make(LockService::class)->incrbyAmount($key, $amount);
        if($result){
            if($result[0]!=-1&&$result[1]!=-2){
                $result[0] = bcdiv((string)$result[0], '100', 2);
            }
            $result[1] = bcdiv((string)$result[1], '100', 2);
            return $result;
        }else{
            return [-1, 0];
        }
    }

	/**
	 * 数据库锁
	 * @param $key
	 * @param $fn
	 * @param int $ex
	 * @return bool|mixed
	 */
	public static function lock($key, $fn = [], int $ex = 10)
	{
		$service = app()->make(LockService::class);
		if ($fn instanceof \Closure) {
			return $service->exec($key, $fn, $ex);
		} else {
			return $service->lock($key, 1, $ex);
		}
	}

	/**
	 * 销毁锁
	 * @param $key
	 * @return bool
	 */
	public static function unLock($key)
	{
		return app()->make(LockService::class)->unlock($key);
	}

    /**
     * 魔术方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::redisHandler()->{$name}(...$arguments);
    }

    /**
     * 魔术方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return self::redisHandler()->{$name}(...$arguments);
    }

}
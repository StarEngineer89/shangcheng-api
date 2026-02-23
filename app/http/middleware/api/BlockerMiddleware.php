<?php
namespace app\http\middleware\api;


use app\Request;
use core\exceptions\ApiException;
use core\interfaces\MiddlewareInterface;
use core\services\CacheService;

/**
 * reids锁
 * Class BlockerMiddleware
 * @author MrBruce
 * @package app\http\middleware\api
 */
class BlockerMiddleware implements MiddlewareInterface
{

    /**
     * @param Request $request
     * @param \Closure $next
     */
    public function handle(Request $request, \Closure $next)
    {
        $uid = $request->uid();
        $key = md5($request->rule()->getRule() . $uid. json_encode($request->param()));
        if (!CacheService::setMutex($key)) {
            throw new ApiException('请求太过频繁，请稍后再试');
        }

        $response = $next($request);

        $this->after($response, $key);

        return $response;
    }

    /**
     * @param $response
     * @param $key
     */
    public function after($response, $key)
    {
        CacheService::delMutex($key);
    }
}

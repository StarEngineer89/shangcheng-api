<?php
namespace app\http\middleware\admin;

use app\Request;
use app\services\admin\AdminAuthServices;
use core\exceptions\ApiException;
use core\interfaces\MiddlewareInterface;
use think\exception\DbException;

/**
 * Class AdminAuthTokenMiddleware
 * @package app\admin\middleware
 */
class AdminAuthTokenMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next, bool $force = true)
    {

        $authInfo = null;
        $token = trim(ltrim($request->header('Authori-zation'), 'Bearer'));
        if (!$token) $token = trim(ltrim($request->header('Authorization'), 'Bearer'));//正式版，删除此行，某些服务器无法获取到token调整为 Authori-zation
        try {
            /** @var AdminAuthServices $service */
            $service = app()->make(AdminAuthServices::class);
            $authInfo = $service->parseToken($token);
        } catch (ApiException $e) {
            if ($force)
                return app('json')->make($e->getCode(), $e->getMessage());
        }

        if (!is_null($authInfo)) {
            $request->macro('admin', function (string $key = null) use (&$authInfo) {
                if ($key) {
                    return $authInfo['admin'][$key] ?? '';
                }
                return $authInfo['admin'];
            });
            $request->macro('tokenData', function () use (&$authInfo) {
                return $authInfo['tokenData'];
            });
        }
        $request->macro('isLogin', function () use (&$authInfo) {
            return !is_null($authInfo);
        });
        $request->macro('adminId', function () use (&$authInfo) {
            return is_null($authInfo) ? 0 : (int)$authInfo['admin']->id;
        });

        return $next($request);
    }
}

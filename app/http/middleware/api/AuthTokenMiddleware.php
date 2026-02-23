<?php
namespace app\http\middleware\api;

use app\Request;
use app\services\user\UserAuthServices;
use core\exceptions\ApiException;
use core\interfaces\MiddlewareInterface;
use think\exception\DbException;

/**
 * Class AuthTokenMiddleware
 * @package app\api\middleware
 */
class AuthTokenMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next, bool $force = true, bool $is_mer = false)
    {
        
        $authInfo = null;
        $token = trim(ltrim($request->header('Authori-zation')??'', 'Bearer'));
        $fromType = $request->header('Form-type');
        if (!$token) $token = trim(ltrim($request->header('Authorization')??'', 'Bearer'));//正式版，删除此行，某些服务器无法获取到token调整为 Authori-zation
        try {
            /** @var UserAuthServices $service */
            $service = app()->make(UserAuthServices::class);
            $authInfo = $service->parseToken($token, $fromType);
        } catch (ApiException $e) {
            if ($force)
                return app('json')->make($e->getCode(), $e->getMessage());
        }
        
        if ($is_mer){
            if (!empty($authInfo)) {
                if(!$authInfo['user']['is_mer']){
                    return app('json')->make(410000, 'Unauthorized access');
                }
            }
        }
        
        
        $request->macro('user', function (string $key = null) use (&$authInfo) {
            if (!empty($authInfo)) {
                if ($key) {
                    return $authInfo['user'][$key] ?? '';
                }
                return $authInfo['user'];
            }else{
                return [];
            }
        });
        
        $request->macro('tokenData', function () use (&$authInfo) {
            return !empty($authInfo)?$authInfo['tokenData']:[];
        });
        
        $request->macro('isLogin', function () use (&$authInfo) {
            return !empty($authInfo);
        });
        
        $request->macro('uid', function () use (&$authInfo) {
            return empty($authInfo) ? 0 : (int)$authInfo['user']['id'];
        });
        
        $request->macro('merid', function () use (&$authInfo) {
            return empty($authInfo) ? 0 : (int)$authInfo['user']['mer_id'];
        });

        return $next($request);
    }
}

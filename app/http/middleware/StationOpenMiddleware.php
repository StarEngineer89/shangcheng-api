<?php
namespace app\http\middleware;

use app\Request;
use core\interfaces\MiddlewareInterface;

/**
 * 站点升级
 * Class StationOpenMiddleware
 * @package app\api\middleware
 */
class StationOpenMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next)
    {
        if (!sys_config('site_open', 1)) {
            return app('json')->make('410010', '站点升级中，请稍候访问');
        }
        $lang = $request->header('lang');
        $request->macro('lang', function () use ($lang) {
            $langArr = ['en', 'zh-Hans'];
            return in_array($lang, $langArr)?$lang:'en';
        });
        return $next($request);
    }
}

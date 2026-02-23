<?php
declare (strict_types=1);
namespace app\services\admin;
use app\services\BaseServices;
use app\model\Admin;
use core\exceptions\ApiException;
use core\services\CacheService;
use core\utils\JwtAuth;
use think\annotation\Inject;

/**
 *
 * Class AdminAuthServices
 * @package app\services
 * @mixin Admin
 */
class AdminAuthServices extends BaseServices
{

    protected function setModel(): string
    {
        return Admin::class;
    }

    /**
     * 获取授权信息
     * @param $token
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException\
     */
    public function parseToken($token): array
    {
        $md5Token = is_null($token) ? '' : md5($token);

        if ($token === 'undefined') {
            throw new ApiException('请登录', 410000);
        }
        if (!$token || !$tokenData = CacheService::getTokenBucket($md5Token))
            throw new ApiException('请登录', 410000);

        if (!is_array($tokenData) || empty($tokenData) || !isset($tokenData['uid'])) {
            throw new ApiException('请登录', 410000);
        }

        /** @var JwtAuth $jwtAuth */
        $jwtAuth = app()->make(JwtAuth::class);
        //设置解析token
        [$id, $type, $auth] = $jwtAuth->parseToken($token);
        try {
            $jwtAuth->verifyToken();
        } catch (\Throwable $e) {
            if (!request()->isCli()) CacheService::clearToken($md5Token);
            throw new ApiException('登录已过期,请重新登录', 410000);
        }

        /** @var AdminServices $AdminServices */
        $AdminServices = app()->make(AdminServices::class);
        $user = $AdminServices->getUserInfo($id);
        if (!$user) throw new ApiException('用户不存在，请重新登陆', 410000);
        if (!$user['status'])
            throw new ApiException('您已被禁止登录，请联系管理员', 410000);

        if (!$user || $user->id != $tokenData['uid']) {
            if (!request()->isCli()) CacheService::clearToken($md5Token);
            throw new ApiException('登录状态有误,请重新登录', 410000);
        }
        
        $keyName = 'admin.token.'.$id;
        if (!CacheService::has($keyName)) {
            throw new ApiException('登录已过期,请重新登录', 410000);
        }
        // $cache_token = CacheService::get($keyName);
        // if($cache_token!=$md5Token){
        //     throw new ApiException('您的账号在其他地方登录,请检查账户状态', 99999);
        // }

        //有密码在检测
        if ( $auth !== md5($user['pwd'])) {
            throw new ApiException('登录已过期,请重新登录', 410000);
        }

        $tokenData['type'] = $type;
        $admin = $user;
        return compact('admin', 'tokenData');
    }


}

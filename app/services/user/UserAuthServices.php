<?php
declare (strict_types=1);
namespace app\services\user;

use app\services\BaseServices;
use app\model\User;
use core\exceptions\ApiException;
use core\services\CacheService;
use core\utils\JwtAuth;

/**
 *
 * Class UserAuthServices
 * @package app\services
 * @mixin User
 */
class UserAuthServices extends BaseServices
{

    protected function setModel(): string
    {
        return User::class;
    }

    /**
     * 获取授权信息
     * @param $token
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException\
     */
    public function parseToken($token, $fromType): array
    {
        $md5Token = is_null($token) ? '' : md5($token);

        if ($token === 'undefined') {
            throw new ApiException('Please Log In', 410000);
        }
        if(!$fromType){
            throw new ApiException('Please Log In', 410000); 
        }
        $fromType = strtoupper($fromType);
        if(!in_array($fromType, $this->fromType)){
            throw new ApiException('Please Log In', 410000); 
        }
        if (!$token || !$tokenData = CacheService::getTokenBucket($md5Token))
            throw new ApiException('Please Log In！', 410000);

        if (!is_array($tokenData) || empty($tokenData) || !isset($tokenData['uid'])) {
            throw new ApiException('Please Log In！！', 410000);
        }

        /** @var JwtAuth $jwtAuth */
        $jwtAuth = app()->make(JwtAuth::class);
        //设置解析token
        [$id, $type, $auth] = $jwtAuth->parseToken($token);
        try {
            $jwtAuth->verifyToken();
        } catch (\Throwable $e) {
            if (!request()->isCli()) CacheService::clearToken($md5Token);
            throw new ApiException('Your login has expired. Please log in again.', 410000);
        }

        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $user = $userService->getUserCacheInfo($id);
        if (!$user) throw new ApiException('Please log in again', 410000);
        if (!$user['status'])
            throw new ApiException('You have been banned from logging in', 410000);

        if (!$user || $user['id'] != $tokenData['uid']) {
            if (!request()->isCli()) CacheService::clearToken($md5Token);
            throw new ApiException('Please log in again', 410000);
        }
        
        $keyName = 'user.token.'.$fromType.'.'.$id;
        if (!CacheService::has($keyName)) {
            throw new ApiException('Please log in again', 410000);
        }
        $cache_token = CacheService::get($keyName);
        if($cache_token!=$md5Token){
            throw new ApiException('Please log in again', 99999);
        }

        //有密码在检测
        if ( $auth !== md5($user['pwd'])) {
            throw new ApiException('Please log in again', 410000);
        }

        $tokenData['type'] = $type;
        return compact('user', 'tokenData');
    }


}
<?php
declare (strict_types=1);
namespace app\services\admin;
use app\model\Admin;
use app\services\BaseServices;
use core\services\CacheService;
use think\annotation\Inject;
use core\exceptions\ApiException;
use core\utils\GoogleAuthenticator;

/**
 *
 * Class AdminLoginServices
 * @package app\services
 */
class AdminLoginServices extends BaseServices
{
    protected function setModel(): string
    {
        return Admin::class;
    }
    
    /**
     * 账号登陆
     * @param $account
     * @param $password
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login($account, $password, $captcha, $ip, $fromType)
    {
        $time = time();
        $fromType = strtoupper($fromType);
        if (!$fromType || !in_array($fromType, $this->fromType)) {
            throw new ApiException('来源非法');
        }
        $user = $this->getModel()->field('id,pwd,status,gakey')->where('account', $account)->find();
        if ($user) {
            $user = $user->toArray();
            $uid = $user['id'];
            if (!password_verify($password, $user['pwd'])){
                event('admin.loginlog', [$uid, $time, $ip, 1, '密码错误']);
                throw new ApiException('账号或密码错误');
            }
            if($user['gakey']){
                if(!$captcha)
                   throw new ApiException('请输入谷歌验证码');
                $ga=app()->make(GoogleAuthenticator::class);
                $checkResult = $ga->verifyCode($user['gakey'], $captcha, 2);
                if(!$checkResult){
                    event('admin.loginlog', [$uid, $time, $ip, 1, '谷歌验证码错误']);
                    throw new ApiException('谷歌验证码错误');
                }
            }
        } else {
            throw new ApiException('账号或密码错误');
        }
        if (!$user['status'])
            throw new ApiException('已被禁止，请联系管理员');

        //更新用户信息
        $token = $this->createToken($uid, 'admin', $user['pwd']);
        if ($token) {
            $update = [];
            $update['last_time'] = $time;
            $update['last_ip'] = $ip;
            $res = $this->getModel()->where('id', $uid)->update($update);
            if(!$res){
                throw new ApiException('系统繁忙，请稍候再试');
            }
            
            ///记录登录的token
            $keyName = 'admin.token.'.$uid;
            CacheService::set($keyName, md5($token['token']));
            
			// 用户登录成功事件
            event('admin.loginlog', [$uid, $time, $ip, 0 ,strtoupper($fromType)]);
			event('admin.login', [$uid, $ip]);
            return ['token' => $token['token'], 'expires_time' => $token['params']['exp']];
        } else
            throw new ApiException('登录失败');
    }
    
}
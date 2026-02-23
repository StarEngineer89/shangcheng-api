<?php
namespace app\controller\admin;

use app\Request;
use Psr\SimpleCache\InvalidArgumentException;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Config;
use think\captcha\facade\Captcha;
use core\services\CacheService;
use app\services\admin\AdminLoginServices;
use think\facade\Log;

/**
 * 登录
 * Class Login
 * @package app\controller\admin
 */
class Login
{

    /**
     * @var AdminLoginServices
     */
    #[Inject]
    protected AdminLoginServices $services;
    
    /**
     * 账号登陆
     * @param Request $request
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function login(Request $request)
    {
        [$account, $password, $captcha] = $request->postMore([
            'account', 'password', 'captcha'
        ], true);
        if (!$account || !$password) {
            return app('json')->fail('请输入账号和密码');
        }
        $fromType = $request->getFromType();
		$ip = truthIp();
		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{4,19}$/', $account)){
            return app('json')->fail('用户名必须英文字母开头5-20位');
        }
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,20}$/', $password)){
            return app('json')->fail('密码长度为6-20包含字母及数字的字符组合');
        }
        
        return app('json')->success('登录成功', $this->services->login($account, $password, $captcha, $ip, $fromType));
    }
    

    /**
     * 退出登录
     * @param Request $request
     */
    public function logout(Request $request)
    {
        $key = trim(ltrim($request->header(Config::get('cookie.token_name')), 'Bearer'));
        CacheService::redisHandler()->delete(md5($key));
        return app('json')->success('成功');
    }


    public function captcha(Request $request)
    {
        $rep = Captcha::create();
        return app('json')->success(['img'=>$rep['img']]);
    }
    
    /**
     * @return mixed
     */
    public function ajcaptcha(Request $request)
    {
        [$captchaType] = $request->postMore([
            ['captchaType', ''],
        ], true);
        if(!$captchaType){
            return app('json')->fail('缺少参数');
        }
        return app('json')->success(aj_captcha_create($captchaType));
    }

    /**
     * 一次验证
     * @return mixed
     */
    public function ajcheck(Request $request)
    {
        [$token, $pointJson, $captchaType] = $request->postMore([
            ['token', ''],
            ['pointJson', ''],
            ['captchaType', ''],
        ], true);
        try {
            aj_captcha_check_one($captchaType, $token, $pointJson);
            return app('json')->success();
        } catch (\Throwable $e) {
            return app('json')->fail('验证码错误');
        }
    }

}

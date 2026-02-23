<?php
namespace app\controller\api;

use app\Request;
use Psr\SimpleCache\InvalidArgumentException;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Config;
use think\facade\Log;
use think\captcha\facade\Captcha;
use core\services\CacheService;
use app\services\user\LoginServices;
use core\services\EmailService;
use core\services\AliSMSService;

/**
 * 登录
 * Class Login
 * @package app\controller\api
 */
class Login
{

    /**
     * @var LoginServices
     */
    #[Inject]
    protected LoginServices $services;
    
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
        [$account, $password] = $request->postMore([
            'account', 'password'
        ], true);
        if (!$account || !$password) {
            return app('json')->fail('Please enter your username and password');
        }
        if(!check_phone($account)&&!check_mail($account)){
            return app('json')->fail('Please enter your email address or mobile phone number.');
        }
        $type = 0;
        if(check_mail($account)){
            $type = 1;
        }
		$fromType = $request->getFromType();
		$ip = truthIp();
		
		$res = $this->services->login($account, $password, $type, $ip, $fromType);

        return app('json')->success($res);
    }
    
    /**
     * 注册新用户
     * @param Request $request
     * @return mixed
     */
    public function register(Request $request)
    {
        [$account, $type, $vcode, $password, $region] = $request->postMore([
            ['account', ''],
            [['type', 'd'], 0],
            ['vcode', ''],
            ['password', ''],
            ['region', '']
        ],true);
        $type = $type==1?1:0;
        if($type==1&&!check_mail($account)){
            return app('json')->fail('Please enter a valid email address');
        }
        if($type==0&&!check_phone($account)){
            return app('json')->fail('Please enter a valid mobile phone number');
        }
        if($type==0&&!$region){
            return app('json')->fail('Please enter country Code');
        }
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,20}$/', $password)){
            return app('json')->fail('Password length is 6-20 characters containing letters and numbers');
        }
        $code = CacheService::get('verify.key.'.md5($account));
        if ($code!=$vcode){
            return app('json')->fail('Incorrect verification code');
        }
		$ip = truthIp();
		$fromType = $request->getFromType();
        $registerStatus = $this->services->register($account, $type, $password, $region, $ip, $fromType);
        return app('json')->success('success');
    }
    
    public function getcode(Request $request)
    {
        [$account, $type, $region] = $request->postMore([
            ['account', ''],
            [['type', 'd'], 0],
            ['region', '']
        ],true);
        $type = $type==1?1:0;
        if($type==1&&!check_mail($account)){
            return app('json')->fail('Please enter a valid email address');
        }
        if($type==0&&!check_phone($account)){
            return app('json')->fail('Please enter a valid mobile phone number');
        }
        if($type==0&&!$region){
            return app('json')->fail('Please enter country Code');
        }
        $code = rand(100000, 999999);
        CacheService::set('verify.key.'.md5($account), $code);
        if($type==1){
            $EmailService = app()->make(EmailService::class);
            $EmailService->create($account, $code);
        }else{
            app()->make(AliSMSService::class)->sendMessageToGlobe($region.$account, $code);
        }
        return app('json')->success($code);
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

}

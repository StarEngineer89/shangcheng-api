<?php
declare (strict_types=1);
namespace app\services\user;

use app\model\User;
use app\services\BaseServices;
use core\services\CacheService;
use core\exceptions\ApiException;
use think\facade\Config;

/**
 *
 * Class LoginServices
 * @package app\services
 */
class LoginServices extends BaseServices
{
    protected function setModel(): string
    {
        return User::class;
    }
    
    /**
     * H5账号登陆
     * @param $account
     * @param $password
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login($account, $password, $type, $ip, $fromType)
    {
        $time = time();
        $fromType = strtoupper($fromType);
        if (!$fromType || !in_array($fromType, $this->fromType)) {
            throw new ApiException('Illegal source');
        }
        $field_name = $type==1?'email':'phone';
        $user = $this->getModel()->field('id, pwd, status')->where($field_name, $account)->where('is_del', 0)->find();
        if ($user) {
            $user = $user->toArray();
            $uid = $user['id'];
            if (!password_verify($password, $user['pwd'])){
                throw new ApiException('Incorrect username or password');
            }
        } else {
            throw new ApiException('Incorrect username or password');
        }
        if (!$user['status'])
            throw new ApiException('This account has been blocked');

        //更新用户信息
        $token = $this->createToken($uid, 'api', $user['pwd']);
        if ($token) {
            $update = [];
            $update['last_time'] = $time;
            $update['last_ip'] = $ip;
            $res = $this->getModel()->where('id', $uid)->update($update);
            if(!$res){
                throw new ApiException('The system is busy, please try again later');
            }
            
            ///记录登录的token
            $keyName = 'user.token.'.$fromType.'.'.$uid;
            CacheService::set($keyName, md5($token['token']));
			
			$UserServices = app()->make(UserServices::class);
			$UserServices->clearUserCache($uid);
			
            return ['token' => $token['token'], 'expires_time' => $token['params']['exp']];
        } else
            throw new ApiException('Login failed');
    }
    
    /**
     * 用户注册
     * @param $account
     * @param $nickname
     * @param $password
     * @param $invite_code
     * @return User|\think\Model
     */
    public function register($account, $type, $password, $region, $ip, $fromType = 'h5')
    {
        $fromType = strtoupper($fromType);
        if (!$fromType || !in_array($fromType, $this->fromType)) {
            throw new ApiException('Illegal source');
        }
        if($type==1){
            if ($this->getModel()->where('email', $account)->count()) {
                throw new ApiException('The user already exists');
            }
        }else{
            if ($this->getModel()->where('phone',$account)->count()) {
                throw new ApiException('The user already exists');
            }
        }
        $data = [];
        $data['pwd'] = $this->passwordHash($password);
        $data['reg_time'] = time();
        if($type==1){
            $data['email'] = $account;
        }else{
            $data['phone'] = $account;
            $data['country_code'] = $region;
        }
        $data['reg_ip'] = $ip;
        $data['status'] = 1;
        if (!$re = $this->getModel()->create($data)) {
            throw new ApiException('The system is busy, please try again later');
        } else {
            return $re;
        }
    }
}
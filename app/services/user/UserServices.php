<?php
namespace app\services\user;

use app\services\BaseServices;
use app\model\User;
use core\exceptions\ApiException;
use core\services\CacheService;
use think\facade\Db;


/**
 *
 * Class UserServices
 * @package app\services
 * @mixin User
 */
class UserServices extends BaseServices
{
    protected function setModel(): string
    {
        return User::class;
    }


     /**
     * 搜索数据
     * @param string $keyword
     * @return bool
     */
    public function searchKeyword(string $keyword)
    {
       return $this->getModel()->where('account','LIKE', "%$keyword%")->whereOr('nickname','LIKE',"%$keyword%")->column('id');
    }
    
    
    /**
     * 搜索账号
     * @param string $account
     * @return bool
     */
    public function searchPhone(string $account)
    {
       return $this->getModel()->where('phone',$account)->find();
    }
    public function searchEmail(string $account)
    {
       return $this->getModel()->where('email',$account)->find();
    }
    
    /**
     * 搜索昵称
     * @param string $nickname
     * @return bool
     */
    public function searchNickname(string $nickname)
    {
        return $this->cacheTag()->remember('user_nickname_' . md5($nickname), function () use ($nickname) {
             return $this->getModel()->where('nickname',$nickname)->find();
        },60);
    }
    
    /**
     * 检查昵称
     * @param string $nickname
     * @return bool
     */
    public function hasNickname(string $nickname)
    {
        return $this->getModel()->where('nickname',$nickname)->count();
    }
    
    public function fromInvite($code)
    {
        return $this->getModel()->where('invitation_code', $code)->find();
    }
    /**
     * 获取用户缓存信息
     * @param int $uid
     * @param string $field
     * @param int $expire
     * @return bool|mixed|null
     */
    public function getUserCacheInfo($uid)
    {
        $this->clearUserCache($uid);
        return $this->cacheTag()->remember('user_info_' . $uid, function () use ($uid) {
                $user = $this->getModel()->where('id',$uid)->find();
                if(empty($user)){
                    return [];
                }else{
                    return $user->toArray();
                }
        });
    }
    
    /**
     * 清除用户缓存
     */
    public function clearUserCache($uid)
    {
        CacheService::delete($this->getCacheKey('user_info_'.$uid));
    }
    
    /**
     * 更新IP城市
     * @param int $uid
     * @param string $city
     */
    public function loginCity($uid, $city = '')
    {
        if(!$city) return false;
        $userInfo = $this->getModel()->where('id', $uid)->find();
		if ($userInfo->login_city != $city) {
			$userInfo->login_city = $city;
			$userInfo->save();
			$this->clearUserCache($uid);
		}
        return true;
    }
    
    
    /**
     * 保存头像
     * @param int $uid
     * @param int $avatar
     */
    public function setAvatar($uid, $avatar)
    {
        $res = $this->getModel()->where('id', $uid)->update(['avatar'=>$avatar]);
        $this->clearUserCache($uid);
        return true;
    }
    
    public function setSignture($uid, $img)
    {
        $res = $this->getModel()->where('id', $uid)->update(['signature_pic'=>$img]);
        $this->clearUserCache($uid);
        return true;
    }
    
    /**
     * 保存昵称
     * @param int $uid
     * @param string $nick
     */
    public function setNick($uid, $nick)
    {
        $res = $this->getModel()->where('id', $uid)->update(['nickname'=>$nick]);
        $this->clearUserCache($uid);
        return true;
    }
    
    /**
     * 保存手机
     * @param int $uid
     * @param string $region
     * @param string $phone
     */
    public function setPhone($uid, $region, $phone)
    {
        $res = $this->getModel()->where('id', $uid)->update(['country_code'=>$region, 'phone'=>$phone]);
        $this->clearUserCache($uid);
        return true;
    }
    
    /**
     * 保存邮箱
     * @param int $uid
     * @param string $email
     */
    public function setEmail($uid, $email)
    {
        $res = $this->getModel()->where('id', $uid)->update(['email'=>$email]);
        $this->clearUserCache($uid);
        return true;
    }
    
    /**
     * 保存性别
     * @param int $uid
     * @param string $gender
     */
    public function setGender($uid, $gender)
    {
        $res = $this->getModel()->where('id', $uid)->update(['sex'=>$gender]);
        $this->clearUserCache($uid);
        return true;
    }
    
    public function setDel($uid)
    {
        $res = $this->getModel()->where('id', $uid)->update(['is_del'=>1]);
        $this->clearUserCache($uid);
        return true;
    }
    
    /**
     * 修改密码
     * @param int $uid
     * @param string $password
     */
    public function editPass($uid, $password)
    {
        $res = $this->getModel()->where('id', $uid)->update(['pwd'=>$this->passwordHash($password)]);
        $this->clearUserCache($uid);
        return true;
    }
    
    /**
     * 设置支付密码
     * @param int $uid
     * @param string $passo
     * @param string $passn
     */
    public function setSPass($uid, $passn)
    {
        $user = $this->getUserCacheInfo($uid);
        if($user['withdraw_pwd']==md5($passn)){
            return true;
        }
        $res = $this->getModel()->where('id', $uid)->update(['withdraw_pwd'=>md5($passn)]);
        $this->clearUserCache($uid);
        return true;
    }
    
    /**
     * 修改支付密码
     * @param int $uid
     * @param string $passn
     */
    public function editSPass($uid, $passn)
    {
        $res = $this->getModel()->where('id', $uid)->update(['withdraw_pwd'=>md5($passn)]);
        $this->clearUserCache($uid);
        return true;
    }
    
    
    /**
     * 加款
     * @param int $uid
     * @param string $amount
     */
    public function addMoney($uid, $amount)
    {
        return $this->transaction(function() use($uid, $amount){
            $user = $this->getModel()->where('id', $uid)->lock(true)->find();
            if(!$user){
                throw new ApiException('用户不存在');
            }
            $update = [];
            $update['money'] = bcadd((string)$user['money'], (string)$amount, 2);

            $time = time();
            $resu = $this->getModel()->where('id', $uid)->update($update);
            if(!$resu){
                throw new ApiException('系统繁忙，请稍后再试');
            }

            $UserMoneylogServices = app()->make(UserMoneylogServices::class);
            $reslog = $UserMoneylogServices->addMoneylog([
                    'type'=>2,
                    'uid'=>$uid,
                    'state'=>1,
                    'title'=>'Additional',
                    'amount'=>$amount,
                    'money'=>$update['money'],
                    'add_time'=>$time
                ]);
            if(!$reslog){
                throw new ApiException('系统繁忙，请稍后再试');
            }

            CacheService::delete($this->getCacheKey('user_info_' . $uid));
            return [
                'money' => $update['money']
            ];

        });
    }

    /**
     * 扣款
     * @param int $uid
     * @param string $amount
     */
    public function subMoney($uid, $amount)
    {
        return $this->transaction(function() use($uid, $amount){
            $user = $this->getModel()->where('id', $uid)->lock(true)->find();
            if(!$user){
                throw new ApiException('用户不存在');
            }
            if($user['money']<$amount){
                throw new ApiException('用户余额不足');
            }
            $update = [];
            $update['money'] = bcsub((string)$user['money'], $amount, 2);

            $time = time();
            $resu = $this->getModel()->where('id', $uid)->update($update);
            if(!$resu){
                throw new ApiException('系统繁忙，请稍后再试');
            }

            $UserMoneylogServices = app()->make(UserMoneylogServices::class);
            $reslog = $UserMoneylogServices->addMoneylog([
                    'type'=>3,
                    'uid'=>$uid,
                    'state'=>2,
                    'title'=>'Deduction',
                    'amount'=>$amount,
                    'money'=>$update['money'],
                    'add_time'=>$time
                ]);
            if(!$reslog){
                throw new ApiException('系统繁忙，请稍后再试');
            }

            CacheService::delete($this->getCacheKey('user_info_' . $uid));
            return [
                'money' => $update['money']
            ];

        });
    }
    
    
    
    /**
     * 获取列表
     * @param $keyword
     */
    public function getlist($uid, $last_ip, $reg_ip, $min_money, $max_money, $status, $start_date, $end_date, $email, $phone, $invite_code)
    {
        $where = [];
        if($phone){
            $where[] = ['phone', 'LIKE', "%$phone%"];
        }
        if($email){
            $where[] = ['email', 'LIKE', "%$email%"];
        }
        if($uid){
            $where[] = ['id', '=', $uid];
        }
        if($last_ip){
            $where[] = ['last_ip', 'LIKE', "%$last_ip%"];
        }
        if($status!=-1){
            $where[] = ['status', '=', $status];
        }
        if($reg_ip){
            $where[] = ['reg_ip', 'LIKE', "%$reg_ip%"];
        }
        if($min_money){
            $where[] = ['money', '>=', $min_money];
        }
        if($max_money){
            $where[] = ['money', '<=', $max_money];
        }
        if($invite_code){
            $where[] = ['invitation_code', '=', $invite_code];
        }
        
        
        if($start_date&&!$end_date){
           $where[] = ['reg_time', '>=', strtotime($start_date)];
           $where[] = ['reg_time', '<', strtotime('+1day',strtotime($start_date))];
        }
        if(!$start_date&&$end_date){
           $where[] = ['reg_time', '>=', strtotime($end_date)];
           $where[] = ['reg_time', '<', strtotime('+1day',strtotime($end_date))];
        }
        if($start_date&&$end_date){
           if($start_date==$end_date){
               $where[] = ['reg_time', '>=', strtotime($start_date)];
               $where[] = ['reg_time', '<', strtotime('+1day',strtotime($end_date))];
           }else{
               $_start_date = $start_date;
               if(strtotime($start_date)>strtotime($end_date)){
                   $start_date = $end_date;
                   $end_date = $_start_date;
               }
               $where[] = ['reg_time', '>=', strtotime($start_date)];
               $where[] = ['reg_time', '<', strtotime('+1day',strtotime($end_date))];
           }
        }
        
        [$page, $limit] = $this->getPageValue();
        $list = $this->getModel()->where($where)->order('id','desc')->page($page, $limit)->select()->toArray();
        $count = $this->getModel()->where($where)->count();
        foreach($list as &$item){
            $item['datetime'] = date('Y-m-d H:i', $item['reg_time']);
            $item['lasttime'] = date('Y-m-d H:i', $item['last_time']);
            if($item['is_promoter']==1){
               $item['user_type_name'] = '业务员';
            }else{
               $item['user_type_name'] = '普通会员';
            }
            if($item['status']==1){
               $item['status_name'] = '正常';
            }else{
               $item['status_name'] = '冻结';
            }
            if(!$item['phone']){
               $item['country_code']="--";
            }
        }
        return compact('list','count');
    }
    
    
    public function userAdd($phone, $email, $password, $code, $invite_code, $remark, $user_type)
    {
        if($phone){
            if ($this->getModel()->where('phone', $phone)->where('country_code', $code)->count()) {
                throw new ApiException('手机已存在');
            }
        }
        if($email){
            if ($this->getModel()->where('email', $email)->count()) {
                throw new ApiException('邮箱已存在');
            }
        }
        if($invite_code){
            if ($this->getModel()->where('invitation_code', $invite_code)->count()) {
                throw new ApiException('邀请码已存在');
            }
        }
        $ip = truthIp();
        $data = [];
        $data['country_code'] = $code;
        $data['phone'] = $phone;
        $data['email'] = $email;
        $data['pwd'] = $this->passwordHash($password);
        $data['invitation_code'] = $invite_code;
        $data['mark'] = $remark;
        $time = time();
        $data['is_promoter'] = $user_type==1?1:0;
        $data['reg_time'] = $time;
        $data['reg_ip'] = $ip;
        $data['last_time'] = $time;
        $data['last_ip'] = $ip;
        $data['status'] = 1;
        if (!$re = $this->getModel()->create($data)) {
            throw new ApiException('注册失败');
        }
        return true;
    }
    
    
    
        /**
     * 管理员修改密码
     * @param int $uid
     * @param string $passn
     */
    public function editAPass($uid, $passn, $admin)
    {
        $user = $this->getUserCacheInfo($uid);
        if(!$user){
            throw new ApiException('用户不存在');
        }
        $res = $this->getModel()->where('id', $uid)->update(['pwd'=>$this->passwordHash($passn)]);
        CacheService::delete($this->getCacheKey('user_info_' . $uid));
        return true;
    }
    /**
     * 管理员修改交易密码
     * @param int $uid
     * @param string $passn
     */
    public function editATPass($uid, $passn, $admin)
    {
        $user = $this->getUserCacheInfo($uid);
        if(!$user){
            throw new ApiException('用户不存在');
        }
        $res = $this->getModel()->where('id', $uid)->update(['withdraw_pwd'=>md5($passn)]);
        CacheService::delete($this->getCacheKey('user_info_' . $uid));
        return true;
    }
    
    /**
     * 管理员修改邀请码
     * @param int $uid
     * @param string $code
     */
    public function editInviteCode($uid, $code)
    {
        $user = $this->getUserCacheInfo($uid);
        if(!$user){
            throw new ApiException('用户不存在');
        }
        if ($this->getModel()->where('invitation_code',$code)->count()) {
            throw new ApiException('邀请码已存在');
        }
        $res = $this->getModel()->where('id', $uid)->update(['invitation_code'=>$code]);
        CacheService::delete($this->getCacheKey('user_info_' . $uid));
        return true;
    }
    
    public function editRemark($uid, $remark)
    {
        $user = $this->getUserCacheInfo($uid);
        if(!$user){
            throw new ApiException('用户不存在');
        }
        $res = $this->getModel()->where('id', $uid)->update(['mark'=>$remark]);
        CacheService::delete($this->getCacheKey('user_info_' . $uid));
        return true;
    }

}

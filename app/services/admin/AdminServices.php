<?php
namespace app\services\admin;

use app\services\BaseServices;
use app\model\Admin;
use core\exceptions\ApiException;
use core\services\CacheService;
use core\utils\GoogleAuthenticator;

/**
 *
 * Class AdminServices
 * @package app\services
 * @mixin Admin
 */
class AdminServices extends BaseServices
{
    protected function setModel(): string
    {
        return Admin::class;
    }

    /**
     * 获取用户信息
     * @param int $uid
     * @param string $field
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserInfo(int $uid, $field = '*')
    {
        if (is_string($field)) $field = explode(',', $field);
        return $this->getModel()->field($field)->where('id',$uid)->find();
    }
    
    /**
     * 是否存在
     * @param int $uid
     * @return bool
     */
    public function userExist(int $uid)
    {
        return $this->getModel()->where('id', $uid)->count();
    }
    
    /**
     * 某个字段累加某个数值
     * @param string $field
     * @param int $num
     */
    public function incField(int $uid, string $field, int $num = 1)
    {
        return $this->getModel()->where('id', $uid)->inc($field, $num)->update();
    }
    
    
    /**
     * 更新IP城市
     * @param int $uid
     * @param string $city
     */
    public function loginCity(int $uid, string $city = '')
    {
        if(!$city) return false;
        $userInfo = $this->getModel()->where('id',$uid)->find();
		if ($userInfo->login_city != $city) {
			$userInfo->login_city = $city;
			$userInfo->save();
		}
        return true;
    }
    
    
    public function getList()
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->getModel()->field('id,account,remark,reg_time,last_time,role_id')->order('id','desc')->page($page, $limit)->select()->toArray();
        $count = $this->getModel()->count();
        foreach ($list as &$item){
           $item['datetime'] = date('Y-m-d H:i:s', $item['reg_time']);
           $item['logintime'] = date('Y-m-d H:i:s', $item['last_time']);
           if(!$item['role_id']){
               $item['rolename'] = '管理员';
           }
        }
        return compact('list','count');
    }
    
    
    public function addAdmin($username, $password, $remark, $role_id = 0)
    {
        if($role_id){
            $SystemRoleServices = app()->make(SystemRoleServices::class);
            $role = $SystemRoleServices->getModel()->where('id', $role_id)->find();
            if(empty($role)){
                throw new ApiException("角色不存在");
            }
        }
        if($this->getModel()->where('account', $username)->count()){
            throw new ApiException("用户名已被占用");
        }
        $time = time();
        $data = [
               'account' => $username,
               'pwd' => $this->passwordHash($password),
               'remark'=>$remark,
               'role_id'=>$role_id,
               'reg_time'=>time(),
               'last_time'=>$time
            ];
        $res = $this->getModel()->create($data);
        if(!$res){
            throw new ApiException("系统繁忙，请稍后再试");
        }
        $data['datetime'] = date('Y-m-d H:i',$time);
        $data['logintime'] = $data['datetime'];
        $this->cacheTag()->clear();
        return $data;
    }
    
    public function editAdmin($uid, $password, $remark, $role_id = 0)
    {
        if($role_id){
            $SystemRoleServices = app()->make(SystemRoleServices::class);
            $role = $SystemRoleServices->getModel()->where('id', $role_id)->find();
            if(empty($role)){
                throw new ApiException("角色不存在");
            }
        }
        $admin = $this->getModel()->where('id', $uid)->find();
        if(!$admin){
            throw new ApiException("用户不存在");
        }
        $data = [];
        if($password){
            $data['pwd'] = $this->passwordHash($password);
        }
        if($remark!=$admin['remark']){
            $data['remark'] = $remark;
        }
        
        if($role_id!=$admin['role_id']){
            $data['role_id'] = $role_id;
        }
        if($data){
            $res = $this->getModel()->where('id', $uid)->update($data);
            if(!$res){
                throw new ApiException("系统繁忙，请稍后再试");
            }
        }
        $this->cacheTag()->clear();
        return true;
    }
    
    public function delAdmin($uid)
    {
        if($this->getModel()->count()==1){
            throw new ApiException("至少保留一个管理员");
        }
        $admin = $this->getModel()->where('id', $uid)->find();
        if(!$admin){
            throw new ApiException("用户不存在");
        }
        $res = $this->getModel()->where('id', $uid)->delete();
        if(!$res){
            throw new ApiException("系统繁忙，请稍后再试");
        }
        $this->cacheTag()->clear();
        return true;
    }
    
    
    public function setMFA($uid, $mfacode, $secret)
    {
        if (!$this->getModel()->where('id',$uid)->update(['gakey' => $secret])) {
            throw new ApiException('设置谷歌验证码失败');
        }
        $this->cacheTag()->clear();
        $keyName = 'admin.gakey.'.$uid;
        if(CacheService::has($keyName)){
           CacheService::delete($keyName);
        }
        return true;
    }
    
    
    public function delMFA($uid)
    {
        if (!$this->getModel()->where('id',$uid)->update(['gakey' => ''])) {
            throw new ApiException('删除谷歌验证码失败');
        }
        $this->cacheTag()->clear();
        return true;
    }
    
    
    /**
     * 重置密码
     * @param $uid
     * @param $password
     */
    public function resetPass($uid, $password)
    {
        if (!$this->getModel()->where('id',$uid)->update(['pwd' => $this->passwordHash($password)])) {
            throw new ValidateException('修改密码失败');
        }
        $this->cacheTag()->clear();
        return true;
    }
 
}

<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\order\OrderServices;
use app\services\user\UserServices;
use app\services\user\UserRechargeServices;
use app\services\merchant\MerchantRechargeServices;
use app\services\merchant\MerchantWithdrawServices;
use app\services\merchant\MerchantTrafficOrderServices;
use app\services\admin\AdminServices;
use app\services\admin\AdminNotificationsServices;
use app\services\system\SystemConfigServices;
use core\utils\GoogleAuthenticator;
use think\facade\Log;

/**
 * 管理员类
 * Class Admin
 * @package app\controller\admin
 */
class Admin
{
    /**
     * @var AdminServices
     */
    #[Inject]
    protected AdminServices $services;
    
    public function topinfo(Request $request)
    {
        $MerchantWithdrawServices = app()->make(MerchantWithdrawServices::class);
        $MerchantRechargeServices = app()->make(MerchantRechargeServices::class);
        $MerchantTrafficOrderServices = app()->make(MerchantTrafficOrderServices::class);
        $UserRechargeServices = app()->make(UserRechargeServices::class);
        $AdminNotificationsServices = app()->make(AdminNotificationsServices::class);
        $data = [];
        $data['mer_withdraw'] = $MerchantWithdrawServices->getnewcount();
        $data['mer_recharge'] = $MerchantRechargeServices->getnewcount();
        $data['user_recharge'] = $UserRechargeServices->getnewcount();
        $data['mer_trafficorder'] = $MerchantTrafficOrderServices->getnewcount();
        $data['notifications'] = $AdminNotificationsServices->getList();
        return app('json')->success($data);
    }
    
    
    
     /**
     * 修改登录密码
     * @return mixed
     */
    public function resetPass(Request $request)
     {
        [$oldpasswd, $newpasswd] = $request->postMore([
            'oldpasswd', 'newpasswd'
        ], true);
        $admin = $request->admin();
        if (!password_verify($oldpasswd, $admin['pwd'])){
            return app('json')->fail('原始密码输入错误');
        }
        if(!$newpasswd){
            return app('json')->fail('请输入新密码');
        }
        if(strlen($newpasswd)<6){
            return app('json')->fail('密码不能少于六位数');
        }
        if (password_verify($newpasswd, $admin['pwd'])){
            return app('json')->fail('新密码与原始密码相同');
        }
        $this->services->resetPass($admin['id'],$newpasswd);
        return app('json')->success("修改成功");
    }
     /**
     * 获取管理员列表
     * @return mixed
     */
    public function adminList(Request $request)
    {
        $data = $this->services->getList();
        return app('json')->success($data);
    }
    
    
    
    public function RoleList(Request $request)
    {
        $SystemRoleServices = app()->make(SystemRoleServices::class);
        $data = $SystemRoleServices->getList();
        foreach ($data as &$item){
            unset($item['content']);
        }
        return app('json')->success($data);
    }
    
    public function menuList(Request $request)
    {
        $admin = $request->admin();
        $SystemRoleServices = app()->make(SystemRoleServices::class);
        $SystemMenuServices = app()->make(SystemMenuServices::class);
        $menulist = $SystemMenuServices->getList();
        $menu = [];
        $permission = [];
        if($admin['role_id']){
               $role = $SystemRoleServices->getRole($admin['role_id']);
               $menu_ids = $role['content']?explode(',',$role['content']):[];
               foreach ($menulist as $k=>$v){
                    if($v['pid']==0&&in_array($v['id'], $menu_ids)){
                        $children = [];
                        foreach ($menulist as $kk=>$vv){
                            if($vv['pid']==$v['id']){
                                if(in_array($vv['id'], $menu_ids)){
                                        $children[] = [
                                           'id'=>$vv['path'],
                                           'icon'=>'',
                                           'title'=>$vv['name'],
                                        ];
                                        foreach ($menulist as $kkk=>$vvv){
                                            if($vvv['pid']==$vv['id']&&in_array($vvv['id'], $menu_ids)){
                                               $permission[] = $vvv['router'];
                                            }
                                        }
                                }
                            }
                        }
                        $menu[] = [
                               'id'=>$v['path'],
                               'icon'=>$v['icon'],
                               'title'=>$v['name'],
                               'children'=>$children
                            ];
                    }
               }
        }else{
            foreach ($menulist as $k=>$v){
                if($v['pid']==0){
                    $children = [];
                    foreach ($menulist as $kk=>$vv){
                        if($vv['pid']==$v['id']){
                            $children[] = [
                                   'id'=>$vv['path'],
                                   'icon'=>'',
                                   'title'=>$vv['name'],
                                ];
                            foreach ($menulist as $kkk=>$vvv){
                                if($vvv['pid']==$vv['id']){
                                  $permission[] = $vvv['router'];
                                }
                            }
                        }
                    }
                    $menu[] = [
                           'id'=>$v['path'],
                           'icon'=>$v['icon'],
                           'title'=>$v['name'],
                           'children'=>$children
                        ];
                }
            }
        }
        return app('json')->success(compact('menu','permission'));
    }
    
    public function addAdmin(Request $request)
    {
        [$username, $password, $remark, $role_id] = $request->postMore([
            ['username', ''],
            ['password', ''],
            ['remark',''],
            [['role_id','d'],0]
        ], true);
        if (!$username || !$password || !$remark) {
            return app('json')->fail('缺少参数');
        }
        if(!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{5,19}$/',$username)){
            return app('json')->fail('用户名必须英文字母开头6-20位');
        }
        if(strlen($password)<6){
            return app('json')->fail('密码不能少于六位数');
        }
        if(!$role_id) $role_id = 0;
        if($role_id&&!preg_match('/^[1-9]\d*$/',$role_id)){
            return app('json')->fail('参数错误');
        }
        $data = $this->services->addAdmin($username, $password, $remark, $role_id);
        return app('json')->success($data);
    }
    
    public function editAdmin(Request $request)
    {
        [$uid, $password, $remark, $role_id] = $request->postMore([
            [['uid', 'd'],0],
            ['password', ''],
            ['remark',''],
            [['role_id','d'],0]
        ], true);
        if (!$uid) {
            return app('json')->fail('缺少参数');
        }
        if($password&&strlen($password)<6){
            return app('json')->fail('密码不能少于六位数');
        }
        if(!$role_id) $role_id = 0;
        if($role_id&&!preg_match('/^[1-9]\d*$/',$role_id)){
            return app('json')->fail('参数错误');
        }
        $data = $this->services->editAdmin($uid, $password, $remark, $role_id);
        return app('json')->success('保存成功');
    }
    
    public function delAdmin(Request $request)
    {
        [$uid] = $request->postMore([
            [['uid', 'd'],0]
        ], true);
        if (!$uid) {
            return app('json')->fail('缺少参数');
        }
        $data = $this->services->delAdmin($uid);
        return app('json')->success('保存成功');
    }
    
    /**
     * 获取谷歌验证码图片
     * @return mixed
     */
    public function mfaInfo(Request $request)
    {
        $admin = $request->admin();
        if($admin['gakey']){
            return app('json')->success(['status'=>1]);
        }
        $ga=app()->make(GoogleAuthenticator::class);
        $secret = $ga->createSecret();
        $keyName = 'admin.gakey.'.$admin['id'];
        CacheService::set($keyName,$secret,3600);
        $qrCodeUrl = $ga->getQRCodeGoogleUrl(sys_config('site_name','').':'.$admin['account'], $secret, sys_config('site_name','googleVerify'));
        return app('json')->success(['status'=>0,'qrurl'=>$qrCodeUrl]);
    }
    
    
    /**
     * 设置谷歌验证码
     * @return mixed
     */
    public function saveMFA(Request $request)
    {
        [$mfacode] = $request->postMore([
            'mfacode'
        ], true);
        if(!$mfacode){
            return app('json')->fail('请输入谷歌验证码');
        }
        $admin = $request->admin();
        if($admin['gakey']){
            return app('json')->fail('您已设置谷歌验证码，请刷新');
        }
        $keyName = 'admin.gakey.'.$admin['id'];
        if(!CacheService::has($keyName)){
            return app('json')->fail('当前二维码已过期，请刷新');
        }
        $secret = CacheService::get($keyName);
        $ga=app()->make(GoogleAuthenticator::class);
        $checkResult = $ga->verifyCode($secret, $mfacode, 2);
        if(!$checkResult){
            return app('json')->fail('谷歌验证码错误');
        }
        $data = $this->services->setMFA($admin['id'], $mfacode, $secret);
        return app('json')->success("设置成功");
    }
    
    /**
     * 删除谷歌验证码
     * @return mixed
     */
    public function delMFA(Request $request)
    {
        [$mfacode] = $request->postMore([
            'mfacode'
        ], true);
        if(!$mfacode){
            return app('json')->fail('请输入谷歌验证码');
        }
        $admin = $request->admin();
        if(!$admin['gakey']){
            return app('json')->fail('未设置谷歌验证码');
        }
        $secret = $admin['gakey'];
        $ga=app()->make(GoogleAuthenticator::class);
        $checkResult = $ga->verifyCode($secret, $mfacode, 2);
        if(!$checkResult){
            return app('json')->fail('谷歌验证码错误');
        }
        $this->services->delMFA($admin['id']);
        return app('json')->success("删除成功");
    }
    

}
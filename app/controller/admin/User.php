<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\user\UserServices;
use app\services\user\UserRechargeServices;

/**
 * 用户相关
 * Class User
 * @package app\controller\room
 */
class User
{
    /**
     * @var UserServices
     */
    #[Inject]
    protected UserServices $services;
    
    
    /**
     * 获取用户列表
     * @return mixed
     */
    public function userList(Request $request)
    {
        [$uid, $last_ip, $reg_ip, $min_money, $max_money, $status, $start_date, $end_date, $email, $phone, $invite_code] = $request->postMore([
            [['uid', 'd'],0],
            ['last_ip',''],
            ['reg_ip',''],
            [['min_money', 'd'], 0],
            [['max_money', 'd'], 0],
            [['status', 'd'], 0],
            ['start_date',''],
            ['end_date',''],
            ['email',''],
            ['phone',''],
            ['invite_code',''],
        ], true);
        if ($phone&&!preg_match('/^\+?\d+$/', $phone)) {
            return app('json')->fail('请输入正确的手机号码格式');
        }
        if($uid&&!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('用户ID为正整数');
        }
        if($status&&!in_array($status,[1,2])){
            return app('json')->fail('用户状态参数错误');
        }
        if(!$status){
            $status = -1;
        }else{
            $status = $status==1?1:0;
        }
        if($start_date&&!strtotime($start_date)){
            return app('json')->fail('开始日期错误');
        }
        if($end_date&&!strtotime($end_date)){
            return app('json')->fail('结束日期错误');
        }
        
        if($min_money&&!preg_match('/^[1-9]\d*$/',$min_money)){
            return app('json')->fail('余额请输入正整数');
        }
        if($max_money&&!preg_match('/^[1-9]\d*$/',$max_money)){
            return app('json')->fail('余额请输入正整数');
        }
        if($min_money>$max_money){
            $_max_money = $max_money;
            $max_money = $min_money;
            $min_money = $_max_money;
        }
        
        if ($invite_code&&!preg_match('/^\d{6}$/', $invite_code)){
            return app('json')->fail('请输入六位数字邀请码');
        }
        
        $data = $this->services->getList($uid, $last_ip, $reg_ip, $min_money, $max_money, $status, $start_date, $end_date, $email, $phone, $invite_code);
        return app('json')->success($data);
    }
    
    
    public function userAdd(Request $request)
     {
         [$phone, $code, $email, $password, $invite_code, $remark, $user_type] = $request->postMore([
                ['phone', ''],
                ['code', ''],
                ['email', ''],
                ['password', ''],
                ['invite_code', ''],
                ['remark', ''],
                [['user_type', 'd'],0]
         ],true);
         
         $user_type = $user_type==1?1:0;
         
         if($phone&&!check_phone($phone)){
             return app('json')->fail('请输入正确的手机号码');
         }
         if($phone&&!$code){
             return app('json')->fail('请输入国家地区');
         }
         if($email&&!check_mail($email)){
             return app('json')->fail('请输入正确的邮箱');
         }
         if(!$email&&!$phone){
             return app('json')->fail('请输入手机或邮箱');
         }
         if (!$password||!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,20}$/', $password)){
            return app('json')->fail('登录密码为包含英文大小写及数字的6-20组合密码');
         }
         if ($user_type==1&&!preg_match('/^\d{6}$/', $invite_code)){
            return app('json')->fail('业务员请输入六位数字邀请码');
         }
         if (mb_strlen($remark)>10){
            return app('json')->fail('备注不能大于10字符');
         }
         
         $data = $this->services->userAdd($phone, $email, $password, $code, $invite_code, $remark, $user_type);
         
         return app('json')->success('保存成功');
     }
     
     
     
     public function addMoney(Request $request)
    {
        [$uid, $amount] = $request->postMore([
            [['uid', 'd'],0],
            ['amount', '']
        ], true);
        if (!$uid||!$amount) {
            return app('json')->fail('缺少参数');
        }
        if(!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('参数错误');
        }
        if(!preg_match('/^\d+(\.\d{0,2})?$/',$amount)){
            return app('json')->fail('请输入正确的金额');
        }
        $data = $this->services->addMoney($uid, $amount);
        return app('json')->success($data);
    }
    
    
    public function subMoney(Request $request)
    {
        [$uid, $amount] = $request->postMore([
            [['uid', 'd'],0],
            ['amount', '']
        ], true);
        if (!$uid||!$amount) {
            return app('json')->fail('缺少参数');
        }
        if(!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('参数错误');
        }
        if(!preg_match('/^\d+(\.\d{0,2})?$/',$amount)){
            return app('json')->fail('请输入正确的金额');
        }
        $data = $this->services->subMoney($uid, $amount);
        return app('json')->success($data);
    }
    
    
    public function editPass(Request $request)
    {
        [$uid, $password] = $request->postMore([
            [['uid', 'd'],0],
            ['password', '']
        ], true);
        if (!$uid||!$password) {
            return app('json')->fail('缺少参数');
        }
        if(!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('参数错误');
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,20}$/', $password)){
            return app('json')->fail('密码长度为6-20包含大小写字母及数字的字符组合');
        }
        $admin = $request->admin();
        $data = $this->services->editAPass($uid, $password, $admin);
        return app('json')->success('保存成功');
    }
    
    public function editTPass(Request $request)
    {
        [$uid, $password] = $request->postMore([
            [['uid', 'd'],0],
            ['password', '']
        ], true);
        if (!$uid||!$password) {
            return app('json')->fail('缺少参数');
        }
        if(!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('参数错误');
        }
        if(!preg_match('/^\d{6}$/',$password)){
            return app('json')->fail('请输入六位数字密码');
        }
        $admin = $request->admin();
        $data = $this->services->editATPass($uid, $password, $admin);
        return app('json')->success('保存成功');
    }
    
    public function editInviteCode(Request $request)
    {
        [$uid, $code] = $request->postMore([
            [['uid', 'd'],0],
            ['code', '']
        ], true);
        if (!$uid||!$code) {
            return app('json')->fail('缺少参数');
        }
        if(!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('参数错误');
        }
        if(!preg_match('/^\d{6}$/',$code)){
            return app('json')->fail('请输入六位数字');
        }
        $data = $this->services->editInviteCode($uid, $code);
        return app('json')->success('保存成功');
    }
    
    
    /**
     * 获取充值列表
     * @return mixed
     */
    public function rechargeList(Request $request)
    {
        [$id, $uid, $status, $start_date, $end_date, $order_no, $email, $phone] = $request->postMore([
            [['id', 'd'],0],
            [['uid', 'd'],0],
            [['status', 'd'],0],
            ['start_date', ''],
            ['end_date', ''],
            ['order_no', ''],
            ['email', ''],
            ['phone', ''],
        ], true);
        if($id&&!preg_match('/^[1-9]\d*$/',$id)){
            return app('json')->fail('参数错误');
        }
        if($uid&&!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('参数错误');
        }
        if($status&&!in_array($status,[1,2,3])){
            return app('json')->fail('参数错误');
        }
        if($start_date&&!strtotime($start_date)){
            return app('json')->fail('开始日期错误');
        }
        if($end_date&&!strtotime($end_date)){
            return app('json')->fail('结束日期错误');
        }
        if($order_no&&!preg_match('/^[A-Za-z0-9]{1,50}$/', $order_no)){
            return app('json')->fail('订单号格式错误');
        }
        $user_ids = [];
        if(!$uid&&$phone){
            if (!check_phone($phone)) {
                return app('json')->success(['list'=>[],'count'=>0]);
            }
            $user_phone = $this->services->searchPhone($phone);
            if(empty($user_phone)){
                return app('json')->success(['list'=>[],'count'=>0]);
            }
            $user_ids[] = $user_phone->id;
        }
        if(!$uid&&$email){
            if (!check_mail($email)) {
                return app('json')->success(['list'=>[],'count'=>0]);
            }
            $user_email = $this->services->searchEmail($email);
            if(empty($user_email)){
                return app('json')->success(['list'=>[],'count'=>0]);
            }
            $user_ids[] = $user_phone->id;
        }
        if($uid){
            $user_ids[] = $uid;
        }
        $UserRechargeServices = app()->make(UserRechargeServices::class);
        $data = $UserRechargeServices->getAList($id, $user_ids, $status, $start_date, $end_date, $order_no, $email, $phone);
        return app('json')->success($data);
    }
    
    
    public function rechargeEdit(Request $request)
    {
        [$id, $type, $is_truth] = $request->postMore([
            [['id', 'd'],0],
            [['type','d'],0],
            [['is_truth','d'],0]
        ], true);
        if(!preg_match('/^[1-9]\d*$/',$id)){
            return app('json')->fail('参数错误');
        }
        if(!in_array($type,[1,2])){
            return app('json')->fail('参数错误');
        }
        $is_truth = $is_truth==1?1:0;
        $admin = $request->admin();
        $UserRechargeServices = app()->make(UserRechargeServices::class);
        $data = $UserRechargeServices->rechargeEdit($admin, $id, $type, $is_truth);
        return app('json')->success('保存成功');
    }


}
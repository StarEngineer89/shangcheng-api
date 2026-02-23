<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\user\UserServices;
use app\services\merchant\MerchantServices;
use app\services\merchant\MerchantRechargeServices;
use app\services\merchant\MerchantWithdrawServices;
use app\services\merchant\MerchantApplyServices;
use app\services\merchant\MerchantMoneylogServices;
use app\services\merchant\MerchantTrafficOrderServices;

/**
 * 商户相关
 * Class Merchant
 * @package app\controller
 */
class Merchant
{
    /**
     * @var MerchantServices
     */
    #[Inject]
    protected MerchantServices $services;
    
    public function editmer(Request $request)
    {
        [$id, $type_id, $mer_name, $mer_info, $mer_address, $is_best, $mer_banner, $mini_banner] = $request->postMore([
            [['id', 'd'],0],
            [['type_id', 'd'], 0],
            ['mer_name',''],
            ['mer_info',''],
            ['mer_address',''],
            [['is_best', 'd'], 0],
            ['mer_banner',''],
            ['mini_banner','']
        ], true);
        if($id&&!preg_match('/^[1-9]\d*$/',$id)){
            return app('json')->fail('ID为正整数');
        }
        $is_best = $is_best==1?1:0;
        if(!in_array($type_id, [3, 5])){
            return app('json')->fail('类型错误');
        }
        $data = $this->services->editInfo($id, $type_id, $mer_name, $mer_info, $mer_address, $is_best, $mer_banner, $mini_banner);
        return app('json')->success('success');
    }
    
    /**
     * 获取用户列表
     * @return mixed
     */
    public function merList(Request $request)
    {
        [$uid, $min_money, $max_money, $status,$email, $phone, $invite_uid, $mer_name] = $request->postMore([
            [['uid', 'd'],0],
            [['min_money', 'd'], 0],
            [['max_money', 'd'], 0],
            [['status', 'd'], 0],
            ['email',''],
            ['phone',''],
            ['invite_uid',''],
            ['mer_name',''],
        ], true);
        if($uid&&!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('用户ID为正整数');
        }
        if($invite_uid&&!preg_match('/^[1-9]\d*$/',$invite_uid)){
            return app('json')->fail('邀请人ID为正整数');
        }
        if($status&&!in_array($status,[1,2])){
            return app('json')->fail('用户状态参数错误');
        }
        if(!$status){
            $status = -1;
        }else{
            $status = $status==1?1:0;
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
        
        $user_ids = [];
        $UserServices = app()->make(UserServices::class);
        if($phone){
            if (!check_phone($phone)) {
                return app('json')->success(['list'=>[],'count'=>0]);
            }
            $user_phone = $UserServices->searchPhone($phone);
            if(empty($user_phone)){
                return app('json')->success(['list'=>[],'count'=>0]);
            }
            $user_ids[] = $user_phone->id;
        }
        if($email){
            if (!check_mail($email)) {
                return app('json')->success(['list'=>[],'count'=>0]);
            }
            $user_email = $UserServices->searchEmail($email);
            if(empty($user_email)){
                return app('json')->success(['list'=>[],'count'=>0]);
            }
            $user_ids[] = $user_phone->id;
        }
        
        $data = $this->services->getList($uid, $user_ids, $min_money, $max_money, $status, $invite_uid, $mer_name);
        return app('json')->success($data);
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
        [$id, $uid, $status, $start_date, $end_date, $order_no, $mer_name] = $request->postMore([
            [['id', 'd'],0],
            [['uid', 'd'],0],
            [['status', 'd'],0],
            ['start_date', ''],
            ['end_date', ''],
            ['order_no', ''],
            ['mer_name', '']
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
        if(!$uid&&$mer_name){
            $user_ids = $this->services->searchMerName($mer_name);
            if(empty($user_ids)){
                return app('json')->success(['list'=>[],'count'=>0]);
            }
        }
        if($uid){
           $user_ids[]= $uid;
        }
        $MerchantRechargeServices = app()->make(MerchantRechargeServices::class);
        $data = $MerchantRechargeServices->getAList($id, $user_ids, $status, $start_date, $end_date, $order_no);
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
        $MerchantRechargeServices = app()->make(MerchantRechargeServices::class);
        $data = $MerchantRechargeServices->rechargeEdit($admin, $id, $type, $is_truth);
        return app('json')->success('保存成功');
    }
    
    
    public function applyList(Request $request)
    {
        [$uid, $mer_name, $phone] = $request->postMore([
            [['uid', 'd'],0],
            ['mer_name', ''],
            ['phone', ''],
        ], true);
        if($uid&&!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('参数错误');
        }
        $user_ids = [];
        if($uid){
            $user_ids[] = $uid;
        }
        $MerchantApplyServices = app()->make(MerchantApplyServices::class);
        $data = $MerchantApplyServices->getAList($user_ids, $mer_name, $phone);
        return app('json')->success($data);
    }
    
    
    
    public function applyedit(Request $request)
    {
        [$id, $name, $idnumber, $status] = $request->postMore([
            [['id', 'd'],0],
            ['name', ''],
            ['idnumber', ''],
            [['status', 'd'],0],
        ], true);
        if(!preg_match('/^[1-9]\d*$/',$id)){
            return app('json')->fail('参数错误');
        }
        $status = $status==1?1:2;
        $admin = $request->admin();
        $MerchantApplyServices = app()->make(MerchantApplyServices::class);
        $data = $MerchantApplyServices->applyEdit($admin, $id, $name, $idnumber, $status);
        return app('json')->success($data);
    }
    
    
    public function numedit(Request $request)
    {
        $data = $request->postMore([
            [['id', 'd'],0],
            [['level', 'd'],0],
            ['spread_ratio', ''],
            [['store_score', 'd'],0],
            [['credit_score', 'd'],0],
            [['product_score', 'd'],0],
            [['service_score', 'd'],0],
            [['postage_score', 'd'],0],
            [['min_nums', 'd'],0],
            [['max_nums', 'd'],0],
            [['mer_state', 'd'],0],
            [['status', 'd'],0],
        ]);
        if(!preg_match('/^[1-9]\d*$/',$data['id'])){
            return app('json')->fail('参数错误');
        }
        if(!preg_match('/^\d+(\.\d{0,2})?$/',$data['spread_ratio'])){
            return app('json')->fail('请输入正确的利润比例');
        }
        if($data['min_nums']>$data['max_nums']){
            $max_nums = $data['max_nums'];
            $data['max_nums'] = $data['min_nums'];
            $data['min_nums'] = $max_nums;
        }
        $admin = $request->admin();
        $data = $this->services->numEdit($data);
        return app('json')->success('success');
    }
    
    
    
    public function withdrawList(Request $request)
    {
        [$id, $uid, $status, $start_date, $end_date, $order_no, $mer_name] = $request->postMore([
            [['id', 'd'],0],
            [['uid', 'd'],0],
            [['status', 'd'],0],
            ['start_date', ''],
            ['end_date', ''],
            ['order_no', ''],
            ['mer_name', '']
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
        if(!$uid&&$mer_name){
            $user_ids = $this->services->searchMerName($mer_name);
            if(empty($user_ids)){
                return app('json')->success(['list'=>[],'count'=>0]);
            }
        }
        if($uid){
           $user_ids[]= $uid;
        }
        $MerchantWithdrawServices = app()->make(MerchantWithdrawServices::class);
        $data = $MerchantWithdrawServices->getAList($id, $user_ids, $status, $start_date, $end_date, $order_no);
        return app('json')->success($data);
    }
    
    
    public function withdrawEdit(Request $request)
    {
        [$id, $type] = $request->postMore([
            [['id', 'd'],0],
            [['type','d'],0]
        ], true);
        if(!preg_match('/^[1-9]\d*$/',$id)){
            return app('json')->fail('参数错误');
        }
        if(!in_array($type,[1,2])){
            return app('json')->fail('参数错误');
        }
        $admin = $request->admin();
        $MerchantWithdrawServices = app()->make(MerchantWithdrawServices::class);
        $data = $MerchantWithdrawServices->withdrawEdit($admin, $id, $type);
        return app('json')->success('保存成功');
    }
    
    
    
    public function moneylog(Request $request)
    {
        [$id, $uid, $mer_name,$start_date, $end_date] = $request->postMore([
            [['id', 'd'],0],
            [['uid', 'd'],0],
            ['mer_name', ''],
            [['type', 'd'],0],
            ['start_date', ''],
            ['end_date', '']
        ], true);
        if($id&&!preg_match('/^[1-9]\d*$/',$id)){
            return app('json')->fail('参数错误');
        }
        if($uid&&!preg_match('/^[1-9]\d*$/',$uid)){
            return app('json')->fail('参数错误');
        }
        if($start_date&&!strtotime($start_date)){
            return app('json')->fail('开始日期错误');
        }
        if($end_date&&!strtotime($end_date)){
            return app('json')->fail('结束日期错误');
        }
        $user_ids = [];
        if(!$uid&&$mer_name){
            $user_ids = $this->services->searchMerName($mer_name);
            if(empty($user_ids)){
                return app('json')->success(['list'=>[],'count'=>0]);
            }
        }
        if($uid){
           $user_ids[]= $uid;
        }
        $MerchantMoneylogServices = app()->make(MerchantMoneylogServices::class);
        $data = $MerchantMoneylogServices->getAList($id, $user_ids, $start_date, $end_date);
        return app('json')->success($data);
    }
    
    /**
     * 获取用户列表
     * @return mixed
     */
    public function trafficOrderList(Request $request)
    {
        [$mer_id, $mer_name, $start_date, $end_date] = $request->postMore([
            [['mer_id', 'd'],0],
            ['mer_name',''],
            ['start_date',''],
            ['end_date','']
        ], true);
        
        $MerchantTrafficOrderServices = app()->make(MerchantTrafficOrderServices::class);
        $data = $MerchantTrafficOrderServices->getList($mer_id, $mer_name, $start_date, $end_date);
        return app('json')->success($data);
    }
}
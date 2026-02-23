<?php
namespace app\controller\api;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\user\UserServices;
use app\services\user\UserFavServices;
use app\services\user\UserCollectServices;
use app\services\user\UserAddressServices;
use app\services\user\UserVisitsServices;
use app\services\user\UserRechargeServices;
use app\services\product\ProductServices;
use app\services\product\ProductAttrValueServices;
use app\services\product\CartServices;
use app\services\order\OrderServices;
use app\services\merchant\MerchantServices;
use app\services\merchant\MerchantApplyServices;

/**
 * 用户类
 * Class User
 * @package app\controller\api
 */
class User
{
    /**
     * @var UserServices
     */
    #[Inject]
    protected UserServices $services;
    
    
    public function info(Request $request)
    {
        $user = $request->user();
        $phone = null;
        if($user['phone']){
           $phone = substr($user['phone'], 0, 2).'****'.substr($user['phone'], -3);
        }
        $email = null;
        if($user['email']){
           $email = substr($user['email'], 0, 2).'**@**'.substr($user['email'], -3);
        }
        $paypass = $user['withdraw_pwd']?1:0;
        $data = [
               'uid'=>$user['id'],
               'phone'=>$phone,
               'email'=>$email,
               'nickname'=>$user['nickname'],
               'avatar'=>$user['avatar'],
               'paypass'=>$paypass,
               'money'=>$user['money'],
               'gender'=>$user['sex'],
               'is_mer'=>$user['is_mer'],
               'mer_id'=>$user['mer_id'],
               'invitation_code'=>$user['invitation_code'],
               'signature_pic'=>$user['signature_pic']
            ];
        return app('json')->success($data);
    }
    
    public function ordernum(Request $request)
    {
        $uid = $request->uid();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->orderCount($uid);
        $UserFavServices = app()->make(UserFavServices::class);
        $data['favnum'] = $UserFavServices->favCount($uid);
        $UserCollectServices = app()->make(UserCollectServices::class);
        $data['collectnum'] = $UserCollectServices->collectCount($uid);
        return app('json')->success($data);
    }
    
    public function setAvatar(Request $request)
    {
        [$avatar] = $request->postMore([
            ['avatar', '']
        ], true);
        if(!$avatar){
            return app('json')->fail('Missing parameters');
        }
        $uid = $request->uid();
        if(!preg_match('/^https?:\/\/.+\.(jpeg|jpg|png|webp|svg|bmp|tiff|ico)(\?.*)?$/i',$avatar)){
            return app('json')->fail('Missing parameters');
        }
        $res = $this->services->setAvatar($uid, $avatar);
        return app('json')->success("success");
    }
    
    public function setSignture(Request $request)
    {
        [$img] = $request->postMore([
            ['img', '']
        ], true);
        if(!$img){
            return app('json')->fail('Missing parameters');
        }
        $uid = $request->uid();
        if(!preg_match('/^https?:\/\/.+\.(jpeg|jpg|png|webp|svg|bmp|tiff|ico)(\?.*)?$/i',$img)){
            return app('json')->fail('Missing parameters');
        }
        $res = $this->services->setSignture($uid, $img);
        return app('json')->success("success");
    }
    
    /**
     * 修改密码
     * @return mixed
     */
    public function editPass(Request $request)
    {
        [$opassword,$password] = $request->postMore([
            ['opassword', ''],
            ['password', '']
        ], true);
        if(!$opassword||!$password){
            return app('json')->fail('Missing parameters');
        }
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,20}$/', $password)){
            return app('json')->fail('Password length is 6-20 characters containing letters and numbers');
        }
        $user = $request->user();
        if (!password_verify($opassword, $user['pwd'])){
            return app('json')->fail('Original login password verification failed');
        }
        if (password_verify($password, $user['pwd'])){
            return app('json')->fail('The new password must not be the same as the original password');
        }
        $res = $this->services->editPass($user['id'], $password);
        return app('json')->success('success');
    }
    
    /**
     * 修改支付密码
     * @return mixed
     */
    public function editSPass(Request $request)
    {
        [$opassword,$password] = $request->postMore([
            ['opassword', ''],
            ['password', '']
        ], true);
        if(!$opassword||!$password){
            return app('json')->fail('Missing parameters');
        }
        if(!preg_match('/^\d{6}$/', $password)){
             return app('json')->fail('Missing parameters');
         }
        $user = $request->user();
        if (md5($opassword)!=$user['withdraw_pwd']){
            return app('json')->fail('Original login password verification failed');
        }
        $res = $this->services->editSPass($user['id'], $password);
        return app('json')->success('success');
    }
    
    public function checktpass(Request $request)
    {
        [$password] = $request->postMore([
            ['password', '']
        ], true);
        if(!$password){
            return app('json')->fail('Missing parameters');
        }
        $user = $request->user();
        if ($user['withdraw_pwd']!=md5($password)){
            return app('json')->fail('Verification failed');
        }
        return app('json')->success('success');
    }
    
    /**
     * 保存昵称
     * @return mixed
     */
    public function setNick(Request $request)
    {
        [$nick] = $request->postMore([
            ['nick', '']
        ], true);
        if(!$nick){
            return app('json')->fail('Missing parameters');
        }
        $pattern = '/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u';
        if (!preg_match($pattern, $nick)){
            return app('json')->fail('Nicknames must be 2-16 characters long; please do not enter special characters.');
        }
        $uid = $request->uid();
        $res = $this->services->setNick($uid, $nick);
        return app('json')->success('success');
    }
    
    public function setPhone(Request $request)
    {
        [$region, $account, $vcode, $password] = $request->postMore([
            ['region', ''],
            ['account', ''],
            ['vcode', ''],
            ['password', '']
        ], true);
        if(!$region||!$account||!$vcode||!$password){
            return app('json')->fail('Missing parameters');
        }
        if(!check_phone($account)){
            return app('json')->fail('Please enter a valid mobile phone number');
        }
        $code = CacheService::get('verify.key.'.md5($account));
        if ($code!=$vcode){
            return app('json')->fail('Incorrect verification code');
        }
        $user = $request->user();
        if (!password_verify($password, $user['pwd'])){
            return app('json')->fail('Password verification error');
        }
        $res = $this->services->setPhone($user['id'], $region, $account);
        return app('json')->success('success');
    }
    
    /**
     * 保存性别
     * @return mixed
     */
    public function setGender(Request $request)
    {
        [$gender] = $request->postMore([
            [['gender', 'd'], 0]
        ], true);
        if(!in_array($gender,[0, 1,2])){
            return app('json')->fail('Missing parameters');
        }
        $uid = $request->uid();
        $res = $this->services->setGender($uid, $gender);
        return app('json')->success('保存成功');
    }
    
    public function setDel(Request $request)
    {
        $uid = $request->uid();
        $res = $this->services->setDel($uid);
        return app('json')->success('保存成功');
    }
    
    public function setEmail(Request $request)
    {
        [$account, $vcode, $password] = $request->postMore([
            ['account', ''],
            ['vcode', ''],
            ['password', '']
        ], true);
        if(!$account||!$vcode||!$password){
            return app('json')->fail('Missing parameters');
        }
        if(!check_mail($account)){
            return app('json')->fail('Please enter a valid email address');
        }
        $code = CacheService::get('verify.key.'.md5($account));
        if ($code!=$vcode){
            return app('json')->fail('Incorrect verification code');
        }
        $user = $request->user();
        if (!password_verify($password, $user['pwd'])){
            return app('json')->fail('Password verification error');
        }
        $res = $this->services->setEmail($user['id'], $account);
        return app('json')->success('success');
    }
    
    public function prodetail(Request $request)
    {
         [$pro_id] = $request->postMore([
            [['pro_id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $pro_id)){
             return app('json')->fail('Missing parameters');
         }
         $ProductServices = app()->make(ProductServices::class);
         $mer_id = $ProductServices->getMerId($pro_id);
         if(!$mer_id){
             return app('json')->fail('The product does not exist');
         }
         
         $uid = $request->uid();
         $data = [];
         ///购物车数量
         $CartServices = app()->make(CartServices::class);
         $data['cartnum'] = $CartServices->cartnum($uid);
         ///收藏商品
         $UserFavServices = app()->make(UserFavServices::class);
         $data['favproduct'] = $UserFavServices->favProduct($uid, $pro_id);
         ///是否关注店铺
         $UserCollectServices = app()->make(UserCollectServices::class);
         $data['colectmer'] = $UserCollectServices->collectMer($uid, $mer_id);
         return app('json')->success($data);
    }
    
    public function collectMer(Request $request)
    {
         [$mer_id] = $request->postMore([
            [['mer_id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $mer_id)){
             return app('json')->fail('Missing parameters');
         }
         $MerchantServices = app()->make(MerchantServices::class);
         $mer = $MerchantServices->getMer($mer_id);
         if(empty($mer)){
             return app('json')->fail('Merchant does not exist');
         }
         
         $uid = $request->uid();
         ///是否关注店铺
         $UserCollectServices = app()->make(UserCollectServices::class);
         $colectmer = $UserCollectServices->collectMer($uid, $mer_id);
         if($colectmer==1){
             $UserCollectServices->unfollowMer($uid, $mer_id);
         }else{
             $UserCollectServices->followMer($uid, $mer_id);
         }
         return app('json')->success('success');
    }
    
    public function favPro(Request $request)
    {
         [$pro_id] = $request->postMore([
            [['pro_id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $pro_id)){
             return app('json')->fail('Missing parameters');
         }
         $ProductServices = app()->make(ProductServices::class);
         $mer_id = $ProductServices->getMerId($pro_id);
         if(!$mer_id){
             return app('json')->fail('The product does not exist');
         }
         
         $uid = $request->uid();
         ///是否收藏商品
         $UserFavServices = app()->make(UserFavServices::class);
         $favproduct = $UserFavServices->favProduct($uid, $pro_id);
         if($favproduct==1){
             $UserFavServices->unFavPro($uid, $pro_id);
         }else{
             $UserFavServices->favPro($uid, $pro_id);
         }
         return app('json')->success('success');
    }
    
    public function storelist(Request $request)
    {
         $uid = $request->uid();
         $lang = $request->lang();
         ///是否收藏商品
         $UserCollectServices = app()->make(UserCollectServices::class);
         $data = $UserCollectServices->getList($lang, $uid);
         return app('json')->success($data);
    }
    
    public function favlist(Request $request)
    {
         $uid = $request->uid();
         $lang = $request->lang();
         ///是否收藏商品
         $UserFavServices = app()->make(UserFavServices::class);
         $data = $UserFavServices->getFavList($lang, $uid);
         return app('json')->success($data);
    }
    
    public function favDel(Request $request)
    {
         [$id] = $request->postMore([
            [['id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $id)){
             return app('json')->fail('Missing parameters');
         }
         $uid = $request->uid();
         ///是否收藏商品
         $UserFavServices = app()->make(UserFavServices::class);
         $UserFavServices->favDel($uid, $id);
         return app('json')->success('success');
    }
    
    
    public function addCart(Request $request)
    {
         [$pro_id, $sku_id, $cart_num, $is_buy, $sku] = $request->postMore([
            [['pro_id', 'd'], 0],
            [['sku_id', 'd'], 0],
            [['cart_num', 'd'], 0],
            [['is_buy', 'd'], 0],
            ['sku', []]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $pro_id)){
             return app('json')->fail('Missing parameters');
         }
         if($sku_id&&!preg_match('/^[1-9]\d*$/', $sku_id)){
             return app('json')->fail('Missing parameters');
         }
         if(!preg_match('/^[1-9]\d*$/', $cart_num)){
             return app('json')->fail('Missing parameters');
         }
         $is_buy = $is_buy==1?1:0;
         $ProductServices = app()->make(ProductServices::class);
         $mer_id = $ProductServices->getMerId($pro_id);
         if(!$mer_id){
             return app('json')->fail('The product does not exist');
         }
         if($sku_id){
             $ProductAttrValueServices = app()->make(ProductAttrValueServices::class);
             $sku = $ProductAttrValueServices->getSku($sku_id);
             if(empty($sku)){
                 return app('json')->fail('The SKU does not exist');
             }
             $sku_arr = json_decode($sku['detail'], true);
             $sku_str_arr = [];
             foreach ($sku_arr as $key=>$value){
                 $sku_str_arr[] = $key.':'.$sku_arr[$key];
             }
             $sku_str = implode(',', $sku_str_arr);
         }else{
             $sku_str = '';
             if($sku){
                 if(!is_array($sku)){
                     return app('json')->fail('Missing parameters');
                 }
                 $sku_str_arr = [];
                 foreach ($sku as $key=>$value){
                     $sku_str_arr[] = $key.':'.$sku[$key];
                 }
                 $sku_str = implode(',', $sku_str_arr);
             }
         }
         $uid = $request->uid();
         ///更新购物车
         $CartServices = app()->make(CartServices::class);
         $data = $CartServices->addCart($uid, $pro_id, $sku_id, $cart_num, $is_buy, $mer_id, $sku_str);
         return app('json')->success($data);
    }
    
    public function cart(Request $request)
    {
         [$cart_id] = $request->postMore([
            ['cart_id', []]
         ], true);
         if(!empty($cart_id)&&!is_array($cart_id)){
             return app('json')->fail('Missing parameters');
         }
         $lang = $request->lang();
         $uid = $request->uid();
         $CartServices = app()->make(CartServices::class);
         $data = $CartServices->getCart($lang, $uid, $cart_id);
         return app('json')->success($data);
    }
    
    
    public function delcart(Request $request)
    {
         [$cart_id] = $request->postMore([
            ['cart_id', []]
         ], true);
         if(!is_array($cart_id)||empty($cart_id)){
             return app('json')->fail('Missing parameters');
         }
         $uid = $request->uid();
         $CartServices = app()->make(CartServices::class);
         $data = $CartServices->delCart($uid, $cart_id);
         return app('json')->success('success');
    }
    
    public function cartnum(Request $request)
    {
         [$cart_id, $num] = $request->postMore([
            [['cart_id', 'd'], 0],
            [['num', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $cart_id)){
             return app('json')->fail('Missing parameters');
         }
         if(!preg_match('/^[1-9]\d*$/', $num)){
             return app('json')->fail('Missing parameters');
         }
         $uid = $request->uid();
         $CartServices = app()->make(CartServices::class);
         $data = $CartServices->setCartNum($uid, $cart_id, $num);
         return app('json')->success('success');
    }
    
    
    public function addAddress(Request $request)
    {
         $data = $request->postMore([
            ['name', ''],
            ['phone', ''],
            // ['build', ''],
            ['street', ''],
            ['city', ''],
            ['province', ''],
            ['zipcode', ''],
            ['country', ''],
            [['is_default', 'd'], 0]
         ]);
         foreach ($data as $key => $value) {
             if(empty($value)&&$key!='is_default'){
                 return app('json')->fail('Missing parameters');
             }
             if($key=='street'&&mb_strlen($value)>100){
                 return app('json')->fail('The string cannot exceed the limit 100');
             }else{
                 if(mb_strlen($value)>50){
                     return app('json')->fail('The string cannot exceed the limit 50');
                 }
             }
         }
         $data['is_default'] = $data['is_default']==1?1:0;
         $uid = $request->uid();
         $UserAddressServices = app()->make(UserAddressServices::class);
         $res = $UserAddressServices->add($uid, $data);
         return app('json')->success('success');
    }
    
    
    public function addressList(Request $request)
    {
        $uid = $request->uid();
        $UserAddressServices = app()->make(UserAddressServices::class);
        $data = $UserAddressServices->getList($uid);
        return app('json')->success($data);
    }
    
    public function addressDel(Request $request)
    {
         [$id] = $request->postMore([
            [['id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $id)){
             return app('json')->fail('Missing parameters');
         }
         $uid = $request->uid();
         $UserAddressServices = app()->make(UserAddressServices::class);
         $data = $UserAddressServices->del($uid, $id);
         return app('json')->success('success');
    }
    
    public function addDefault(Request $request)
    {
         [$id] = $request->postMore([
            [['id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $id)){
             return app('json')->fail('Missing parameters');
         }
         $uid = $request->uid();
         $UserAddressServices = app()->make(UserAddressServices::class);
         $data = $UserAddressServices->setdefault($uid, $id);
         return app('json')->success('success');
    }
    
    public function addressDefault(Request $request)
    {
         $uid = $request->uid();
         $UserAddressServices = app()->make(UserAddressServices::class);
         $data = $UserAddressServices->getdefault($uid);
         return app('json')->success($data);
    }
    
    public function addPass(Request $request)
    {
         [$password] = $request->postMore([
            [['password', 'd'], 0]
         ], true);
         if(!preg_match('/^\d{6}$/', $password)){
             return app('json')->fail('Missing parameters');
         }
         $uid = $request->uid();
         $data = $this->services->setSPass($uid, $password);
         return app('json')->success('success');
    }
    
    
    public function visits(Request $request)
    {
         $uid = $request->uid();
         $UserVisitsServices = app()->make(UserVisitsServices::class);
         $data = $UserVisitsServices->getList($uid);
         return app('json')->success($data);
    }
    
    
    public function doRecharge(Request $request)
    {
        [$amount, $type, $paytype] = $request->postMore([
            [['amount', 'd'],0],
            [['type', 'd'],0],
            [['paytype', 'd'],0]
         ],true);
        if(!$amount||!preg_match('/^[1-9]\d*$/',$amount)){
            return app('json')->fail('Missing parameters');
        }
        if(!in_array($type, [0, 1])){
            return app('json')->fail('Missing parameters');
        }
        $user = $request->user();
        $UserRechargeServices = app()->make(UserRechargeServices::class);
        $data = $UserRechargeServices->saveRecharge($user, $amount, $type, $paytype);
        return app('json')->success($data);
    }
     
     
    public function rechargeLog(Request $request)
    {
         $uid = $request->uid();
         $UserRechargeServices = app()->make(UserRechargeServices::class);
         $data = $UserRechargeServices->getList($uid);
         return app('json')->success($data);
    }
    
    
    public function orderlist(Request $request)
    {
        [$status] = $request->postMore([
            [['status', 'd'],0]
         ],true);
        if(!in_array($status, [0, 1, 2, 3, 4])){
            return app('json')->fail('Missing parameters');
        }
        $uid = $request->uid();
        $lang = $request->lang();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->getList($lang, $uid, $status);
        return app('json')->success($data);
    }
    
    public function orderrefund(Request $request)
    {
        [$id] = $request->postMore([
            [['id', 'd'],0]
         ],true);
        if(!preg_match('/^[1-9]\d*$/',$id)){
            return app('json')->fail('Missing parameters');
        }
        $uid = $request->uid();
        $lang = $request->lang();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->refund($uid, $id);
        return app('json')->success($data);
    }
    
    
    public function orderrecived(Request $request)
    {
        [$id] = $request->postMore([
            [['id', 'd'],0]
         ],true);
        if(!preg_match('/^[1-9]\d*$/',$id)){
            return app('json')->fail('Missing parameters');
        }
        $uid = $request->uid();
        $lang = $request->lang();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->recived($uid, $id);
        return app('json')->success($data);
    }
    
    
    
    public function applyMer(Request $request)
    {
        $data = $request->postMore([
            ['mer_name', ''],
            ['name', ''],
            ['phone', ''],
            ['idnumber', ''],
            [['cate_id', 'd'],0],
            [['type_id', 'd'],0],
            ['invite_code', ''],
            ['pics', []]
         ]);
        if(!preg_match('/^[1-9]\d*$/',$data['cate_id'])){
            return app('json')->fail('Missing parameters');
        }
        if(!in_array($data['type_id'], [3,5])){
            return app('json')->fail('Missing parameters');
        }
        if(!is_array($data['pics'])||!$data['pics']){
            return app('json')->fail('Missing parameters');
        }
        foreach ($data as $key => $value) {
            if(empty($value)){
                return app('json')->fail('Missing parameters');
            }
            if($key!='pics'){
                 if(mb_strlen($value)>50){
                     return app('json')->fail('The string cannot exceed the limit 50');
                 }
            }
        }
        $invite_code = $data['invite_code'];
        $invite_user = $this->services->fromInvite($invite_code);
        if(empty($invite_user)){
            return app('json')->fail('Invitation code does not exist');
        }
        $uid = $request->uid();
        $data['pics'] = implode(',', $data['pics']);
        $data['uid'] = $uid;
        $data['invite_uid']=$invite_user->id;
        $data['add_time'] = time();
        $MerchantApplyServices = app()->make(MerchantApplyServices::class);
        $data = $MerchantApplyServices->addApply($data);
        return app('json')->success('success');
    }
    
    
}
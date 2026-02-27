<?php
namespace app\controller\api;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\order\OrderServices;
use app\services\product\ProductServices;
use app\services\product\CategoryServices;
use app\services\product\PlatformProductServices;
use app\services\merchant\MerchantServices;
use app\services\merchant\MerchantCategoryServices;
use app\services\merchant\MerchantRechargeServices;
use app\services\merchant\MerchantBankServices;
use app\services\merchant\MerchantWithdrawServices;
use app\services\merchant\MerchantMoneylogServices;

/**
 * 商户类
 * Class Merchant
 * @package app\controller\api
 */
class Merchant
{
    /**
     * @var MerchantServices
     */
    #[Inject]
    protected MerchantServices $services;


    public function info(Request $request)
    {
        $user = $request->user();
        $phone = null;
        if ($user['phone']) {
            $phone = substr($user['phone'], 0, 2) . '****' . substr($user['phone'], -3);
        }
        $email = null;
        if ($user['email']) {
            $email = substr($user['email'], 0, 2) . '**@**' . substr($user['email'], -3);
        }
        $paypass = $user['withdraw_pwd'] ? 1 : 0;
        $data = [];
        $data['user'] = [
            'uid' => $user['id'],
            'phone' => $phone,
            'email' => $email,
            'nickname' => $user['nickname'],
            'avatar' => $user['avatar'],
            'paypass' => $paypass,
            'money' => $user['money'],
            'gender' => $user['sex']
        ];
        $lang = $request->lang();
        $mer = $this->services->getMer($user['mer_id']);
        $catename = app()->make(MerchantCategoryServices::class)->catename($lang, $mer['category_id']);
        $data['mer'] = [
            'mer_id' => $mer['id'],
            'mer_name' => $mer['mer_name'],
            'real_name' => $mer['real_name'],
            'mer_avatar' => $mer['mer_avatar'],
            'mer_money' => $mer['mer_money'],
            'mer_info' => $mer['mer_info'],
            'mer_address' => $mer['mer_address'],
            'spread_ratio' => $mer['spread_ratio'],
            'store_score' => $mer['store_score'],
            'credit_score' => $mer['credit_score'],
            'level' => $mer['level'],
            'create_time' => $mer['create_time'],
            'cate_name' => $catename
        ];
        return app('json')->success($data);
    }

    public function ordernum(Request $request)
    {
        $mer_id = $request->merid();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->orderMerCount($mer_id);
        return app('json')->success($data);
    }

    public function profit(Request $request)
    {
        $mer_id = $request->merid();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->orderProfit($mer_id);
        return app('json')->success($data);
    }



    public function datacenter(Request $request)
    {
        [$type] = $request->postMore([
            [['type', 'd'], 0]
        ], true);
        $mer_id = $request->merid();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->orderDataProfit($mer_id, $type);
        return app('json')->success($data);
    }


    public function prolist(Request $request)
    {
        [$keyword] = $request->postMore([
            ['keyword', '']
        ], true);
        $lang = $request->lang();
        $ProductServices = app()->make(ProductServices::class);
        $mer_id = $request->merid();
        $list = $ProductServices->getMerList($lang, $mer_id, $keyword);
        return app('json')->success($list);
    }


    public function showpro(Request $request)
    {
        [$id, $status] = $request->postMore([
            [['id', 'd'], 0],
            [['status', 'd'], 0]
        ], true);
        $ProductServices = app()->make(ProductServices::class);
        $mer_id = $request->merid();
        $status = $status == 1 ? 1 : 0;
        $ProductServices->showpro($mer_id, $id, $status);
        return app('json')->success('success');
    }

    public function goodpro(Request $request)
    {
        [$id, $status] = $request->postMore([
            [['id', 'd'], 0],
            [['status', 'd'], 0]
        ], true);
        $ProductServices = app()->make(ProductServices::class);
        $mer_id = $request->merid();
        $status = $status == 1 ? 1 : 0;
        $ProductServices->goodpro($mer_id, $id, $status);
        return app('json')->success('success');
    }

    public function setrefund(Request $request)
    {
        [$id, $status] = $request->postMore([
            [['id', 'd'], 0],
            [['status', 'd'], 0]
        ], true);
        $OrderServices = app()->make(OrderServices::class);
        $mer_id = $request->merid();
        if (!in_array($status, [1, 2])) {
            return app('json')->fail('Missing parameters');
        }
        $OrderServices->setrefund($mer_id, $id, $status);
        return app('json')->success('success');
    }


    public function platlist(Request $request)
    {
        [$keyword, $cate_id] = $request->postMore([
            ['keyword', ''],
            [['cate_id', 'd'], 0]
        ], true);
        if ($cate_id && !preg_match('/^[1-9]\d*$/', $cate_id)) {
            return app('json')->fail('Missing parameters');
        }
        $lang = $request->lang();
        $PlatformProductServices = app()->make(PlatformProductServices::class);
        $mer_id = $request->merid();
        $list = $PlatformProductServices->getList($lang, $mer_id, $cate_id, $keyword);
        return app('json')->success($list);
    }

    public function platItem(Request $request)
    {
        [$pro_id] = $request->postMore([
            [['pro_id', 'd'], 0]
        ], true);
        if ($pro_id && !preg_match('/^[1-9]\d*$/', $pro_id)) {
            return app('json')->fail('Missing parameters');
        }
        $lang = $request->lang();
        $PlatformProductServices = app()->make(PlatformProductServices::class);
        $mer_id = $request->merid();
        $item = $PlatformProductServices->getDetail($lang, $pro_id, $mer_id);
        return app('json')->success($item);
    }

    public function platcate(Request $request)
    {
        $lang = $request->lang();
        $CategoryServices = app()->make(CategoryServices::class);
        $list = $CategoryServices->platcatelist($lang);
        return app('json')->success($list);
    }


    public function postpro(Request $request)
    {
        [$pro_ids] = $request->postMore([
            ['pro_ids', []]
        ], true);
        if (!is_array($pro_ids) || empty($pro_ids)) {
            return app('json')->fail('Missing parameters');
        }
        $PlatformProductServices = app()->make(PlatformProductServices::class);
        $mer_id = $request->merid();
        $list = $PlatformProductServices->postPro($mer_id, $pro_ids);
        return app('json')->success('success');
    }


    public function orderlist(Request $request)
    {
        [$status, $is_caigou, $keyword] = $request->postMore([
            [['status', 'd'], 0],
            [['is_caigou', 'd'], 0],
            ['keyword', '']
        ], true);
        if (!in_array($status, [-2, -1, 0, 1, 2, 3, 4])) {
            return app('json')->fail('Missing parameters');
        }
        if (!in_array($is_caigou, [0, 1, 2])) {
            return app('json')->fail('Missing parameters');
        }
        $mer_id = $request->merid();
        $lang = $request->lang();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->getMerList($lang, $mer_id, $status, $is_caigou, $keyword);
        return app('json')->success($data);
    }


    public function cgorder(Request $request)
    {
        [$password, $order_id] = $request->postMore([
            ['password', ''],
            [['order_id', 'd'], 0]
        ], true);
        if (!preg_match('/^[1-9]\d*$/', $order_id)) {
            return app('json')->fail('Missing parameters');
        }
        $user = $request->user();
        if (md5($password) != $user['withdraw_pwd']) {
            return app('json')->fail('Incorrect payment password');
        }
        $OrderServices = app()->make(OrderServices::class);
        $mer_id = $request->merid();
        $list = $OrderServices->caigou($mer_id, $order_id);
        return app('json')->success('success');
    }


    public function setAvatar(Request $request)
    {
        [$avatar] = $request->postMore([
            ['avatar', '']
        ], true);
        if (!$avatar) {
            return app('json')->fail('Missing parameters');
        }
        $mer_id = $request->merid();
        if (!preg_match('/^https?:\/\/.+\.(jpeg|jpg|png|webp|svg|bmp|tiff|ico)(\?.*)?$/i', $avatar)) {
            return app('json')->fail('Missing parameters');
        }
        $res = $this->services->setAvatar($mer_id, $avatar);
        return app('json')->success("success");
    }

    public function setBanner(Request $request)
    {
        [$avatar] = $request->postMore([
            ['avatar', '']
        ], true);
        if (!$avatar) {
            return app('json')->fail('Missing parameters');
        }
        $mer_id = $request->merid();
        if (!preg_match('/^https?:\/\/.+\.(jpeg|jpg|png|webp|svg|bmp|tiff|ico)(\?.*)?$/i', $avatar)) {
            return app('json')->fail('Missing parameters');
        }
        $res = $this->services->setBanner($mer_id, $avatar);
        return app('json')->success("success");
    }


    public function saveinfo(Request $request)
    {
        [$mer_info, $real_name, $address] = $request->postMore([
            ['mer_info', ''],
            ['real_name', ''],
            ['address', '']
        ], true);
        if (!$mer_info && !$address) {
            return app('json')->fail('Missing parameters');
        }
        $mer_id = $request->merid();
        if (mb_strlen($mer_info) > 500) {
            return app('json')->fail('Introduction limited to 500 characters');
        }
        if (mb_strlen($mer_info) > 100) {
            return app('json')->fail('Address limited to 100 characters');
        }
        $res = $this->services->setInfo($mer_id, $mer_info, $real_name, $address);
        return app('json')->success("success");
    }


    public function doRecharge(Request $request)
    {
        [$amount, $type, $paytype, $pic] = $request->postMore([
            [['amount', 'd'], 0],
            [['type', 'd'], 0],
            [['paytype', 'd'], 0],
            ['pic', '']
        ], true);
        if (!$amount || !preg_match('/^[1-9]\d*$/', $amount)) {
            return app('json')->fail('Missing parameters');
        }
        if (!in_array($type, [0, 1])) {
            return app('json')->fail('Missing parameters');
        }
        if ($pic && !preg_match('/^https?:\/\/.+\.(jpeg|jpg|png|webp|svg|bmp|tiff|ico)(\?.*)?$/i', $pic)) {
            return app('json')->fail('Missing parameters');
        }
        $mer_id = $request->merid();
        $MerchantRechargeServices = app()->make(MerchantRechargeServices::class);
        $data = $MerchantRechargeServices->saveRecharge($mer_id, $amount, $type, $paytype, $pic);
        return app('json')->success($data);
    }


    public function rechargeLog(Request $request)
    {
        $mer_id = $request->merid();
        $MerchantRechargeServices = app()->make(MerchantRechargeServices::class);
        $data = $MerchantRechargeServices->getList($mer_id);
        return app('json')->success($data);
    }


    public function saveBank(Request $request)
    {
        $data = $request->postMore([
            [['type', 'd'], 0],
            ['bankname', ''],
            ['city', ''],
            ['bankaddress', ''],
            ['ename', ''],
            ['account', ''],
            ['swift', ''],
            ['bankcode', ''],
            ['uaddress', ''],
            ['zipcode', ''],
            ['zhengpic', ''],
            ['fanpic', '']
        ]);
        if (!in_array($data['type'], [1, 2])) {
            return app('json')->fail('Missing parameters');
        }
        if ($data['zhengpic'] && !preg_match('/^https?:\/\/.+\.(jpeg|jpg|png|webp|svg|bmp|tiff|ico)(\?.*)?$/i', $data['zhengpic'])) {
            return app('json')->fail('Missing parameters');
        }
        if ($data['fanpic'] && !preg_match('/^https?:\/\/.+\.(jpeg|jpg|png|webp|svg|bmp|tiff|ico)(\?.*)?$/i', $data['fanpic'])) {
            return app('json')->fail('Missing parameters');
        }
        if (!$data['bankname']) {
            return app('json')->fail('Missing parameters');
        }
        if (!$data['ename']) {
            return app('json')->fail('Missing parameters');
        }
        if (!$data['account']) {
            return app('json')->fail('Missing parameters');
        }
        foreach ($data as $key => $value) {
            if ($key != 'type' && $key != 'zhengpic' && $key != 'fanpic') {
                if ($key == 'uaddress' || $key == 'bankaddress') {
                    if (mb_strlen($value) > 100) {
                        return app('json')->fail('The string cannot exceed the limit 100');
                    }
                } else {
                    if (mb_strlen($value) > 50) {
                        return app('json')->fail('The string cannot exceed the limit 50');
                    }
                }
            }
        }
        $mer_id = $request->merid();
        $data['uid'] = $mer_id;
        $data['add_time'] = time();
        $MerchantBankServices = app()->make(MerchantBankServices::class);
        $data = $MerchantBankServices->saveBank($data);
        return app('json')->success('success');
    }

    public function getBank(Request $request)
    {
        $mer_id = $request->merid();
        $MerchantBankServices = app()->make(MerchantBankServices::class);
        $data = $MerchantBankServices->getBank($mer_id);
        if (empty($data)) {
            return app('json')->success([]);
        }
        return app('json')->success($data->toArray());
    }


    public function doWithdraw(Request $request)
    {
        [$amount] = $request->postMore([
            [['amount', 'd'], 0]
        ], true);
        if (!$amount || !preg_match('/^[1-9]\d*$/', $amount)) {
            return app('json')->fail('Missing parameters');
        }
        $mer_id = $request->merid();
        $MerchantBankServices = app()->make(MerchantBankServices::class);
        $bank = $MerchantBankServices->getBank($mer_id);
        if (empty($bank)) {
            return app('json')->fail('Missing parameters');
        }
        $MerchantWithdrawServices = app()->make(MerchantWithdrawServices::class);
        $data = $MerchantWithdrawServices->addWithdraw($mer_id, $amount);
        return app('json')->success('success');
    }


    public function withdrawLog(Request $request)
    {
        $mer_id = $request->merid();
        $MerchantWithdrawServices = app()->make(MerchantWithdrawServices::class);
        $data = $MerchantWithdrawServices->getList($mer_id);
        return app('json')->success($data);
    }

    public function moneylog(Request $request)
    {
        $mer_id = $request->merid();
        $MerchantMoneylogServices = app()->make(MerchantMoneylogServices::class);
        $data = $MerchantMoneylogServices->getList($mer_id);
        return app('json')->success($data);
    }




    public function orderdetail(Request $request)
    {
        [$order_id] = $request->postMore([
            [['order_id', 'd'], 0]
        ], true);
        if (!preg_match('/^[1-9]\d*$/', $order_id)) {
            return app('json')->fail('Missing parameters');
        }
        $lang = $request->lang();
        $mer_id = $request->merid();
        $OrderServices = app()->make(OrderServices::class);
        $data = $OrderServices->getInfo($lang, $order_id, $mer_id);
        return app('json')->success($data);
    }


}
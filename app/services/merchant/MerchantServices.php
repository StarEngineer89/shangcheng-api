<?php
declare(strict_types=1);
namespace app\services\merchant;

use app\services\BaseServices;
use app\services\merchant\MerchantMoneylogServices;
use app\model\Merchant;

/**
 * Class MerchantServices
 * @package app\services
 * @mixin Merchant
 */
class MerchantServices extends BaseServices
{
    protected function setModel(): string
    {
        return Merchant::class;
    }

    public function getMer($mer_id)
    {
        return $this->getModel()->where('id', $mer_id)->find();
    }

    public function searchMerName(string $keyword)
    {
        return $this->getModel()->where('mer_name', 'LIKE', "%$keyword%")->column('id');
    }

    public function indexMerchant()
    {
        $list = $this->getModel()->field('id,mer_name,mer_avatar,mini_banner,type_id')->with([
            'product' => function ($query) {
                $query->field('id,image,price,mer_id')->limit(4);
            }
        ])->where('is_best', 1)->where('status', 1)->where('is_del', 0)->select()->toArray();
        return compact('list');
    }

    public function numEdit($data)
    {
        $id = $data['id'];
        unset($data['id']);
        return $this->getModel()->where('id', $id)->update($data);
    }

    public function getmers()
    {
        return $this->getModel()->field('id,min_nums,max_nums')->where('id', '>', 54)->where('status', 1)->where('mer_state', 1)->select()->toArray();
    }

    /**
     * 保存头像
     * @param int $uid
     * @param int $avatar
     */
    public function setAvatar($uid, $avatar)
    {
        $res = $this->getModel()->where('id', $uid)->update(['mer_avatar' => $avatar]);
        return true;
    }

    public function setBanner($uid, $avatar)
    {
        $res = $this->getModel()->where('id', $uid)->update(['mer_banner' => $avatar]);
        return true;
    }

    public function setInfo($uid, $mer_info, $real_name, $address)
    {
        $res = $this->getModel()->where('id', $uid)->update(['mer_info' => $mer_info, 'real_name' => $real_name, 'mer_address' => $address]);
        return true;
    }


    public function editInfo($id, $type_id, $mer_name, $mer_info, $mer_address, $is_best, $mer_banner, $mini_banner)
    {
        $res = $this->getModel()->where('id', $id)->update([
            'type_id' => $type_id,
            'mer_name' => $mer_name,
            'mer_info' => $mer_info,
            'mer_address' => $mer_address,
            'is_best' => $is_best,
            'mer_banner' => $mer_banner,
            'mini_banner' => $mini_banner
        ]);
        return true;
    }

    /**
     * 加款
     * @param int $uid
     * @param string $amount
     */
    public function addMoney($uid, $amount)
    {
        return $this->transaction(function () use ($uid, $amount) {
            $user = $this->getModel()->where('id', $uid)->lock(true)->find();
            if (!$user) {
                throw new ApiException('商户不存在');
            }
            $update = [];
            $update['mer_money'] = bcadd((string) $user['mer_money'], (string) $amount, 2);

            $time = time();
            $resu = $this->getModel()->where('id', $uid)->update($update);
            if (!$resu) {
                throw new ApiException('系统繁忙，请稍后再试');
            }

            $MerchantMoneylogServices = app()->make(MerchantMoneylogServices::class);
            $reslog = $MerchantMoneylogServices->addMoneylog([
                'type' => 2,
                'uid' => $uid,
                'state' => 1,
                'title' => 'Additional',
                'amount' => $amount,
                'money' => $update['mer_money'],
                'add_time' => $time
            ]);
            if (!$reslog) {
                throw new ApiException('系统繁忙，请稍后再试');
            }
            return [
                'money' => $update['mer_money']
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
        return $this->transaction(function () use ($uid, $amount) {
            $user = $this->getModel()->where('id', $uid)->lock(true)->find();
            if (!$user) {
                throw new ApiException('商户不存在');
            }
            if ($user['mer_money'] < $amount) {
                throw new ApiException('商户余额不足');
            }
            $update = [];
            $update['mer_money'] = bcsub((string) $user['mer_money'], $amount, 2);

            $time = time();
            $resu = $this->getModel()->where('id', $uid)->update($update);
            if (!$resu) {
                throw new ApiException('系统繁忙，请稍后再试');
            }

            $MerchantMoneylogServices = app()->make(MerchantMoneylogServices::class);
            $reslog = $MerchantMoneylogServices->addMoneylog([
                'type' => 3,
                'uid' => $uid,
                'state' => 2,
                'title' => 'Deduction',
                'amount' => $amount,
                'money' => $update['mer_money'],
                'add_time' => $time
            ]);
            if (!$reslog) {
                throw new ApiException('系统繁忙，请稍后再试');
            }
            return [
                'money' => $update['mer_money']
            ];

        });
    }



    /**
     * 获取列表
     * @param $keyword
     */
    public function getlist($uid, $user_ids, $min_money, $max_money, $status, $invite_uid, $mer_name)
    {
        $where = [];
        if ($uid) {
            $where[] = ['id', '=', $uid];
        } else {
            $where[] = ['id', '>', 54];
        }
        if ($status != -1) {
            $where[] = ['status', '=', $status];
        }
        if ($min_money) {
            $where[] = ['mer_money', '>=', $min_money];
        }
        if ($max_money) {
            $where[] = ['mer_money', '<=', $max_money];
        }
        if ($mer_name) {
            $where[] = ['spread_uid', '=', $mer_name];
        }
        if ($user_ids) {
            $where[] = ['mer_uid', 'in', $user_ids];
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->getModel()->with(['user'])->where($where)->order('id', 'desc')->page($page, $limit)->select()->toArray();
        $count = $this->getModel()->where($where)->count();
        return compact('list', 'count');
    }

    public function find($params)
    {
        $where = $params;
        return $this->getModel()->where($where)->find()->toArray();
    }
}

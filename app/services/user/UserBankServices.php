<?php
declare (strict_types=1);
namespace app\services\user;
use app\services\BaseServices;
use app\model\UserBank;
use core\exceptions\ApiException;

/**
 * Class UserBankServices
 * @package app\services
 * @mixin UserBank
 */
class UserBankServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserBank::class;
    }
    
    
    public function getTypeName($type, $arr = false){
        $typeName = ['','微信','支付宝','银行卡','虚拟币','钱包'];
        if($arr){
            return $typeName;
        }
        return isset($typeName[$type])?$typeName[$type]:'';
    }
    
    /**
     * 获取用户收款缓存信息
     * @param $user_id
     * @return bool|mixed|null
     */
    public function getBankCacheInfo(int $user_id)
    {$this->cacheTag()->clear();
        return $this->cacheTag()->remember('user_bank_'.$user_id, function () use ($user_id) {
            return $this->getModel()->where('uid',$user_id)->column('*','type');
        });
    }
    
    
    /**
     * 修改收款方式
     * @param $uid
     * @param $room_num
     * @param $amount
     * @param $type
     * @param $intype
     */
    public function editBank($uid, $bank_name, $bank_realname, $bank_account, $bank_address, $bank_pic, $type)
    {
        $user_bank = $this->getModel()->where('uid',$uid)->column('*','type');
        if(!isset($user_bank[$type])){
            $res = $this->getModel()->create([
                    'bank_name' => $bank_name,
                    'bank_realname' => $bank_realname,
                    'bank_account' => $bank_account,
                    'bank_address' => $bank_address,
                    'bank_pic' => $bank_pic,
                    'type' => $type,
                    'uid' => $uid
                ]);
            if(!$res){
                throw new ApiException('系统繁忙，请稍候再试');
            }
        }else{
            $update = [];
            if($bank_name!=$user_bank[$type]['bank_name']){
                $update['bank_name'] = $bank_name;
            }
            if($bank_realname!=$user_bank[$type]['bank_realname']){
                $update['bank_realname'] = $bank_realname;
            }
            if($bank_account!=$user_bank[$type]['bank_account']){
                $update['bank_account'] = $bank_account;
            }
            if($bank_address!=$user_bank[$type]['bank_address']){
                $update['bank_address'] = $bank_address;
            }
            if($bank_pic!=$user_bank[$type]['bank_pic']){
                $update['bank_pic'] = $bank_pic;
            }
            if(!$update){
                return true;
            }
            $res = $this->getModel()->where('id',$user_bank[$type]['id'])->update($update);
            if(!$res){
                throw new ApiException('系统繁忙，请稍候再试');
            }
        }
        $this->cacheTag()->clear();
        return true;
    }
   
}
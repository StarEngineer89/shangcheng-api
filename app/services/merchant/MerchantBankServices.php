<?php
declare (strict_types=1);
namespace app\services\merchant;

use app\services\BaseServices;
use app\model\MerchantBank;
use core\exceptions\ApiException;

/**
 * Class MerchantBankServices
 * @package app\services
 * @mixin MerchantBank
 */
class MerchantBankServices extends BaseServices
{
    protected function setModel(): string
    {
        return MerchantBank::class;
    }
    
    public function getBank($mer_id)
    {
        return $this->getModel()->where('uid', $mer_id)->find();
    }
    
    /**
     * 添加
     */
    public function saveBank($data)
    {
        if($this->getModel()->where('uid', $data['uid'])->count()){
            unset($data['add_time']);
            return $this->getModel()->where('uid', $data['uid'])->update($data);
        }
        return $res = $this->getModel()->create($data);
    }
    
}
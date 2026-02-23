<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class MerchantWithdraw
 * @package app\model
 */
class MerchantWithdraw extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'merchant_withdraw';
    
    public function mer()
    {
        return $this->hasOne(Merchant::class, 'id', 'uid')->bind([
                'mer_name'=>'mer_name'
            ]);
    }
    
    public function bank()
    {
        return $this->hasOne(MerchantBank::class, 'uid', 'uid');
    }
    
}
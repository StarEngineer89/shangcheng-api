<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class MerchantRecharge
 * @package app\model
 */
class MerchantRecharge extends BaseModel
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
    protected $name = 'merchant_recharge';
    
    
    public function mer()
    {
        return $this->hasOne(Merchant::class, 'id', 'uid')->bind([
                'mer_name'=>'mer_name'
            ]);
    }
    
}
<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class Order
 * @package app\model
 */
class MerchantTrafficOrder extends BaseModel
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
    protected $name = 'merchant_trafficorder';
    
    
    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'id', 'merchant_id');
    }
    
}
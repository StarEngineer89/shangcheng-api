<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class Order
 * @package app\model
 */
class Order extends BaseModel
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
    protected $name = 'order';
    
    
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
    
}
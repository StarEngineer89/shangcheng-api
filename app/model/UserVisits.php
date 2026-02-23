<?php
namespace app\model;
use core\basic\BaseModel;

/**
 * 商品访问模型
 * Class UserVisits
 * @package app\model
 */
class UserVisits extends BaseModel
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
    protected $name = 'user_visits';
    
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
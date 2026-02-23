<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class Product
 * @package app\model
 */
class Product extends BaseModel
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
    protected $name = 'store_product';
    
    public function mer()
    {
        return $this->hasOne(Merchant::class, 'id', 'mer_id')->bind([
                'type_id'=>'type_id',
                'mer_name'=>'mer_name',
                'mer_avatar'=>'mer_avatar'
            ]);
    }
}
<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class Merchant
 * @package app\model
 */
class Merchant extends BaseModel
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
    protected $name = 'merchant';
    
    public function product()
    {
        return $this->hasMany(Product::class, 'mer_id', 'id');
    }
    
    public function cate()
    {
        return $this->hasMany(MerchantCategory::class, 'id', 'category_id');
    }
    
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'mer_uid')->bind([
                'phone'=>'phone',
                'email'=>'email'
            ]);
    }
    
}
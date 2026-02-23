<?php
namespace app\model;

use core\basic\BaseModel;

/**
 * 用户关注商户模型
 * Class UserCollect
 * @package app\model
 */
class UserCollect extends BaseModel
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
    protected $name = 'user_collect';
    
    public function product()
    {
        return $this->hasMany(Product::class, 'mer_id', 'mer_id');
    }
    
    public function mer()
    {
        return $this->hasOne(Merchant::class, 'id', 'mer_id')->bind([
               'mer_name'=>'mer_name',
               'mer_avatar'=>'mer_avatar',
               'store_score'=>'store_score'
            ]);
    }
}
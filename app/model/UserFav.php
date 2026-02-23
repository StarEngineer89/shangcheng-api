<?php
namespace app\model;

use core\basic\BaseModel;

/**
 * 用户收藏模型
 * Class UserFav
 * @package app\model
 */
class UserFav extends BaseModel
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
    protected $name = 'user_fav';
    
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
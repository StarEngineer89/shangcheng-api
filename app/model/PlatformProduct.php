<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class PlatformProduct
 * @package app\model
 */
class PlatformProduct extends BaseModel
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
    protected $name = 'store_platform_product';
    
    
    public function content()
    {
        return $this->hasOne(PlatformProductContent::class, 'product_id', 'id');
    }
    
    
    public function attr()
    {
        return $this->hasMany(PlatformProductAttr::class, 'product_id', 'id');
    }
    
    
    public function attrvalue()
    {
        return $this->hasMany(PlatformProductAttrValue::class, 'product_id', 'id');
    }
    
}
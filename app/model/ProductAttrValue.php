<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class ProductAttrValue
 * @package app\model
 */
class ProductAttrValue extends BaseModel
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
    protected $name = 'store_product_attr_value';
}
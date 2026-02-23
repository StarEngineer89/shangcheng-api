<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 模型
 * Class UserAddress
 * @package app\model
 */
class UserAddress extends BaseModel
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
    protected $name = 'user_address';
}
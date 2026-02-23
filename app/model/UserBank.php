<?php
namespace app\model;
use core\basic\BaseModel;

/**
 * 用户收款信息模型
 * Class UserBank
 * @package app\model
 */
class UserBank extends BaseModel
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
    protected $name = 'user_bank';
    
}
<?php
namespace app\model;

use core\basic\BaseModel;
/**
 * 系统配置模型
 * Class SystemConfig
 * @package app\model
 */
class SystemConfig extends BaseModel
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
    protected $name = 'system_config';
}
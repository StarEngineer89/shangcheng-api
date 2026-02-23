<?php
namespace app\model;

use core\basic\BaseModel;

/**
 * 用户邀请关系表模型
 * Class UserInvite
 * @package app\model
 */
class UserInvite extends BaseModel
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
    protected $name = 'user_invite';
}
<?php
namespace app\model;

use core\basic\BaseModel;

/**
 * 用户充值表模型
 * Class UserRecharge
 * @package app\model
 */
class UserRecharge extends BaseModel
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
    protected $name = 'user_recharge';
    
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'uid')->bind([
                'phone'=>'phone',
                'email'=>'email',
                'user_type'=>'is_promoter'
            ]);
    }
}
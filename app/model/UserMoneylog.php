<?php
namespace app\model;

use core\basic\BaseModel;

/**
 * 用户资金日志表模型
 * Class UserMoneylog
 * @package app\model
 */
class UserMoneylog extends BaseModel
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
    protected $name = 'user_moneylog';
    
    
    /**
	 * 关联用户
	 * @return \think\model\relation\HasOne
	 */
    public function user()
    {
        return $this->HasOne(User::class, 'id', 'uid');
    }
}
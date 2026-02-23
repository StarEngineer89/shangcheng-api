<?php
namespace app\model;
use core\basic\BaseModel;

/**
 * 用户登录日志模型
 * Class UserLoginlog
 * @package app\model
 */
class UserLoginlog extends BaseModel
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
    protected $name = 'user_loginlog';
    
    /**
	 * 关联用户
	 * @return \think\model\relation\HasOne
	 */
    public function user()
    {
        return $this->HasOne(User::class, 'id', 'uid')->bind([
               'username' => 'account',
               'nickname' => 'nickname'
            ]);
    }
}
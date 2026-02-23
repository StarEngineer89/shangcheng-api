<?php
namespace app\model;
use core\basic\BaseModel;

/**
 * 后台登录日志模型
 * Class AdminLoginlog
 * @package app\model
 */
class AdminLoginlog extends BaseModel
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
    protected $name = 'admin_loginlog';
    
    /**
     * 关联用户
     * @return Cai|\think\model\relation\hasOne
     */
    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->bind(['account'=>'account']);
    }
}
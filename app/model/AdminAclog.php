<?php
namespace app\model;
use core\basic\BaseModel;

/**
 * 后台用户操作记录模型
 * Class AdminAclog
 * @package app\model
 */
class AdminAclog extends BaseModel
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
    protected $name = 'admin_aclog';
    
    /**
	 * 关联用户
	 * @return \think\model\relation\HasOne
	 */
    public function admin()
    {
        return $this->HasOne(Admin::class, 'id', 'uid')->bind([
               'username' => 'account'
            ]);
    }
}
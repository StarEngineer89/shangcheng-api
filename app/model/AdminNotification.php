<?php
namespace app\model;

use core\basic\BaseModel;
use think\model\concern\SoftDelete;

/**
 * Class Admin
 * @package app\model
 */
class AdminNotification extends BaseModel
{
    use SoftDelete;

    /**
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'admin_notifications';

    protected $deleteTime = 'delete_time';

    protected $updateTime = false;
}
<?php
namespace app\model;
use core\basic\BaseModel;

/**
 * Class User
 * @package app\model
 */
class User extends BaseModel
{
    /**
     * @var string
     */
    protected $pk = 'id';

    protected $name = 'user';

}
<?php
namespace app;

use core\utils\Json;
use think\Service;
use app\Request;

/**
 * Class AppService
 * @package app
 */
class AppService extends Service
{

    public $bind = [
        'json' => Json::class,
        'request'=> Request::class
    ];

    public function boot()
    {
        defined('DS') || define('DS', DIRECTORY_SEPARATOR);
    }

}

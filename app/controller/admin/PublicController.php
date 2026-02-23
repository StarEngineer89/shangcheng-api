<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;

/**
 * 公共类
 * Class PublicController
 * @package app\controller\admin
 */
class PublicController
{
    /**
     * 获取网站配置
     * @return mixed
     */
    public function config()
    {
        $data = [];
        $data['site_name'] = sys_config('site_name');
        $data['check_url'] = sys_config('check_url');
        return app('json')->success($data);
    }


}
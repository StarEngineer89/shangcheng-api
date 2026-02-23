<?php
namespace app\controller;

use app\Request;
use think\facade\Config;
use think\facade\Log;
use think\captcha\facade\Captcha;
use core\services\CacheService;
use core\services\AliSMSService;
use core\services\HttpService;
use app\services\product\ProductServices;

/**
 * 测试
 * Class Test
 * @package app\controller
 */
class Test
{

    public function runTest(Request $request)
    {
        // $page = (int)$request->get('page');
        // echo app()->make(ProductServices::class)->checklist($page);
        // $page = $page + 1;
        // echo "<script>window.location.href='/test?page=".$page."'</script>";
    }
}

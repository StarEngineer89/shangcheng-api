<?php
namespace app\controller\api;

use app\Request;
use core\services\CacheService;

/**
 * 公共类
 * Class Base
 * @package app\controller\api
 */
class Base
{
    /**
     * 获取网站配置
     * @return mixed
     */
    public function config()
    {
        $data = [];
        $data['region'] = [
               ['name'=>'Uion State','code'=>'+1'],
               ['name'=>'中國香港','code'=>'+852'],
               ['name'=>'조선','code'=>'+850'],
               ['name'=>'Timor-Leste','code'=>'+670'],
               ['name'=>'Philippines','code'=>'+63'],
               ['name'=>'한국','code'=>'+82'],
               ['name'=>'Cambodia','code'=>'+855'],
               ['name'=>'Laos','code'=>'+856'],
               ['name'=>'Malaysia','code'=>'+60'],
               ['name'=>'Myanmar','code'=>'+95'],
               ['name'=>'Laos','code'=>'+856'],
               ['name'=>'Japan','code'=>'+81'],
               ['name'=>'ประเทศไทย','code'=>'+66'],
               ['name'=>'Brunei','code'=>'+673'],
               ['name'=>'Singapore','code'=>'+65'],
               ['name'=>'Indonesia','code'=>'+62'],
               ['name'=>'Việt Nam','code'=>'+84'],
               ['name'=>'中国','code'=>'+86'],
               ['name'=>'Australia','code'=>'+61'],
               ['name'=>'Canada','code'=>'+1'],
               ['name'=>'中國臺灣','code'=>'+886']
            ];
        $data['kefu_url'] = sys_config('kefu_url', '');
        return app('json')->success($data);
    }


}
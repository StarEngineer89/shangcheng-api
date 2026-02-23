<?php
namespace app\controller\admin;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\system\SystemConfigServices;
use app\services\admin\AdminServices;
use core\utils\GoogleAuthenticator;

/**
 * 系统配置类
 * Class Sysconfig
 * @package app\controller\admin
 */
class Sysconfig
{
     /**
     * @var SystemConfigServices
     */
     #[Inject]
     protected SystemConfigServices $services;
     
     
     /**
     * 获取列表
     * @return mixed
     */
    public function getConfig(Request $request)
    {
         $data = $this->services->getList();
         return app('json')->success($data);
    }
    
    public function editConfig(Request $request)
    {
        [$id, $content] = $request->postMore([
            [['id', 'd'],0],
            ['content', '']
        ], true);
        if (!$id||$content==='') {
            return app('json')->fail('缺少参数');
        }
        $data = $this->services->editConfig($id, $content);
        return app('json')->success('保存成功');
    }
}
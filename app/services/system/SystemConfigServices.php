<?php
declare (strict_types=1);

namespace app\services\system;

use app\services\BaseServices;
use app\model\SystemConfig;
use core\exceptions\ApiException;
use core\services\CacheService;

/**
 * Class SystemConfigServices
 * @package app\services
 * @mixin SystemConfig
 */
class SystemConfigServices extends BaseServices
{
    protected function setModel(): string
    {
        return SystemConfig::class;
    }
    
    /**
     * 获取服务器非对称密钥
     * @return array
     */
    public function getRSAKey()
    {
        return $this->cacheTag()->remember('system_rsa_key', function (){
              return $this->makePKey();
        });
    }
    
    

    /**
     * 获取某个系统配置
     * @param string $configNmae
     * @return mixed
     */
    public function getConfigValue(string $configNmae)
    {
        $res = $this->getConfigAll();
        return isset($res[$configNmae])?$res[$configNmae]:NULL;
    }
    
    /**
     * 获取所有配置
     * @return array
     */
    public function getConfigAll()
    {
        //$this->cacheTag()->clear();
        return $this->cacheTag()->remember('system_config', function (){
            return $this->getModel()->column('value', 'name');
        });
    }
    
    public function getList()
    {
        return $this->cacheTag()->remember('system_config_list', function (){
            return $this->getModel()->select()->toArray();
        });
    }
    
    
    /**
     * 保存配置
     * @param int $id
     * @param string $content
     * @return bool
     */
    public function editConfig($id, $content = '')
    {
        $res = $this->getModel()->where('id', $id)->find();
        if(!$res){
            throw new ApiException('该配置不存在');
        }
        if($res['value']==$content){
            return true;
        }
        $resu = $this->getModel()->where('id', $id)->update(['value'=>$content]);
        if(!$resu){
            throw new ApiException('系统繁忙，请稍后再试');
        }
        //$this->cacheTag()->clear();
        CacheService::delete($this->getCacheKey('system_config'));
        CacheService::delete($this->getCacheKey('system_config_list'));
        return true;
    }

}
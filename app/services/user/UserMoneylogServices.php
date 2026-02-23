<?php
declare (strict_types=1);
namespace app\services\user;

use app\services\BaseServices;
use app\model\UserMoneylog;
use core\exceptions\ApiException;
use think\facade\Log;

/**
 * Class UserMoneylogServices
 * @package app\services
 * @mixin UserMoneylog
 */
class UserMoneylogServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserMoneylog::class;
    }
    
    public function getType($type, $list = false){
                    ///// 1       2     3      4       5      6        7        8     9      10
        $typeName = ['购物','赠送','充值','提现','手续费','佣金','提现失败'];
        if($list){
            return $typeName;
        }
        return isset($typeName[$type])?$typeName[$type]:'';
    }
    /**
     * 记录积分日志
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addMoneylog(array $data)
    {
        if ($this->getModel()->create($data)) {
            return true;
        } else {
            return false;
        }
    }
    
}

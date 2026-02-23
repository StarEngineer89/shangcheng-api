<?php
declare (strict_types=1);
namespace app\services\admin;
use app\services\BaseServices;
use app\model\AdminNotification;

/**
 * Class AdminAclogServices
 * @package app\services
 * @mixin AdminAclog
 */
class AdminNotificationsServices extends BaseServices
{
    protected function setModel(): string
    {
        return AdminNotification::class;
    }
    

    public function setNotification($msg)
    {
        $data = [
            'msg' => $msg,
            'status' => 0,
        ];
        if ($this->getModel()->create($data)) {
            return true;
        } else {
            return false;
        }
    }
    
    
    public function getList()
    {
        $list = $this->getModel()->where('status', 0)->field('id, msg, create_time')->select()->toArray();
        $count = $this->getModel()->where('status', 0)->count();
        
        if ($count) {
            foreach ($list as $item) {
                $this->getModel()->where('id', $item['id'])->update(['status' => 1]);
            }
        }
        
        return compact('list', 'count');
        
    }

}

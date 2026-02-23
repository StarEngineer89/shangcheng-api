<?php
declare (strict_types=1);
namespace app\services\admin;
use app\services\BaseServices;
use app\model\AdminAclog;
use think\facade\Log;

/**
 * Class AdminAclogServices
 * @package app\services
 * @mixin AdminAclog
 */
class AdminAclogServices extends BaseServices
{
    protected function setModel(): string
    {
        return AdminAclog::class;
    }
    

    /**
     * 记录操作日志
     * @param int $uid
     * @param int $time
     * @param string $ip
     * @param string $content
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setAclog(int $uid, int $time, string $ip, string $content = '')
    {
        $city = $this->convertIp($ip);
        $data = [
            'uid' => $uid,
            'add_time' => $time,
            'ip' => $ip,
            'ip_city' => $city,
            'content' => $content
        ];
        if ($this->getModel()->create($data)) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * 获取操作记录
     * @param $uid
     * @param $datetype
     * @param $room_num
     * @param $type
     */
    public function getList($user_id, $startdate, $enddate, $ip = '')
    {
        $starttime = strtotime(date('Y-m-d'));
        if($startdate){
            $starttime = strtotime($startdate);
        }
        
        $endtime = time();
        if($enddate){
            $endtime = strtotime('+1day',strtotime($enddate));
        }
    
        $where=[];
        [$page, $limit] = $this->getPageValue();
        if($user_id){
           $where[] = ['uid', '=', $user_id];
        }
        $where[] = ['add_time', '>=', $starttime];
        $where[] = ['add_time', '<', $endtime];
        if($ip){
            $where[] = ['ip', 'LIKE', "%$ip%"];
        }
        $field = '*, FROM_UNIXTIME(add_time, "%Y-%m-%d %H:%i:%s") as addtime';
        $list = $this->getModel()->with(['admin'])->where($where)->field($field)->order('add_time', 'desc')->page($page, $limit)->select()->toArray();
        $count = $this->getModel()->where($where)->count();
        
        return compact('list', 'count');
        
    }

}

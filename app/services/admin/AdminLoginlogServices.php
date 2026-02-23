<?php
declare (strict_types=1);
namespace app\services\admin;

use app\services\BaseServices;
use app\model\AdminLoginlog;
use think\facade\Log;

/**
 * Class AdminLoginlogServices
 * @package app\services
 * @mixin AdminLoginlog
 */
class AdminLoginlogServices extends BaseServices
{
    protected function setModel(): string
    {
        return AdminLoginlog::class;
    }
    

    /**
     * 记录登录日志
     * @param int $uid
     * @param int $time
     * @param string $ip
     * @param int $is_fail
     * @param string $content
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setLoginlog(int $uid, int $time, string $ip, int $is_fail = 0, string $content = '')
    {
        $city = $this->convertIp($ip);
        $data = [
            'uid' => $uid,
            'login_time' => $time,
            'login_ip' => $ip,
            'login_city' => $city,
            'is_fail' => $is_fail,
            'content' => $content
        ];
        if ($this->getModel()->create($data)) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * 获取登录记录
     * @param $room_id
     * @param $startdate
     * @param $enddate
     * @param $status
     * @param $keyword
     */
    public function getList($admin_id, $startdate, $enddate, $ip)
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
        $where[] = ['login_time', '>=', $starttime];
        $where[] = ['login_time', '<', $endtime];
        $where[] = ['uid', '=', $admin_id];
        
        if($ip){
            $where[] = ['login_ip', 'LIKE', "%$ip%"];
        }
        
        $list = $this->getModel()->where($where)->with(['admin'])
                    ->field('*, FROM_UNIXTIME(login_time, "%H:%i:%s") as logintime')
                    ->cache(60)->order('login_time', 'desc')->page($page, $limit)->select()->toArray();
        $count = $this->getModel()->where($where)->count();
        return compact('list','count');
    }

}

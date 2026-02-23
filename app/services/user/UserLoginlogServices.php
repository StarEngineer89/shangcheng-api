<?php
declare (strict_types=1);
namespace app\services\user;
use app\services\room\RoomUserServices;
use app\services\BaseServices;
use app\model\UserLoginlog;
use think\facade\Db;

/**
 * Class UserLoginlogServices
 * @package app\services
 * @mixin UserLoginlog
 */
class UserLoginlogServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserLoginlog::class;
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
    public function setLoginlog($uid, $time, $ip, $is_fail = 0, $content = '')
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
     * 房主获取登录记录
     * @param $room_id
     * @param $startdate
     * @param $enddate
     * @param $status
     * @param $keyword
     */
    public function getList($room_id, $user_id, $startdate, $enddate, $ip)
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
        $where[] = ['l.login_time', '>=', $starttime];
        $where[] = ['l.login_time', '<', $endtime];

        
        if($user_id){
           if(is_array($user_id)){
              $where[] = ['l.uid', 'in', $user_id]; 
           }else{
              $where[] = ['l.uid', '=', $user_id];
           }
        }
        
        if($ip){
            $where[] = ['l.login_ip', 'LIKE', "%$ip%"];
        }
        
        [$page, $limit] = $this->getPageValue();
        $roomUserServices = app()->make(RoomUserServices::class);
        $UserServices = app()->make(UserServices::class);
        $list = Db::table($this->getTable())->alias('l')->join($roomUserServices->getTable().' r', 'l.uid = r.user_id')
                    ->join($UserServices->getTable().' u', 'u.id = l.uid')
                    ->where($where)
                    ->field('u.account,r.room_id,l.*, FROM_UNIXTIME(l.login_time, "%H:%i:%s") as logintime')
                    ->cache(60)->order('login_time', 'desc')->page($page, $limit)->select()->toArray();
        $count = Db::table($this->getTable())->alias('l')->join($roomUserServices->getTable().' r', 'l.uid = r.user_id')
                    ->join($UserServices->getTable().' u', 'u.id = l.uid')->where($where)->count();
        return compact('list','count');
    }
}
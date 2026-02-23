<?php
declare (strict_types=1);
namespace app\services\user;

use app\services\BaseServices;
use app\model\UserAddress;
use think\facade\Log;

/**
 * Class UserAddressServices
 * @package app\services
 * @mixin UserAddress
 */
class UserAddressServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserAddress::class;
    }
    
    
    public function add($uid, $data)
    {
        if($data['is_default']==1){
            $this->getModel()->where('uid', $uid)->update(['is_default'=>0]);
        }
        $data['uid'] = $uid;
        $data['add_time'] = time();
        return $this->getModel()->create($data);
    }
    
    public function getList($uid = 0)
    {
        [$page, $limit] = $this->getPageValue();
        $where = [];
        if($uid){
            $where[] = ['uid', '=', $uid];
        }
        $list = $this->getModel()->where($where)->page($page, $limit)->select()->toArray();
        $count = 0;
        if(!$uid){
            $count = $this->getModel()->where($where)->count();
        }
        return compact('list', 'count');
    }
    
    public function del($uid, $id)
    {
        return $this->getModel()->where('uid', $uid)->where('id', $id)->delete();
    }
    
    public function setdefault($uid, $id)
    {
        $this->getModel()->where('uid', $uid)->update(['is_default'=>0]);
        $this->getModel()->where('uid', $uid)->where('id', $id)->update(['is_default'=>1]);
    }
    
    public function getdefault($uid)
    {
        $add = $this->getModel()->where('uid', $uid)->where('is_default', 1)->find();
        if(empty($add)){
            return [];
        }
        return $add->toArray();
    }
}

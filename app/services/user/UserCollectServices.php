<?php
declare (strict_types=1);
namespace app\services\user;

use app\services\BaseServices;
use app\model\UserCollect;
use think\facade\Log;

/**
 * Class UserCollectServices
 * @package app\services
 * @mixin UserCollect
 */
class UserCollectServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserCollect::class;
    }
    
    public function collectCount($uid)
    {
        return $this->getModel()->where('uid', $uid)->count();
    }
    
    public function collectMer($uid, $mer_id)
    {
        return $this->getModel()->where('uid', $uid)->where('mer_id', $mer_id)->count();
    }
    
    public function followMer($uid, $mer_id)
    {
        return $this->getModel()->create([
                'uid'=>$uid,
                'mer_id'=>$mer_id,
                'add_time'=>time()
            ]);
    }
    
    public function unfollowMer($uid, $mer_id)
    {
        return $this->getModel()->where('uid', $uid)->where('mer_id', $mer_id)->delete();
    }
    
    
    public function getList($lang, $uid)
    {
        [$page, $limit] = $this->getPageValue();
        $name = 'store_name'.$this->lang[$lang];
        $list = $this->getModel()->with(['mer', 'product'=>function($query) use($name){
              $query->field("id,image,price,mer_id,{$name} as title")->limit(3);
        }])->where('uid', $uid)->order('add_time', 'desc')->page($page, $limit)->select()->toArray();
        return compact('list');
    }

}

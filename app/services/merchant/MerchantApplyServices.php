<?php
declare (strict_types=1);
namespace app\services\merchant;

use app\services\BaseServices;
use app\services\user\UserServices;
use app\model\MerchantApply;
use think\facade\Log;
use core\exceptions\ApiException;

/**
 * Class MerchantApplyServices
 * @package app\services
 * @mixin MerchantApply
 */
class MerchantApplyServices extends BaseServices
{
    protected function setModel(): string
    {
        return MerchantApply::class;
    }
    
    public function addApply($data)
    {
        $MerchantServices = app()->make(MerchantServices::class);
        if($MerchantServices->getModel()->where('mer_uid', $data['uid'])->count()){
            throw new ApiException('You are already a merchant, please do not apply again');
        }
        return $this->getModel()->create($data);
    }
    
    
    public function getAList($user_ids, $mer_name, $phone)
    {
        [$page, $limit] = $this->getPageValue();
        $where = [];
        if(!empty($user_ids)){
            $where[] = ['uid', 'in', $user_ids];
        }
        if($mer_name){
            $where[] = ['mer_name', 'LIKE', "%$mer_name%"];
        }
        if($phone){
            $where[] = ['phone', 'LIKE', "%$phone%"];
        }
        $list = $this->getModel()->where($where)->order('add_time','desc')->page($page, $limit)->select()->toArray();
        $count = $this->getModel()->where($where)->count();
        foreach ($list as &$item){
            $item['datetime'] = date('Y-m-d H:i:s', $item['add_time']);
            if($item['status']==1){
                $item['status_name'] = '成功';
            }elseif($item['status']==2){
                $item['status_name'] = '失败';
            }else{
                $item['status_name'] = '待审核';
            }
            if($item['pics']){
                $item['pics'] = explode(',', $item['pics']);
            }else{
                $item['pics'] = [];
            }
            if($item['admit_time']){
                $item['admittime'] = date('Y-m-d H:i', $item['admit_time']);
            }else{
                $item['admittime'] = '--';
            }
            if(!$item['admit_name']){
               $item['admit_name'] = '--';
            }
        }
        return compact('list', 'count');
    }
    
    
    public function applyEdit($admin, $id, $name, $idnumber, $status)
    {
        $apply = $this->getModel()->where('id', $id)->find();
        if(empty($apply)){
            throw new ApiException('申请不存在');
        }
        $apply = $apply->toArray();
        if($apply['status']){
            throw new ApiException('申请已处理');
        }
        if($status==2){
            $this->getModel()->where('id', $id)->update([
                'status'=>2,
                'admit_name'=>$admin['account'],
                'admit_time'=>time()
            ]);
            return true;
        }else{
            return $this->transaction(function() use($apply, $admin, $id, $name, $idnumber, $status){
                $this->getModel()->where('id', $id)->update([
                     'status'=>1,
                     'admit_name'=>$admin['account'],
                     'admit_time'=>time()
                ]);
                $MerchantServices = app()->make(MerchantServices::class);
                if($MerchantServices->getModel()->where('mer_uid', $apply['uid'])->count()){
                    throw new ApiException('用户已有商户');
                }
                $res = $MerchantServices->getModel()->create([
                         'mer_uid'=>$apply['uid'],
                         'category_id'=>$apply['cate_id'],
                         'type_id'=>$apply['type_id'],
                         'spread_uid'=>$apply['invite_uid'],
                         'mer_name'=>$apply['mer_name'],
                         'create_time'=>date('Y-m-d H:i:s'),
                         'mer_state'=>1
                    ]);
                if(!$res){
                    throw new ApiException('系统繁忙');
                }
                $UserServices = app()->make(UserServices::class);
                $res_user = $UserServices->getModel()->where('id', $apply['uid'])->update([
                       'is_mer'=>1,
                       'mer_id'=>$res->id
                    ]);
                if(!$res_user){
                    throw new ApiException('系统繁忙');
                }
                    return true;
            });
        }
    }

}

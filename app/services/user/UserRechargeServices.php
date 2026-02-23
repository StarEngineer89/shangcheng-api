<?php
declare (strict_types=1);
namespace app\services\user;

use app\services\BaseServices;
use app\model\UserRecharge;
use core\exceptions\ApiException;
use core\services\CacheService;

/**
 * Class UserRechargeServices
 * @package app\services
 * @mixin UserRecharge
 */
class UserRechargeServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserRecharge::class;
    }
    public function getnewcount()
    {
        return $this->getModel()->where('status', 0)->count();
    }
    /**
     * 添加充值
     */
    public function saveRecharge($user, $amount, $type, $paytype)
    {
        return $this->transaction(function() use($user, $amount, $type, $paytype){
             $order_no = getNewOrderId('R');
             $time = time();
             $data = [
                     'order_no'=>$order_no,
                     'type'=>$type,
                     'uid'=>$user['id'],
                     'amount'=>$amount,
                     'add_time'=>$time,
                     'paytype'=>$paytype
                 ];
             $res = $this->getModel()->create($data);
             if(!$res){
                 throw new ApiException('The system is busy, please try again later.');
             }
             return [
                    'order_no'=>$order_no
                 ];
        });
    }
    
    
    
    public function getList($uid)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->getModel()->field('id,order_no,amount,add_time,status,type')->where('uid', $uid)->order('add_time','desc')->page($page, $limit)->select()->toArray();
        foreach ($list as &$item){
            $item['date_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        return compact('list');
    }
    
    
    
    public function getAList($id, $user_ids, $status, $start_date, $end_date, $order_no, $email, $phone)
    {
        [$page, $limit] = $this->getPageValue();
        $where = [];
        if(!empty($user_ids)){
            $where[] = ['uid', 'in', $user_ids];
        }
        if($id){
            $where[] = ['id', '=', $id];
        }
        if($status){
            $status = $status - 1;
            $where[] = ['status', '=', $status];
        }
        if($order_no){
            $where[] = ['order_no', '=', $order_no];
        }
        if($start_date&&!$end_date){
           $where[] = ['add_time', '>=', strtotime($start_date)];
           $where[] = ['add_time', '<', strtotime('+1day',strtotime($start_date))];
        }
        if(!$start_date&&$end_date){
           $where[] = ['add_time', '>=', strtotime($end_date)];
           $where[] = ['add_time', '<', strtotime('+1day',strtotime($end_date))];
        }
        if($start_date&&$end_date){
           if($start_date==$end_date){
               $where[] = ['add_time', '>=', strtotime($start_date)];
               $where[] = ['add_time', '<', strtotime('+1day',strtotime($end_date))];
           }else{
               $_start_date = $start_date;
               if(strtotime($start_date)>strtotime($end_date)){
                   $start_date = $end_date;
                   $end_date = $_start_date;
               }
               $where[] = ['add_time', '>=', strtotime($start_date)];
               $where[] = ['add_time', '<', strtotime('+1day',strtotime($end_date))];
           }
        }
        if($start_date&&$end_date&&$start_date==$end_date&&$status==1){
            $list = $this->getModel()->with(['user'])->where($where)->order('add_time','desc')->select()->toArray();
            $count = 1;
        }else{
            $list = $this->getModel()->with(['user'])->where($where)->order('add_time','desc')->page($page, $limit)->select()->toArray();
            $count = $this->getModel()->where($where)->count();
        }
        foreach ($list as &$item){
            $item['date_time'] = date('Y-m-d H:i:s', $item['add_time']);
            if($item['admit_time']){
                $item['admittime'] = date('Y-m-d H:i', $item['admit_time']);
            }else{
                $item['admittime'] = '--';
            }
            if(!$item['admit_name']){
               $item['admit_name'] = '--';
            }
            if($item['user_type']==1){
                $item['user_type_name'] = '业务员';
            }else{
                $item['user_type_name'] = '普通会员';
            }
            if($item['status']==1){
                $item['status_name'] = '成功';
            }elseif($item['status']==2){
                $item['status_name'] = '失败';
            }else{
                $item['status_name'] = '待审核';
            }
        }
        return compact('list', 'count');
    }
    
    
    
    public function rechargeEdit($admin, $id, $type, $is_truth = 0)
    {
        return $this->transaction(function() use($admin, $id, $type, $is_truth){
              $draw = $this->getModel()->where('id', $id)->lock(true)->find();
              if(!$draw){
                  throw new ApiException('数据不存在');
              }
              $draw = $draw->toArray();
              if($draw['status']){
                  throw new ApiException('数据已处理');
              }
              $update = [];
              if($type==2){
                  $update['status'] = 2;
                  $update['admit_name'] = $admin['account'];
                  $update['admit_time'] = time();
                  $res = $this->getModel()->where('id', $id)->update($update);
                  if(!$res){
                     throw new ApiException('系统繁忙，请稍后再试');
                  }
              }else{
                  $time = time();
                  $amount = $draw['amount'];
                  $update['status'] = 1;
                  $update['admit_name'] = $admin['account'];
                  $update['admit_time'] = $time;
                  $res = $this->getModel()->where('id', $id)->update($update);
                  if(!$res){
                     throw new ApiException('系统繁忙，请稍后再试');
                  }
                  
                  $uid = $draw['uid'];
                  $UserServices = app()->make(UserServices::class);
                  $user = $UserServices->getModel()->where('id', $uid)->lock(true)->find();
                  $update = [];
                  $update['money'] = bcadd((string)$user['money'], $amount, 2);
                   
                  $resu = $UserServices->getModel()->where('id', $uid)->update($update);
                  if(!$resu){
                     throw new ApiException('系统繁忙，请稍后再试');
                  }
                  
                  $UserMoneylogServices = app()->make(UserMoneylogServices::class);
                  $resMoneylog = $UserMoneylogServices->addMoneylog([
                        'type'=>2,
                        'uid'=>$uid,
                        'state'=>1,
                        'title'=>'Recharge',
                        'amount'=>$amount,
                        'money'=>$update['money'],
                        'add_time'=>$time
                  ]);
                  if(!$resMoneylog){
                    throw new ApiException('系统繁忙，请稍候再试！');
                  }
                  
                  CacheService::delete($this->getCacheKey('user_info_' . $uid));
                  
              }
              
              return true;
             
        });
    }
    
    
}
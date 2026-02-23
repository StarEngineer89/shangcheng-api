<?php
declare (strict_types=1);
namespace app\services\merchant;

use app\services\BaseServices;
use app\model\MerchantWithdraw;
use core\exceptions\ApiException;

/**
 * Class MerchantWithdrawServices
 * @package app\services
 * @mixin MerchantWithdraw
 */
class MerchantWithdrawServices extends BaseServices
{
    protected function setModel(): string
    {
        return MerchantWithdraw::class;
    }
    
    public function getnewcount()
    {
        return $this->getModel()->where('status', 0)->count();
    }
    
    public function addWithdraw($mer_id, $amount)
    {
        return $this->transaction(function() use($mer_id, $amount){
            $MerchantServices = app()->make(MerchantServices::class);
            $mer = $MerchantServices->getModel()->where('id', $mer_id)->lock(true)->find();
            if($mer['mer_money']<$amount){
                throw new ApiException('Insufficient balance');
            }
            $order_no = getNewOrderId('W');
            $time = time();
            $res = $this->getModel()->create([
                    'uid'=>$mer_id,
                    'amount'=>$amount,
                    'order_no'=>$order_no,
                    'add_time'=>$time
                ]);
            if(!$res){
                throw new ApiException('The system is busy, please try again later');
            }
            $update = [];
            $update['mer_money'] = bcsub((string)$mer['mer_money'], (string)$amount, 2);
            $res_user = $MerchantServices->getModel()->where('id', $mer_id)->update($update);
            if(!$res_user){
                throw new ApiException('The system is busy, please try again later');
            }
            $MerchantMoneylogServices = app()->make(MerchantMoneylogServices::class);
            $res_userlog = $MerchantMoneylogServices->addMoneylog([
                    'type'=>3,
                    'uid'=>$mer_id,
                    'state'=>2,
                    'title'=>'Withdrawal',
                    'amount'=>$amount,
                    'money'=>$update['mer_money'],
                    'add_time'=>$time,
                    'remark'=>$order_no
                ]);
            if(!$res_userlog){
                throw new ApiException('The system is busy, please try again later');
            }
            return true;
        });
    }
    
    
    public function getList($mer_id)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->getModel()->field('id,order_no,amount,add_time,status')->where('uid', $mer_id)->order('add_time','desc')->page($page, $limit)->select()->toArray();
        foreach ($list as &$item){
            $item['date_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        return compact('list');
    }
    
    
    
    public function getAList($id, $user_ids, $status, $start_date, $end_date, $order_no)
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
            $list = $this->getModel()->with(['mer', ['bank']])->where($where)->order('add_time','desc')->select()->toArray();
            $count = 1;
        }else{
            $list = $this->getModel()->with(['mer', 'bank'])->where($where)->order('add_time','desc')->page($page, $limit)->select()->toArray();
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
    
    
    
    public function withdrawEdit($admin, $id, $type)
    {
        return $this->transaction(function() use($admin, $id, $type){
              $draw = $this->getModel()->where('id', $id)->lock(true)->find();
              if(!$draw){
                  throw new ApiException('数据不存在');
              }
              $draw = $draw->toArray();
              if($draw['status']){
                  throw new ApiException('数据已处理');
              }
              $update = [];
              if($type==1){
                  $update['status'] = 1;
                  $update['admit_name'] = $admin['account'];
                  $update['admit_time'] = time();
                  $res = $this->getModel()->where('id', $id)->update($update);
                  if(!$res){
                     throw new ApiException('系统繁忙，请稍后再试');
                  }
              }else{
                  $time = time();
                  $amount = $draw['amount'];
                  $update['status'] = 2;
                  $update['admit_name'] = $admin['account'];
                  $update['admit_time'] = $time;
                  $res = $this->getModel()->where('id', $id)->update($update);
                  if(!$res){
                     throw new ApiException('系统繁忙，请稍后再试');
                  }
                  
                  $uid = $draw['uid'];
                  $MerchantServices = app()->make(MerchantServices::class);
                  $user = $MerchantServices->getModel()->where('id', $uid)->lock(true)->find();
                  $update = [];
                  $update['mer_money'] = bcadd((string)$user['mer_money'], $amount, 2);
                   
                  $resu = $MerchantServices->getModel()->where('id', $uid)->update($update);
                  if(!$resu){
                     throw new ApiException('系统繁忙，请稍后再试');
                  }
                  
                  $MerchantMoneylogServices = app()->make(MerchantMoneylogServices::class);
                  $resMoneylog = $MerchantMoneylogServices->addMoneylog([
                        'type'=>3,
                        'uid'=>$uid,
                        'state'=>1,
                        'title'=>'Withdrawal Fail',
                        'amount'=>$amount,
                        'money'=>$update['mer_money'],
                        'add_time'=>$time
                  ]);
                  if(!$resMoneylog){
                    throw new ApiException('系统繁忙，请稍候再试！');
                  }
              }
              
              return true;
             
        });
    }
}
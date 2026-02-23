<?php
declare (strict_types=1);
namespace app\services\user;

use app\services\BaseServices;
use app\model\UserDraw;
use core\exceptions\ApiException;
use app\services\room\RoomUserServices;
use core\services\ChatService;

/**
 * Class UserDrawServices
 * @package app\services
 * @mixin UserDraw
 */
class UserDrawServices extends BaseServices
{
    protected function setModel(): string
    {
        return UserDraw::class;
    }
    public function drawCountUncheck($room_id, $user_id)
    {
        return $this->getModel()->where('room_id', $room_id)->where('user_id', $user_id)->where('status', 0)->count();
    }
    /**
     * 添加提现
     * @param $room_id
     * @param $user_id
     */
    public function addDraw($room_id, $user_id, $amount, $content = '')
    {
        if(!$amount) return false;
        return $this->transaction(function () use ($room_id, $user_id, $amount, $content) {
                 $roomUserServices = app()->make(RoomUserServices::class);
                 $user = $roomUserServices->getModel()->where('room_id', $room_id)->where('user_id', $user_id)->lock(true)->find();
                 if(empty($user)){
                     throw new ApiException('用户不存在');
                 }
                 $user = $user->toArray();
                 if ($user['money'] < $amount ) {
                    throw new ApiException('积分不足');
                 }
                 $order_no = getNewOrderId('W');
                 
                 $money = $user['money'];
                 $new_money = bcsub((string)$money, (string)$amount, 2);
                 $update_user = [
                        'money' => $new_money
                     ];
                 if($user['user_type']==2){
                    $total_drawing = bcadd((string)$user['total_drawing'], (string)$amount, 2);
                    $update_user['total_drawing'] = $total_drawing;
                 }
                 $resUser = $roomUserServices->getModel()->where('id', $user['id'])->update($update_user);
                 if(!$resUser){
                     throw new ApiException('系统繁忙，请稍候再试');
                 }
                 
                 $time = time();
                 $draw = [
                    'order_no' => $order_no,
                    'room_id' => $room_id,
                    'uid' => $user_id,
                    'amount' => $amount,
                    'before_money'=>$money,
                    'after_money' => $new_money,
                    'add_time' => $time,
                    'content' => $content
                 ];
                 if($user['user_type']==2){
                     $draw['status'] = 1;
                     $draw['remark'] = '假人自动审核';
                 }
                 $res = $this->getModel()->create($draw);
                 if(!$res){
                    throw new ApiException('系统繁忙，请重新提交');
                 }
                 
                 $userMoneylogServices = app()->make(UserMoneylogServices::class);
                 $reslog = $userMoneylogServices->addMoneylog([
                          'type' => 2,
                          'uid' => $user_id,
                          'user_type' => $user['user_type'],
                          'room_id' => $room_id,
                          'amount' => -$amount,
                          'before_money' => $money,
                          'after_money' => $new_money,
                          'content' => '提现',
                          'add_time' => $time,
                          'remark' => $res->id,
                     ]);
                 if(!$reslog){
                    throw new ApiException('系统繁忙，请稍候再试');
                 }
                
                $roomUserServices->clearRoomUserCache($room_id, $user_id);
                return [
                       'money'=>$new_money
                    ];
        });
    }
    
    
    /**
     * 审核订单
     * @param $room_id
     * @param $user_id
     */
    public function auditDrawing($id, $room_id, $status, $content = '', int $audit_uid = 0)
    {
        if(!$status) return true;
        return $this->transaction(function () use ($id, $room_id, $status, $content, $audit_uid) {
                 $order = $this->getModel()->where('id',$id)->find();
                 if (!$order) {
                    throw new ApiException('账单不存在');
                 }
                 if ($order['status']) {
                    throw new ApiException('订单已处理');
                 }
                 
                 $time = time();
                 $order->status = $status;
                 $order->remark = $content;
                 $order->audit_time = $time;
                 $order->audit_uid = $audit_uid;
                 $order->save();
                 
                 $roomUserServices = app()->make(RoomUserServices::class);
                 $user = $roomUserServices->getUserLockInfo((int)$room_id, (int)$order['uid']);
                 if(!$user){
                     throw new ApiException('用户不存在');
                 }
                 
                 $amount = $order['amount'];
                 
                 if($status==1){
                     $total_drawing = bcadd((string)$user['total_drawing'], (string)$amount, 2);
                     $user->total_drawing = $total_drawing;
                     $user->save();
                 }
                 
                 if($status==2){
                     
                     $money = $user['money'];
                     $new_money = bcadd((string)$money, (string)$amount, 2);
                     $user->money = $new_money;
                     $user->save();
                     
                     $userMoneylogServices = app()->make(UserMoneylogServices::class);
                     $reslog = $userMoneylogServices->addMoneylog([
                              'type' => 7,
                              'uid' => $user->id,
                              'user_type' => $user->user_type,
                              'room_id' => $room_id,
                              'amount' => $amount,
                              'before_money' => $money,
                              'after_money' => $new_money,
                              'content' => '提现失败',
                              'add_time' => $time,
                              'remark' => $order->id,
                         ]);
                     if(!$reslog){
                        throw new ApiException('系统繁忙，请稍候再试');
                     }
                     
                             
                        $_msgdata = [];
                        $_msgdata['money'] = $new_money;
                        $ChatService = app()->make(ChatService::class);
                        $ChatService->sendUserMoney($user->id, $room_id, 0, $new_money);

                 }
                    $roomUserServices->cacheTag()->clear();
                 
                 return [
                       'audit_time'=>$order->audit_time,
                       'audittime'=>date('Y-m-d H:i:s',$order->audit_time)
                    ];
        });
    }
}
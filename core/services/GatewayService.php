<?php
namespace core\services;

use GatewayClient\Gateway;
use think\exception\ValidateException;
use think\facade\Log;

class GatewayService
{
    private $fromType = ['H5','MP','PC','APP'];
    public function __construct()
    {
        Gateway::$registerAddress = '127.0.0.1:1238';
    }
    
    public function sendGroup($userId, $msg)
    {
        Gateway::sendToGroup('room_'.$userId, $msg);
    }
    
    public function sendGroupCai($room_id, $cai_id, $msg)
    {
        Gateway::sendToGroup('room_cai_'.$room_id.'_'.$cai_id, $msg);
    }
    
    public function sendUser($uid, $msg)
    {
        foreach ($this->fromType as $type){
           $userId = 'user.'.$type.'.'.$uid;
           Gateway::sendToUid($userId, $msg);
        }
    }
    
    public function sendRoom($uid, $msg)
    {
        foreach ($this->fromType as $type){
           $userId = 'room.'.$type.'.'.$uid;
           Gateway::sendToUid($userId, $msg);
        }
    }
}
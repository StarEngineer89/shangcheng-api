<?php
declare (strict_types=1);
namespace app\services\merchant;

use app\services\BaseServices;
use app\model\MerchantMoneylog;
use core\exceptions\ApiException;
use think\facade\Log;

/**
 * Class MerchantMoneylogServices
 * @package app\services
 * @mixin MerchantMoneylog
 */
class MerchantMoneylogServices extends BaseServices
{
    protected function setModel(): string
    {
        return MerchantMoneylog::class;
    }
    
    public function getType($type, $list = false){
                    ///// 1       2     3      4       5      6        7        8     9      10
        $typeName = ['购物','赠送','充值','提现','手续费','佣金','提现失败'];
        if($list){
            return $typeName;
        }
        return isset($typeName[$type])?$typeName[$type]:'';
    }
    /**
     * 记录积分日志
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addMoneylog(array $data)
    {
        if ($this->getModel()->create($data)) {
            return true;
        } else {
            return false;
        }
    }
    
    
    public function getList($mer_id)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->getModel()->field('id,title,amount,add_time,money,state')->where('uid', $mer_id)->order('add_time','desc')->page($page, $limit)->select()->toArray();
        foreach ($list as &$item){
            $item['date_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        return compact('list');
    }
    
    
    
    public function getAList($id, $user_ids, $start_date, $end_date)
    {
        [$page, $limit] = $this->getPageValue();
        $where = [];
        
        if(!empty($user_ids)){
            $where[] = ['uid','in',$user_ids];
        }
        
        if($id){
            $where[] = ['id','=',$id];
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
        
        $list = $this->getModel()->with(['mer'])->where($where)->order('add_time','desc')->page($page, $limit)->select()->toArray();
        $count = $this->getModel()->where($where)->count();
        foreach ($list as &$item){
            $item['date_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        return compact('list', 'count');
    }
    
}

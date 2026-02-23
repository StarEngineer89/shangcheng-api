<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\model\Category;
use think\facade\Log;

/**
 * Class CategoryServices
 * @package app\services
 * @mixin Category
 */
class CategoryServices extends BaseServices
{
    protected function setModel(): string
    {
        return Category::class;
    }
    
    
    public function indexCategory($lang)
    {
        $where=[];
        $where[] = ['is_show', '=', 1];
        $where[] = ['mer_id', '=', 0];
        $where[] = ['pid', '=', 0];
        $where[] = ['is_hot', '=', 1];
        $name = 'cate_name'.$this->lang[$lang];
        $list = $this->getModel()->field("id,{$name} as name,pic")->where($where)->cache(600)->limit(5)->order('sort', 'desc')->select()->toArray();
        foreach ($list as &$item){
            $item['namearr'] = explode(' ', $item['name']);
        }
        return compact('list');
    }
    
    
    
    public function catelist($lang)
    {
        $where=[];
        $where[] = ['is_show', '=', 1];
        $where[] = ['mer_id', '=', 0];
        $name = 'cate_name'.$this->lang[$lang];
        $list = $this->getModel()->field("id,{$name} as name,pic,pid,level")->where($where)->order('sort', 'desc')->select()->toArray();
        $toplist = [];
        $topchild = [];
        $catelist = [];
        $childlist = [];
        foreach ($list as &$item){
            if($item['pid']==0){
                $item['namearr'] = explode(' ', $item['name']);
                $toplist[] = $item;
            }else{
                if($item['level']==1){
                    if(!isset($topchild[$item['pid']])){
                        $topchild[$item['pid']] = [];
                    }
                    $topchild[$item['pid']][] = $item;
                }elseif($item['level']==2){
                    if(!isset($childlist[$item['pid']])){
                        $childlist[$item['pid']] = [];
                    }
                    $childlist[$item['pid']][] = $item;
                }
            }
        }
        foreach ($toplist as $_item){
            $clist = isset($topchild[$_item['id']])?$topchild[$_item['id']]:[];
            foreach ($clist as $citem){
                $catelist[] = $citem;
            }
        }
        return compact('toplist', 'catelist', 'childlist');
    }
    
    public function platcatelist($lang)
    {
        $where=[];
        $where[] = ['is_show', '=', 1];
        $where[] = ['mer_id', '=', 0];
        $name = 'cate_name'.$this->lang[$lang];
        $list = $this->getModel()->field("id,{$name} as name,pid,level")->where($where)->order('sort', 'desc')->select()->toArray();
        $catelist = [];
        $childlist = [];
        foreach ($list as &$item){
            if($item['level']==0){
                $catelist[] = [
                       'id'=>$item['id'],
                       'text'=>$item['name'],
                       'value'=>$item['id'].'-0'
                    ];
            }elseif($item['level']==1){
                if(!isset($childlist[$item['pid']])){
                    $childlist[$item['pid']] = [];
                }
                $childlist[$item['pid']][] = [
                       'id'=>$item['id'],
                       'text'=>$item['name'],
                       'value'=>$item['pid'].'-'.$item['id']
                    ];
            }
        }
        foreach ($catelist as &$_item){
            $clist = isset($childlist[$_item['id']])?$childlist[$_item['id']]:[];
            $_item['children'] = $clist;
        }
        return compact('catelist');
    }
    
    public function mercatelist($lang, $mer_id)
    {
        $where=[];
        $where[] = ['is_show', '=', 1];
        $where[] = ['mer_id', '=', $mer_id];
        $name = 'cate_name'.$this->lang[$lang];
        $list = $this->getModel()->field("id,{$name} as name,ot_cate_id,pid,level")->where($where)->order('sort', 'desc')->select()->toArray();
        $catelist = [];
        $childlist = [];
        foreach ($list as &$item){
            if($item['pid']==0){
                $catelist[] = $item;
            }else{
                if(!isset($childlist[$item['pid']])){
                    $childlist[$item['pid']] = [];
                }
                $childlist[$item['pid']][] = $item;
            }
        }
        return compact('catelist', 'childlist');
    }

}

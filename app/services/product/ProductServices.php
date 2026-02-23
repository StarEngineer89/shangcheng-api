<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\services\merchant\MerchantServices;
use app\services\user\UserVisitsServices;
use core\exceptions\ApiException;
use app\model\Product;
use think\facade\Log;

/**
 * Class ProductServices
 * @package app\services
 * @mixin Product
 */
class ProductServices extends BaseServices
{
    protected function setModel(): string
    {
        return Product::class;
    }
    
    public function checklist($page)
    {
        $list = $this->getModel()->where('mer_id', '>', 54)->group('old_product_id')->page($page, 20)->select()->toArray();
        if(!count($list)){
            return 'OK';
        }
        foreach ($list as $item){
            $count = $this->getModel()->where('mer_id', $item['mer_id'])->where('old_product_id', $item['old_product_id'])->where('id','<>', $item['id'])->delete();
            echo $count.'<br>';
        }
    }
    
    /**
     * 获取首页产品
     */
    public function getIndexList($lang = '')
    {
        $where=[];
        [$page, $limit] = $this->getPageValue();
        $where[] = ['is_show', '=', 1];
        $where[] = ['status', '=', 1];
        $where[] = ['is_del', '=', 0];
        $name = 'store_name'.$this->lang[$lang];
        $list = $this->getModel()->field("id,mer_id,image,{$name} as title,price")->where($where)->with(['mer'])->order(['mer_id'=>'RAND()'])->page($page, $limit)->select()->toArray();
        return compact('list');
    }
    
    public function getCateList($lang, $cate_id, $type, $sort, $brand_id, $min_price, $max_price, $mer_id = 0, $keyword = '')
    {
        $where=[];
        [$page, $limit] = $this->getPageValue();
        $where[] = ['is_show', '=', 1];
        $where[] = ['status', '=', 1];
        $where[] = ['is_del', '=', 0];
        $name = 'store_name'.$this->lang[$lang];
        $cname = 'cate_name'.$this->lang[$lang];
        if($keyword){
            $where[] = [$name, 'LIKE', "%$keyword%"];
        }
        $cate = [
               'id'=>0
            ];
        if($cate_id){
            $CategoryServices = app()->make(CategoryServices::class);
            $cate = $CategoryServices->getModel()->field("id,level,{$cname} as name")->where('id', $cate_id)->find();
            if(empty($cate)){
                throw new ApiException('Category does not exist');
            }
            $cate = $cate->toArray();
            $cateIds = [];
            if($cate['level']==2){
                $cateIds[] = $cate_id;
            }else{
                $cateIds = $CategoryServices->getModel()->where('mer_id', 0)->where('level', 2)->where('path', 'like', '%/'.$cate_id.'/%')->column('id');
            }
            $where[] = ['cate_id', 'in', $cateIds];
        }
        $order = ['mer_id'=>'RAND()'];
        if($mer_id){
           $where[] = ['mer_id', '=', $mer_id];
           $order = ['create_time'=>'desc'];
        }
        $sort = $sort=='desc'?'desc':'asc';
        if($type=='price'){
            $order = ['price'=>$sort];
        }
        if($type=='sale'){
            $order = ['sales'=>$sort];
        }
        if($brand_id){
            $where[] = ['brand_id', '=', $brand_id];
        }
        if($min_price){
            $where[] = ['price', '>=', $min_price];
        }
        if($max_price){
            $where[] = ['price', '<=', $max_price];
        }
        
        $list = $this->getModel()->field("id,mer_id,image,{$name} as title,price")->where($where)->with(['mer'])->order($order)->page($page, $limit)->select()->toArray();
        return compact('list', 'cate');
    }
    
    
    public function getMerList($lang, $mer_id, $keyword = '')
    {
        $where=[];
        [$page, $limit] = $this->getPageValue();
        $where[] = ['status', '=', 1];
        $where[] = ['is_del', '=', 0];
        $where[] = ['mer_id', '=', $mer_id];
        $name = 'store_name'.$this->lang[$lang];
        if($keyword){
            $where[] = [$name, 'LIKE', "%$keyword%"];
        }
        $list = $this->getModel()->field("id,mer_id,image,{$name} as title,price,is_good,is_show")->where($where)->order('id', 'desc')->page($page, $limit)->select()->toArray();
        return compact('list');
    }
    
    public function getMerId($pro_id)
    {
        return $this->getModel()->where('id', $pro_id)->value('mer_id');
    }
    
    public function showpro($mer_id, $id, $status)
    {
        $pro = $this->getModel()->where('id', $id)->find();
        if(empty($pro)){
            throw new ApiException('The product does not exist.');
        }
        $pro = $pro->toArray();
        if($pro['mer_id']!=$mer_id){
            throw new ApiException('The product does not exist.');
        }
        return $this->getModel()->where('id', $id)->update(['is_show'=>$status]);
    }
    
    public function goodpro($mer_id, $id, $status)
    {
        $pro = $this->getModel()->where('id', $id)->find();
        if(empty($pro)){
            throw new ApiException('The product does not exist.');
        }
        $pro = $pro->toArray();
        if($pro['mer_id']!=$mer_id){
            throw new ApiException('The product does not exist.');
        }
        return $this->getModel()->where('id', $id)->update(['is_good'=>$status]);
    }
    
    public function getDetail($lang, $pro_id, $uid = 0)
    {
        $name = 'store_name'.$this->lang[$lang];
        $product = $this->getModel()->field("id,image,slider_image,{$name} as title,price,sales,mer_id,ficti")->where('id', $pro_id)->find();
        if(empty($product)){
            throw new ApiException('The product does not exist.');
        }
        $product = $product->toArray();
        $product['sales'] = $product['sales'] + $product['ficti'];
        if($product['slider_image']){
            $product['slider_image'] = explode(',', $product['slider_image']);
        }else{
            $image = [];
            $image[] = $product['image'];
            $product['slider_image'] = $image;
        }
        $ProductContentServices = app()->make(ProductContentServices::class);
        $product['content'] = $ProductContentServices->getModel()->where('product_id', $pro_id)->value('content');
        $MerchantServices = app()->make(MerchantServices::class);
        $merchant = $MerchantServices->getModel()->field("id,mer_name,mer_avatar,care_count,store_score,product_score,service_score,postage_score,type_id")->where('id', $product['mer_id'])->find();
        if(empty($merchant)){
           throw new ApiException('Merchant does not exist'); 
        }
        $merchant = $merchant->toArray();
        $recommed = $this->getModel()->field("id,image,{$name} as title,price")->where('mer_id', $product['mer_id'])->orderRaw('RAND()')->limit(3)->select()->toArray();
        
        $ProductAttrServices = app()->make(ProductAttrServices::class);
        $productAttrs = $ProductAttrServices->getModel()->where('product_id', $pro_id)->select()->toArray();
        $attrlist = [];
        foreach ($productAttrs as $item){
            if($item['attr_values']){
                $attr_arr = explode('-!-', $item['attr_values']);
                $attrlist[] = [
                       'name'=>$item['attr_name'],
                       'attr'=>$attr_arr
                    ];
            }
        }
        if(!empty($attrlist)){
          $ProductAttrValueServices = app()->make(ProductAttrValueServices::class);
          $productattr = $ProductAttrValueServices->getModel()->field('id, sku, stock, image, price')->where('product_id', $pro_id)->select()->toArray();
        }else{
          $productattr = [];
        }
        if($uid){
            $UserVisitsServices = app()->make(UserVisitsServices::class);
            $UserVisitsServices->addVisits($uid, $pro_id, $product['mer_id']);
        }
        return compact('product', 'merchant', 'recommed', 'attrlist', 'productattr');
    }
    
    
    public function getSearch($lang, $keyword)
    {
        [$page, $limit] = $this->getPageValue();
        $MerchantServices = app()->make(MerchantServices::class);
        $merchant = $MerchantServices->getModel()->field('id,mer_avatar as image,mer_name COLLATE utf8mb4_unicode_ci as title,store_score as price,1 as type')->whereLike('mer_name', "%{$keyword}%")->buildSql();
        $name = 'store_name'.$this->lang[$lang];
        $product = $this->getModel()->field("id,image,{$name} COLLATE utf8mb4_unicode_ci as title,price,2 as type")->whereLike($name, "%{$keyword}%");
        $list = $product->union($merchant)->page($page, $limit)->order('type asc, id desc')->select()->toArray();
        return compact('list');
    }
}

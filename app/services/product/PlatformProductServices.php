<?php
declare (strict_types=1);
namespace app\services\product;

use app\services\BaseServices;
use app\jobs\order\AddproJob;
use app\services\merchant\MerchantServices;
use app\services\user\UserVisitsServices;
use core\exceptions\ApiException;
use app\model\PlatformProduct;
use core\services\CacheService;
use think\facade\Log;

/**
 * Class PlatformProductServices
 * @package app\services
 * @mixin PlatformProduct
 */
class PlatformProductServices extends BaseServices
{
    protected function setModel(): string
    {
        return PlatformProduct::class;
    }
    public function getList($lang, $mer_id, $cate_id, $keyword = '')
    {
        $where=[];
        [$page, $limit] = $this->getPageValue();
        $where[] = ['is_show', '=', 1];
        $where[] = ['is_del', '=', 0];
        $name = 'store_name'.$this->lang[$lang];
        if($keyword){
            $where[] = [$name, 'LIKE', "%$keyword%"];
        }
        if($cate_id){
            $CategoryServices = app()->make(CategoryServices::class);
            $cate = $CategoryServices->getModel()->where('id', $cate_id)->find();
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
        $ProductServices = app()->make(ProductServices::class);
        $ids = $ProductServices->getModel()->where('mer_id', $mer_id)->column('old_product_id');
        $proids = CacheService::sMembers('mer_pro_ids');Log::info('proids: '.json_encode($proids));
        $exitids = array_unique(array_merge($ids, $proids));
        $list = $this->getModel()->field("id,image,{$name} as title,price")->whereNotIn('id', $exitids)->where($where)->order('id', 'desc')->page($page, $limit)->select()->toArray();
        return compact('list');
    }
    
    public function getDetail($lang, $pro_id, $uid = 0)
    {
        $name = 'store_name'.$this->lang[$lang];
        $product = $this->getModel()->field("id,image,slider_image,{$name} as title,price,mer_id,ficti")->find($pro_id);
        if(empty($product)){
            throw new ApiException('The product does not exist.');
        }
        $product = $product->toArray();
        // $product['sales'] = $product['sales'] + $product['ficti'];
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
        $merchant = $MerchantServices->getModel()->field("id,mer_name,mer_avatar,care_count,store_score,product_score,service_score,postage_score,type_id")->where('id', $product['mer_id'])->find($product['mer_id']);
        if(empty($merchant)){
        //   throw new ApiException('Merchant does not exist'); 
        } else {
            $merchant = $merchant->toArray();
        }
        
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
    
    public function addPro($mer_id, $item)
    {
        $this->transaction(function() use($item, $mer_id){
                $ProductServices = app()->make(ProductServices::class);
                $ProductAttrServices = app()->make(ProductAttrServices::class);
                $ProductAttrValueServices = app()->make(ProductAttrValueServices::class);
                $ProductContentServices = app()->make(ProductContentServices::class);
                $CategoryServices = app()->make(CategoryServices::class);
                if(!$ProductServices->getModel()->where('mer_id', $mer_id)->where('old_product_id', $item['id'])->count()){
                    $pro = $ProductServices->getModel()->create([
                              'mer_id'=>$mer_id,
                              'store_name'=>$item['store_name'],
                              'store_name_en'=>$item['store_name_en'],
                              'store_name_tw'=>$item['store_name_tw'],
                              'store_name_ja'=>$item['store_name_ja'],
                              'store_name_th'=>$item['store_name_th'],
                              'store_name_vi'=>$item['store_name_vi'],
                              'store_name_id'=>$item['store_name_id'],
                              'store_name_hi'=>$item['store_name_hi'],
                              'store_name_tr'=>$item['store_name_tr'],
                              'store_name_ar'=>$item['store_name_ar'],
                              'store_name_ko'=>$item['store_name_ko'],
                              'store_info'=>$item['store_info'],
                              'keyword'=>$item['keyword'],
                              'is_show'=>1,
                              'status'=>1,
                              'is_del'=>0,
                              'cate_id'=>$item['cate_id'],
                              'unit_name'=>$item['unit_name'],
                              'sort'=>$item['sort'],
                              'sales'=>0,
                              'price'=>$item['price'],
                              'cost'=>$item['cost'],
                              'ot_price'=>$item['ot_price'],
                              'stock'=>$item['stock'],
                              'is_good'=>0,
                              'ficti'=>0,
                              'browse'=>0,
                              'video_link'=>$item['video_link'],
                              'rate'=>$item['rate'],
                              'reply_count'=>$item['reply_count'],
                              'create_time'=>date('Y-m-d H:i:s'),
                              'old_product_id'=>$item['id'],
                              'image'=>$item['image'],
                              'slider_image'=>$item['slider_image']
                        ]);
                        
                    if($pro->id){
                        CacheService::sRem('mer_pro_ids', $item['id']);
                        
                        $cate = $CategoryServices->getModel()->where('id', $item['cate_id'])->find();
                        if(!empty($cate)){
                            $cate = $cate->toArray();
                            if($cate['pid']){
                                 $catepid = $CategoryServices->getModel()->where('id', $cate['pid'])->find();
                                 if(!empty($catepid)){
                                     $catepid = $catepid->toArray();
                                     if(!$CategoryServices->getModel()->where('ot_cate_id', $cate['pid'])->where('mer_id', $mer_id)->count()){
                                         $catepid['ot_cate_id'] = $cate['pid'];
                                         $catepid['pid'] = 0;
                                         $catepid['path'] = '/';
                                         $catepid['level'] = 1;
                                         $catepid['mer_id'] = $mer_id;
                                         unset($catepid['id']);
                                         $res_pid = $CategoryServices->getModel()->create($catepid);
                                         if($res_pid->id){
                                               $cate['ot_cate_id'] = $item['cate_id'];
                                               $cate['level'] = 2;
                                               $cate['pid'] = $res_pid->id;
                                               $cate['path'] = '/'.$res_pid->id.'/';
                                               unset($cate['id']);
                                               $cate['mer_id'] = $mer_id;
                                               $res_cate = $CategoryServices->getModel()->create($cate);
                                         }
                                     }
                                 }
                            }
                        }
                        
                        
                        if($item['content']){
                            $ProductContentServices->getModel()->create([
                                   'product_id'=>$pro->id,
                                   'content'=>$item['content']['content']
                                ]);
                        }
                        if($item['attr']){
                            foreach ($item['attr'] as $attr){
                                $ProductAttrServices->getModel()->create([
                                       'product_id'=>$pro->id,
                                       'attr_name'=>$attr['attr_name'],
                                       'attr_values'=>$attr['attr_values']
                                    ]);
                            }
                        }
                        if($item['attrvalue']){
                            foreach ($item['attrvalue'] as $attrvalue){
                                $ProductAttrValueServices->getModel()->create([
                                       'product_id'=>$pro->id,
                                       'detail'=>$attrvalue['detail'],
                                       'sku'=>$attrvalue['sku'],
                                       'stock'=>$attrvalue['stock'],
                                       'sales'=>$attrvalue['sales'],
                                       'image'=>$attrvalue['image'],
                                       'bar_code'=>$attrvalue['bar_code'],
                                       'cost'=>$attrvalue['cost'],
                                       'ot_price'=>$attrvalue['ot_price'],
                                       'price'=>$attrvalue['price'],
                                       'volume'=>$attrvalue['volume'],
                                       'weight'=>$attrvalue['weight'],
                                       'type'=>$attrvalue['type'],
                                       'extension_one'=>$attrvalue['extension_one'],
                                       'extension_two'=>$attrvalue['extension_two'],
                                       'unique'=>$attrvalue['unique']
                                    ]);
                            }
                        }
                    }
                }
        });
    }
    
    public function postPro($mer_id, $pro_ids)
    {
        $prolist = $this->getModel()->with(['content', 'attr', 'attrvalue'])->whereIn('id', $pro_ids)->select()->toArray();
        foreach ($prolist as $item){
            CacheService::sAdd('mer_pro_ids', $item['id']);
            AddproJob::dispatch([$mer_id, $item]);
        }
        return true;
    }
}

<?php
namespace app\controller\api;

use app\Request;
use core\services\CacheService;
use think\annotation\Inject;
use app\services\product\ProductServices;
use app\services\product\CategoryServices;
use app\services\merchant\MerchantServices;
use app\services\merchant\MerchantCategoryServices;
use think\facade\Log;

/**
 * 用户类
 * Class Index
 * @package app\controller\api
 */
class Index
{
     public function product(Request $request)
     {
         $lang = $request->lang();
         $ProductServices = app()->make(ProductServices::class);
         $list = $ProductServices->getIndexList($lang);
         return app('json')->success($list);
     }
     
     public function merchant(Request $request)
     {
         $MerchantServices = app()->make(MerchantServices::class);
         $list = $MerchantServices->indexMerchant();
         return app('json')->success($list);
     }
     
     public function catemerchant(Request $request)
     {
         $lang = $request->lang();
         $MerchantCategoryServices = app()->make(MerchantCategoryServices::class);
         $list = $MerchantCategoryServices->merCategory($lang);
         return app('json')->success($list);
     }
     
     public function category(Request $request)
     {
         $lang = $request->lang();
         $CategoryServices = app()->make(CategoryServices::class);
         $list = $CategoryServices->indexCategory($lang);
         return app('json')->success($list);
     }
     
     public function catelist(Request $request)
     {
         $lang = $request->lang();
         $CategoryServices = app()->make(CategoryServices::class);
         $list = $CategoryServices->catelist($lang);
         return app('json')->success($list);
     }
     
     public function prolist(Request $request)
     {
         [$cate_id, $type, $sort, $brand_id, $min_price, $max_price, $mer_id, $keyword] = $request->postMore([
            [['cate_id', 'd'], 0],
            ['type', ''],
            ['sort', ''],
            [['brand_id', 'd'], 0],
            [['min_price', 'd'], 0],
            [['max_price', 'd'], 0],
            [['mer_id', 'd'], 0],
            ['keyword', '']
         ], true);
         if($cate_id&&!preg_match('/^[1-9]\d*$/', $cate_id)){
             return app('json')->fail('Missing parameters');
         }
         $lang = $request->lang();
         $ProductServices = app()->make(ProductServices::class);
         $list = $ProductServices->getCateList($lang, $cate_id, $type, $sort, $brand_id, $min_price, $max_price, $mer_id, $keyword);
         return app('json')->success($list);
     }
     
     
     public function search(Request $request)
     {
         [$keyword] = $request->postMore([
            ['keyword', '']
         ], true);
         if(mb_strlen($keyword)>50){
             return app('json')->fail('limited to 50 characters');
         }
         $lang = $request->lang();
         $ProductServices = app()->make(ProductServices::class);
         $list = $ProductServices->getSearch($lang, $keyword);
         return app('json')->success($list);
     }
     
     
     public function deatil(Request $request)
     {
         [$pro_id] = $request->postMore([
            [['pro_id', 'd'], 0]
         ], true);
         if(!preg_match('/^[1-9]\d*$/', $pro_id)){
             return app('json')->fail('Missing parameters');
         }
         $lang = $request->lang();
         $uid = $request->uid();
         $ProductServices = app()->make(ProductServices::class);
         $data = $ProductServices->getDetail($lang, $pro_id, $uid);
         return app('json')->success($data);
     }
}
<?php
use app\http\middleware\AllowOriginMiddleware;
use app\http\middleware\api\AuthTokenMiddleware;
use app\http\middleware\api\BlockerMiddleware;
use app\http\middleware\api\MerchantMiddleware;
use app\http\middleware\StationOpenMiddleware;
use think\facade\Config;
use think\facade\Route;
use think\Response;

Route::any('test', 'Test/runTest')->name('test');
/**
 * 用户端路由配置
 */
Route::group('api', function () {
    
	//登录注册类
    Route::group(function () {
        //获取验证码
        Route::post('getcode', 'Login/getcode')->name('api_getcode');
        //注册
        Route::post('register', 'Login/register')->name('api_register');
        //登录
        Route::post('login', 'Login/login')->name('api_login');
        //检查更新
        Route::post('update', 'Base/appUpdate')->name('app_update');
        //基础数据
        Route::post('base', 'Base/config')->name('app_config');


	    ///首页数据
	    Route::post('index/product', 'Index/product')->name('api_index_product');
	    Route::post('index/merchant', 'Index/merchant')->name('api_index_merchant');
	    Route::post('index/category', 'Index/category')->name('api_index_category');
	    Route::post('index/catelist', 'Index/catelist')->name('api_index_catelist');
	    Route::post('index/prolist', 'Index/prolist')->name('api_index_prolist');
	    Route::post('index/cate_merchant', 'Index/catemerchant')->name('api_index_catemerchant');
	    Route::post('index/search', 'Index/search')->name('api_index_search');

    });
    
    //非授权接口
    Route::group(function () {
	    //店铺信息
        Route::post('store/merchant', 'Store/merchant')->name('api_store_merchant');
        Route::post('store/category', 'Store/category')->name('api_store_category');
	    Route::post('index/detail', 'Index/deatil')->name('api_index_deatil');
        
    })->middleware(AuthTokenMiddleware::class, false);
    
    
    Route::group(function () {
	    ///商户信息
	    Route::post('merchant/info', 'Merchant/info')->name('api_merchant_info');
	    ///商品列表
	    Route::post('merchant/prolist', 'Merchant/prolist')->name('api_merchant_prolist');
	    ///下架商品
	    Route::post('merchant/showpro', 'Merchant/showpro')->name('api_merchant_showpro');
	    ///推荐商品
	    Route::post('merchant/goodpro', 'Merchant/goodpro')->name('api_merchant_goodpro');
	    ///平台分类
	    Route::post('merchant/platcate', 'Merchant/platcate')->name('api_merchant_platcate');
	    ///平台商品
	    Route::post('merchant/platlist', 'Merchant/platlist')->name('api_merchant_platlist');
	    Route::post('merchant/platItem', 'Merchant/platItem')->name('api_merchant_platItem');
	    ///铺货商品
	    Route::post('merchant/postpro', 'Merchant/postpro')->name('api_merchant_postpro');
	    ///商户订单
	    Route::post('merchant/orderlist', 'Merchant/orderlist')->name('api_merchant_orderlist');
	    //订单详情
	    Route::post('merchant/orderdetail', 'Merchant/orderdetail')->name('api_merchant_orderdetail');
	    ///采购
	    Route::post('merchant/cgorder', 'Merchant/cgorder')->name('api_merchant_cgorder');
	    ///处理退款申请
	    Route::post('merchant/setrefund', 'Merchant/setrefund')->name('api_merchant_setrefund');
	   
	    ///店铺头像
	    Route::post('merchant/avatar', 'Merchant/setAvatar')->name('api_merchant_avatar');
	    ///店铺横幅
	    Route::post('merchant/banner', 'Merchant/setBanner')->name('api_merchant_banner');
	    ///店铺信息
	    Route::post('merchant/saveinfo', 'Merchant/saveinfo')->name('api_merchant_saveinfo');
	   
	   
	    ///提交充值
	    Route::post('merchant/recharge', 'Merchant/doRecharge')->name('api_merchant_recharge');
	    ///充值记录
	    Route::post('merchant/rechargelist', 'Merchant/rechargeLog')->name('api_merchant_rechargelog');
	    ///保存银行
	    Route::post('merchant/savebank', 'Merchant/saveBank')->name('api_merchant_savebank');
	    ///获取银行
	    Route::post('merchant/getbank', 'Merchant/getBank')->name('api_merchant_getbank');
	    ///提交提现
	    Route::post('merchant/withdraw', 'Merchant/doWithdraw')->name('api_merchant_withdraw');
	    ///提现记录
	    Route::post('merchant/withdrawlist', 'Merchant/withdrawLog')->name('api_merchant_withdrawlog');
	    ///资金记录
	    Route::post('merchant/moneylog', 'Merchant/moneylog')->name('api_merchant_moneylog');
	    
	    
	    ///资金统计
	    Route::post('merchant/profit', 'Merchant/profit')->name('api_merchant_profit');
	    ///订单统计
	    Route::post('merchant/ordernum', 'Merchant/ordernum')->name('api_merchant_ordernum');
	    ///图表统计
	    Route::post('merchant/datacenter', 'Merchant/datacenter')->name('api_merchant_datacenter');
	    
	    Route::post('merchant/trafficorder', 'Order/createTrafficOrder')->name('api_create_trafficorder');
    })->middleware(AuthTokenMiddleware::class, true, true);


    //会员授权接口
    Route::group(function () {
	    //退出登录
        Route::post('logout', 'Login/logout')->name('api_logout');
        //AWS上传配置
        Route::post('upload/s3config', 'Upload/s3Config')->name('api_s3config');
	    //注销账号
        Route::post('user/cancel', 'User/setDel')->name('api_user_cancel');
        ///用户信息
	    Route::post('user/info', 'User/info')->name('api_user_info');
        ///浏览历史
	    Route::post('user/visits', 'User/visits')->name('api_user_visits');
        ///订单统计
	    Route::post('user/ordernum', 'User/ordernum')->name('api_user_ordernum');
	    
	    
	    ///修改头像
	    Route::post('user/avatar', 'User/setAvatar')->name('api_user_avatar');
	    ///提交签名
	    Route::post('user/signature', 'User/setSignture')->name('api_user_signature');
	    ///修改昵称
	    Route::post('user/nick', 'User/setNick')->name('api_user_nickname');
	    ///修改密码
	    Route::post('user/pass', 'User/editPass')->name('api_user_pass');
	    ///验证支付密码
	    Route::post('user/checktpass', 'User/checktpass')->name('api_user_ckpass');
	    ///修改支付密码
	    Route::post('user/spass', 'User/editSPass')->name('api_user_spass');
	    ///保存性别
	    Route::post('user/gender', 'User/setGender')->name('api_user_gender');
	    ///设置手机号码
	    Route::post('user/setphone', 'User/setPhone')->name('api_user_phone');
	    ///设置邮箱地址
	    Route::post('user/setemail', 'User/setEmail')->name('api_user_email');
	    ///提交充值
	    Route::post('user/recharge', 'User/doRecharge')->name('api_user_recharge');
	    ///充值记录
	    Route::post('user/rechargelist', 'User/rechargeLog')->name('api_user_rechargelog');
	    
	    ///订单列表
	    Route::post('user/orderlist', 'User/orderlist')->name('api_user_orderlist');
	    ///订单退款
	    Route::post('user/orderrefund', 'User/orderrefund')->name('api_user_orderrefund');
	    ///订单收货
	    Route::post('user/orderrecived', 'User/orderrecived')->name('api_user_orderrecived');
	    ///商户申请
	    Route::post('user/apply', 'User/applyMer')->name('api_user_applymer');
	    
	    
        ///访问信息
	    Route::post('user/prodetail', 'User/prodetail')->name('api_user_prodetail');
        ///关注店铺
	    Route::post('user/collect', 'User/collectMer')->name('api_user_collectmer');
        ///我的关注店铺列表
	    Route::post('user/storelist', 'User/storelist')->name('api_user_storelist');
        ///收藏商品
	    Route::post('user/favpro', 'User/favPro')->name('api_user_favpro');
        ///我的收藏商品列表
	    Route::post('user/favlist', 'User/favlist')->name('api_user_favlist');
        ///删除收藏商品
	    Route::post('user/favdel', 'User/favDel')->name('api_user_favdel');
        ///加入购物车
	    Route::post('user/addcart', 'User/addCart')->name('api_user_addcart');
        ///获取购物车
	    Route::post('user/cart', 'User/cart')->name('api_user_cart');
        ///删除购物车
	    Route::post('user/delcart', 'User/delcart')->name('api_user_delcart');
        ///修改购物车数量
	    Route::post('user/cartnum', 'User/cartnum')->name('api_user_cartnum');
        ///添加地址
	    Route::post('user/address/add', 'User/addAddress')->name('api_user_addaddress');
        ///获取地址列表
	    Route::post('user/address/list', 'User/addressList')->name('api_user_address_list');
        ///删除地址
	    Route::post('user/address/del', 'User/addressDel')->name('api_user_address_del');
        ///设为默认地址
	    Route::post('user/address/defaultadd', 'User/addDefault')->name('api_user_add_default');
        ///获取默认地址
	    Route::post('user/address/default', 'User/addressDefault')->name('api_user_address_default');
        ///设置支付密码
	    Route::post('user/paypass/add', 'User/addPass')->name('api_user_addpass');
	    
	    
        ///提交订单
	    Route::post('user/order', 'Order/createOrder')->name('api_create_order');
        
	
    })->middleware(AuthTokenMiddleware::class, true);


    /**
     * miss 路由
     */
    Route::miss(function () {
        if (app()->request->isOptions()) {
            $header = Config::get('cookie.header');
            $header['Access-Control-Allow-Origin'] = app()->request->header('origin');
            return Response::create('ok')->code(200)->header($header);
        } else
            return Response::create()->code(404);
    });

})->prefix('api.')->middleware(AllowOriginMiddleware::class)->middleware(StationOpenMiddleware::class);


<?php
use app\http\middleware\AllowOriginMiddleware;
use app\http\middleware\admin\AdminAuthTokenMiddleware;
use app\http\middleware\StationOpenMiddleware;
use think\facade\Config;
use think\facade\Route;
use think\Response;

/**
 * 用户端路由配置
 */
Route::group('admin', function () {


	//登录注册类
    Route::group(function () {
        //图片验证码
        Route::get('captcha', 'Login/captcha')->name('captcha');
        //登录
        Route::post('login', 'Login/login')->name('login');
        //网站配置
        // Route::post('system/config', 'PublicController/config')->name('system_config');
        Route::post('base', 'PublicController/config')->name('system_config');

    })->middleware(StationOpenMiddleware::class);
    
    //会员授权接口
    Route::group(function () {
	    //退出登录
        Route::post('logout', 'Login/logout')->name('logout');
	    //获取提醒
        Route::post('admin/topinfo', 'Admin/topinfo')->name('admin_topinfo');
	    
        //系统配置
        Route::post('sysconfig/list', 'Sysconfig/getConfig')->name('sys_config');
	    //保存系统配置
        Route::post('sysconfig/edit', 'Sysconfig/editConfig')->name('sys_save_config');
        
        //修改密码
        Route::post('admin/pass', 'Admin/resetPass')->name('admin_pass');
	    //获取MFA信息
        Route::post('admin/mfa', 'Admin/mfaInfo')->name('admin_mfa');
	    //修改MFA信息
        Route::post('admin/mfaedit', 'Admin/saveMFA')->name('admin_mfaedit');
	    //删除MFA信息
        Route::post('admin/mfadel', 'Admin/delMFA')->name('admin_mfadel');
       
        //获取管理员列表
        Route::post('admin/list', 'Admin/adminList')->name('admin_list');
        //获取角色列表
        Route::post('admin/role', 'Admin/roleList')->name('admin_rolelist');
        //添加管理
        Route::post('admin/add', 'Admin/addAdmin')->name('admin_add');
        //编辑管理
        Route::post('admin/edit', 'Admin/editAdmin')->name('admin_edit');
        //添加管理
        Route::post('admin/del', 'Admin/delAdmin')->name('admin_del');
       
        //获取用户列表
        Route::post('user/list', 'User/userList')->name('room_user_list');
        //添加用户
        Route::post('user/add', 'User/userAdd')->name('user_add');
        //加款
        Route::post('user/addmoney', 'User/addMoney')->name('user_addmoney');
        //扣款
        Route::post('user/submoney', 'User/subMoney')->name('user_submoney');
        //获取用户密码
        Route::post('user/pass', 'User/editPass')->name('user_pass');
        //获取用户交易密码
        Route::post('user/tpass', 'User/editTPass')->name('user_tpass');
        //修改邀请码
        Route::post('user/invitecode', 'User/editInviteCode')->name('user_invitecode');
        //修改备注
        Route::post('user/remark', 'User/editRemark')->name('user_remark');
        
        //获取充值列表
        Route::post('user/recharge', 'User/rechargeList')->name('user_recharge_list');
        //修改充值
        Route::post('user/rechargeedit', 'User/rechargeEdit')->name('user_recharge_edit');
        
        
        
        //获取商户列表
        Route::post('mer/list', 'Merchant/merList')->name('mer_list');
        //加款
        Route::post('mer/addmoney', 'Merchant/addMoney')->name('mer_addmoney');
        //扣款
        Route::post('mer/submoney', 'Merchant/subMoney')->name('mer_submoney');
        //修改资料
        Route::post('mer/edit', 'Merchant/editmer')->name('mer_meredit');
        //修改配置资料
        Route::post('mer/numedit', 'Merchant/numedit')->name('mer_numedit');
        
        //获取商户申请列表
        Route::post('mer/applylist', 'Merchant/applyList')->name('mer_applyList');
        //处理商户申请
        Route::post('mer/applyedit', 'Merchant/applyedit')->name('mer_applyedit');
        
        //获取充值列表
        Route::post('mer/recharge', 'Merchant/rechargeList')->name('mer_recharge_list');
        //修改充值
        Route::post('mer/rechargeedit', 'Merchant/rechargeEdit')->name('mer_recharge_edit');
        //获取提现列表
        Route::post('mer/withdraw', 'Merchant/withdrawList')->name('mer_withdraw_list');
        //修改提现
        Route::post('mer/withdrawedit', 'Merchant/withdrawEdit')->name('mer_withdraw_edit');
        //获取商户流量列表
        Route::post('mer/trafficorder', 'Merchant/trafficOrderList')->name('mer_list');

        
        //资金列表
        Route::post('mer/moneylog', 'Merchant/moneylog')->name('mer_moneylog');
        
        ///获取上传数据
        Route::post('s3config', 'Upload/s3Config')->name('upload_s3config');
	    
    })->middleware(StationOpenMiddleware::class)->middleware(AdminAuthTokenMiddleware::class, true);


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

})->prefix('admin.')->middleware(AllowOriginMiddleware::class)->middleware(StationOpenMiddleware::class);
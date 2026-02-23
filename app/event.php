<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        'AppInit' => [],
        'HttpRun' => [],
        'HttpEnd' => [\app\listener\http\HttpEnd::class], //HTTP请求结束回调事件
        'LogLevel' => [],
        'LogWrite' => [],
        //swoole 初始化事件
        'swoole.init' => [
            \core\listeners\InitSwooleLockListen::class, //初始化
        ],
        //swoole 启动事件
        'swoole.workerStart' => [\core\listeners\SwooleStartListen::class],
        'swoole.workerExit' => [],
        'swoole.workerError' => [],
        'beforeWorkerStop' => [\core\listeners\SwooleStopListen::class],
        'swoole.workerStop' => [\core\listeners\SwooleStopListen::class],
        'swoole.shutDown' => [\core\listeners\SwooleShutdownListen::class],//swoole 停止事件
        'swoole.websocket.user' => [\app\webscoket\handler\UserHandler::class],//socket 用户调用事件
        'swoole.websocket.room' => [\app\webscoket\handler\RoomHandler::class],//socket 房主调用事件
        'swoole.websocket.admin' => [\app\webscoket\handler\AdminHandler::class],//socket 后台调用事件
        'swoole.websocket.agent' => [\app\webscoket\handler\AgentHandler::class],//socket 代理调用事件

        //定时执行
        'crontab' => !env('TIMER_ENABLE', false) ? [] : [
            \app\listener\timer\SystemTimer::class,//定时任务
        ],
        
         ///会员
        'user.login' => [\app\listener\user\UserLogin::class], //用户登录事件
        'user.loginlog' => [\app\listener\user\UserLoginlog::class], //用户登录日志事件
        'user.loginroom' => [\app\listener\user\UserLoginRoom::class], //用户进入房间日志事件
        
        ///房间
        'room.inform' => [\app\listener\room\RoomInform::class], //通知房主事件
        'room.invite' => [\app\listener\room\UserInvite::class], //用户推荐关系绑定事件
        'room.loginlog' => [\app\listener\room\RoomLoginlog::class], //用户登录日志事件

    ],

    'subscribe' => [

    ],
];

<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // 默认使用的数据库连接配置
    'default'         => env('DATABASE_DRIVER', 'mysql'),
    // 自定义时间查询规则
    'time_query_rule' => [],
    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => 'timestamp',
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 时间字段配置 配置格式：create_time,update_time
    'datetime_field'  => '',
    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'            => env('DATABASE_TYPE', 'mysql'),
            // 服务器地址
            'hostname'        => env('DATABASE_HOSTNAME', '127.0.0.1'),
            // 数据库名
            'database'        => env('DATABASE_DATABASE', ''),
            // 用户名
            'username'        => env('DATABASE_USERNAME', 'root'),
            // 密码
            'password'        => env('DATABASE_PASSWORD', ''),
            // 端口
            'hostport'        => env('DATABASE_HOSTPORT', '3306'),
            // 连接dsn
            'dsn'             => '',
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8
            'charset'         => env('DATABASE_CHARSET', 'utf8mb4'),
            // 数据库表前缀
            'prefix'          => env('DATABASE_PREFIX', 'eb_'),
            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 字段缓存
            'fields_cache'    => !env('APP_DEBUG', false),
            // 监听SQL
            'trigger_sql'     => env('APP_DEBUG', true),
            // 是否需要进行SQL性能分析
            'sql_explain'     => false,
            // Builder类
            'builder'         => '',
            // Query类
            'query'           => '',
            // 是否需要断线重连
            'break_reconnect' => true,
        ],
        // 更多的数据库配置信息
        'mongodb'=>[
            // 数据库类型
            'type'              => 'mongo',
            // 服务器地址
            'hostname'          => env('MANGODB_HOSTNAME', '127.0.0.1'),
            // 数据库名
            'database'          => env('MANGODB_DATABASE', ''),
            // 用户名
            'username'          => env('MANGODB_USERNAME', 'root'),
            // 密码
            'password'          => env('MANGODB_PASSWORD', ''),
            // 端口
            'hostport'          => '27017',
        ],
    ],
    //数据分页配置
    'page' => [
        //页码key
        'pageKey' => 'page',
        //每页截取key
        'limitKey' => 'limit',
        //每页截取最大值
        'limitMax' => 100,
        //默认条数
        'defaultLimit' => 10,
    ]
];

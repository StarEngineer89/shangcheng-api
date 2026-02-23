<?php

use app\webscoket\Manager;
use Swoole\Table;


return [
    'http' => [
        'enable' => true,
        'host' => '0.0.0.0',
        'port' => 20199,
        'worker_num' => swoole_cpu_num() * 2,
        'max_request' => 10000,
        'options' => [
            'package_max_length' => 50 * 1024 * 1024,
            'buffer_output_size' => 10 * 1024 * 1024,
        ],
    ],
    'websocket' => [
        'enable' => false
    ],
    'rpc' => [
        'server' => [
            'enable' => false,
            'host' => '0.0.0.0',
            'port' => 9000,
            'message_queue_key' => ftok(__FILE__, 'a'),
            'worker_num' => swoole_cpu_num() * 2,
            'services' => [
            ],
        ],
        'client' => [
        ],
    ],
    //队列
    'queue' => [
        'enable' => env('QUEUE_ENABLE', true),
        'workers' => [
            '852d696cc27b8' => [],
            'BATCH852d696cc27b8' => [],
            'KXCMS_PRO_TASK' => [],
            'KXCMS_PRO_LOG' => [],
            'KXCMS_PRO_SOCKET' => []
        ],
    ],
    //热更新
    'hot_update' => [
        'enable' => env('APP_DEBUG', false),
        'name' => ['*.php'],
        'include' => [app_path(), root_path('core'), root_path('route')],
        'exclude' => [],
    ],
    //连接池
    'pool' => [
        'db' => [
            'enable' => true,
            'min_active'    => 4,
            'max_active' => swoole_cpu_num() * 16,
            'max_wait_time' => 10,
        ],
        'cache' => [
            'enable' => true,
            'min_active'    => 4,
            'max_active' => swoole_cpu_num() * 16,
            'max_wait_time' => 10,
        ],
        //自定义连接池
    ],
    'tables' => [//高性能内存数据库
        'user' => [
            'size' => 20480,
            'columns' => [
                ['name' => 'fd', 'type' => Table::TYPE_STRING, 'size' => 50],
                ['name' => 'type', 'type' => Table::TYPE_INT],
                ['name' => 'room_num', 'type' => Table::TYPE_INT],
                ['name' => 'uid', 'type' => Table::TYPE_INT],
                ['name' => 'room_id', 'type' => Table::TYPE_INT]
            ]
        ]
    ],
    //每个worker里需要预加载以共用的实例
    'concretes' => [],
    //重置器
    'resetters' => [],
    //每次请求前需要清空的实例
    'instances' => [],
    //每次请求前需要重新执行的服务
    'services' => [],
];

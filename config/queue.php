<?php


return [
    'default' => 'redis',
    'connections' => [
        'sync' => [
            'type' => 'sync',
        ],
        'database' => [
            'type' => 'database',
            'queue' => 'default',
            'table' => 'jobs',
        ],
        'redis' => [
            'type'          => 'redis',
            'queue'         => env('QUEUE_LISTEN_NAME', 'KXCMS_PRO'),
            'batch_queue'   => env('QUEUE_BATCH_LISTEN_NAME', 'KXCMS_PRO_BATCH'),
            'host'          => env('REDIS_HOSTNAME', '127.0.0.1'),
            'port'          => env('REDIS_PORT', 6379),
            'password'      => env('REDIS_PASSWORD', ''),
            'select'        => 1,
            'timeout'       => 0,
            'persistent'    => false,
        ],
    ],
    'failed' => [
        'type' => 'none',
        'table' => 'failed_jobs',
    ],
];

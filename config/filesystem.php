<?php

use think\facade\Env;

return [
    'default' => env('FILESYSTEM_DRIVER', 'public'),
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'storage',
        ],
        'public' => [
            'type'       => 'local',
            'root'       => app()->getRootPath() . 'public/uploads',
            'url'        => '/uploads',
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
    ],
];

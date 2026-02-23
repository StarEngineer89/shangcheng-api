<?php
use app\ExceptionHandle;
use app\Request;
use core\topthink\Route;
use core\topthink\TraceDebug;

// 容器Provider定义文件
return [
    'think\Request'          => Request::class,
    'think\Route'            => Route::class,
    'think\exception\Handle' => ExceptionHandle::class,
    'think\trace\TraceDebug' => TraceDebug::class
];

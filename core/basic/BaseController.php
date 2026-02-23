<?php
declare (strict_types=1);

namespace core\basic;

use think\facade\App;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \app\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * @var
     */
    protected $services;

    /**
     * 需要授权的接口地址
     * @var string[]
     */
    private $authRule = [];
    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = app('request');
        $this->initialize();
    }

    /**
     * @return mixed
     */
    abstract protected function initialize();


}

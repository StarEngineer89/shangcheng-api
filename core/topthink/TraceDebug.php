<?php
namespace core\topthink;


use think\event\LogWrite;

class TraceDebug extends \think\trace\TraceDebug
{
    /**
     * @param \think\Request $request
     * @param \Closure $next
     * @return mixed|void
     */
    public function handle($request, \Closure $next)
    {
        $debug = $this->app->config->get('trace.enable');

        // 注册日志监听
        if ($debug) {
            $this->log = [];
            $this->app->event->listen(LogWrite::class, function ($event) {
                if (empty($this->config['channel']) || $this->config['channel'] == $event->channel) {
                    $this->log = array_merge_recursive($this->log, $event->log);
                }
            });
        }

        $response = $next($request);

        // Trace调试注入
        if ($debug) {
            $data = $response->getContent();
            $this->traceDebug($response, $data);
            $response->content($data);
        }

        return $response;
    }
}

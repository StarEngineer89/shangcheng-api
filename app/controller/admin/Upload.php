<?php
namespace app\controller\admin;

use app\Request;
use core\services\AwsUploadService;

class Upload
{
    public function s3Config(Request $request)
    {
        [$type, $ext] = $request->postMore([
            ['type', ''],
            ['ext', ''],
        ], true);
        if (!$type||!$ext) {
            return app('json')->fail('缺少参数');
        }
        if(!in_array($ext, ['jpg','png'])){
            return app('json')->fail('参数错误');
        }
        $adminId = $request->adminId();
        $UploadService = app()->make(AwsUploadService::class);
        $data = $UploadService->uploadUrl($type, $ext, $adminId);
        return app('json')->success($data);
    }
}
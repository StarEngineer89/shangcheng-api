<?php
namespace app\controller\api;
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
            return app('json')->fail('Missing parameters');
        }
        $uid = $request->uid();
        $UploadService = app()->make(AwsUploadService::class);
        $data = $UploadService->uploadUrl($type, $ext, $uid);
        return app('json')->success($data);
    }
}
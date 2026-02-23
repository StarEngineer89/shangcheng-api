<?php
namespace core\services;

use Aws\S3\S3Client;
use Aws\S3\PostObjectV4;
use Aws\Exception\AwsException;
use GuzzleHttp\Client as HttpClient;
use think\exception\ValidateException;
use think\facade\Log;

class AwsUploadService
{
    private $s3;
    private $bucket;

    public function __construct()
    {
        $this->bucket = env('AWS_BUCKET_NAME','');
        $this->s3 = new S3Client([
            'region'  => env('AWS_REGION',''),
            'version' => 'latest',
            'credentials' => [
                'key'    => env('AWS_APPKEY',''),
                'secret' => env('AWS_APPSECRET',''),
            ],
            'suppress_php_deprecation_warning' => true,
        ]);
    }

    public function uploadUrl($type, $ext, $uid = 0)
    {
        try {
            $bucketName = $this->bucket; // 替换为您的存储桶名称
            $objectKey = 'uploads/'.$type.'/'.$uid.'_'.date('YmdHis').rand(100000,999999).'.'.$ext; // S3 对象路径
            $expiration = '+20 minutes'; // 预签名有效期
            $formInputs = [
                'key' => $objectKey,
                'acl' => 'public-read', // 可选，设置访问权限
            ];
            $options = [
                ['eq', '$acl', 'public-read'],
                ['eq', '$bucket', $bucketName],
                ['content-length-range', 0, 10485760], // 文件大小限制（0-10MB）
                ['starts-with', '$key', 'uploads/'],  // 限制上传路径以 'uploads/' 开头
            ];
            $postObject = new PostObjectV4($this->s3, $bucketName, $formInputs, $options, $expiration);

            $formAttributes = $postObject->getFormAttributes(); // 包含 `action` URL
            $formInputs = $postObject->getFormInputs(); // 包含表单字段

            return [
                'url' => $formAttributes['action'],
                'fields' => $formInputs,
            ];
        } catch (AwsException $e) {
            // 处理错误
            throw new ValidateException('上传失败：'.$e->getMessage());
        }
    }

    /**
     * 上传文件到 S3
     *
     * @param array $file 上传的文件数组 ($_FILES['file'])
     * @param string $folder S3 中的存储文件夹
     * @return array 返回上传结果
     */
    public function uploadToS3($file, $folder = 'uploads')
    {
        $originalName = $file->getOriginalName();
        $tempPath = $file->getPathname();
        $mime = $file->getMime();
        $size = $file->getSize();
        
        if (!$file->isValid()) {
            throw new ValidateException('无效的文件上传');
        }
        
        // $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        // if (!in_array($mime, $allowedTypes)) {
        //     throw new ValidateException('仅支持jpg/png格式图片');
        // }
        
        // 限制文件大小 (2MB)
        $maxSize = 10 * 1024 * 1024;
        if ($size > $maxSize) {
            throw new ValidateException('文件大小超过限制 (10MB)');
        }

        try {
            $result = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key'    => "$folder/$originalName",
                'SourceFile' => $tempPath,
                'ACL'    => 'public-read',
            ]);
            return $result['ObjectURL'];
        } catch (AwsException $e) {
            Log::error('文件上传失败'.$e->getMessage());
            throw new ValidateException('文件上传失败'.$e->getMessage());
        }
    }
    
    public function uploadUrlToS3($imageUrl){
        $s3Key  = 'uploads/image/'.date('YmdHis').rand(100000,999999).'.png';
        $http = new HttpClient();
        $response = $http->get($imageUrl, ['stream' => true]);
        $contentType = $response->getHeaderLine('Content-Type') ?: 'application/octet-stream';
        $stream = $response->getBody();
        $contents = $stream->getContents(); 
        // 上传到 S3
        $result = $this->s3->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $s3Key,
            'Body'        => $contents,
            'ContentType' => $contentType,
            'ACL'         => 'public-read',
        ]);
        return !empty($result['ObjectURL'])?$result['ObjectURL']:false;
    }
}
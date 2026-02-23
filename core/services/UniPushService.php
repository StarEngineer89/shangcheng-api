<?php
namespace core\services;

use core\services\CacheService;
use think\facade\Log;
use think\Exception;
/**
 * Class UniPushService
 * @package core\services
 */
class UniPushService {
    private $baseUrl = "https://restapi.getui.com/v2"; // 个推 RestAPI V2 基础 URL
    private $appId;
    private $appKey;
    private $masterSecret;
    private $authToken;

    public function __construct() {
        $this->appId = sys_config('unipush_appid');
        $this->appKey = sys_config('unipush_appkey');
        $this->masterSecret = sys_config('unipush_mastersecret');

        // 获取授权 Token
        if(CacheService::has('UNIPUSH_TOKEN')&&CacheService::get('UNIPUSH_TOKEN')){
            $this->authToken = CacheService::get('UNIPUSH_TOKEN');
        }else{
            $this->authToken = $this->getAuthToken();
        }
    }

    // 获取授权 Token
    private function getAuthToken() {
        $url = "{$this->baseUrl}/{$this->appId}/auth";
        $timestamp = time() * 1000;
        $sign = hash('sha256', $this->appKey . $timestamp . $this->masterSecret);

        $response = $this->sendRequest('POST', $url, [
            'sign' => $sign,
            'timestamp' => $timestamp,
            'appkey' => $this->appKey,
        ]);

        if ($response['status'] === 200 && isset($response['response']['data']['token'])) {
            $expire_time = floor($response['response']['data']['expire_time']/1000);
            $time = floor($expire_time - time() - 300);
            CacheService::set('UNIPUSH_TOKEN', $response['response']['data']['token'], $time);
            return $response['response']['data']['token'];
        } else {
            throw new Exception("获取授权 Token 失败: " . json_encode($response));
        }
    }

    // 发送推送消息（单推）
    public function pushToSingle($cid, $message) {
        $url = "{$this->baseUrl}/{$this->appId}/push/single/cid";

        $payload = [
            'request_id' => uniqid(),
            'audience' => ['cid' => [$cid]],
            'settings' => ['ttl' => 3600000], // 消息有效时间，单位 ms
            'push_message' => [
                'notification' => [
                    'title' => $message['title'],
                    'body' => $message['body'],
                    'click_type' => 'startapp', // 点击后不做跳转，可调整
                ],
            ],
            'push_channel' => [
                "ios"=>[
                    "type"=>"notify",
                    "aps"=>[
                        "alert"=>[
                            "title" => $message['title'],
                            "body" => $message['body'],
                        ],
                        "content-available" => 0,
                        "sound" => "default",
                        "category" => "ACTIONABLE"
                    ],
                    "auto_badge"=>"+1"
                ]
            ]
        ];

        return $this->sendRequest('POST', $url, $payload);
    }

    // 发送推送消息（群推）
    public function pushToList($cids, $message) {
        $url = "{$this->baseUrl}/{$this->appId}/push/list/cid";

        $payload = [
            'request_id' => uniqid(),
            'audience' => ['cid' => $cids],
            'settings' => ['ttl' => 3600000], // 消息有效时间，单位 ms
            'push_message' => [
                'notification' => [
                    'title' => $message['title'],
                    'body' => $message['body'],
                    'click_type' => 'startapp', // 点击后不做跳转，可调整
                ],
            ]
        ];

        return $this->sendRequest('POST', $url, $payload);
    }

    // 发送 HTTP 请求
    private function sendRequest($method, $url, $data = null) {
        $curl = curl_init();

        $headers = [
            "Content-Type: application/json",
            "token: {$this->authToken}",
        ];

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            throw new Exception('cURL 错误: ' . curl_error($curl));
        }
        curl_close($curl);
        return [
            'status' => $httpCode,
            'response' => json_decode($response, true),
        ];
    }
}

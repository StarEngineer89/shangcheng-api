<?php
namespace core\services;

use AlibabaCloud\SDK\Dysmsapi\V20180501\Dysmsapi;
use AlibabaCloud\Darabonba\Env\Env;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\Tea\Console\Console;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20180501\Models\SendMessageToGlobeRequest;

class AliSMSService
{
    /**
     * 使用AK&SK初始化账号Client
     * @return Dysmsapi
     */
    public static function createDysmsapiClient()
    {
        $config = new Config([
            "accessKeyId" => env('AliAccessKeyId', ''),
            "accessKeySecret" => env('AliAccessKeySecret', '')
        ]);
        $config->endpoint = "dysmsapi.ap-southeast-1.aliyuncs.com";
        return new Dysmsapi($config);
    }

    /**
     * @param Dysmsapi $client
     * @param string $to
     * @param string $message
     * @param string $from
     * @param string $taskId
     * @return void
     */
    public static function sendMessageToGlobe($number, $code)
    {
        $client = self::createDysmsapiClient();
        $req = new SendMessageToGlobeRequest([
            "to" => str_replace('+', '', $number),
            "message" => "【AlaMall】 Your verification code is {$code}, and the verification code is valid within 5 minutes",
            "from" => 'AlaMall'
        ]);
        $resp = $client->sendMessageToGlobe($req);
        $resjson = Utils::toJSONString(Utils::toMap($resp));
        $resource = json_decode($resjson, true);
        if (!empty($resource['body']['ResponseCode']) && $resource['body']['ResponseCode'] == 'OK') {
            return 'OK';
        } else {
            return !empty($resource['body']['ResponseDescription']) ? $resource['body']['ResponseDescription'] : 'Fail';
        }
    }
}
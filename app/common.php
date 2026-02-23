<?php
use app\Request;
use app\services\system\SystemConfigServices;
use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\ClickWordCaptchaService;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;


if (!function_exists('sys_config')) {
    /**
     * 获取系统单个配置
     * @param string $name
     * @param string $default
     * @return string
     */
    function sys_config(string $name, $default = '')
    {
        if (empty($name)){
            return $default;
        }
        $systemConfigServices = app()->make(SystemConfigServices::class);
        $sysConfig = $systemConfigServices->getConfigValue($name);
        if (is_array($sysConfig)) {
            foreach ($sysConfig as &$item) {
                if (strpos($item, '/uploads/file/') !== false || strpos($item, '/uploads/images/') !== false) $item = set_file_url($item);
            }
        } else {
            if (strpos($sysConfig, '/uploads/file/') !== false || strpos($sysConfig, '/uploads/images/') !== false) $sysConfig = set_file_url($sysConfig);
        }
        $config = is_array($sysConfig) ? $sysConfig : trim($sysConfig);
        if ($config === '' || $config === false) {
            return $default;
        } else {
            return $config;
        }
    }
}

if (!function_exists('set_file_url')) {
    /**
     * 设置附加路径
     * @param $url
     * @return bool
     */
    function set_file_url($image, $siteUrl = '')
    {
        if (!strlen(trim($siteUrl))) $siteUrl = sys_config('file_url','');
        if (!$image) return $image;
        if (is_array($image)) {
            foreach ($image as &$item) {
                $domainTop1 = substr($item, 0, 4);
                $domainTop2 = substr($item, 0, 2);
                if ($domainTop1 != 'http' && $domainTop2 != '//')
                    $item = $siteUrl . str_replace('\\', '/', $item);
            }
        } else {
            $domainTop1 = substr($image, 0, 4);
            $domainTop2 = substr($image, 0, 2);
            if ($domainTop1 != 'http' && $domainTop2 != '//')
                $image = $siteUrl . str_replace('\\', '/', $image);
        }
        return $image;
    }
}

if (!function_exists('calculateCNLength')) {
   /**
     * 计算包含中文的字符长度
     * @param $time
     * @return string
     */
   function calculateCNLength($nickname) {
        $length = 0;
        // 正则表达式匹配中文字符
        $pattern = '/[\x80-\xff]/u';
        
        // 遍历每个字符
        for ($i = 0; $i < mb_strlen($nickname, 'UTF-8'); $i++) {
            $char = mb_substr($nickname, $i, 1, 'UTF-8');
            // 判断是否为中文字符
            if (preg_match($pattern, $char)) {
                $length += 2; // 中文字符按2个字符计算
            } else {
                $length += 1; // 其他字符按1个字符计算
            }
        }
        
        return $length;
    }
}

if (!function_exists('time_tran')) {
    /**
     * 时间戳人性化转化
     * @param $time
     * @return string
     */
    function time_tran($time)
    {
        $t = time() - $time;
        $f = array(
            '31536000' => '年',
            '2592000' => '个月',
            '604800' => '星期',
            '86400' => '天',
            '3600' => '小时',
            '60' => '分钟',
            '1' => '秒'
        );
        foreach ($f as $k => $v) {
            if (0 != $c = floor($t / (int)$k)) {
                return $c . $v . '前';
            }
        }
    }
}

if (!function_exists('url_to_path')) {
    /**
     * url转换路径
     * @param $url
     * @return string
     */
    function url_to_path($url)
    {
        $path = trim(str_replace('/', DS, $url), DS);
        if (0 !== strripos($path, 'public'))
            $path = 'public' . DS . $path;
        return app()->getRootPath() . $path;
    }
}

if (!function_exists('path_to_url')) {
    /**
     * 路径转url路径
     * @param $path
     * @return string
     */
    function path_to_url($path)
    {
        return trim(str_replace(DS, '/', $path), '.');
    }
}


if (!function_exists('get_thumb_water')) {
    /**
     * 处理数组获取缩略图、水印
     * @param $list
     * @param string $type
     * @param array|string[] $field 1、['image','images'] type 取值参数:type 2、['small'=>'image','mid'=>'images'] type 取field数组的key
     * @param bool $is_remote_down
     * @return array|mixed|string|string[]
     */
    function get_thumb_water($list, string $type = 'small', array $field = ['image'], bool $is_remote_down = false)
    {
        if (!$list || !$field) return $list;
        $baseType = $type;
        $data = $list;
        if (is_string($list)) {
            $field = [$type => 'image'];
            $data = ['image' => $list];
        }
        if (is_array($data)) {
            foreach ($field as $type => $key) {
                if (is_integer($type)) {//索引数组，默认type
                    $type = $baseType;
                }
                //一维数组
                if (isset($data[$key])) {
                    if (is_array($data[$key])) {
                        $path_data = [];
                        foreach ($data[$key] as $k => $path) {
                            $path_data[] = get_image_thumb($path, $type, $is_remote_down);
                        }
                        $data[$key] = $path_data;
                    } else {
                        $data[$key] = get_image_thumb($data[$key], $type, $is_remote_down);
                    }
                } else {
                    foreach ($data as &$item) {
                        if (!isset($item[$key]))
                            continue;
                        if (is_array($item[$key])) {
                            $path_data = [];
                            foreach ($item[$key] as $k => $path) {
                                $path_data[] = get_image_thumb($path, $type, $is_remote_down);
                            }
                            $item[$key] = $path_data;
                        } else {
                            $item[$key] = get_image_thumb($item[$key], $type, $is_remote_down);
                        }
                    }
                }
            }
        }
        return is_string($list) ? ($data['image'] ?? '') : $data;
    }
}
if (!function_exists('put_image')) {
    /**
     * 获取图片转为base64
     * @param $url
     * @param string $filename
     * @return bool|string
     */
    function put_image($url, string $filename = '')
    {

        if ($url == '') {
            return false;
        }
        try {
            if ($filename == '') {

                $ext = pathinfo($url);
                if ($ext['extension'] != "jpg" && $ext['extension'] != "png" && $ext['extension'] != "jpeg") {
                    return false;
                }
                $filename = time() . "." . $ext['extension'];
            }
            $pathArr = parse_url($url);
            $path = $pathArr['path'] ?? '';
            if ($path && file_exists(public_path() . trim($path, '/'))) {
                return $path;
            } else {
                //文件保存路径
                ob_start();
                $url = str_replace('phar://', '', $url);
                readfile($url);
                $img = ob_get_contents();
                ob_end_clean();
                $path = 'uploads/qrcode';
                $fp2 = fopen(public_path() . $path . '/' . $filename, 'a');
                fwrite($fp2, $img);
                fclose($fp2);
                return $path . '/' . $filename;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('make_path')) {

    /**
     * 上传路径转化,默认路径
     * @param $path
     * @param int $type
     * @param bool $force
     * @return string
     * @throws Exception
     */
    function make_path($path, int $type = 2, bool $force = false)
    {
        $path = DS . ltrim(rtrim($path));
        switch ($type) {
            case 1:
                $path .= DS . date('Y');
                break;
            case 2:
                $path .= DS . date('Y') . DS . date('m');
                break;
            case 3:
                $path .= DS . date('Y') . DS . date('m') . DS . date('d');
                break;
        }
        try {
            if (is_dir(app()->getRootPath() . 'public' . DS . 'uploads' . $path) == true || mkdir(app()->getRootPath() . 'public' . DS . 'uploads' . $path, 0777, true) == true) {
                return trim(str_replace(DS, '/', $path), '.');
            } else return '';
        } catch (\Exception $e) {
            if ($force)
                throw new \Exception($e->getMessage());
            return '无法创建文件夹，请检查您的上传目录权限：' . app()->getRootPath() . 'public' . DS . 'uploads' . DS . 'attach' . DS;
        }

    }
}

if (!function_exists('check_phone')) {
    /**
     * 手机号验证
     * @param $phone
     * @return false|int
     */
    function check_phone($phone)
    {
        return preg_match("/^\d{6,14}$/", $phone);
    }
}

if (!function_exists('check_mail')) {
    /**
     * 邮箱验证
     * @param $mail
     * @return false|int
     */
    function check_mail($mail)
    {
        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('aj_captcha_check_one')) {
    /**
     * 验证滑块1次验证
     * @param string $captchaType
     * @param string $token
     * @param string $pointJson
     * @return bool
     */
    function aj_captcha_check_one(string $captchaType, string $token, string $pointJson)
    {
        aj_get_serevice($captchaType)->check($token, $pointJson);
        return true;
    }
}

if (!function_exists('aj_captcha_check_two')) {
    /**
     * 验证滑块2次验证
     * @param string $captchaType
     * @param string $captchaVerification
     * @return bool
     */
    function aj_captcha_check_two(string $captchaType, string $captchaVerification)
    {
        aj_get_serevice($captchaType)->verificationByEncryptCode($captchaVerification);
        return true;
    }
}


if (!function_exists('aj_captcha_create')) {
    /**
     * 创建验证码
     * @return array
     */
    function aj_captcha_create(string $captchaType)
    {
        return aj_get_serevice($captchaType)->get();
    }
}

if (!function_exists('aj_get_serevice')) {

    /**
     * @param string $captchaType
     * @return ClickWordCaptchaService|BlockPuzzleCaptchaService
     */
    function aj_get_serevice(string $captchaType)
    {
        $config = Config::get('ajcaptcha');
        switch ($captchaType) {
            case "clickWord":
                $service = new ClickWordCaptchaService($config);
                break;
            case "blockPuzzle":
                $service = new BlockPuzzleCaptchaService($config);
                break;
            default:
                throw new ValidateException('captchaType参数不正确！');
        }
        return $service;
    }
}

if (!function_exists('mb_substr_str')) {

    /**
     * 截取制定长度,并使用填充
     * @param string $value
     * @param int $length
     * @param string $str
     * @return string
     */
    function mb_substr_str(string $value, int $length, string $str = '...', int $type = 0)
    {
        if (mb_strlen($value) > $length) {
            $value = mb_substr($value, 0, $length - mb_strlen($str)) . $str;
        }

        //等于1时去掉数组
        if ($type === 1) {
            $value = preg_replace('/[0-9]/', '', $value);
        }

        return $value;
    }
}
if (!function_exists('msectime')) {
    function msectime(){
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
}
if (!function_exists('response_log_write')) {

    /**
     * 日志写入
     * @param mixed $data
     * @param string $logType
     * @param string $type
     * @param mixed $id
     */
    function response_log_write(mixed $data, string $logType = \think\Log::ERROR, string $type = '', mixed $id = 0)
    {
        $request = app()->make(Request::class);

        try {

            if (!$type || !$id) {
                foreach ([
                             'adminId' => 'admin',
                             'roomUid' => 'room',
                             'uid' => 'user',
                             'agentId' => 'agent'
                         ] as $value => $vv) {
                    if ($request->hasMacro($value)) {
                        $id = $request->{$value}();
                        $type = $vv;
                    }
                }
            }

            //日志内容
            $log = [
                $id,//管理员ID
                $type,
                $request->ip(),//客户ip
                ceil(msectime() - ($request->time(true) * 1000)),//耗时（毫秒）
                $request->method(true),//请求类型
                str_replace("/", "", $request->rootUrl()),//应用
                $request->baseUrl(),//路由
                json_encode($request->param(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),//请求参数
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),//报错数据
            ];

            Log::write(implode("|", $log), $logType);
        } catch (\Throwable $e) {

            $data = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'previous' => $e->getPrevious(),
            ];
            Log::error(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        }
    }
}

if (!function_exists('stringToIntArray')) {

    /**
     * 处理ids等并过滤参数
     * @param $string
     * @param string $separator
     * @return array
     */
    function stringToIntArray(string $string, string $separator = ',')
    {
        return !empty($string) ? array_unique(array_diff(array_map('intval', explode($separator, $string)), [0])) : [];
    }
}


if (!function_exists('image_to_base64')) {
    /**
     * 获取图片转为base64
     * @param string $avatar
     * @return bool|string
     */
    function image_to_base64($avatar = '', $timeout = 9)
    {
        try {
            if (file_exists($avatar)) {
                $app_img_file = $avatar; // 图片路径
                $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等
                $fp = fopen($app_img_file, "r"); // 图片是否可读权限
                $img_base64 = '';
                if ($fp) {
                    $filesize = filesize($app_img_file);
                    $content = fread($fp, $filesize);
                    $file_content = chunk_split(base64_encode($content)); // base64编码
                    switch ($img_info[2]) {           //判读图片类型
                        case 1:
                            $img_type = "gif";
                            break;
                        case 2:
                            $img_type = "jpg";
                            break;
                        case 3:
                            $img_type = "png";
                            break;
                    }
                    $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//合成图片的base64编码
                }
                fclose($fp);
                return $img_base64;
            } else {
                $avatar = str_replace('https', 'http', $avatar);
                $url = parse_url($avatar);
                $path = $url['path'] ?? '';
                $urlPath = public_path() . $url['path'];
                //本地文件直接读取返回
                if (is_file($urlPath)) {
                    $imageType = pathinfo($path)['extension'] ?? 'jpeg';
                    return 'data:image/' . $imageType . ';base64,' . base64_encode(file_get_contents($urlPath));
                }
                $url = $url['host'];
                $header = [
                    'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',
                    'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Accept-Encoding: gzip, deflate, br',
                    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                    'Host:' . $url
                ];
                $dir = pathinfo($url);
                $host = $dir['dirname'];
                $refer = $host . '/';
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_REFERER, $refer);
                curl_setopt($curl, CURLOPT_URL, $avatar);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                $data = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                if ($code == 200) {
                    return "data:image/jpeg;base64," . base64_encode($data);
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}


if (!function_exists('getFileHeaders')) {

    /**
     * 获取文件大小头部信息
     * @param string $url
     * @param $isData
     * @return array
     */
    function getFileHeaders(string $url, $isData = true)
    {
        stream_context_set_default(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $header['size'] = 0;
        $header['type'] = 'image/jpeg';
        if (!$isData) {
            return $header;
        }
        try {
            $headerArray = get_headers(str_replace('\\', '/', $url), true);
            if (!isset($headerArray['Content-Length'])) {
                $header['size'] = 0;
            } else {
                if (is_array($headerArray['Content-Length']) && count($headerArray['Content-Length']) == 2) {
                    $header['size'] = $headerArray['Content-Length'][1];
                } else {
                    $header['size'] = $headerArray['Content-Length'] ?? 0;
                }
            }
            if (!isset($headerArray['Content-Type'])) {
                $header['type'] = 'image/jpeg';
            } else {
                if (is_array($headerArray['Content-Type']) && count($headerArray['Content-Type']) == 2) {
                    $header['type'] = $headerArray['Content-Type'][1];
                } else {
                    $header['type'] = $headerArray['Content-Type'] ?? 'image/jpeg';
                }
            }
        } catch (\Exception $e) {
        }
        return $header;
    }
}

if (!function_exists('formatFileSize')) {

    /**
     * 格式化文件大小
     * @param $size
     * @return mixed|string|null
     */
    function formatFileSize($size)
    {
        if (!$size) {
            return '0KB';
        }
        try {
            $toKb = 1024;
            $toMb = $toKb * 1024;
            $toGb = $toMb * 1024;
            if ($size >= $toGb) {
                return round($size / $toGb, 2) . 'GB';
            } elseif ($size >= $toMb) {
                return round($size / $toMb, 2) . 'MB';
            } elseif ($size >= $toKb) {
                return round($size / $toKb, 2) . 'KB';
            } else {
                return $size . 'B';
            }
        } catch (\Exception $e) {
            return '0KB';
        }
    }

}

if (!function_exists('secsToStr')) {

    /**
     * 时间戳转成 n天n时n分
     * @param int $secs
     * @return string
     */
    function secsToStr(int $secs): string
    {
        $r = '';
        if(!$secs) return $r;
        if ($secs >= 86400) {
            $days = floor($secs / 86400);
            $secs = $secs % 86400;
            $r = $days . '天';
        }
        if ($secs >= 3600) {
            $hours = floor($secs / 3600);
            $secs = $secs % 3600;
            $r .= $hours . '小时';
        }
        if ($secs >= 60) {
            $minutes = floor($secs / 60);
            $secs = $secs % 60;
            $r .= $minutes . '分钟';
        }
        if ($secs) {
            $r .= $secs . '秒';
        }
        return $r;
    }
}
if (!function_exists('numberToChinese')) {
    function numberToChinese($number) {
        $chineseNumbers = [
            '0' => '零',
            '1' => '一',
            '2' => '二',
            '3' => '三',
            '4' => '四',
            '5' => '五',
            '6' => '六',
            '7' => '七',
            '8' => '八',
            '9' => '九'
        ];
    
        if ($number < 10) {
            return $chineseNumbers[$number];
        }
    
        $numberStr = strval($number);
        $length = strlen($numberStr);
        $result = '';
    
        if ($length == 2) {
            $tens = $numberStr[0];
            $units = $numberStr[1];
    
            $result .= $chineseNumbers[$tens];
            $result .= '十';
            if ($units != '0') {
                $result .= $chineseNumbers[$units];
            }
            return $result;
        } else {
            return $number;
        }
    }
}
/**
 * 判断远程图片是否存在
 * @return string
 * @throws \Exception
 */
if (!function_exists('checkImageExists')) {
    function checkImageExists($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // 只请求头部
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 处理重定向
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        // 判断状态码和 Content-Type 是否是图片
        return ($httpCode == 200 && strpos($contentType, 'image/') === 0);
    }
}
/**
 * 使用雪花算法生成订单ID
 * @return string
 * @throws \Exception
 */
if (!function_exists('getNewOrderId')) {
    function getNewOrderId(string $prefix = '')
    {
        $snowflake = new \Godruoyi\Snowflake\Snowflake();
        $is_callable = function ($currentTime) {
            $redis = Cache::store('redis');
            $swooleSequenceResolver = new \Godruoyi\Snowflake\RedisSequenceResolver($redis->handler());
            return $swooleSequenceResolver->sequence($currentTime);
        };
        //32位
        if (PHP_INT_SIZE == 4) {
            $id = abs($snowflake->setSequenceResolver($is_callable)->id());
        } else {
            $id = $snowflake->setStartTimeStamp(strtotime('2020-06-05') * 1000)->setSequenceResolver($is_callable)->id();
        }
        return $prefix . $id;
    }
}

if (!function_exists('truthIp')) {
    function truthIp()
    {
        $request = app()->make(Request::class);
        if ($request->header('x-forwarded-for')) {
            $ip = $request->header('x-forwarded-for');
            $ips = explode(',', $ip);
            $ip = $ips[0];
        }elseif ($request->header('client_ip')) {
            $ip = $request->header('client_ip');
        } elseif ($request->header('x-real-ip')) {
            $ip = $request->header('x-real-ip');
        }elseif ($request->header('remote-host')) {
            $ip = $request->header('remote-host');
        } else {
            $ip = '0.0.0.0';
        }
        if(!$request->isValidIP($ip)){
            $ip = '0.0.0.0';
        }
        return $ip;
    }
}
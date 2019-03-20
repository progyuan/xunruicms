<?php namespace Phpcmf\ThirdParty\Storage;

// qiniu存储
class Qiniu {

    // 存储内容
    protected $data;

    // 文件存储路径
    protected $filename;

    // 文件存储目录
    protected $filepath;

    // 附件存储的信息
    protected $attachment;

    // 是否进行图片水印
    protected $watermark;

    private $accessKey;
    private $secretKey;

    // 初始化参数
    public function init($attachment, $filename) {

        $this->filename = trim($filename, DIRECTORY_SEPARATOR);
        $this->filepath = dirname($filename);
        $this->filepath == '.' && $this->filepath = '';
        $this->attachment = $attachment;


        $this->accessKey = $this->attachment['value']['AK'];
        $this->secretKey = $this->attachment['value']['SK'];

        return $this;
    }

    // 文件上传模式
    public function upload($type = 0, $data, $watermark) {

        $this->data = $data;
        $this->watermark = $watermark;

        // 本地临时文件
        $filePath = WRITEPATH.'attach/'.SYS_TIME.'-'.str_replace([DIRECTORY_SEPARATOR, '/'], '-', $this->filename);
        if ($type) {
            // 移动失败
            if (!(move_uploaded_file($this->data, $filePath) || !is_file($srcPath))) {
                return dr_return_data(0, dr_lang('文件移动失败'));
            }
        } else {
            $file_size = file_put_contents($filePath, $this->data);
            if (!$file_size || !is_file($filePath)) {
                return dr_return_data(0, dr_lang('文件创建失败'));
            }
        }
        // 强制水印
        if ($this->watermark) {
            $config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark');
            $config['source_image'] = $filePath;
            $config['dynamic_output'] = false;
            \Phpcmf\Service::L('Image')->watermark($config);
        }


        // 要上传的空间
        $bucket = $this->attachment['value']['bucket'];

        // 生成上传 Token
        $token = $this->uploadToken($bucket);


        // 上传到七牛后保存的文件名
        $key = $this->filename;

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            return dr_return_data(0, $err);
        } else {
            // 上传成功
            $md5 = md5_file($srcPath);
            @unlink($srcPath);
            return dr_return_data(1, 'ok', [
                'url' => $this->attachment['url'].$this->filename,
                'md5' => $md5,
            ]);
        }

    }


    // 删除文件
    public function delete() {


        $bucketMgr = new BucketManager($this);

        //删除$bucket 中的文件 $key
        $err = $bucketMgr->delete($this->attachment['value']['bucket'], $this->filename);
        if ($err !== null) {
            log_message('error', '七牛存储删除失败：'.$err. $this->attachment['url'].$this->filename);
        }

    }


    public function getAccessKey()
    {
        return $this->accessKey;
    }

    public function sign($data)
    {
        $hmac = hash_hmac('sha1', $data, $this->secretKey, true);
        return $this->accessKey . ':' . base64_urlSafeEncode($hmac);
    }

    public function signWithData($data)
    {
        $data = base64_urlSafeEncode($data);
        return $this->sign($data) . ':' . $data;
    }

    public function signRequest($urlString, $body, $contentType = null)
    {
        $url = parse_url($urlString);
        $data = '';
        if (array_key_exists('path', $url)) {
            $data = $url['path'];
        }
        if (array_key_exists('query', $url)) {
            $data .= '?' . $url['query'];
        }
        $data .= "\n";

        if ($body !== null && $contentType === 'application/x-www-form-urlencoded') {
            $data .= $body;
        }
        return $this->sign($data);
    }

    public function verifyCallback($contentType, $originAuthorization, $url, $body)
    {
        $authorization = 'QBox ' . $this->signRequest($url, $body, $contentType);
        return $originAuthorization === $authorization;
    }

    public function privateDownloadUrl($baseUrl, $expires = 3600)
    {
        $deadline = time() + $expires;

        $pos = strpos($baseUrl, '?');
        if ($pos !== false) {
            $baseUrl .= '&e=';
        } else {
            $baseUrl .= '?e=';
        }
        $baseUrl .= $deadline;

        $token = $this->sign($baseUrl);
        return "$baseUrl&token=$token";
    }

    public function uploadToken(
        $bucket,
        $key = null,
        $expires = 3600,
        $policy = null,
        $strictPolicy = true,
        Zone $zone = null
    ) {
        $deadline = time() + $expires;
        $scope = $bucket;
        if ($key !== null) {
            $scope .= ':' . $key;
        }
        $args = array();
        $args = self::copyPolicy($args, $policy, $strictPolicy);
        $args['scope'] = $scope;
        $args['deadline'] = $deadline;

        if ($zone === null) {
            $zone = new Zone();
        }

        list($upHosts, $err) = $zone->getUpHosts($this->accessKey, $bucket);
        if ($err === null) {
            $args['upHosts'] = $upHosts;
        }

        $b = json_encode($args);
        return $this->signWithData($b);
    }

    /**
     *上传策略，参数规格详见
     *http://developer.qiniu.com/docs/v6/api/reference/security/put-policy.html
     */
    private static $policyFields = array(
        'callbackUrl',
        'callbackBody',
        'callbackHost',
        'callbackBodyType',
        'callbackFetchKey',

        'returnUrl',
        'returnBody',

        'endUser',
        'saveKey',
        'insertOnly',

        'detectMime',
        'mimeLimit',
        'fsizeMin',
        'fsizeLimit',

        'persistentOps',
        'persistentNotifyUrl',
        'persistentPipeline',

        'deleteAfterDays',

        'upHosts',
    );

    private static $deprecatedPolicyFields = array(
        'asyncOps',
    );

    private static function copyPolicy(&$policy, $originPolicy, $strictPolicy)
    {
        if ($originPolicy === null) {
            return array();
        }
        foreach ($originPolicy as $key => $value) {
            if (in_array((string) $key, self::$deprecatedPolicyFields, true)) {
                throw new \InvalidArgumentException("{$key} has deprecated");
            }
            if (!$strictPolicy || in_array((string) $key, self::$policyFields, true)) {
                $policy[$key] = $value;
            }
        }
        return $policy;
    }

    public function authorization($url, $body = null, $contentType = null)
    {
        $authorization = 'QBox ' . $this->signRequest($url, $body, $contentType);
        return array('Authorization' => $authorization);
    }


}


final class Zone
{
    public $ioHost;            // 七牛源站Host
    public $upHost;
    public $upHostBackup;

    //array(
    //    <scheme>:<ak>:<bucket> ==>
    //        array('deadline' => 'xxx', 'upHosts' => array(), 'ioHost' => 'xxx.com')
    //)
    public $hostCache;
    public $scheme = 'http';

    public function __construct($scheme = null)
    {
        $this->hostCache = array();
        if ($scheme != null) {
            $this->scheme = $scheme;
        }
    }

    public function getUpHostByToken($uptoken)
    {
        list($ak, $bucket) = $this->unmarshalUpToken($uptoken);
        list($upHosts,) = $this->getUpHosts($ak, $bucket);
        return $upHosts[0];
    }

    public function getBackupUpHostByToken($uptoken)
    {
        list($ak, $bucket) = $this->unmarshalUpToken($uptoken);
        list($upHosts,) = $this->getUpHosts($ak, $bucket);

        $upHost = isset($upHosts[1]) ? $upHosts[1] : $upHosts[0];
        return $upHost;
    }

    public function getIoHost($ak, $bucket)
    {
        list($bucketHosts,) = $this->getBucketHosts($ak, $bucket);
        $ioHosts = $bucketHosts['ioHost'];
        return $ioHosts[0];
    }

    public function getUpHosts($ak, $bucket)
    {
        list($bucketHosts, $err) = $this->getBucketHosts($ak, $bucket);
        if ($err !== null) {
            return array(null, $err);
        }

        $upHosts = $bucketHosts['upHosts'];
        return array($upHosts, null);
    }

    private function unmarshalUpToken($uptoken)
    {
        $token = explode(':', $uptoken);
        if (count($token) !== 3) {
            throw new \Exception("Invalid Uptoken", 1);
        }

        $ak = $token[0];
        $policy = base64_urlSafeDecode($token[2]);
        $policy = json_decode($policy, true);

        $scope = $policy['scope'];
        $bucket = $scope;

        if (strpos($scope, ':')) {
            $scopes = explode(':', $scope);
            $bucket = $scopes[0];
        }

        return array($ak, $bucket);
    }

    public function getBucketHosts($ak, $bucket)
    {
        $key = $this->scheme . ":$ak:$bucket";

        $bucketHosts = $this->getBucketHostsFromCache($key);
        if (count($bucketHosts) > 0) {
            return array($bucketHosts, null);
        }

        list($hosts, $err) = $this->bucketHosts($ak, $bucket);
        if ($err !== null) {
            return array(null , $err);
        }

        $schemeHosts = $hosts[$this->scheme];
        $bucketHosts = array(
            'upHosts' => $schemeHosts['up'],
            'ioHost' => $schemeHosts['io'],
            'deadline' => time() + $hosts['ttl']
        );

        $this->setBucketHostsToCache($key, $bucketHosts);
        return array($bucketHosts, null);
    }

    private function getBucketHostsFromCache($key)
    {
        $ret = array();
        if (count($this->hostCache) === 0) {
            $this->hostCacheFromFile();
        }

        if (!array_key_exists($key, $this->hostCache)) {
            return $ret;
        }

        if ($this->hostCache[$key]['deadline'] > time()) {
            $ret = $this->hostCache[$key];
        }

        return $ret;
    }

    private function setBucketHostsToCache($key, $val)
    {
        $this->hostCache[$key] = $val;
        $this->hostCacheToFile();
        return;
    }

    private function hostCacheFromFile()
    {

        $path = $this->hostCacheFilePath();
        if (!file_exists($path)) {
            return;
        }

        $bucketHosts = file_get_contents($path);
        $this->hostCache = json_decode($bucketHosts, true);
        return;
    }

    private function hostCacheToFile()
    {
        $path = $this->hostCacheFilePath();
        file_put_contents($path, json_encode($this->hostCache), LOCK_EX);
        return;
    }

    private function hostCacheFilePath()
    {
        return sys_get_temp_dir() . '/.qiniu_phpsdk_hostscache.json';
    }

    /*  请求包：
     *   GET /v1/query?ak=<ak>&&bucket=<bucket>
     *  返回包：
     *
     *  200 OK {
     *    "ttl": <ttl>,              // 有效时间
     *    "http": {
     *      "up": [],
     *      "io": [],                // 当bucket为global时，我们不需要iohost, io缺省
     *    },
     *    "https": {
     *      "up": [],
     *      "io": [],                // 当bucket为global时，我们不需要iohost, io缺省
     *    }
     *  }
     **/
    private function bucketHosts($ak, $bucket)
    {
        $url = Config::UC_HOST . '/v1/query' . "?ak=$ak&bucket=$bucket";
        $ret = Client::Get($url);
        if (!$ret->ok()) {
            return array(null, $ret->statusCode .' - '. $ret->error);
        }
        $r = ($ret->body === null) ? array() : $ret->json();
        return array($r, null);
    }
}


final class Config
{
    const SDK_VER = '7.1.1';

    const BLOCK_SIZE = 4194304; //4*1024*1024 分块上传块大小，该参数为接口规格，不能修改

    const RS_HOST  = 'http://rs.qbox.me';               // 文件元信息管理操作Host
    const RSF_HOST = 'http://rsf.qbox.me';              // 列举操作Host
    const API_HOST = 'http://api.qiniu.com';            // 数据处理操作Host
    const UC_HOST  = 'http://uc.qbox.me';              // Host

    public $zone;

    public function __construct(Zone $z = null)         // 构造函数，默认为zone0
    {
        // if ($z === null) {
        $this->zone = new Zone();
        // }
    }
}


final class Client
{
    public static function get($url, array $headers = array())
    {
        $request = new Request('GET', $url, $headers);
        return self::sendRequest($request);
    }

    public static function post($url, $body, array $headers = array())
    {
        $request = new Request('POST', $url, $headers, $body);
        return self::sendRequest($request);
    }

    public static function multipartPost(
        $url,
        $fields,
        $name,
        $fileName,
        $fileBody,
        $mimeType = null,
        array $headers = array()
    ) {
        $data = array();
        $mimeBoundary = md5(microtime());

        foreach ($fields as $key => $val) {
            array_push($data, '--' . $mimeBoundary);
            array_push($data, "Content-Disposition: form-data; name=\"$key\"");
            array_push($data, '');
            array_push($data, $val);
        }

        array_push($data, '--' . $mimeBoundary);
        $mimeType = empty($mimeType) ? 'application/octet-stream' : $mimeType;
        $fileName = self::escapeQuotes($fileName);
        array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
        array_push($data, "Content-Type: $mimeType");
        array_push($data, '');
        array_push($data, $fileBody);

        array_push($data, '--' . $mimeBoundary . '--');
        array_push($data, '');

        $body = implode("\r\n", $data);
        $contentType = 'multipart/form-data; boundary=' . $mimeBoundary;
        $headers['Content-Type'] = $contentType;
        $request = new Request('POST', $url, $headers, $body);
        return self::sendRequest($request);
    }

    private static function userAgent()
    {
        $sdkInfo = "QiniuPHP/" . Config::SDK_VER;

        $systemInfo = php_uname("s");
        $machineInfo = php_uname("m");

        $envInfo = "($systemInfo/$machineInfo)";

        $phpVer = phpversion();

        $ua = "$sdkInfo $envInfo PHP/$phpVer";
        return $ua;
    }

    public static function sendRequest($request)
    {
        $t1 = microtime(true);
        $ch = curl_init();
        $options = array(
            CURLOPT_USERAGENT => self::userAgent(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_CUSTOMREQUEST  => $request->method,
            CURLOPT_URL => $request->url
        );

        // Handle open_basedir & safe mode
        if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
            $options[CURLOPT_FOLLOWLOCATION] = true;
        }

        if (!empty($request->headers)) {
            $headers = array();
            foreach ($request->headers as $key => $val) {
                array_push($headers, "$key: $val");
            }
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

        if (!empty($request->body)) {
            $options[CURLOPT_POSTFIELDS] = $request->body;
        }
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $t2 = microtime(true);
        $duration = round($t2-$t1, 3);
        $ret = curl_errno($ch);
        if ($ret !== 0) {
            $r = new Response(-1, $duration, array(), null, curl_error($ch));
            curl_close($ch);
            return $r;
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = self::parseHeaders(substr($result, 0, $header_size));
        $body = substr($result, $header_size);
        curl_close($ch);
        return new Response($code, $duration, $headers, $body, null);
    }

    private static function parseHeaders($raw)
    {
        $headers = array();
        $headerLines = explode("\r\n", $raw);
        foreach ($headerLines as $line) {
            $headerLine = trim($line);
            $kv = explode(':', $headerLine);
            if (count($kv) >1) {
                $headers[$kv[0]] = trim($kv[1]);
            }
        }
        return $headers;
    }

    private static function escapeQuotes($str)
    {
        $find = array("\\", "\"");
        $replace = array("\\\\", "\\\"");
        return str_replace($find, $replace, $str);
    }
}



final class Request
{
    public $url;
    public $headers;
    public $body;
    public $method;

    public function __construct($method, $url, array $headers = array(), $body = null)
    {
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
    }
}


/**
 * HTTP response Object
 */
final class Response
{
    public $statusCode;
    public $headers;
    public $body;
    public $error;
    private $jsonData;
    public $duration;

    /** @var array Mapping of status codes to reason phrases */
    private static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Reserved for WebDAV advanced collections expired proposal',
        426 => 'Upgrade required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    );

    /**
     * @param int $code 状态码
     * @param double $duration 请求时长
     * @param array $headers 响应头部
     * @param string $body 响应内容
     * @param string $error 错误描述
     */
    public function __construct($code, $duration, array $headers = array(), $body = null, $error = null)
    {
        $this->statusCode = $code;
        $this->duration = $duration;
        $this->headers = $headers;
        $this->body = $body;
        $this->error = $error;
        $this->jsonData = null;
        if ($error !== null) {
            return;
        }

        if ($body === null) {
            if ($code >= 400) {
                $this->error = self::$statusTexts[$code];
            }
            return;
        }
        if (self::isJson($headers)) {
            try {
                $jsonData = self::bodyJson($body);
                if ($code >=400) {
                    $this->error = $body;
                    if ($jsonData['error'] !== null) {
                        $this->error = $jsonData['error'];
                    }
                }
                $this->jsonData = $jsonData;
            } catch (\InvalidArgumentException $e) {
                $this->error = $body;
                if ($code >= 200 && $code < 300) {
                    $this->error = $e->getMessage();
                }
            }
        } elseif ($code >=400) {
            $this->error = $body;
        }
        return;
    }

    public function json()
    {
        return $this->jsonData;
    }

    private static function bodyJson($body)
    {
        return json_decode((string) $body, true, 512);
    }

    public function xVia()
    {
        $via = $this->headers['X-Via'];
        if ($via === null) {
            $via = $this->headers['X-Px'];
        }
        if ($via === null) {
            $via = $this->headers['Fw-Via'];
        }
        return $via;
    }

    public function xLog()
    {
        return $this->headers['X-Log'];
    }

    public function xReqId()
    {
        return $this->headers['X-Reqid'];
    }

    public function ok()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300 && $this->error === null;
    }

    public function needRetry()
    {
        $code = $this->statusCode;
        if ($code< 0 || ($code / 100 === 5 and $code !== 579) || $code === 996) {
            return true;
        }
    }

    private static function isJson($headers)
    {
        return array_key_exists('Content-Type', $headers) &&
        strpos($headers['Content-Type'], 'application/json') === 0;
    }
}


/**
 * 计算文件的crc32检验码:
 *
 * @param $file string  待计算校验码的文件路径
 *
 * @return string 文件内容的crc32校验码
 */
function crc32_file($file)
{
    $hash = hash_file('crc32b', $file);
    $array = unpack('N', pack('H*', $hash));
    return sprintf('%u', $array[1]);
}

/**
 * 计算输入流的crc32检验码
 *
 * @param $data 待计算校验码的字符串
 *
 * @return string 输入字符串的crc32校验码
 */
function crc32_data($data)
{
    $hash = hash('crc32b', $data);
    $array = unpack('N', pack('H*', $hash));
    return sprintf('%u', $array[1]);
}

/**
 * 对提供的数据进行urlsafe的base64编码。
 *
 * @param string $data 待编码的数据，一般为字符串
 *
 * @return string 编码后的字符串
 * @link http://developer.qiniu.com/docs/v6/api/overview/appendix.html#urlsafe-base64
 */
function base64_urlSafeEncode($data)
{
    $find = array('+', '/');
    $replace = array('-', '_');
    return str_replace($find, $replace, base64_encode($data));
}

/**
 * 对提供的urlsafe的base64编码的数据进行解码
 *
 * @param string $str 待解码的数据，一般为字符串
 *
 * @return string 解码后的字符串
 */
function base64_urlSafeDecode($str)
{
    $find = array('-', '_');
    $replace = array('+', '/');
    return base64_decode(str_replace($find, $replace, $str));
}

/**
 * Wrapper for JSON decode that implements error detection with helpful
 * error messages.
 *
 * @param string $json    JSON data to parse
 * @param bool $assoc     When true, returned objects will be converted
 *                        into associative arrays.
 * @param int    $depth   User specified recursion depth.
 *
 * @return mixed
 * @throws \InvalidArgumentException if the JSON cannot be parsed.
 * @link http://www.php.net/manual/en/function.json-decode.php
 */
function json_decode($json, $assoc = false, $depth = 512)
{
    static $jsonErrors = array(
        JSON_ERROR_DEPTH => 'JSON_ERROR_DEPTH - Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'JSON_ERROR_STATE_MISMATCH - Underflow or the modes mismatch',
        JSON_ERROR_CTRL_CHAR => 'JSON_ERROR_CTRL_CHAR - Unexpected control character found',
        JSON_ERROR_SYNTAX => 'JSON_ERROR_SYNTAX - Syntax error, malformed JSON',
        JSON_ERROR_UTF8 => 'JSON_ERROR_UTF8 - Malformed UTF-8 characters, possibly incorrectly encoded'
    );

    if (empty($json)) {
        return null;
    }
    $data = \json_decode($json, $assoc, $depth);

    if (JSON_ERROR_NONE !== json_last_error()) {
        $last = json_last_error();
        return null;
    }

    return $data;
}

/**
 * 计算七牛API中的数据格式
 *
 * @param $bucket 待操作的空间名
 * @param $key 待操作的文件名
 *
 * @return string  符合七牛API规格的数据格式
 * @link http://developer.qiniu.com/docs/v6/api/reference/data-formats.html
 */
function entry($bucket, $key)
{
    $en = $bucket;
    if (!empty($key)) {
        $en = $bucket . ':' . $key;
    }
    return base64_urlSafeEncode($en);
}

/**
 * array 辅助方法，无值时不set
 *
 * @param $array 待操作array
 * @param $key key
 * @param $value value 为null时 不设置
 *
 * @return array 原来的array，便于连续操作
 */
function setWithoutEmpty(&$array, $key, $value)
{
    if (!empty($value)) {
        $array[$key] = $value;
    }
    return $array;
}


/**
 * 主要涉及了资源上传接口的实现
 *
 * @link http://developer.qiniu.com/docs/v6/api/reference/up/
 */
final class UploadManager
{
    private $config;

    public function __construct(Config $config = null)
    {
        if ($config === null) {
            $config = new Config();
        }
        $this->config = $config;
    }

    /**
     * 上传二进制流到七牛
     *
     * @param $upToken    上传凭证
     * @param $key        上传文件名
     * @param $data       上传二进制流
     * @param $params     自定义变量，规格参考
     *                    http://developer.qiniu.com/docs/v6/api/overview/up/response/vars.html#xvar
     * @param $mime       上传数据的mimeType
     * @param $checkCrc   是否校验crc32
     *
     * @return array    包含已上传文件的信息，类似：
     *                                              [
     *                                                  "hash" => "<Hash string>",
     *                                                  "key" => "<Key string>"
     *                                              ]
     */
    public function put(
        $upToken,
        $key,
        $data,
        $params = null,
        $mime = 'application/octet-stream',
        $checkCrc = false
    ) {
        $params = self::trimParams($params);
        return FormUploader::put(
            $upToken,
            $key,
            $data,
            $this->config,
            $params,
            $mime,
            $checkCrc
        );
    }


    /**
     * 上传文件到七牛
     *
     * @param $upToken    上传凭证
     * @param $key        上传文件名
     * @param $filePath   上传文件的路径
     * @param $params     自定义变量，规格参考
     *                    http://developer.qiniu.com/docs/v6/api/overview/up/response/vars.html#xvar
     * @param $mime       上传数据的mimeType
     * @param $checkCrc   是否校验crc32
     *
     * @return array    包含已上传文件的信息，类似：
     *                                              [
     *                                                  "hash" => "<Hash string>",
     *                                                  "key" => "<Key string>"
     *                                              ]
     */
    public function putFile(
        $upToken,
        $key,
        $filePath,
        $params = null,
        $mime = 'application/octet-stream',
        $checkCrc = false
    ) {
        $file = fopen($filePath, 'rb');
        $params = self::trimParams($params);
        $stat = fstat($file);
        $size = $stat['size'];
        if ($size <= Config::BLOCK_SIZE) {
            $data = fread($file, $size);
            fclose($file);

            return FormUploader::put(
                $upToken,
                $key,
                $data,
                $this->config,
                $params,
                $mime,
                $checkCrc
            );
        }

        $up = new ResumeUploader(
            $upToken,
            $key,
            $file,
            $size,
            $params,
            $mime,
            $this->config
        );
        $ret = $up->upload();
        fclose($file);
        return $ret;
    }

    public static function trimParams($params)
    {
        if ($params === null) {
            return null;
        }
        $ret = array();
        foreach ($params as $k => $v) {
            $pos = strpos($k, 'x:');
            if ($pos === 0 && !empty($v)) {
                $ret[$k] = $v;
            }
        }
        return $ret;
    }
}


final class FormUploader
{

    /**
     * 上传二进制流到七牛, 内部使用
     *
     * @param $upToken    上传凭证
     * @param $key        上传文件名
     * @param $data       上传二进制流
     * @param $params     自定义变量，规格参考
     *                    http://developer.qiniu.com/docs/v6/api/overview/up/response/vars.html#xvar
     * @param $mime       上传数据的mimeType
     * @param $checkCrc   是否校验crc32
     *
     * @return array    包含已上传文件的信息，类似：
     *                                              [
     *                                                  "hash" => "<Hash string>",
     *                                                  "key" => "<Key string>"
     *                                              ]
     */
    public static function put(
        $upToken,
        $key,
        $data,
        $config,
        $params,
        $mime,
        $checkCrc
    ) {
        $fields = array('token' => $upToken);
        if ($key === null) {
            $fname = 'filename';
        } else {
            $fname = $key;
            $fields['key'] = $key;
        }
        if ($checkCrc) {
            $fields['crc32'] = crc32_data($data);
        }
        if ($params) {
            foreach ($params as $k => $v) {
                $fields[$k] = $v;
            }
        }

        $upHost = $config->zone->getUpHostByToken($upToken);
        $response = Client::multipartPost($upHost, $fields, 'file', $fname, $data, $mime);
        if (!$response->ok()) {
            return array(null, $response->statusCode .' - '. $response->error);
        }
        return array($response->json(), null);
    }

    /**
     * 上传文件到七牛，内部使用
     *
     * @param $upToken    上传凭证
     * @param $key        上传文件名
     * @param $filePath   上传文件的路径
     * @param $params     自定义变量，规格参考
     *                    http://developer.qiniu.com/docs/v6/api/overview/up/response/vars.html#xvar
     * @param $mime       上传数据的mimeType
     * @param $checkCrc   是否校验crc32
     *
     * @return array    包含已上传文件的信息，类似：
     *                                              [
     *                                                  "hash" => "<Hash string>",
     *                                                  "key" => "<Key string>"
     *                                              ]
     */
    public static function putFile(
        $upToken,
        $key,
        $filePath,
        $config,
        $params,
        $mime,
        $checkCrc
    ) {

        $fields = array('token' => $upToken, 'file' => self::createFile($filePath, $mime));
        if ($key !== null) {
            $fields['key'] = $key;
        }
        if ($checkCrc) {
            $fields['crc32'] = \Qiniu\crc32_file($filePath);
        }
        if ($params) {
            foreach ($params as $k => $v) {
                $fields[$k] = $v;
            }
        }
        $fields['key'] = $key;
        $headers =array('Content-Type' => 'multipart/form-data');

        $upHost = $config->zone->getUpHostByToken($upToken);
        $response = client::post($upHost, $fields, $headers);
        if (!$response->ok()) {
            return array(null, $response->statusCode .' - '. $response->error);
        }
        return array($response->json(), null);
    }

    private static function createFile($filename, $mime)
    {
        // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename, $mime);
        }

        // Use the old style if using an older version of PHP
        $value = "@{$filename}";
        if (!empty($mime)) {
            $value .= ';type=' . $mime;
        }

        return $value;
    }
}

/**
 * 主要涉及了空间资源管理及批量操作接口的实现，具体的接口规格可以参考
 *
 * @link http://developer.qiniu.com/docs/v6/api/reference/rs/
 */
final class BucketManager
{
    private $auth;
    private $zone;

    public function __construct( $auth, Zone $zone = null)
    {
        $this->auth = $auth;
        if ($zone === null) {
            $this->zone = new Zone();
        }
    }

    /**
     * 获取指定账号下所有的空间名。
     *
     * @return string[] 包含所有空间名
     */
    public function buckets()
    {
        return $this->rsGet('/buckets');
    }




    /**
     * 获取资源的元信息，但不返回文件内容
     *
     * @param $bucket     待获取信息资源所在的空间
     * @param $key        待获取资源的文件名
     *
     * @return array    包含文件信息的数组，类似：
     *                                              [
     *                                                  "hash" => "<Hash string>",
     *                                                  "key" => "<Key string>",
     *                                                  "fsize" => "<file size>",
     *                                                  "putTime" => "<file modify time>"
     *                                              ]
     *
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/stat.html
     */
    public function stat($bucket, $key)
    {
        $path = '/stat/' . entry($bucket, $key);
        return $this->rsGet($path);
    }

    /**
     * 删除指定资源
     *
     * @param $bucket     待删除资源所在的空间
     * @param $key        待删除资源的文件名
     *
     * @return mixed      成功返回NULL，失败返回对象Qiniu\Http\Error
     * @link  http://developer.qiniu.com/docs/v6/api/reference/rs/delete.html
     */
    public function delete($bucket, $key)
    {
        $path = '/delete/' . entry($bucket, $key);

        list($a, $error) = $this->rsPost($path);

        return $error;
    }



    private function rsPost($path, $body = null)
    {
        $url = Config::RS_HOST . $path;
        return $this->post($url, $body);
    }

    private function rsGet($path)
    {
        $url = Config::RS_HOST . $path;
        return $this->get($url);
    }

    private function ioPost($path, $body = null)
    {
        $url = Config::IO_HOST . $path;
        return $this->post($url, $body);
    }

    private function get($url)
    {
        $headers = $this->auth->authorization($url);
        $ret = Client::get($url, $headers);
        if (!$ret->ok()) {
            return array(null, new Error($url, $ret));
        }
        return array($ret->json(), null);
    }

    private function post($url, $body)
    {
        $headers = $this->auth->authorization($url, $body, 'application/x-www-form-urlencoded');
        $ret = Client::post($url, $body, $headers);
        if (!$ret->ok()) {
            return array(null, $ret->statusCode .' - '. $ret->error);
        }
        $r = ($ret->body === null) ? array() : $ret->json();
        return array($r, null);
    }

    public static function buildBatchCopy($source_bucket, $key_pairs, $target_bucket)
    {
        return self::twoKeyBatch('copy', $source_bucket, $key_pairs, $target_bucket);
    }


    public static function buildBatchRename($bucket, $key_pairs)
    {
        return self::buildBatchMove($bucket, $key_pairs, $bucket);
    }


    public static function buildBatchMove($source_bucket, $key_pairs, $target_bucket)
    {
        return self::twoKeyBatch('move', $source_bucket, $key_pairs, $target_bucket);
    }


    public static function buildBatchDelete($bucket, $keys)
    {
        return self::oneKeyBatch('delete', $bucket, $keys);
    }


    public static function buildBatchStat($bucket, $keys)
    {
        return self::oneKeyBatch('stat', $bucket, $keys);
    }

    private static function oneKeyBatch($operation, $bucket, $keys)
    {
        $data = array();
        foreach ($keys as $key) {
            array_push($data, $operation . '/' . entry($bucket, $key));
        }
        return $data;
    }

    private static function twoKeyBatch($operation, $source_bucket, $key_pairs, $target_bucket)
    {
        if ($target_bucket === null) {
            $target_bucket = $source_bucket;
        }
        $data = array();
        foreach ($key_pairs as $from_key => $to_key) {
            $from = entry($source_bucket, $from_key);
            $to = entry($target_bucket, $to_key);
            array_push($data, $operation . '/' . $from . '/' . $to);
        }
        return $data;
    }
}


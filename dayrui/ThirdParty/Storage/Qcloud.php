<?php namespace Phpcmf\ThirdParty\Storage;

// qq存储
class Qcloud {

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

    private $accessKeyId;
    private $accessKeySecret;
    private $hostname;
    private $APPID;

    // 初始化参数
    public function init($attachment, $filename) {

        $this->filename = trim($filename, DIRECTORY_SEPARATOR);
        $this->filepath = dirname($filename);
        $this->filepath == '.' && $this->filepath = '';
        $this->attachment = $attachment;

        $this->APPID = $attachment['value']['Id'];
        $this->accessKeyId = $attachment['value']['KeyId'];
        $this->accessKeySecret = $attachment['value']['KeySecret'];
        $this->hostname = $attachment['value']['host'];

        Conf::init($this->APPID , $this->accessKeyId, $this->accessKeySecret);
        Cosapi::setRegion($attachment['value']['Region']);

        return $this;
    }

    // 文件上传模式
    public function upload($type = 0, $data, $watermark) {

        $this->data = $data;
        $this->watermark = $watermark;

        // 本地临时文件
        $srcPath = WRITEPATH.'attach/'.SYS_TIME.'-'.str_replace([DIRECTORY_SEPARATOR, '/'], '-', $this->filename);
        if ($type) {
            // 移动失败
            if (!(move_uploaded_file($this->data, $srcPath) || !is_file($srcPath))) {
                return dr_return_data(0, dr_lang('文件移动失败'));
            }
        } else {
            $file_size = file_put_contents($srcPath, $this->data);
            if (!$file_size || !is_file($srcPath)) {
                return dr_return_data(0, dr_lang('文件创建失败'));
            }
        }
        // 强制水印
        if ($this->watermark) {
            $config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark');
            $config['source_image'] = $srcPath;
            $config['dynamic_output'] = false;
            \Phpcmf\Service::L('Image')->watermark($config);
        }


        $rt = Cosapi::upload( $this->attachment['value']['bucket'], $srcPath, '/'.$this->filename);
        if ($rt['code'] == 0) {
            // 上传成功
            $md5 = md5_file($srcPath);
            @unlink($srcPath);
            return dr_return_data(1, 'ok', [
                'url' => $this->attachment['url'].$this->filename,
                'md5' => $md5,
            ]);
        }

        return dr_return_data(0, $rt['code']. ' - '.$rt['message']);
    }


    // 删除文件
    public function delete() {

        $rt = Cosapi::delFile($this->attachment['value']['bucket'], '/'.$this->filename);
        if ($rt['code'] == 0) {
            return;
        }

        log_message('error', '腾讯云存储删除失败：'.$rt['code']. ' - '.$rt['message']);
    }



}



class Conf {
    // Cos php sdk version number.
    const VERSION = 'v4.2.3';
    const API_COSAPI_END_POINT = 'http://region.file.myqcloud.com/files/v2/';

    // Please refer to http://console.qcloud.com/cos to fetch your app_id, secret_id and secret_key.
    public static $APP_ID = '';
    public static $SECRET_ID = '';
    public static $SECRET_KEY = '';

    public static function init($APP_ID, $SECRET_ID, $SECRET_KEY) {
        self::$APP_ID = $APP_ID;
        self::$SECRET_ID = $SECRET_ID;
        self::$SECRET_KEY = $SECRET_KEY;
    }

    /**
     * Get the User-Agent string to send to COS server.
     */
    public static function getUserAgent() {
        return 'cos-php-sdk-' . self::VERSION;
    }
}


/**
 * Auth class for creating reusable or nonreusable signature.
 */
class Auth {
    // Secret id or secret key is not valid.
    const AUTH_SECRET_ID_KEY_ERROR = -1;

    /**
     * Create reusable signature for listDirectory in $bucket or uploadFile into $bucket.
     * If $filepath is not null, this signature will be binded with this $filepath.
     * This signature will expire at $expiration timestamp.
     * Return the signature on success.
     * Return error code if parameter is not valid.
     */
    public static function createReusableSignature($expiration, $bucket, $filepath = null) {
        $appId = Conf::$APP_ID;
        $secretId = Conf::$SECRET_ID;
        $secretKey = Conf::$SECRET_KEY;

        if (empty($appId) || empty($secretId) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }

        if (empty($filepath)) {
            return self::createSignature($appId, $secretId, $secretKey, $expiration, $bucket, null);
        } else {
            if (preg_match('/^\//', $filepath) == 0) {
                $filepath = '/' . $filepath;
            }

            return self::createSignature($appId, $secretId, $secretKey, $expiration, $bucket, $filepath);
        }
    }

    /**
     * Create nonreusable signature for delete $filepath in $bucket.
     * This signature will expire after single usage.
     * Return the signature on success.
     * Return error code if parameter is not valid.
     */
    public static function createNonreusableSignature($bucket, $filepath) {
        $appId = Conf::$APP_ID;
        $secretId = Conf::$SECRET_ID;
        $secretKey = Conf::$SECRET_KEY;

        if (empty($appId) || empty($secretId) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }

        if (preg_match('/^\//', $filepath) == 0) {
            $filepath = '/' . $filepath;
        }
        $fileId = '/' . $appId . '/' . $bucket . $filepath;

        return self::createSignature($appId, $secretId, $secretKey, 0, $bucket, $fileId);
    }

    /**
     * A helper function for creating signature.
     * Return the signature on success.
     * Return error code if parameter is not valid.
     */
    private static function createSignature(
        $appId, $secretId, $secretKey, $expiration, $bucket, $fileId) {
        if (empty($secretId) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }

        $now = time();
        $random = rand();
        $plainText = "a=$appId&k=$secretId&e=$expiration&t=$now&r=$random&f=$fileId&b=$bucket";
        $bin = hash_hmac('SHA1', $plainText, $secretKey, true);
        $bin = $bin.$plainText;

        $signature = base64_encode($bin);

        return $signature;
    }
}


const COSAPI_SUCCESS         = 0;
const COSAPI_PARAMS_ERROR    = -1;
const COSAPI_NETWORK_ERROR   = -2;
const COSAPI_INTEGRITY_ERROR = -3;

class Cosapi {

    //计算sign签名的时间参数
    const EXPIRED_SECONDS = 180;
    //1M
    const SLICE_SIZE_1M = 1048576;
    //20M 大于20M的文件需要进行分片传输
    const MAX_UNSLICE_FILE_SIZE = 20971520;
    //失败尝试次数
    const MAX_RETRY_TIMES = 3;

    //HTTP请求超时时间
    private static $timeout = 60;
    private static $region = 'gz'; // default region is guangzou

    /**
     * 设置HTTP请求超时时间
     * @param  int  $timeout  超时时长
     */
    public static function setTimeout($timeout = 60) {
        if (!is_int($timeout) || $timeout < 0) {
            return false;
        }

        self::$timeout = $timeout;
        return true;
    }

    public static function setRegion($region) {
        self::$region = $region;
    }

    /**
     * 上传文件,自动判断文件大小,如果小于20M则使用普通文件上传,大于20M则使用分片上传
     * @param  string  $bucket   bucket名称
     * @param  string  $srcPath      本地文件路径
     * @param  string  $dstPath      上传的文件路径
     * @param  string  $bizAttr      文件属性
     * @param  string  $slicesize    分片大小(512k,1m,2m,3m)，默认:1m
     * @param  string  $insertOnly   同名文件是否覆盖
     * @return [type]                [description]
     */
    public static function upload(
        $bucket, $srcPath, $dstPath, $bizAttr=null, $sliceSize=null, $insertOnly=null) {
        if (!file_exists($srcPath)) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'file ' . $srcPath .' not exists',
                'data' => array()
            );
        }

        $dstPath = self::normalizerPath($dstPath, false);
        $insertOnly = 1;

        //文件大于20M则使用分片传输
        if (filesize($srcPath) < self::MAX_UNSLICE_FILE_SIZE ) {
            return self::uploadFile($bucket, $srcPath, $dstPath, $bizAttr, $insertOnly);
        } else {
            $sliceSize = self::getSliceSize($sliceSize);
            return self::uploadBySlicing($bucket, $srcPath, $dstPath, $bizAttr, $sliceSize, $insertOnly);
        }
    }

    /*
     * 创建目录
     * @param  string  $bucket bucket名称
     * @param  string  $folder       目录路径
	 * @param  string  $bizAttr    目录属性
     */
    public static function createFolder($bucket, $folder, $bizAttr = null) {
        if (!self::isValidPath($folder)) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'folder ' . $folder . ' is not a valid folder name',
                'data' => array()
            );
        }

        $folder = self::normalizerPath($folder, True);
        $folder = self::cosUrlEncode($folder);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $folder);
        $signature = Auth::createReusableSignature($expired, $bucket);

        $data = array(
            'op' => 'create',
            'biz_attr' => (isset($bizAttr) ? $bizAttr : ''),
        );

        $data = json_encode($data);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ),
        );

        return self::sendRequest($req);
    }

    /*
     * 目录列表
     * @param  string  $bucket bucket名称
     * @param  string  $path     目录路径，sdk会补齐末尾的 '/'
     * @param  int     $num      拉取的总数
     * @param  string  $pattern  eListBoth,ListDirOnly,eListFileOnly  默认both
     * @param  int     $order    默认正序(=0), 填1为反序,
     * @param  string  $offset   透传字段,用于翻页,前端不需理解,需要往前/往后翻页则透传回来
     */
    public static function listFolder(
        $bucket, $folder, $num = 20,
        $pattern = 'eListBoth', $order = 0,
        $context = null) {
        $folder = self::normalizerPath($folder, True);

        return self::listBase($bucket, $folder, $num, $pattern, $order, $context);
    }

    /*
     * 目录列表(前缀搜索)
     * @param  string  $bucket bucket名称
     * @param  string  $prefix   列出含此前缀的所有文件
     * @param  int     $num      拉取的总数
     * @param  string  $pattern  eListBoth(默认),ListDirOnly,eListFileOnly
     * @param  int     $order    默认正序(=0), 填1为反序,
     * @param  string  $offset   透传字段,用于翻页,前端不需理解,需要往前/往后翻页则透传回来
     */
    public static function prefixSearch(
        $bucket, $prefix, $num = 20,
        $pattern = 'eListBoth', $order = 0,
        $context = null) {
        $path = self::normalizerPath($prefix);

        return self::listBase($bucket, $prefix, $num, $pattern, $order, $context);
    }

    /*
     * 目录更新
     * @param  string  $bucket bucket名称
     * @param  string  $folder      文件夹路径,SDK会补齐末尾的 '/'
     * @param  string  $bizAttr   目录属性
     */
    public static function updateFolder($bucket, $folder, $bizAttr = null) {
        $folder = self::normalizerPath($folder, True);

        return self::updateBase($bucket, $folder, $bizAttr);
    }

    /*
      * 查询目录信息
      * @param  string  $bucket bucket名称
      * @param  string  $folder       目录路径
      */
    public static function statFolder($bucket, $folder) {
        $folder = self::normalizerPath($folder, True);

        return self::statBase($bucket, $folder);
    }

    /*
     * 删除目录
     * @param  string  $bucket bucket名称
     * @param  string  $folder       目录路径
	 *  注意不能删除bucket下根目录/
     */
    public static function delFolder($bucket, $folder) {
        if (empty($bucket) || empty($folder)) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'bucket or path is empty');
        }

        $folder = self::normalizerPath($folder, True);

        return self::delBase($bucket, $folder);
    }

    /*
     * 更新文件
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件路径
     * @param  string  $authority:  eInvalid(继承Bucket的读写权限)/eWRPrivate(私有读写)/eWPrivateRPublic(公有读私有写)
	 * @param  array   $customer_headers_array 携带的用户自定义头域,包括
     * 'Cache-Control' => '*'
     * 'Content-Type' => '*'
     * 'Content-Disposition' => '*'
     * 'Content-Language' => '*'
     * 'x-cos-meta-自定义内容' => '*'
     */
    public static function update($bucket, $path,
                                  $bizAttr = null, $authority=null,$customer_headers_array=null) {
        $path = self::normalizerPath($path);

        return self::updateBase($bucket, $path, $bizAttr, $authority, $customer_headers_array);
    }

    /*
     * 查询文件信息
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件路径
     */
    public static function stat($bucket, $path) {
        $path = self::normalizerPath($path);

        return self::statBase($bucket, $path);
    }

    /*
     * 删除文件
     * @param  string  $bucket
     * @param  string  $path      文件路径
     */
    public static function delFile($bucket, $path) {
        if (empty($bucket) || empty($path)) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'path is empty');
        }

        $path = self::normalizerPath($path);

        return self::delBase($bucket, $path);
    }

    /**
     * 内部方法, 上传文件
     * @param  string  $bucket  bucket名称
     * @param  string  $srcPath     本地文件路径
     * @param  string  $dstPath     上传的文件路径
     * @param  string  $bizAttr     文件属性
     * @param  int     $insertOnly  是否覆盖同名文件:0 覆盖,1:不覆盖
     * @return [type]               [description]
     */
    private static function uploadFile($bucket, $srcPath, $dstPath, $bizAttr = null, $insertOnly = null) {
        $srcPath = realpath($srcPath);
        $dstPath = self::cosUrlEncode($dstPath);

        if (filesize($srcPath) >= self::MAX_UNSLICE_FILE_SIZE ) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'file '.$srcPath.' larger then 20M, please use uploadBySlicing interface',
                'data' => array()
            );
        }

        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $dstPath);
        $signature = Auth::createReusableSignature($expired, $bucket);
        $fileSha = hash_file('sha1', $srcPath);

        $data = array(
            'op' => 'upload',
            'sha' => $fileSha,
            'biz_attr' => (isset($bizAttr) ? $bizAttr : ''),
        );

        $data['filecontent'] = file_get_contents($srcPath);

        if (isset($insertOnly) && strlen($insertOnly) > 0) {
            $data['insertOnly'] = (($insertOnly == 0 || $insertOnly == '0' ) ? 0 : 1);
        }

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
            ),
        );

        return self::sendRequest($req);
    }

    /**
     * 内部方法,上传文件
     * @param  string  $bucket  bucket名称
     * @param  string  $srcPath     本地文件路径
     * @param  string  $dstPath     上传的文件路径
     * @param  string  $bizAttr     文件属性
     * @param  string  $sliceSize   分片大小
     * @param  int     $insertOnly  是否覆盖同名文件:0 覆盖,1:不覆盖
     * @return [type]                [description]
     */
    private static function uploadBySlicing(
        $bucket, $srcFpath,  $dstFpath, $bizAttr=null, $sliceSize=null, $insertOnly=null) {

        $srcFpath = realpath($srcFpath);
        $fileSize = filesize($srcFpath);
        $dstFpath = self::cosUrlEncode($dstFpath);
        $url = self::generateResUrl($bucket, $dstFpath);
        $sliceCount = ceil($fileSize / $sliceSize);
        // expiration seconds for one slice mutiply by slice count
        // will be the expired seconds for whole file
        $expiration = time() + (self::EXPIRED_SECONDS * $sliceCount);
        if ($expiration >= (time() + 10 * 24 * 60 * 60)) {
            $expiration = time() + 10 * 24 * 60 * 60;
        }
        $signature = Auth::createReusableSignature($expiration, $bucket);

        $sliceUploading = new SliceUploading(self::$timeout * 1000, self::MAX_RETRY_TIMES);
        for ($tryCount = 0; $tryCount < self::MAX_RETRY_TIMES; ++$tryCount) {
            if ($sliceUploading->initUploading(
                $signature,
                $srcFpath,
                $url,
                $fileSize, $sliceSize, $bizAttr, $insertOnly)) {
                break;
            }

            $errorCode = $sliceUploading->getLastErrorCode();
            if ($errorCode === -4019) {
                // Delete broken file and retry again on _ERROR_FILE_NOT_FINISH_UPLOAD error.
                Cosapi::delFile($bucket, $dstFpath);
                continue;
            }

            if ($tryCount === self::MAX_RETRY_TIMES - 1) {
                return array(
                    'code' => $sliceUploading->getLastErrorCode(),
                    'message' => $sliceUploading->getLastErrorMessage(),
                    'request_id' => $sliceUploading->getRequestId(),
                );
            }
        }

        if (!$sliceUploading->performUploading()) {
            return array(
                'code' => $sliceUploading->getLastErrorCode(),
                'message' => $sliceUploading->getLastErrorMessage(),
                'request_id' => $sliceUploading->getRequestId(),
            );
        }

        if (!$sliceUploading->finishUploading()) {
            return array(
                'code' => $sliceUploading->getLastErrorCode(),
                'message' => $sliceUploading->getLastErrorMessage(),
                'request_id' => $sliceUploading->getRequestId(),
            );
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'request_id' => $sliceUploading->getRequestId(),
            'data' => array(
                'access_url' => $sliceUploading->getAccessUrl(),
                'resource_path' => $sliceUploading->getResourcePath(),
                'source_url' => $sliceUploading->getSourceUrl(),
            ),
        );
    }

    /*
     * 内部公共函数
     * @param  string  $bucket bucket名称
     * @param  string  $path       文件夹路径
     * @param  int     $num        拉取的总数
     * @param  string  $pattern    eListBoth(默认),ListDirOnly,eListFileOnly
     * @param  int     $order      默认正序(=0), 填1为反序,
     * @param  string  $context    在翻页查询时候用到
     */
    private static function listBase(
        $bucket, $path, $num = 20, $pattern = 'eListBoth', $order = 0, $context = null) {
        $path = self::cosUrlEncode($path);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $path);
        $signature = Auth::createReusableSignature($expired, $bucket);

        $data = array(
            'op' => 'list',
        );

        if (self::isPatternValid($pattern) == false) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'parameter pattern invalid',
            );
        }
        $data['pattern'] = $pattern;

        if ($order != 0 && $order != 1) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'parameter order invalid',
            );
        }
        $data['order'] = $order;

        if ($num < 0 || $num > 199) {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'parameter num invalid, num need less then 200',
            );
        }
        $data['num'] = $num;

        if (isset($context)) {
            $data['context'] = $context;
        }

        $url = $url . '?' . http_build_query($data);

        $req = array(
            'url' => $url,
            'method' => 'get',
            'timeout' => self::$timeout,
            'header' => array(
                'Authorization: ' . $signature,
            ),
        );

        return self::sendRequest($req);
    }

    /*
     * 内部公共方法(更新文件和更新文件夹)
     * @param  string  $bucket  bucket名称
     * @param  string  $path        路径
     * @param  string  $bizAttr     文件/目录属性
     * @param  string  $authority:  eInvalid/eWRPrivate(私有)/eWPrivateRPublic(公有读写)
	 * @param  array   $customer_headers_array 携带的用户自定义头域,包括
     * 'Cache-Control' => '*'
     * 'Content-Type' => '*'
     * 'Content-Disposition' => '*'
     * 'Content-Language' => '*'
     * 'x-cos-meta-自定义内容' => '*'
     */
    private static function updateBase(
        $bucket, $path, $bizAttr = null, $authority = null, $custom_headers_array = null) {
        $path = self::cosUrlEncode($path);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $path);
        $signature = Auth::createNonreusableSignature($bucket, $path);

        $data = array('op' => 'update');

        if (isset($bizAttr)) {
            $data['biz_attr'] = $bizAttr;
        }

        if (isset($authority) && strlen($authority) > 0) {
            if(self::isAuthorityValid($authority) == false) {
                return array(
                    'code' => COSAPI_PARAMS_ERROR,
                    'message' => 'parameter authority invalid');
            }

            $data['authority'] = $authority;
        }

        if (isset($custom_headers_array)) {
            $data['custom_headers'] = array();
            self::add_customer_header($data['custom_headers'], $custom_headers_array);
        }

        $data = json_encode($data);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ),
        );

        return self::sendRequest($req);
    }

    /*
     * 内部方法
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件/目录路径
     */
    private static function statBase($bucket, $path) {
        $path = self::cosUrlEncode($path);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $path);
        $signature = Auth::createReusableSignature($expired, $bucket);

        $data = array('op' => 'stat');

        $url = $url . '?' . http_build_query($data);

        $req = array(
            'url' => $url,
            'method' => 'get',
            'timeout' => self::$timeout,
            'header' => array(
                'Authorization: ' . $signature,
            ),
        );

        return self::sendRequest($req);
    }

    /*
     * 内部私有方法
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件/目录路径路径
     */
    private static function delBase($bucket, $path) {
        if ($path == "/") {
            return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'can not delete bucket using api! go to ' .
                    'http://console.qcloud.com/cos to operate bucket');
        }

        $path = self::cosUrlEncode($path);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $path);
        $signature = Auth::createNonreusableSignature($bucket, $path);

        $data = array('op' => 'delete');

        $data = json_encode($data);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ),
        );

        return self::sendRequest($req);
    }

    /*
     * 内部公共方法, 路径编码
     * @param  string  $path 待编码路径
     */
    private static function cosUrlEncode($path) {
        return str_replace('%2F', '/',  rawurlencode($path));
    }

    /*
     * 内部公共方法, 构造URL
     * @param  string  $bucket
     * @param  string  $dstPath
     */
    private static function generateResUrl($bucket, $dstPath) {
        $endPoint = Conf::API_COSAPI_END_POINT;
        $endPoint = str_replace('region', self::$region, $endPoint);

        return $endPoint . Conf::$APP_ID . '/' . $bucket . $dstPath;
    }

    /*
     * 内部公共方法, 发送消息
     * @param  string  $req
     */
    private static function sendRequest($req) {
        $rsp = HttpClient::sendRequest($req);
        if ($rsp === false) {
            return array(
                'code' => COSAPI_NETWORK_ERROR,
                'message' => 'network error',
            );
        }

        $info = HttpClient::info();
        $ret = json_decode($rsp, true);

        if ($ret === NULL) {
            return array(
                'code' => COSAPI_NETWORK_ERROR,
                'message' => $rsp,
                'data' => array()
            );
        }

        return $ret;
    }

    /**
     * Get slice size.
     */
    private static function getSliceSize($sliceSize) {
        // Fix slice size to 1MB.
        return self::SLICE_SIZE_1M;
    }

    /*
     * 内部方法, 规整文件路径
     * @param  string  $path      文件路径
     * @param  string  $isfolder  是否为文件夹
     */
    private static function normalizerPath($path, $isfolder = False) {
        if (preg_match('/^\//', $path) == 0) {
            $path = '/' . $path;
        }

        if ($isfolder == True) {
            if (preg_match('/\/$/', $path) == 0) {
                $path = $path . '/';
            }
        }

        // Remove unnecessary slashes.
        $path = preg_replace('#/+#', '/', $path);

        return $path;
    }

    /**
     * 判断authority值是否正确
     * @param  string  $authority
     * @return [type]  bool
     */
    private static function isAuthorityValid($authority) {
        if ($authority == 'eInvalid' || $authority == 'eWRPrivate' || $authority == 'eWPrivateRPublic') {
            return true;
        }
        return false;
    }

    /**
     * 判断pattern值是否正确
     * @param  string  $authority
     * @return [type]  bool
     */
    private static function isPatternValid($pattern) {
        if ($pattern == 'eListBoth' || $pattern == 'eListDirOnly' || $pattern == 'eListFileOnly') {
            return true;
        }
        return false;
    }

    /**
     * 判断是否符合自定义属性
     * @param  string  $key
     * @return [type]  bool
     */
    private static function isCustomer_header($key) {
        if ($key == 'Cache-Control' || $key == 'Content-Type' ||
            $key == 'Content-Disposition' || $key == 'Content-Language' ||
            $key == 'Content-Encoding' ||
            substr($key,0,strlen('x-cos-meta-')) == 'x-cos-meta-') {
            return true;
        }
        return false;
    }

    /**
     * 增加自定义属性到data中
     * @param  array  $data
     * @param  array  $customer_headers_array
     * @return [type]  void
     */
    private static function add_customer_header(&$data, &$customer_headers_array) {
        if (count($customer_headers_array) < 1) {
            return;
        }
        foreach($customer_headers_array as $key=>$value) {
            if(self::isCustomer_header($key)) {
                $data[$key] = $value;
            }
        }
    }

    // Check |$path| is a valid file path.
    // Return true on success, otherwise return false.
    private static function isValidPath($path) {
        if (strpos($path, '?') !== false) {
            return false;
        }
        if (strpos($path, '*') !== false) {
            return false;
        }
        if (strpos($path, ':') !== false) {
            return false;
        }
        if (strpos($path, '|') !== false) {
            return false;
        }
        if (strpos($path, '\\') !== false) {
            return false;
        }
        if (strpos($path, '<') !== false) {
            return false;
        }
        if (strpos($path, '>') !== false) {
            return false;
        }
        if (strpos($path, '"') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Copy a file.
     * @param $bucket bucket name.
     * @param $srcFpath source file path.
     * @param $dstFpath destination file path.
     * @param $overwrite if the destination location is occupied, overwrite it or not?
     * @return array|mixed.
     */
    public static function copyFile($bucket, $srcFpath, $dstFpath, $overwrite = false) {
        $url = self::generateResUrl($bucket, $srcFpath);
        $sign = Auth::createNonreusableSignature($bucket, $srcFpath);
        $data = array(
            'op' => 'copy',
            'dest_fileid' => $dstFpath,
            'to_over_write' => $overwrite ? 1 : 0,
        );
        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $sign,
            ),
        );

        return self::sendRequest($req);
    }

    /**
     * Move a file.
     * @param $bucket bucket name.
     * @param $srcFpath source file path.
     * @param $dstFpath destination file path.
     * @param $overwrite if the destination location is occupied, overwrite it or not?
     * @return array|mixed.
     */
    public static function moveFile($bucket, $srcFpath, $dstFpath, $overwrite = false) {
        $url = self::generateResUrl($bucket, $srcFpath);
        $sign = Auth::createNonreusableSignature($bucket, $srcFpath);
        $data = array(
            'op' => 'move',
            'dest_fileid' => $dstFpath,
            'to_over_write' => $overwrite ? 1 : 0,
        );
        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $sign,
            ),
        );

        return self::sendRequest($req);
    }
}


function my_curl_reset($handler) {
    curl_setopt($handler, CURLOPT_URL, '');
    curl_setopt($handler, CURLOPT_HTTPHEADER, array());
    curl_setopt($handler, CURLOPT_POSTFIELDS, array());
    curl_setopt($handler, CURLOPT_TIMEOUT, 0);
    curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, 0);
}

class HttpClient {
    private static $httpInfo = '';
    private static $curlHandler;

    /**
     * send http request
     * @param  array $request http请求信息
     *                   url        : 请求的url地址
     *                   method     : 请求方法，'get', 'post', 'put', 'delete', 'head'
     *                   data       : 请求数据，如有设置，则method为post
     *                   header     : 需要设置的http头部
     *                   host       : 请求头部host
     *                   timeout    : 请求超时时间
     *                   cert       : ca文件路径
     *                   ssl_version: SSL版本号
     * @return string    http请求响应
     */
    public static function sendRequest($request) {
        if (self::$curlHandler) {
            if (function_exists('curl_reset')) {
                curl_reset(self::$curlHandler);
            } else {
                my_curl_reset(self::$curlHandler);
            }
        } else {
            self::$curlHandler = curl_init();
        }

        curl_setopt(self::$curlHandler, CURLOPT_URL, $request['url']);

        $method = 'GET';
        if (isset($request['method']) &&
            in_array(strtolower($request['method']), array('get', 'post', 'put', 'delete', 'head'))) {
            $method = strtoupper($request['method']);
        } else if (isset($request['data'])) {
            $method = 'POST';
        }

        $header = isset($request['header']) ? $request['header'] : array();
        $header[] = 'Method:'.$method;
        $header[] = 'User-Agent:'.Conf::getUserAgent();
        $header[] = 'Connection: keep-alive';

        isset($request['host']) && $header[] = 'Host:' . $request['host'];
        curl_setopt(self::$curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(self::$curlHandler, CURLOPT_CUSTOMREQUEST, $method);
        isset($request['timeout']) && curl_setopt(self::$curlHandler, CURLOPT_TIMEOUT, $request['timeout']);

        if (isset($request['data']) && in_array($method, array('POST', 'PUT'))) {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt(self::$curlHandler, CURLOPT_SAFE_UPLOAD, true);
            }

            curl_setopt(self::$curlHandler, CURLOPT_POST, true);
            array_push($header, 'Expect: 100-continue');

            if (is_array($request['data'])) {
                $arr = buildCustomPostFields($request['data']);
                array_push($header, 'Content-Type: multipart/form-data; boundary=' . $arr[0]);
                curl_setopt(self::$curlHandler, CURLOPT_POSTFIELDS, $arr[1]);
            } else {
                curl_setopt(self::$curlHandler, CURLOPT_POSTFIELDS, $request['data']);
            }
        }
        curl_setopt(self::$curlHandler, CURLOPT_HTTPHEADER, $header);

        $ssl = substr($request['url'], 0, 8) == "https://" ? true : false;
        if( isset($request['cert'])){
            curl_setopt(self::$curlHandler, CURLOPT_SSL_VERIFYPEER,true);
            curl_setopt(self::$curlHandler, CURLOPT_CAINFO, $request['cert']);
            curl_setopt(self::$curlHandler, CURLOPT_SSL_VERIFYHOST,2);
            if (isset($request['ssl_version'])) {
                curl_setopt(self::$curlHandler, CURLOPT_SSLVERSION, $request['ssl_version']);
            } else {
                curl_setopt(self::$curlHandler, CURLOPT_SSLVERSION, 4);
            }
        }else if( $ssl ){
            curl_setopt(self::$curlHandler, CURLOPT_SSL_VERIFYPEER,false);   //true any ca
            curl_setopt(self::$curlHandler, CURLOPT_SSL_VERIFYHOST,1);       //check only host
            if (isset($request['ssl_version'])) {
                curl_setopt(self::$curlHandler, CURLOPT_SSLVERSION, $request['ssl_version']);
            } else {
                curl_setopt(self::$curlHandler, CURLOPT_SSLVERSION, 4);
            }
        }
        $ret = curl_exec(self::$curlHandler);
        self::$httpInfo = curl_getinfo(self::$curlHandler);
        return $ret;
    }

    public static function info() {
        return self::$httpInfo;
    }
}

/**
 * Uploading file to cos slice by slice.
 */
class SliceUploading {
    // default task number for concurrently uploading slices.
    const DEFAULT_CONCURRENT_TASK_NUMBER = 3;

    private $timeoutMs;            // int: timeout in milliseconds for each http request.
    private $maxRetryCount;        // int: max retry count on failure.

    private $errorCode;            // int: last error code.
    private $errorMessage;         // string: last error message.
    private $requestId;            // string: request id for last http request.
    private $signature;            // string: signature for auth.
    private $srcFpath;             // string: source file path for uploading.
    private $url;                  // string: destination url for uploading.
    private $fileSize;             // int: source file size.
    private $sliceSize;            // int: slice size for each upload.
    private $session;              // string: session for each upload transaction.
    private $concurrentTaskNumber; // int: concurrent uploading task number.

    private $offset;               // int: current uploading offset.
    private $libcurlWrapper;       // LibcurlWrapper: curl wrapper for sending multi http request concurrently.

    private $accessUrl;            // string: access url.
    private $resourcePath;         // string: resource path.
    private $sourceUrl;            // string: source url.

    /**
     * timeoutMs: max timeout in milliseconds for each http request.
     * maxRetryCount: max retry count for uploading each slice on error.
     */
    public function __construct($timeoutMs, $maxRetryCount) {
        $this->timeoutMs = $timeoutMs;
        $this->maxRetryCount = $maxRetryCount;
        $this->errorCode = COSAPI_SUCCESS;
        $this->errorMessage = '';
        $this->concurrentTaskNumber = self::DEFAULT_CONCURRENT_TASK_NUMBER;

        $this->offset = 0;

        $this->libcurlWrapper = new LibcurlWrapper();
    }

    public function __destruct() {
    }

    public function getLastErrorCode() {
        return $this->errorCode;
    }

    public function getLastErrorMessage() {
        return $this->errorMessage;
    }

    public function getRequestId() {
        return $this->requestId;
    }

    public function getAccessUrl() {
        return $this->accessUrl;
    }

    public function getResourcePath() {
        return $this->resourcePath;
    }

    public function getSourceUrl() {
        return $this->sourceUrl;
    }

    /**
     * Return true on success and return false on failure.
     */
    public function initUploading(
        $signature, $srcFpath, $url, $fileSize, $sliceSize, $bizAttr, $insertOnly) {
        $this->signature = $signature;
        $this->srcFpath = $srcFpath;
        $this->url = $url;
        $this->fileSize = $fileSize;
        $this->sliceSize = $sliceSize;

        // Clear error so caller can successfully retry.
        $this->clearError();

        $request = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => $this->timeoutMs / 1000,
            'data' => array(
                'op' => 'upload_slice_init',
                'filesize' => $fileSize,
                'slice_size' => $sliceSize,
                'insertOnly' => $insertOnly,
            ),
            'header' => array(
                'Authorization: ' . $signature,
            ),
        );

        if (isset($bizAttr) && strlen($bizAttr)) {
            $request['data']['biz_attr'] = $bizAttr;
        }

        $response = $this->sendRequest($request);
        if ($response === false) {
            return false;
        }
        $this->session = $response['data']['session'];

        if (isset($response['data']['slice_size'])) {
            $this->sliceSize = $response['data']['slice_size'];
        }

        if (isset($response['data']['serial_upload']) && $response['data']['serial_upload'] == 1) {
            $this->concurrentTaskNumber = 1;
        }

        return true;
    }

    /**
     * Return true on success and return false on failure.
     */
    public function performUploading() {
        for ($i = 0; $i < $this->concurrentTaskNumber; ++$i) {
            if ($this->offset >= $this->fileSize) {
                break;
            }

            $sliceContent = file_get_contents($this->srcFpath, false, null, $this->offset, $this->sliceSize);
            if ($sliceContent === false) {
                $this->setError(COSAPI_PARAMS_ERROR, 'read file ' . $this->srcFpath . ' error');
                return false;
            }

            $request = new HttpRequest();
            $request->timeoutMs = $this->timeoutMs;
            $request->url = $this->url;
            $request->method = 'POST';
            $request->customHeaders = array(
                'Authorization: ' . $this->signature,
            );
            $request->dataToPost = array(
                'op' => 'upload_slice_data',
                'session' => $this->session,
                'offset' => $this->offset,
                'filecontent' => $sliceContent,
                'datamd5' => md5($sliceContent),
            );
            $request->userData = array(
                'retryCount' => 0,
            );

            $this->libcurlWrapper->startSendingRequest($request, array($this, 'uploadCallback'));

            $this->offset += $this->sliceSize;
        }

        $this->libcurlWrapper->performSendingRequest();

        if ($this->errorCode !== COSAPI_SUCCESS) {
            return false;
        }

        return true;
    }

    /**
     * Return true on success and return false on failure.
     */
    public function finishUploading() {
        $request = array(
            'url' => $this->url,
            'method' => 'post',
            'timeout' => $this->timeoutMs / 1000,
            'data' => array(
                'op' => 'upload_slice_finish',
                'session' => $this->session,
                'filesize' => $this->fileSize,
            ),
            'header' => array(
                'Authorization: ' . $this->signature,
            ),
        );

        $response = $this->sendRequest($request);
        if ($response === false) {
            return false;
        }

        $this->accessUrl = $response['data']['access_url'];
        $this->resourcePath = $response['data']['resource_path'];
        $this->sourceUrl = $response['data']['source_url'];

        return true;
    }

    private function sendRequest($request) {
        $response = HttpClient::sendRequest($request);
        if ($response === false) {
            $this->setError(COSAPI_NETWORK_ERROR, 'network error');
            return false;
        }

        $responseJson = json_decode($response, true);
        if ($responseJson === NULL) {
            $this->setError(COSAPI_NETWORK_ERROR, 'network error');
            return false;
        }

        $this->requestId = $responseJson['request_id'];
        if ($responseJson['code'] != 0) {
            $this->setError($responseJson['code'], $responseJson['message']);
            return false;
        }

        return $responseJson;
    }

    private function clearError() {
        $this->errorCode = COSAPI_SUCCESS;
        $this->errorMessage = 'success';
    }

    private function setError($errorCode, $errorMessage) {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
    }

    public function uploadCallback($request, $response) {
        if ($this->errorCode !== COSAPI_SUCCESS) {
            return;
        }

        $requestErrorCode = COSAPI_SUCCESS;
        $requestErrorMessage = 'success';
        $retryCount = $request->userData['retryCount'];

        $responseJson = json_decode($response->body, true);
        if ($responseJson === NULL) {
            $requestErrorCode = COSAPI_NETWORK_ERROR;
            $requestErrorMessage = 'network error';
        }

        if ($response->curlErrorCode !== CURLE_OK) {
            $requestErrorCode = COSAPI_NETWORK_ERROR;
            $requestErrorMessage = 'network error: curl errno ' . $response->curlErrorCode;
        }

        $this->requestId = $responseJson['request_id'];
        if ($responseJson['code'] != 0) {
            $requestErrorCode = $responseJson['code'];
            $requestErrorMessage = $responseJson['message'];
        }

        if (isset($responseJson['data']['datamd5']) &&
            $responseJson['data']['datamd5'] !== $request->dataToPost['datamd5']) {
            $requestErrorCode = COSAPI_INTEGRITY_ERROR;
            $requestErrorMessage = 'cosapi integrity error';
        }

        if ($requestErrorCode !== COSAPI_SUCCESS) {
            if ($retryCount >= $this->maxRetryCount) {
                $this->setError($requestErrorCode, $requestErrorMessage);
            } else {
                $request->userData['retryCount'] += 1;
                $this->libcurlWrapper->startSendingRequest($request, array($this, 'uploadCallback'));
            }
            return;
        }

        if ($this->offset >= $this->fileSize) {
            return;
        }

        // Send next slice.
        $nextSliceContent = file_get_contents($this->srcFpath, false, null, $this->offset, $this->sliceSize);
        if ($nextSliceContent === false) {
            $this->setError(COSAPI_PARAMS_ERROR, 'read file ' . $this->srcFpath . ' error');
            return;
        }

        $nextSliceRequest = new HttpRequest();
        $nextSliceRequest->timeoutMs = $this->timeoutMs;
        $nextSliceRequest->url = $this->url;
        $nextSliceRequest->method = 'POST';
        $nextSliceRequest->customHeaders = array(
            'Authorization: ' . $this->signature,
        );
        $nextSliceRequest->dataToPost = array(
            'op' => 'upload_slice_data',
            'session' => $this->session,
            'offset' => $this->offset,
            'filecontent' => $nextSliceContent,
            'datamd5' => md5($nextSliceContent),
        );
        $nextSliceRequest->userData = array(
            'retryCount' => 0,
        );

        $this->libcurlWrapper->startSendingRequest($nextSliceRequest, array($this, 'uploadCallback'));

        $this->offset += $this->sliceSize;
    }
}

/**
 * Build custom post fields for safe multipart POST request for php before 5.5.
 * @param $fields array of key -> value fields to post.
 * @return $boundary and encoded post fields.
 */
function buildCustomPostFields($fields) {
    // invalid characters for "name" and "filename"
    static $disallow = array("\0", "\"", "\r", "\n");

    // initialize body
    $body = array();

    // build normal parameters
    foreach ($fields as $key => $value) {
        $key = str_replace($disallow, "_", $key);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$key}\"",
            '',
            filter_var($value),
        ));
    }

    // generate safe boundary
    do {
        $boundary = "---------------------" . md5(mt_rand() . microtime());
    } while (preg_grep("/{$boundary}/", $body));

    // add boundary for each parameters
    foreach ($body as &$part) {
        $part = "--{$boundary}\r\n{$part}";
    }
    unset($part);

    // add final boundary
    $body[] = "--{$boundary}--";
    $body[] = '';

    return array($boundary, implode("\r\n", $body));
}


class HttpRequest {
    public $timeoutMs;        // int: the maximum number of milliseconds to perform this request.
    public $url;              // string: the url this request will be sent to.
    public $method;           // string: POST or GET.
    public $customHeaders;    // array: custom modified, removed and added headers.
    public $dataToPost;       // array: the data to post.
    public $userData;         // any: user custom data.
}

class HttpResponse {
    public $curlErrorCode;    // int: curl last error code.
    public $curlErrorMessage; // string: curl last error message.
    public $statusCode;       // int: http status code.
    public $headers;          // array: response headers.
    public $body;             // string: response body.
}

// A simple wrapper for libcurl using multi interface to do transfers in parallel.
class LibcurlWrapper {
    private $sequence;        // integer: sequence id for each request.
    private $curlMultiHandle; // curl handle: curl multi handle.
    private $curlHandleInfo;  // array: array of active curl handle.
    private $idleCurlHandle;  // array: idle curl handle which can be reused.

    public function __construct() {
        $this->sequence = 0;
        $this->curlMultiHandle = curl_multi_init();
        $this->idleCurlHandle = array();
    }

    public function __destruct() {
        curl_multi_close($this->curlMultiHandle);
        foreach ($this->idleCurlHandle as $handle) {
            curl_close($handle);
        }
        $this->idleCurlHandle = array();
    }

    public function startSendingRequest($httpRequest, $done) {
        $this->sequence += 1;

        if (count($this->idleCurlHandle) !== 0) {
            $curlHandle = array_pop($this->idleCurlHandle);
        } else {
            $curlHandle = curl_init();
            if ($curlHandle === false) {
                return false;
            }
        }

        curl_setopt($curlHandle, CURLOPT_TIMEOUT_MS, $httpRequest->timeoutMs);
        curl_setopt($curlHandle, CURLOPT_URL, $httpRequest->url);
        curl_setopt($curlHandle, CURLOPT_HEADER, 1);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        $headers = $httpRequest->customHeaders;
        array_push($headers, 'User-Agent:'.Conf::getUserAgent());
        if ($httpRequest->method === 'POST') {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($curlHandle, CURLOPT_SAFE_UPLOAD, true);
            }

            curl_setopt($curlHandle, CURLOPT_POST, true);
            $arr = buildCustomPostFields($httpRequest->dataToPost);
            array_push($headers, 'Expect: 100-continue');
            array_push($headers, 'Content-Type: multipart/form-data; boundary=' . $arr[0]);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $arr[1]);
        }
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);

        curl_multi_add_handle($this->curlMultiHandle, $curlHandle);


        $this->curlHandleInfo[$this->sequence]['handle'] = $curlHandle;
        $this->curlHandleInfo[$this->sequence]['done'] = $done;
        $this->curlHandleInfo[$this->sequence]['request'] = $httpRequest;
    }

    public function performSendingRequest() {
        for (;;) {
            $active = null;

            do {
                $mrc = curl_multi_exec($this->curlMultiHandle, $active);
                $info = curl_multi_info_read($this->curlMultiHandle);
                if ($info !== false) {
                    $this->processResult($info);
                }
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($this->curlMultiHandle) == -1) {
                    usleep(1);
                }

                do {
                    $mrc = curl_multi_exec($this->curlMultiHandle, $active);
                    $info = curl_multi_info_read($this->curlMultiHandle);
                    if ($info !== false) {
                        $this->processResult($info);
                    }
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }

            if (count($this->curlHandleInfo) == 0) {
                break;
            }
        }
    }

    private function processResult($info) {
        $result = $info['result'];
        $handle = $info['handle'];
        $sequence = 0;

        foreach ($this->curlHandleInfo as $key => $info) {
            if ($info['handle'] === $handle) {
                $sequence = $key;
                break;
            }
        }

        $request = $this->curlHandleInfo[$sequence]['request'];
        $done = $this->curlHandleInfo[$sequence]['done'];
        $response = new HttpResponse();

        if ($result !== CURLE_OK) {
            $response->curlErrorCode = $result;
            $response->curlErrorMessage = curl_error($handle);

            call_user_func($done, $request, $response);
        } else {
            $responseStr = curl_multi_getcontent($handle);
            $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
            $headerStr = substr($responseStr, 0, $headerSize);
            $body = substr($responseStr, $headerSize);

            $response->curlErrorCode = curl_errno($handle);
            $response->curlErrorMessage = curl_error($handle);
            $response->statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $headLines = explode("\r\n", $headerStr);
            foreach ($headLines as $head) {
                $arr = explode(':', $head);
                if (count($arr) >= 2) {
                    $response->headers[trim($arr[0])] = trim($arr[1]);
                }
            }
            $response->body = $body;

            call_user_func($done, $request, $response);
        }

        unset($this->curlHandleInfo[$sequence]);
        curl_multi_remove_handle($this->curlMultiHandle, $handle);

        array_push($this->idleCurlHandle, $handle);
    }

    private function resetCurl($handler) {
        if (function_exists('curl_reset')) {
            curl_reset($handler);
        } else {
            curl_setopt($handler, CURLOPT_URL, '');
            curl_setopt($handler, CURLOPT_HTTPHEADER, array());
            curl_setopt($handler, CURLOPT_POSTFIELDS, array());
            curl_setopt($handler, CURLOPT_TIMEOUT, 0);
            curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, 0);
        }
    }
}



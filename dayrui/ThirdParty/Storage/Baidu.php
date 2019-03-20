<?php namespace Phpcmf\ThirdParty\Storage;

// baidu存储
class Baidu {

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

    //百度云存储默认外网域名
    const DEFAULT_URL = 'bcs.duapp.com';
    //SDK 版本
    const API_VERSION = '2012-4-17-1.0.1.6';
    const ACL = 'acl';
    const BUCKET = 'bucket';
    const OBJECT = 'object';
    const HEADERS = 'headers';
    const METHOD = 'method';
    const AK = 'ak';
    const SK = 'sk';
    const QUERY_STRING = "query_string";
    const IMPORT_BCS_LOG_METHOD = "import_bs_log_method";
    const IMPORT_BCS_PRE_FILTER = "import_bs_pre_filter";
    const IMPORT_BCS_POST_FILTER = "import_bs_post_filter";
    /**********************************************************
     ******************* Policy Constants**********************
     **********************************************************/
    const STATEMETS = 'statements';
    //Action 用户动作
    //'*'代表所有action
    const BCS_SDK_ACL_ACTION_ALL = '*';
    //与bucket相关的action
    const BCS_SDK_ACL_ACTION_LIST_OBJECT = 'list_object';
    const BCS_SDK_ACL_ACTION_PUT_BUCKET_POLICY = 'put_bucket_policy';
    const BCS_SDK_ACL_ACTION_GET_BUCKET_POLICY = 'get_bucket_policy';
    const BCS_SDK_ACL_ACTION_DELETE_BUCKET = 'delete_bucket';
    //与object相关的action
    const BCS_SDK_ACL_ACTION_GET_OBJECT = 'get_object';
    const BCS_SDK_ACL_ACTION_PUT_OBJECT = 'put_object';
    const BCS_SDK_ACL_ACTION_DELETE_OBJECT = 'delete_object';
    const BCS_SDK_ACL_ACTION_PUT_OBJECT_POLICY = 'put_object_policy';
    const BCS_SDK_ACL_ACTION_GET_OBJECT_POLICY = 'get_object_policy';
    static $ACL_ACTIONS = array (
        self::BCS_SDK_ACL_ACTION_ALL,
        self::BCS_SDK_ACL_ACTION_LIST_OBJECT,
        self::BCS_SDK_ACL_ACTION_PUT_BUCKET_POLICY,
        self::BCS_SDK_ACL_ACTION_GET_BUCKET_POLICY,
        self::BCS_SDK_ACL_ACTION_DELETE_BUCKET,
        self::BCS_SDK_ACL_ACTION_GET_OBJECT,
        self::BCS_SDK_ACL_ACTION_PUT_OBJECT,
        self::BCS_SDK_ACL_ACTION_DELETE_OBJECT,
        self::BCS_SDK_ACL_ACTION_PUT_OBJECT_POLICY,
        self::BCS_SDK_ACL_ACTION_GET_OBJECT_POLICY );
    //EFFECT:
    const BCS_SDK_ACL_EFFECT_ALLOW = "allow";
    const BCS_SDK_ACL_EFFECT_DENY = "deny";
    static $ACL_EFFECTS = array (
        self::BCS_SDK_ACL_EFFECT_ALLOW,
        self::BCS_SDK_ACL_EFFECT_DENY );
    //ACL_TYPE:
    //公开读权限
    const BCS_SDK_ACL_TYPE_PUBLIC_READ = "public-read";
    //公开写权限（不具备删除权限）
    const BCS_SDK_ACL_TYPE_PUBLIC_WRITE = "public-write";
    //公开读写权限（不具备删除权限）
    const BCS_SDK_ACL_TYPE_PUBLIC_READ_WRITE = "public-read-write";
    //公开所有权限
    const BCS_SDK_ACL_TYPE_PUBLIC_CONTROL = "public-control";
    //私有权限，仅bucket所有者具有所有权限
    const BCS_SDK_ACL_TYPE_PRIVATE = "private";
    //SDK中开放此上五种acl_tpe
    static $ACL_TYPES = array (
        self::BCS_SDK_ACL_TYPE_PUBLIC_READ,
        self::BCS_SDK_ACL_TYPE_PUBLIC_WRITE,
        self::BCS_SDK_ACL_TYPE_PUBLIC_READ_WRITE,
        self::BCS_SDK_ACL_TYPE_PUBLIC_CONTROL,
        self::BCS_SDK_ACL_TYPE_PRIVATE );
    /*%******************************************************************************************%*/
    // PROPERTIES
    //是否使用ssl
    protected $use_ssl = false;
    //公钥 account key
    private $ak;
    //私钥 secret key
    private $sk;
    //云存储server地址
    private $hostname;



    public  function get_mimetype($ext) {
        $ext = strtolower ( $ext );
        $mime_types = array (
            '3gp' => 'video/3gpp', 'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff', 'asc' => 'text/plain',
            'atom' => 'application/atom+xml', 'au' => 'audio/basic',
            'avi' => 'video/x-msvideo', 'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream', 'bmp' => 'image/bmp',
            'cdf' => 'application/x-netcdf', 'cgm' => 'image/cgm',
            'class' => 'application/octet-stream',
            'cpio' => 'application/x-cpio',
            'cpt' => 'application/mac-compactpro',
            'csh' => 'application/x-csh', 'css' => 'text/css',
            'dcr' => 'application/x-director', 'dif' => 'video/x-dv',
            'dir' => 'application/x-director', 'djv' => 'image/vnd.djvu',
            'djvu' => 'image/vnd.djvu',
            'dll' => 'application/octet-stream',
            'dmg' => 'application/octet-stream',
            'dms' => 'application/octet-stream',
            'doc' => 'application/msword', 'dtd' => 'application/xml-dtd',
            'dv' => 'video/x-dv', 'dvi' => 'application/x-dvi',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript', 'etx' => 'text/x-setext',
            'exe' => 'application/octet-stream',
            'ez' => 'application/andrew-inset', 'flv' => 'video/x-flv',
            'gif' => 'image/gif', 'gram' => 'application/srgs',
            'grxml' => 'application/srgs+xml',
            'gtar' => 'application/x-gtar', 'gz' => 'application/x-gzip',
            'hdf' => 'application/x-hdf',
            'hqx' => 'application/mac-binhex40', 'htm' => 'text/html',
            'html' => 'text/html', 'ice' => 'x-conference/x-cooltalk',
            'ico' => 'image/x-icon', 'ics' => 'text/calendar',
            'ief' => 'image/ief', 'ifb' => 'text/calendar',
            'iges' => 'model/iges', 'igs' => 'model/iges',
            'jnlp' => 'application/x-java-jnlp-file', 'jp2' => 'image/jp2',
            'jpe' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg', 'js' => 'application/x-javascript',
            'kar' => 'audio/midi', 'latex' => 'application/x-latex',
            'lha' => 'application/octet-stream',
            'lzh' => 'application/octet-stream',
            'm3u' => 'audio/x-mpegurl', 'm4a' => 'audio/mp4a-latm',
            'm4p' => 'audio/mp4a-latm', 'm4u' => 'video/vnd.mpegurl',
            'm4v' => 'video/x-m4v', 'mac' => 'image/x-macpaint',
            'man' => 'application/x-troff-man',
            'mathml' => 'application/mathml+xml',
            'me' => 'application/x-troff-me', 'mesh' => 'model/mesh',
            'mid' => 'audio/midi', 'midi' => 'audio/midi',
            'mif' => 'application/vnd.mif', 'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie', 'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4',
            'mpe' => 'video/mpeg', 'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg', 'mpga' => 'audio/mpeg',
            'ms' => 'application/x-troff-ms', 'msh' => 'model/mesh',
            'mxu' => 'video/vnd.mpegurl', 'nc' => 'application/x-netcdf',
            'oda' => 'application/oda', 'ogg' => 'application/ogg',
            'ogv' => 'video/ogv', 'pbm' => 'image/x-portable-bitmap',
            'pct' => 'image/pict', 'pdb' => 'chemical/x-pdb',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn', 'pic' => 'image/pict',
            'pict' => 'image/pict', 'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'pnt' => 'image/x-macpaint', 'pntg' => 'image/x-macpaint',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'ps' => 'application/postscript', 'qt' => 'video/quicktime',
            'qti' => 'image/x-quicktime', 'qtif' => 'image/x-quicktime',
            'ra' => 'audio/x-pn-realaudio',
            'ram' => 'audio/x-pn-realaudio', 'ras' => 'image/x-cmu-raster',
            'rdf' => 'application/rdf+xml', 'rgb' => 'image/x-rgb',
            'rm' => 'application/vnd.rn-realmedia',
            'roff' => 'application/x-troff', 'rtf' => 'text/rtf',
            'rtx' => 'text/richtext', 'sgm' => 'text/sgml',
            'sgml' => 'text/sgml', 'sh' => 'application/x-sh',
            'shar' => 'application/x-shar', 'silo' => 'model/mesh',
            'sit' => 'application/x-stuffit',
            'skd' => 'application/x-koan', 'skm' => 'application/x-koan',
            'skp' => 'application/x-koan', 'skt' => 'application/x-koan',
            'smi' => 'application/smil', 'smil' => 'application/smil',
            'snd' => 'audio/basic', 'so' => 'application/octet-stream',
            'spl' => 'application/x-futuresplash',
            'src' => 'application/x-wais-source',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc', 'svg' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            't' => 'application/x-troff', 'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl', 'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo', 'tif' => 'image/tiff',
            'tiff' => 'image/tiff', 'tr' => 'application/x-troff',
            'tsv' => 'text/tab-separated-values', 'txt' => 'text/plain',
            'ustar' => 'application/x-ustar',
            'vcd' => 'application/x-cdlink', 'vrml' => 'model/vrml',
            'vxml' => 'application/voicexml+xml', 'wav' => 'audio/x-wav',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbxml' => 'application/vnd.wap.wbxml', 'webm' => 'video/webm',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wmv' => 'video/x-ms-wmv', 'wrl' => 'model/vrml',
            'xbm' => 'image/x-xbitmap', 'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xml' => 'application/xml', 'xpm' => 'image/x-xpixmap',
            'xsl' => 'application/xml', 'xslt' => 'application/xslt+xml',
            'xul' => 'application/vnd.mozilla.xul+xml',
            'xwd' => 'image/x-xwindowdump', 'xyz' => 'chemical/x-xyz',
            'zip' => 'application/zip',
            //add by zhengkan 20110905
            "apk" => "application/vnd.android.package-archive",
            "bin" => "application/octet-stream",
            "cab" => "application/vnd.ms-cab-compressed",
            "gb" => "application/chinese-gb",
            "gba" => "application/octet-stream",
            "gbc" => "application/octet-stream",
            "jad" => "text/vnd.sun.j2me.app-descriptor",
            "jar" => "application/java-archive",
            "nes" => "application/octet-stream",
            "rar" => "application/x-rar-compressed",
            "sis" => "application/vnd.symbian.install",
            "sisx" => "x-epoc/x-sisx-app",
            "smc" => "application/octet-stream",
            "smd" => "application/octet-stream",
            "swf" => "application/x-shockwave-flash",
            "zip" => "application/x-zip-compressed",
            "wap" => "text/vnd.wap.wml wml", "mrp" => "application/mrp",
            //add by zhengkan 20110914
            "wma" => "audio/x-ms-wma",
            "lrc" => "application/lrc" );
        return (isset ( $mime_types [$ext] ) ? $mime_types [$ext] : 'application/octet-stream');
    }

    // 初始化参数
    public function init($attachment, $filename) {

        $this->filename = trim($filename, DIRECTORY_SEPARATOR);
        $this->filepath = dirname($filename);
        $this->filepath == '.' && $this->filepath = '';
        $this->attachment = $attachment;

        $this->ak = $attachment['value']['KeyId'];
        $this->sk = $attachment['value']['KeySecret'];
        $this->hostname = $attachment['value']['host'];

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

        $opt = array();
        $opt['acl'] = "public-write";
        $opt['curlopts'] = array(CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 1800);

        $object = $this->filename;
        $bucket = $this->attachment['value']['bucket'];

        $opt [self::BUCKET] = $bucket;
        $opt [self::OBJECT] = $object;
        $opt ['fileUpload'] = $srcPath;
        $opt [self::METHOD] = 'PUT';
        if (isset ( $opt ['acl'] )) {
            if (in_array ( $opt ['acl'], self::$ACL_TYPES )) {
                $this->set_header_into_opt ( "x-bs-acl", $opt ['acl'], $opt );
            } else {
                return dr_return_data(0, 'Invalid acl string, it should be acl_type');
            }
            unset ( $opt ['acl'] );
        }
        if (isset ( $opt ['filename'] )) {
            $this->set_header_into_opt ( "Content-Disposition", 'attachment; filename=' . $opt ['filename'], $opt );
        }


        $response = $this->authenticate ( $opt );
        if ($response->status == 200) {
            $md5 = md5_file($srcPath);
            @unlink($srcPath);
            // 上传成功
            return dr_return_data(1, 'ok', [
                'url' => $this->attachment['url'].$this->filename,
                'md5' => $md5,
            ]);
        } else {
            return dr_return_data(0, 'error');
        }
    }



    // 删除文件
    public function delete() {

        $opt [self::BUCKET] = $this->attachment['value']['bucket'];
        $opt [self::METHOD] = 'DELETE';
        $opt [self::OBJECT] = $this->filename;

        $response = $this->authenticate ( $opt );
        if ($response->status == 200) {
            return;
        }

        log_message('error', '百度云存储删除失败：'. $this->attachment['url'].$this->filename);
    }


    /**
     * 将url中 '//' 替换为  '/'
     * @param $url
     * @return string
     */
    public static function trimUrl($url) {
        $result = str_replace ( "//", "/", $url );
        while ( $result !== $url ) {
            $url = $result;
            $result = str_replace ( "//", "/", $url );
        }
        return $result;
    }

    /**
     * 生成签名
     * @param array $opt
     * @return boolean|string
     */
    private function format_signature($opt) {
        $flags = "";
        $content = '';
        if (! isset ( $opt [self::AK] ) || ! isset ( $opt [self::SK] )) {
            return false;
        }
        if (isset ( $opt [self::BUCKET] ) && isset ( $opt [self::METHOD] ) && isset ( $opt [self::OBJECT] )) {
            $flags .= 'MBO';
            $content .= "Method=" . $opt [self::METHOD] . "\n"; //method
            $content .= "Bucket=" . $opt [self::BUCKET] . "\n"; //bucket
            $content .= "Object=" . self::trimUrl ( $opt [self::OBJECT] ) . "\n"; //object
        } else {
            return false;
        }
        if (isset ( $opt ['ip'] )) {
            $flags .= 'I';
            $content .= "Ip=" . $opt ['ip'] . "\n";
        }
        if (isset ( $opt ['time'] )) {
            $flags .= 'T';
            $content .= "Time=" . $opt ['time'] . "\n";
        }
        if (isset ( $opt ['size'] )) {
            $flags .= 'S';
            $content .= "Size=" . $opt ['size'] . "\n";
        }
        $content = $flags . "\n" . $content;
        $sign = base64_encode ( hash_hmac ( 'sha1', $content, $opt [self::SK], true ) );
        return 'sign=' . $flags . ':' . $opt [self::AK] . ':' . urlencode ( $sign );
    }

    /**
     * 构造url
     * @param array $opt
     * @return boolean|string
     */
    private function format_url($opt) {
        $sign = $this->format_signature ( $opt );
        if ($sign === false) {
            return false;
        }
        $opt ['sign'] = $sign;
        $url = "";
        $url .= $this->use_ssl ? 'https://' : 'http://';
        $url .= $this->hostname;
        $url .= "/" . rawurlencode ( $opt [self::OBJECT] );
        $url .= '?' . $sign;
        if (isset ( $opt [self::QUERY_STRING] )) {
            foreach ( $opt [self::QUERY_STRING] as $key => $value ) {
                $url .= '&' . $key . '=' . $value;
            }
        }
        return $url;
    }

    /**
     * 将消息发往Baidu BCS.
     * @param array $opt
     * @return BCS_ResponseCore
     */
    private function authenticate($opt) {
        //set common param into opt
        $opt [self::AK] = $this->ak;
        $opt [self::SK] = $this->sk;

        //construct url
        $url = $this->format_url ( $opt );
        if ($url === false) {
            return dr_return_data(0, 'Can not format url, please check your param!' );
        }
        $opt ['url'] = $url;

        //build request
        $request = new BCS_RequestCore ( $opt ['url'] );
        $headers = array (
            'Content-Type' => 'application/x-www-form-urlencoded' );

        $request->set_method ( $opt [self::METHOD] );
        //Write get_object content to fileWriteTo
        if (isset ( $opt ['fileWriteTo'] )) {
            $request->set_write_file ( $opt ['fileWriteTo'] );
        }
        // Merge the HTTP headers
        if (isset ( $opt [self::HEADERS] )) {
            $headers = array_merge ( $headers, $opt [self::HEADERS] );
        }
        // Set content to Http-Body
        if (isset ( $opt ['content'] )) {
            $request->set_body ( $opt ['content'] );
        }
        // Upload file
        if (isset ( $opt ['fileUpload'] )) {
            if (! file_exists ( $opt ['fileUpload'] )) {
                return dr_return_data(0, 'File[' . $opt ['fileUpload'] . '] not found!');
            }
            $request->set_read_file ( $opt ['fileUpload'] );
            // Determine the length to read from the file
            $length = $request->read_stream_size; // The file size by default
            $file_size = $length;
            if (isset ( $opt ["length"] )) {
                if ($opt ["length"] > $file_size) {
                    dr_return_data(0,  "Input opt[length] invalid! It can not bigger than file-size");
                }
                $length = $opt ['length'];
            }
            if (isset ( $opt ['seekTo'] ) && ! isset ( $opt ["length"] )) {
                // Read from seekTo until EOF by default, when set seekTo but not set $opt["length"]
                $length -= ( integer ) $opt ['seekTo'];
            }
            $request->set_read_stream_size ( $length );
            // Attempt to guess the correct mime-type
            if ($headers ['Content-Type'] === 'application/x-www-form-urlencoded') {
                $extension = explode ( '.', $opt ['fileUpload'] );
                $extension = array_pop ( $extension );
                $mime_type = $this->get_mimetype ( $extension );
                $headers ['Content-Type'] = $mime_type;
            }
            $headers ['Content-MD5'] = '';
        }
        // Handle streaming file offsets
        if (isset ( $opt ['seekTo'] )) {
            // Pass the seek position to BCS_RequestCore
            $request->set_seek_position ( ( integer ) $opt ['seekTo'] );
        }
        // Add headers to request and compute the string to sign
        foreach ( $headers as $header_key => $header_value ) {
            // Strip linebreaks from header values as they're illegal and can allow for security issues
            $header_value = str_replace ( array (
                "\r",
                "\n" ), '', $header_value );
            // Add the header if it has a value
            if ($header_value !== '') {
                $request->add_header ( $header_key, $header_value );
            }
        }
        // Set the curl options.
        if (isset ( $opt ['curlopts'] ) && count ( $opt ['curlopts'] )) {
            $request->set_curlopts ( $opt ['curlopts'] );
        }

        $request->send_request ();
        return new BCS_ResponseCore ( $request->get_response_header (), $request->get_response_body (), $request->get_response_code () );
    }

    /**
     * 将常用set http-header的动作抽离出来
     * @param string $header
     * @param string $value
     * @param array $opt
     * @throws BCS_Exception
     * @return void
     */
    private static function set_header_into_opt($header, $value, &$opt) {
        $opt [self::HEADERS] = array ();
        $opt [self::HEADERS] [$header] = $value;
    }
}

/**
 * Handles all HTTP requests using cURL and manages the responses.
 *
 * @version 2011.03.01
 * @copyright 2006-2011 Ryan Parman
 * @copyright 2006-2010 Foleeo Inc.
 * @copyright 2010-2011 Amazon.com, Inc. or its affiliates.
 * @copyright 2008-2011 Contributors
 * @license http://opensource.org/licenses/bsd-license.php Simplified BSD License
 */
class BCS_RequestCore {
    /**
     * The URL being requested.
     */
    public $request_url;
    /**
     * The headers being sent in the request.
     */
    public $request_headers;
    /**
     * The body being sent in the request.
     */
    public $request_body;
    /**
     * The response returned by the request.
     */
    public $response;
    /**
     * The headers returned by the request.
     */
    public $response_headers;
    /**
     * The body returned by the request.
     */
    public $response_body;
    /**
     * The HTTP status code returned by the request.
     */
    public $response_code;
    /**
     * Additional response data.
     */
    public $response_info;
    /**
     * The handle for the cURL object.
     */
    public $curl_handle;
    /**
     * The method by which the request is being made.
     */
    public $method;
    /**
     * Stores the proxy settings to use for the request.
     */
    public $proxy = null;
    /**
     * The username to use for the request.
     */
    public $username = null;
    /**
     * The password to use for the request.
     */
    public $password = null;
    /**
     * Custom CURLOPT settings.
     */
    public $curlopts = null;
    /**
     * The state of debug mode.
     */
    public $debug_mode = false;
    /**
     * The default class to use for HTTP Requests (defaults to <BCS_RequestCore>).
     */
    public $request_class = 'BCS_RequestCore';
    /**
     * The default class to use for HTTP Responses (defaults to <BCS_ResponseCore>).
     */
    public $response_class = 'BCS_ResponseCore';
    /**
     * Default useragent string to use.
     */
    public $useragent = 'BCS_RequestCore/1.4.2';
    /**
     * File to read from while streaming up.
     */
    public $read_file = null;
    /**
     * The resource to read from while streaming up.
     */
    public $read_stream = null;
    /**
     * The size of the stream to read from.
     */
    public $read_stream_size = null;
    /**
     * The length already read from the stream.
     */
    public $read_stream_read = 0;
    /**
     * File to write to while streaming down.
     */
    public $write_file = null;
    /**
     * The resource to write to while streaming down.
     */
    public $write_stream = null;
    /**
     * Stores the intended starting seek position.
     */
    public $seek_position = null;
    /**
     * The user-defined callback function to call when a stream is read from.
     */
    public $registered_streaming_read_callback = null;
    /**
     * The user-defined callback function to call when a stream is written to.
     */
    public $registered_streaming_write_callback = null;
    /*%******************************************************************************************%*/
    // CONSTANTS
    /**
     * GET HTTP Method
     */
    const HTTP_GET = 'GET';
    /**
     * POST HTTP Method
     */
    const HTTP_POST = 'POST';
    /**
     * PUT HTTP Method
     */
    const HTTP_PUT = 'PUT';
    /**
     * DELETE HTTP Method
     */
    const HTTP_DELETE = 'DELETE';
    /**
     * HEAD HTTP Method
     */
    const HTTP_HEAD = 'HEAD';

    /*%******************************************************************************************%*/
    // CONSTRUCTOR/DESTRUCTOR
    /**
     * Constructs a new instance of this class.
     *
     * @param string $url (Optional) The URL to request or service endpoint to query.
     * @param string $proxy (Optional) The faux-url to use for proxy settings. Takes the following format: `proxy://user:pass@hostname:port`
     * @param array $helpers (Optional) An associative array of classnames to use for request, and response functionality. Gets passed in automatically by the calling class.
     * @return $this A reference to the current instance.
     */
    public function __construct($url = null, $proxy = null, $helpers = null) {
        // Set some default values.
        $this->request_url = $url;
        $this->method = self::HTTP_GET;
        $this->request_headers = array ();
        $this->request_body = '';
        // Set a new Request class if one was set.
        if (isset ( $helpers ['request'] ) && ! empty ( $helpers ['request'] )) {
            $this->request_class = $helpers ['request'];
        }
        // Set a new Request class if one was set.
        if (isset ( $helpers ['response'] ) && ! empty ( $helpers ['response'] )) {
            $this->response_class = $helpers ['response'];
        }
        if ($proxy) {
            $this->set_proxy ( $proxy );
        }
        return $this;
    }

    /**
     * Destructs the instance. Closes opened file handles.
     *
     * @return $this A reference to the current instance.
     */
    public function __destruct() {
        if (isset ( $this->read_file ) && isset ( $this->read_stream )) {
            fclose ( $this->read_stream );
        }
        if (isset ( $this->write_file ) && isset ( $this->write_stream )) {
            fclose ( $this->write_stream );
        }
        return $this;
    }

    /*%******************************************************************************************%*/
    // REQUEST METHODS
    /**
     * Sets the credentials to use for authentication.
     *
     * @param string $user (Required) The username to authenticate with.
     * @param string $pass (Required) The password to authenticate with.
     * @return $this A reference to the current instance.
     */
    public function set_credentials($user, $pass) {
        $this->username = $user;
        $this->password = $pass;
        return $this;
    }

    /**
     * Adds a custom HTTP header to the cURL request.
     *
     * @param string $key (Required) The custom HTTP header to set.
     * @param mixed $value (Required) The value to assign to the custom HTTP header.
     * @return $this A reference to the current instance.
     */
    public function add_header($key, $value) {
        $this->request_headers [$key] = $value;
        return $this;
    }

    /**
     * Removes an HTTP header from the cURL request.
     *
     * @param string $key (Required) The custom HTTP header to set.
     * @return $this A reference to the current instance.
     */
    public function remove_header($key) {
        if (isset ( $this->request_headers [$key] )) {
            unset ( $this->request_headers [$key] );
        }
        return $this;
    }

    /**
     * Set the method type for the request.
     *
     * @param string $method (Required) One of the following constants: <HTTP_GET>, <HTTP_POST>, <HTTP_PUT>, <HTTP_HEAD>, <HTTP_DELETE>.
     * @return $this A reference to the current instance.
     */
    public function set_method($method) {
        $this->method = strtoupper ( $method );
        return $this;
    }

    /**
     * Sets a custom useragent string for the class.
     *
     * @param string $ua (Required) The useragent string to use.
     * @return $this A reference to the current instance.
     */
    public function set_useragent($ua) {
        $this->useragent = $ua;
        return $this;
    }

    /**
     * Set the body to send in the request.
     *
     * @param string $body (Required) The textual content to send along in the body of the request.
     * @return $this A reference to the current instance.
     */
    public function set_body($body) {
        $this->request_body = $body;
        return $this;
    }

    /**
     * Set the URL to make the request to.
     *
     * @param string $url (Required) The URL to make the request to.
     * @return $this A reference to the current instance.
     */
    public function set_request_url($url) {
        $this->request_url = $url;
        return $this;
    }

    /**
     * Set additional CURLOPT settings. These will merge with the default settings, and override if
     * there is a duplicate.
     *
     * @param array $curlopts (Optional) A set of key-value pairs that set `CURLOPT` options. These will merge with the existing CURLOPTs, and ones passed here will override the defaults. Keys should be the `CURLOPT_*` constants, not strings.
     * @return $this A reference to the current instance.
     */
    public function set_curlopts($curlopts) {
        $this->curlopts = $curlopts;
        return $this;
    }

    /**
     * Sets the length in bytes to read from the stream while streaming up.
     *
     * @param integer $size (Required) The length in bytes to read from the stream.
     * @return $this A reference to the current instance.
     */
    public function set_read_stream_size($size) {
        $this->read_stream_size = $size;
        return $this;
    }

    /**
     * Sets the resource to read from while streaming up. Reads the stream from its current position until
     * EOF or `$size` bytes have been read. If `$size` is not given it will be determined by <php:fstat()> and
     * <php:ftell()>.
     *
     * @param resource $resource (Required) The readable resource to read from.
     * @param integer $size (Optional) The size of the stream to read.
     * @return $this A reference to the current instance.
     */
    public function set_read_stream($resource, $size = null) {
        if (! isset ( $size ) || $size < 0) {
            $stats = fstat ( $resource );
            if ($stats && $stats ['size'] >= 0) {
                $position = ftell ( $resource );
                if ($position !== false && $position >= 0) {
                    $size = $stats ['size'] - $position;
                }
            }
        }
        $this->read_stream = $resource;
        return $this->set_read_stream_size ( $size );
    }

    /**
     * Sets the file to read from while streaming up.
     *
     * @param string $location (Required) The readable location to read from.
     * @return $this A reference to the current instance.
     */
    public function set_read_file($location) {
        $this->read_file = $location;
        $read_file_handle = fopen ( $location, 'r' );
        return $this->set_read_stream ( $read_file_handle );
    }

    /**
     * Sets the resource to write to while streaming down.
     *
     * @param resource $resource (Required) The writeable resource to write to.
     * @return $this A reference to the current instance.
     */
    public function set_write_stream($resource) {
        $this->write_stream = $resource;
        return $this;
    }

    /**
     * Sets the file to write to while streaming down.
     *
     * @param string $location (Required) The writeable location to write to.
     * @return $this A reference to the current instance.
     */
    public function set_write_file($location) {
        $this->write_file = $location;
        $write_file_handle = fopen ( $location, 'w' );
        return $this->set_write_stream ( $write_file_handle );
    }

    /**
     * Set the proxy to use for making requests.
     *
     * @param string $proxy (Required) The faux-url to use for proxy settings. Takes the following format: `proxy://user:pass@hostname:port`
     * @return $this A reference to the current instance.
     */
    public function set_proxy($proxy) {
        $proxy = parse_url ( $proxy );
        $proxy ['user'] = isset ( $proxy ['user'] ) ? $proxy ['user'] : null;
        $proxy ['pass'] = isset ( $proxy ['pass'] ) ? $proxy ['pass'] : null;
        $proxy ['port'] = isset ( $proxy ['port'] ) ? $proxy ['port'] : null;
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * Set the intended starting seek position.
     *
     * @param integer $position (Required) The byte-position of the stream to begin reading from.
     * @return $this A reference to the current instance.
     */
    public function set_seek_position($position) {
        $this->seek_position = isset ( $position ) ? ( integer ) $position : null;
        return $this;
    }

    /**
     * Register a callback function to execute whenever a data stream is read from using
     * <CFRequest::streaming_read_callback()>.
     *
     * The user-defined callback function should accept three arguments:
     *
     * <ul>
     * <li><code>$curl_handle</code> - <code>resource</code> - Required - The cURL handle resource that represents the in-progress transfer.</li>
     * <li><code>$file_handle</code> - <code>resource</code> - Required - The file handle resource that represents the file on the local file system.</li>
     * <li><code>$length</code> - <code>integer</code> - Required - The length in kilobytes of the data chunk that was transferred.</li>
     * </ul>
     *
     * @param string|array|function $callback (Required) The callback function is called by <php:call_user_func()>, so you can pass the following values: <ul>
     * <li>The name of a global function to execute, passed as a string.</li>
     * <li>A method to execute, passed as <code>array('ClassName', 'MethodName')</code>.</li>
     * <li>An anonymous function (PHP 5.3+).</li></ul>
     * @return $this A reference to the current instance.
     */
    public function register_streaming_read_callback($callback) {
        $this->registered_streaming_read_callback = $callback;
        return $this;
    }

    /**
     * Register a callback function to execute whenever a data stream is written to using
     * <CFRequest::streaming_write_callback()>.
     *
     * The user-defined callback function should accept two arguments:
     *
     * <ul>
     * <li><code>$curl_handle</code> - <code>resource</code> - Required - The cURL handle resource that represents the in-progress transfer.</li>
     * <li><code>$length</code> - <code>integer</code> - Required - The length in kilobytes of the data chunk that was transferred.</li>
     * </ul>
     *
     * @param string|array|function $callback (Required) The callback function is called by <php:call_user_func()>, so you can pass the following values: <ul>
     * <li>The name of a global function to execute, passed as a string.</li>
     * <li>A method to execute, passed as <code>array('ClassName', 'MethodName')</code>.</li>
     * <li>An anonymous function (PHP 5.3+).</li></ul>
     * @return $this A reference to the current instance.
     */
    public function register_streaming_write_callback($callback) {
        $this->registered_streaming_write_callback = $callback;
        return $this;
    }

    /*%******************************************************************************************%*/
    // PREPARE, SEND, AND PROCESS REQUEST
    /**
     * A callback function that is invoked by cURL for streaming up.
     *
     * @param resource $curl_handle (Required) The cURL handle for the request.
     * @param resource $file_handle (Required) The open file handle resource.
     * @param integer $length (Required) The maximum number of bytes to read.
     * @return binary Binary data from a stream.
     */
    public function streaming_read_callback($curl_handle, $file_handle, $length) {
        // Once we've sent as much as we're supposed to send...
        if ($this->read_stream_read >= $this->read_stream_size) {
            // Send EOF
            return '';
        }
        // If we're at the beginning of an upload and need to seek...
        if ($this->read_stream_read == 0 && isset ( $this->seek_position ) && $this->seek_position !== ftell ( $this->read_stream )) {
            if (fseek ( $this->read_stream, $this->seek_position ) !== 0) {
                return '';
            }
        }
        $read = fread ( $this->read_stream, min ( $this->read_stream_size - $this->read_stream_read, $length ) ); // Remaining upload data or cURL's requested chunk size
        $this->read_stream_read += strlen ( $read );
        $out = $read === false ? '' : $read;
        // Execute callback function
        if ($this->registered_streaming_read_callback) {
            call_user_func ( $this->registered_streaming_read_callback, $curl_handle, $file_handle, $out );
        }
        return $out;
    }

    /**
     * A callback function that is invoked by cURL for streaming down.
     *
     * @param resource $curl_handle (Required) The cURL handle for the request.
     * @param binary $data (Required) The data to write.
     * @return integer The number of bytes written.
     */
    public function streaming_write_callback($curl_handle, $data) {
        $length = strlen ( $data );
        $written_total = 0;
        $written_last = 0;
        while ( $written_total < $length ) {
            $written_last = fwrite ( $this->write_stream, substr ( $data, $written_total ) );
            if ($written_last === false) {
                return $written_total;
            }
            $written_total += $written_last;
        }
        // Execute callback function
        if ($this->registered_streaming_write_callback) {
            call_user_func ( $this->registered_streaming_write_callback, $curl_handle, $written_total );
        }
        return $written_total;
    }

    /**
     * Prepares and adds the details of the cURL request. This can be passed along to a <php:curl_multi_exec()>
     * function.
     *
     * @return resource The handle for the cURL object.
     */
    public function prep_request() {
        $curl_handle = curl_init ();
        // Set default options.
        @curl_setopt ( $curl_handle, CURLOPT_URL, $this->request_url );
        @curl_setopt ( $curl_handle, CURLOPT_FILETIME, true );
        @curl_setopt ( $curl_handle, CURLOPT_FRESH_CONNECT, false );
        @curl_setopt ( $curl_handle, CURLOPT_SSL_VERIFYPEER, false );
        @curl_setopt ( $curl_handle, CURLOPT_SSL_VERIFYHOST, true );
        @curl_setopt ( $curl_handle, CURLOPT_CLOSEPOLICY, CURLCLOSEPOLICY_LEAST_RECENTLY_USED );
        @curl_setopt ( $curl_handle, CURLOPT_MAXREDIRS, 5 );
        @curl_setopt ( $curl_handle, CURLOPT_HEADER, true );
        @curl_setopt ( $curl_handle, CURLOPT_RETURNTRANSFER, true );
        @curl_setopt ( $curl_handle, CURLOPT_TIMEOUT, 5184000 );
        @curl_setopt ( $curl_handle, CURLOPT_CONNECTTIMEOUT, 120 );
        @curl_setopt ( $curl_handle, CURLOPT_NOSIGNAL, true );
        @curl_setopt ( $curl_handle, CURLOPT_REFERER, $this->request_url );
        @curl_setopt ( $curl_handle, CURLOPT_USERAGENT, $this->useragent );
        @curl_setopt ( $curl_handle, CURLOPT_READFUNCTION, array (
            $this,
            'streaming_read_callback' ) );
        if ($this->debug_mode) {
            curl_setopt ( $curl_handle, CURLOPT_VERBOSE, true );
        }
        //if (! ini_get ( 'safe_mode' )) {
        //modify by zhengkan
        //curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
        //}
        // Enable a proxy connection if requested.
        if ($this->proxy) {
            @curl_setopt ( $curl_handle, CURLOPT_HTTPPROXYTUNNEL, true );
            $host = $this->proxy ['host'];
            $host .= ($this->proxy ['port']) ? ':' . $this->proxy ['port'] : '';
            curl_setopt ( $curl_handle, CURLOPT_PROXY, $host );
            if (isset ( $this->proxy ['user'] ) && isset ( $this->proxy ['pass'] )) {
                curl_setopt ( $curl_handle, CURLOPT_PROXYUSERPWD, $this->proxy ['user'] . ':' . $this->proxy ['pass'] );
            }
        }
        // Set credentials for HTTP Basic/Digest Authentication.
        if ($this->username && $this->password) {
            @curl_setopt ( $curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
            @curl_setopt ( $curl_handle, CURLOPT_USERPWD, $this->username . ':' . $this->password );
        }
        // Handle the encoding if we can.
        if (extension_loaded ( 'zlib' )) {
            @curl_setopt ( $curl_handle, CURLOPT_ENCODING, '' );
        }
        // Process custom headers
        if (isset ( $this->request_headers ) && count ( $this->request_headers )) {
            $temp_headers = array ();
            foreach ( $this->request_headers as $k => $v ) {
                $temp_headers [] = $k . ': ' . $v;
            }
            @curl_setopt ( $curl_handle, CURLOPT_HTTPHEADER, $temp_headers );
        }
        switch ($this->method) {
            case self::HTTP_PUT :
                @curl_setopt ( $curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT' );
                if (isset ( $this->read_stream )) {
                    if (! isset ( $this->read_stream_size ) || $this->read_stream_size < 0) {
                        return '';
                    }
                    @curl_setopt ( $curl_handle, CURLOPT_INFILESIZE, $this->read_stream_size );
                    @curl_setopt ( $curl_handle, CURLOPT_UPLOAD, true );
                } else {
                    @curl_setopt ( $curl_handle, CURLOPT_POSTFIELDS, $this->request_body );
                }
                break;
            case self::HTTP_POST :
                @curl_setopt ( $curl_handle, CURLOPT_POST, true );
                @curl_setopt ( $curl_handle, CURLOPT_POSTFIELDS, $this->request_body );
                break;
            case self::HTTP_HEAD :
                @curl_setopt ( $curl_handle, CURLOPT_CUSTOMREQUEST, self::HTTP_HEAD );
                @curl_setopt ( $curl_handle, CURLOPT_NOBODY, 1 );
                break;
            default : // Assumed GET
                @curl_setopt ( $curl_handle, CURLOPT_CUSTOMREQUEST, $this->method );
                if (isset ( $this->write_stream )) {
                    @curl_setopt ( $curl_handle, CURLOPT_WRITEFUNCTION, array (
                        $this,
                        'streaming_write_callback' ) );
                    @curl_setopt ( $curl_handle, CURLOPT_HEADER, false );
                } else {
                    @curl_setopt ( $curl_handle, CURLOPT_POSTFIELDS, $this->request_body );
                }
                break;
        }
        // Merge in the CURLOPTs
        if (isset ( $this->curlopts ) && sizeof ( $this->curlopts ) > 0) {
            foreach ( $this->curlopts as $k => $v ) {
                @curl_setopt ( $curl_handle, $k, $v );
            }
        }
        return $curl_handle;
    }

    /**
     * is the environment BAE?
     * @return boolean the result of the answer
     */
    private function isBaeEnv() {
        if (isset ( $_SERVER ['HTTP_HOST'] )) {
            $host = $_SERVER ['HTTP_HOST'];
            $pos = strpos ( $host, '.' );
            if ($pos !== false) {
                $substr = substr ( $host, $pos + 1 );
                if ($substr == 'duapp.com') {
                    return true;
                }
            }
        }
        if (isset ( $_SERVER ["HTTP_BAE_LOGID"] )) {
            return true;
        }

        return false;
    }

    /**
     * Take the post-processed cURL data and break it down into useful header/body/info chunks. Uses the
     * data stored in the `curl_handle` and `response` properties unless replacement data is passed in via
     * parameters.
     *
     * @param resource $curl_handle (Optional) The reference to the already executed cURL request.
     * @param string $response (Optional) The actual response content itself that needs to be parsed.
     * @return BCS_ResponseCore A <BCS_ResponseCore> object containing a parsed HTTP response.
     */
    public function process_response($curl_handle = null, $response = null) {
        // Accept a custom one if it's passed.
        if ($curl_handle && $response) {
            $this->curl_handle = $curl_handle;
            $this->response = $response;
        }
        // As long as this came back as a valid resource...
        if (is_resource ( $this->curl_handle )) {
            // Determine what's what.
            $header_size = curl_getinfo ( $this->curl_handle, CURLINFO_HEADER_SIZE );
            $this->response_headers = substr ( $this->response, 0, $header_size );
            $this->response_body = substr ( $this->response, $header_size );
            $this->response_code = curl_getinfo ( $this->curl_handle, CURLINFO_HTTP_CODE );
            $this->response_info = curl_getinfo ( $this->curl_handle );
            // Parse out the headers
            $this->response_headers = explode ( "\r\n\r\n", trim ( $this->response_headers ) );
            $this->response_headers = array_pop ( $this->response_headers );
            $this->response_headers = explode ( "\r\n", $this->response_headers );
            array_shift ( $this->response_headers );
            // Loop through and split up the headers.
            $header_assoc = array ();
            foreach ( $this->response_headers as $header ) {
                $kv = explode ( ': ', $header );
                //$header_assoc [strtolower ( $kv [0] )] = $kv [1];
                $header_assoc [$kv [0]] = $kv [1];
            }
            // Reset the headers to the appropriate property.
            $this->response_headers = $header_assoc;
            $this->response_headers ['_info'] = $this->response_info;
            $this->response_headers ['_info'] ['method'] = $this->method;
            if ($curl_handle && $response) {
                return new BCS_ResponseCore ( $this->response_headers, $this->response_body, $this->response_code, $this->curl_handle );
            }
        }
        // BCS_ResponseCore
        // Return false
        return false;
    }

    /**
     * Sends the request, calling necessary utility functions to update built-in properties.
     *
     * @param boolean $parse (Optional) Whether to parse the response with BCS_ResponseCore or not.
     * @return string The resulting unparsed data from the request.
     */
    public function send_request($parse = false) {
        if (false === $this->isBaeEnv ()) {
            set_time_limit ( 0 );
        }
        $curl_handle = $this->prep_request ();
        $this->response = curl_exec ( $curl_handle );
        if ($this->response === false ||
            ($this->method === self::HTTP_GET &&
                curl_errno($curl_handle) === CURLE_PARTIAL_FILE)) {
            return '';
        }
        $parsed_response = $this->process_response ( $curl_handle, $this->response );
        curl_close ( $curl_handle );
        if ($parse) {
            return $parsed_response;
        }
        return $this->response;
    }

    /**
     * Sends the request using <php:curl_multi_exec()>, enabling parallel requests. Uses the "rolling" method.
     *
     * @param array $handles (Required) An indexed array of cURL handles to process simultaneously.
     * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
     * <li><code>callback</code> - <code>string|array</code> - Optional - The string name of a function to pass the response data to. If this is a method, pass an array where the <code>[0]</code> index is the class and the <code>[1]</code> index is the method name.</li>
     * <li><code>limit</code> - <code>integer</code> - Optional - The number of simultaneous requests to make. This can be useful for scaling around slow server responses. Defaults to trusting cURLs judgement as to how many to use.</li></ul>
     * @return array Post-processed cURL responses.
     */
    public function send_multi_request($handles, $opt = null) {
        if (false === $this->isBaeEnv ()) {
            set_time_limit ( 0 );
        }
        // Skip everything if there are no handles to process.
        if (count ( $handles ) === 0)
            return array ();
        if (! $opt)
            $opt = array ();

        // Initialize any missing options
        $limit = isset ( $opt ['limit'] ) ? $opt ['limit'] : - 1;
        // Initialize
        $handle_list = $handles;
        $http = new $this->request_class ();
        $multi_handle = curl_multi_init ();
        $handles_post = array ();
        $added = count ( $handles );
        $last_handle = null;
        $count = 0;
        $i = 0;
        // Loop through the cURL handles and add as many as it set by the limit parameter.
        while ( $i < $added ) {
            if ($limit > 0 && $i >= $limit)
                break;
            curl_multi_add_handle ( $multi_handle, array_shift ( $handles ) );
            $i ++;
        }
        do {
            $active = false;
            // Start executing and wait for a response.
            while ( ($status = curl_multi_exec ( $multi_handle, $active )) === CURLM_CALL_MULTI_PERFORM ) {
                // Start looking for possible responses immediately when we have to add more handles
                if (count ( $handles ) > 0)
                    break;
            }
            // Figure out which requests finished.
            $to_process = array ();
            while ( $done = curl_multi_info_read ( $multi_handle ) ) {
                // Since curl_errno() isn't reliable for handles that were in multirequests, we check the 'result' of the info read, which contains the curl error number, (listed here http://curl.haxx.se/libcurl/c/libcurl-errors.html )
                if ($done ['result'] > 0) {
                    return '';
                } // Because curl_multi_info_read() might return more than one message about a request, we check to see if this request is already in our array of completed requests
                elseif (! isset ( $to_process [( int ) $done ['handle']] )) {
                    $to_process [( int ) $done ['handle']] = $done;
                }
            }
            // Actually deal with the request
            foreach ( $to_process as $pkey => $done ) {
                $response = $http->process_response ( $done ['handle'], curl_multi_getcontent ( $done ['handle'] ) );
                $key = array_search ( $done ['handle'], $handle_list, true );
                $handles_post [$key] = $response;
                if (count ( $handles ) > 0) {
                    curl_multi_add_handle ( $multi_handle, array_shift ( $handles ) );
                }
                curl_multi_remove_handle ( $multi_handle, $done ['handle'] );
                curl_close ( $done ['handle'] );
            }
        } while ( $active || count ( $handles_post ) < $added );
        curl_multi_close ( $multi_handle );
        ksort ( $handles_post, SORT_NUMERIC );
        return $handles_post;
    }

    /*%******************************************************************************************%*/
    // RESPONSE METHODS
    /**
     * Get the HTTP response headers from the request.
     *
     * @param string $header (Optional) A specific header value to return. Defaults to all headers.
     * @return string|array All or selected header values.
     */
    public function get_response_header($header = null) {
        if ($header) {
            //			return $this->response_headers [strtolower ( $header )];
            return $this->response_headers [$header];
        }
        return $this->response_headers;
    }

    /**
     * Get the HTTP response body from the request.
     *
     * @return string The response body.
     */
    public function get_response_body() {
        return $this->response_body;
    }

    /**
     * Get the HTTP response code from the request.
     *
     * @return string The HTTP response code.
     */
    public function get_response_code() {
        return $this->response_code;
    }
}


/**
 * Container for all response-related methods.
 */
class BCS_ResponseCore {
    /**
     * Stores the HTTP header information.
     */
    public $header;
    /**
     * Stores the SimpleXML response.
     */
    public $body;
    /**
     * Stores the HTTP response code.
     */
    public $status;

    /**
     * Constructs a new instance of this class.
     *
     * @param array $header (Required) Associative array of HTTP headers (typically returned by <BCS_RequestCore::get_response_header()>).
     * @param string $body (Required) XML-formatted response from AWS.
     * @param integer $status (Optional) HTTP response status code from the request.
     * @return object Contains an <php:array> `header` property (HTTP headers as an associative array), a <php:SimpleXMLElement> or <php:string> `body` property, and an <php:integer> `status` code.
     */
    public function __construct($header, $body, $status = null) {
        $this->header = $header;
        $this->body = $body;
        $this->status = $status;
        return $this;
    }

    /**
     * Did we receive the status code we expected?
     *
     * @param integer|array $codes (Optional) The status code(s) to expect. Pass an <php:integer> for a single acceptable value, or an <php:array> of integers for multiple acceptable values.
     * @return boolean Whether we received the expected status code or not.
     */
    public function isOK($codes = array(200, 201, 204, 206)) {
        if (is_array ( $codes )) {
            return in_array ( $this->status, $codes );
        }
        return $this->status === $codes;
    }
}

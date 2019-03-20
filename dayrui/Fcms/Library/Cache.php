<?php namespace Phpcmf\Library;

/**
 * cms缓存
 */

class Cache {

    // 文件缓存目录
    private $file_dir;

    /**
     * 构造函数,初始化变量
     */
    public function __construct(...$params) {
        $this->file_dir = WRITEPATH.'data/'; // 设置缓存目录
    }

    /**
     * 分析缓存文件名
     */
    private function parse_cache_file($file_name, $cache_dir = null) {
        return ($cache_dir ? WRITEPATH.$cache_dir.'/' : $this->file_dir).$file_name.'.cache';
    }

    /**
     * 设置缓存目录
     */
    public function init_file($dir) {
        $this->file_dir = WRITEPATH.trim($dir, '/').'/'; // 设置缓存目录
        return $this;
    }


    /**
     * 设置缓存
     */
    public function set_file($key, $value, $cache_dir = null) {

        if (!$key) {
            return false;
        }

        $cache_file = self::parse_cache_file($key, $cache_dir); // 分析缓存文件
        $value = json_encode($value); // 分析缓存内容

        // 分析缓存目录
        $cache_dir = ($cache_dir ? WRITEPATH.$cache_dir.'/' : $this->file_dir);
        !is_dir($cache_dir) ? dr_mkdirs($cache_dir, 0777) : (!is_writeable($cache_dir) && @chmod($cache_dir, 0777));

        return @file_put_contents($cache_file, $value, LOCK_EX) ? true : false;
    }

    /**
     * 获取一个已经缓存的变量
     */
    public function get_file($key, $cache_dir = null) {

        if (!$key) {
            return false;
        }

        $cache_file = self::parse_cache_file($key, $cache_dir); // 分析缓存文件

        return is_file($cache_file) ? @json_decode(@file_get_contents($cache_file), true) : false;
    }

    /**
     * 删除缓存
     *
     * @param string $key
     * @return void
     */
    public function del_file($key, $cache_dir = null) {

        if (!$key) {
            return true;
        }

        $cache_file = self::parse_cache_file($key, $cache_dir);  // 分析缓存文件

        return is_file($cache_file) ? @unlink($cache_file) : true;
    }

    // 删除全部文件缓存
    public function del_all($dir = 'data') {

        !$dir && $dir = 'data';
        $path = WRITEPATH.$dir.'/';


        dr_dir_delete($path);

        return ;
    }

    //------------------------------------------------

    // 调用ci框架缓存
    public function init() {
        return cache();
    }

    // 存储内容
    public function set_data($name, $value, $time = 3600) {
        $time && self::init()->save(md5('cache-'.SITE_ID.'-'.$name), $value, $time);
        return $value;
    }

    // 获取内容
    public function get_data($name) {
        return self::init()->get(md5('cache-'.SITE_ID.'-'.$name));
    }

    // 使用框架
    public function get() {

        $param = func_get_args();
        if (!$param) {
            return null;
        }

        // 取第一个参数作为缓存变量名称
        $name = strtolower(array_shift($param));
        $result = $this->get_data($name);
        if (!$result) {
            // 缓存不存在时重写缓存
            $result = self::get_file($name);
            // 任然不存在就表示没有数据
            if (!$result) {
                return null;
            }
            // 存储缓存
            $this->set_data($name, $result, 3600);
        }

        if (!$param) {
            return $result;
        }

        $var = '';
        foreach ($param as $v) {
            $var.= '[\''.dr_safe_replace($v).'\']';
        }

        $return = null;
        @eval('$return = $result'.$var.';');

        return $return;
    }


    // 删除缓存
    public function clear($name) {

        $this->init()->delete('site-'.SITE_ID.'-'.$name);
        $this->init()->delete(md5('cache-'.SITE_ID.'-'.$name));

        // 重置Zend OPcache
        function_exists('opcache_reset') && opcache_reset();

    }
}
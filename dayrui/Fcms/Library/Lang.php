<?php namespace Phpcmf\Library;

/**
 * 语言包
 */

class Lang {

    public $lang;

    /**
     * 加载自定义语言
     */
    public function __construct(...$params) {
        if (is_file(ROOTPATH.'config/language/'.SITE_LANGUAGE.'/lang.php')) {
            $this->lang = require ROOTPATH.'config/language/'.SITE_LANGUAGE.'/lang.php';
        } else {
            $this->lang = [];
        }
    }

    /**
     * 输出最终语言
     */
    public function text($text) {
        return isset($this->lang[$text]) ? $this->lang[$text] : $text;
    }

}
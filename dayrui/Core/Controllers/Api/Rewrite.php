<?php namespace Phpcmf\Controllers\Api;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// 系统默认伪静态处理
class Rewrite extends \Phpcmf\Common
{

	// test
	public function test() {
		$this->_json(1, '服务器支持伪静态功能，可以自定义URL规则和解析规则了');
	}

	// 网站地图
	public function sitemap() {
	    if (!dr_is_app('zhanzhang')) {
	        exit('未安装站长工具插件');
        }
        header('Content-Type: text/xml');
        echo \Phpcmf\Service::M('zhanzhang', 'zhanzhang')->sitemap();exit;
    }
}

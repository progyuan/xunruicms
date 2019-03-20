<?php namespace Phpcmf\Controllers\Api;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// 接口处理
class Home extends \Phpcmf\Common
{

	public function index() {


	    if (IS_API === 'pay') {
	        // 支付接口部分

            $info = pathinfo($_SERVER['PHP_SELF']);
            $name = basename($info['dirname']);
            $path = trim($info['dirname'], '/');
            $file = str_replace('_url.php', '_api.php', $info['basename']);
            $apifile = WEBPATH.$path.'/'.$file;

            !is_file($apifile) && exit('支付接口文件不存在');

            // 接口配置参数
            $config = $this->member_cache['payapi'][$name];

            require $apifile;

            exit;
        }

        $myfile = MYPATH.'Api/'.ucfirst(IS_API).'.php';

		!is_file($myfile) && exit('api file is error');

		require $myfile;
		exit;
	}

}

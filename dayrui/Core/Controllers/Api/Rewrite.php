<?php namespace Phpcmf\Controllers\Api;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * http://www.tianruixinxi.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



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

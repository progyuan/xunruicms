<?php namespace Phpcmf\Controllers\Admin;

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



class Content extends \Phpcmf\Admin\Content
{

	public function index() {
        if (\Phpcmf\Service::L('Input')->get('p')) {
            \Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '数据结构' => ['db/index', 'fa fa-database'],
                    '执行SQL' => ['content/index{p=1}', 'fa fa-code'],
                ]
            ));
        }
        $page = intval(\Phpcmf\Service::L('Input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' =>  dr_form_hidden(['page' => $page]),
            'sql_cache' => \Phpcmf\Service::L('File')->get_sql_cache(),
        ]);
		\Phpcmf\Service::V()->display('content_index.html');
	}

	public function replace_index() {
		$this->_Replace();
	}

	public function sql_index() {
		$this->_Sql();
	}

}

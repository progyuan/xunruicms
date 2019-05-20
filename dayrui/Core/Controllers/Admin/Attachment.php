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


class Attachment extends \Phpcmf\Common
{
    public $type;
	
	public function __construct(...$params) {
		parent::__construct(...$params);
        $this->type = [
            0 => '本地磁盘',
            //1 => 'FTP服务器',
            2 => '阿里云',
            3 => '腾讯云',
            4 => '百度云',
            5 => '七牛',
        ];;
	}

	public function index() {

        $data = is_file(WRITEPATH.'config/system.php') ? require WRITEPATH.'config/system.php' : [];

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('Input')->post('data', true);
            \Phpcmf\Service::M('System')->save_config($data,
                [
                    'SYS_ATTACHMENT_DB' => (int)$post['SYS_ATTACHMENT_DB'],
                    'SYS_ATTACHMENT_URL' => $post['SYS_ATTACHMENT_URL'],
                    'SYS_ATTACHMENT_PATH' => addslashes($post['SYS_ATTACHMENT_PATH']),
                ]
            );
            \Phpcmf\Service::M('Site')->config(SITE_ID, 'image', \Phpcmf\Service::L('Input')->post('image'));
            \Phpcmf\Service::L('Input')->system_log('设置附件参数');
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('Input')->get('page'));
        $site = \Phpcmf\Service::M('Site')->config(SITE_ID);

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '附件设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '远程附件' => [\Phpcmf\Service::L('Router')->class.'/remote_index', 'fa fa-cloud'],
                    'help' => [359],
                ]
            ),
            'image' => $site['image'],
        ]);
        \Phpcmf\Service::V()->display('attachment_index.html');
	}

	public function remote_index() {

        \Phpcmf\Service::V()->assign([
            'list' => \Phpcmf\Service::M()->table('attachment_remote')->getAll(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '附件设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '远程附件' => [\Phpcmf\Service::L('Router')->class.'/remote_index', 'fa fa-cloud'],
                    '添加' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    'help' => [88],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('attachment_remote.html');
	}

	public function add() {

	    if (IS_AJAX_POST) {
            $data = \Phpcmf\Service::L('Input')->post('data', true);
            $rt = \Phpcmf\Service::M()->table('attachment_remote')->insert([
                'type' => intval($data['type']),
                'name' => (string)$data['name'],
                'url' => (string)$data['url'],
                'value' => dr_array2string($data['value']),
            ]);
            !$rt['code'] && $this->_json(0, $rt['msg']);
            $this->_json(1, dr_lang('操作成功'));
        }
	    
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '附件设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '远程附件' => [\Phpcmf\Service::L('Router')->class.'/remote_index', 'fa fa-cloud'],
                    '添加' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    'help' => [88],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('attachment_add.html');
	}

	public function edit() {

	    $id = intval($_GET['id']);

	    if (IS_AJAX_POST) {
            $data = \Phpcmf\Service::L('Input')->post('data', true);
            $rt = \Phpcmf\Service::M()->table('attachment_remote')->update($id,
                [
                    'type' => intval($data['type']),
                    'name' => (string)$data['name'],
                    'url' => (string)$data['url'],
                    'value' => dr_array2string($data['value']),
                ]
            );
            !$rt['code'] && $this->_json(0, $rt['msg']);
            $this->_json(1, dr_lang('操作成功'));
        }

        $data = \Phpcmf\Service::M()->table('attachment_remote')->get($id);
	    $data['value'] = dr_string2array($data['value']);
	    $data['value'] = $data['value'][intval($data['type'])];

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'form' => dr_form_hidden(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '附件设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-folder'],
                    '远程附件' => [\Phpcmf\Service::L('Router')->class.'/remote_index', 'fa fa-cloud'],
                    '添加' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    '修改' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                    'help' => [88],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('attachment_add.html');
	}

	public function del() {

        $ids = \Phpcmf\Service::L('Input')->get_post_ids();
        !$ids && exit($this->_json(0, dr_lang('你还没有选择呢')));

        \Phpcmf\Service::M()->table('attachment_remote')->deleteAll($ids);
        \Phpcmf\Service::L('Input')->system_log('批量删除远程附件策略: '. @implode(',', $ids));
        exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
    }
	
}

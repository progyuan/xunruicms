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



class Site extends \Phpcmf\Table
{
	private $form; // 表单验证配置
	
	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'多网站管理（Beta）' => ['site/index', 'fa fa-share-alt'],
				'创建站点（Beta）' => ['site/add', 'fa fa-plus'],
                '域名绑定说明' => ['site/bang_index', 'fa fa-code'],
				'help' => ['384'],
			]
		));
		// 表单验证配置
		$this->form = [
			'name' => [
				'name' => '站点名称',
				'rule' => [
					'empty' => dr_lang('站点名称不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
			'domain' => [
				'name' => '域名地址',
				'rule' => [
					'empty' => dr_lang('域名地址不能为空')
				],
				'filter' => [],
				'length' => '200'
			],
		];
	}

	public function index() {

        $this->_init([
            'table' => 'site',
            'order_by' => 'id asc',
        ]);
        $this->_List();
		\Phpcmf\Service::V()->display('site_index.html');
	}
	
	
	public function add() {

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('input')->post('data', true);
			$this->_validation($data);
			\Phpcmf\Service::L('Input')->system_log('创建网站('.$data['name'].')');
			\Phpcmf\Service::M('Site')->create($data);
			exit($this->_json(1, dr_lang('操作成功')));
		}

		\Phpcmf\Service::V()->assign([
			'form' => dr_form_hidden()
		]);
		\Phpcmf\Service::V()->display('site_add.html');
	}

	// 隐藏或者启用
	public function hidden_edit() {

		$id = (int)\Phpcmf\Service::L('Input')->get('id');
		$row = \Phpcmf\Service::M('Site')->table('site')->get($id);
		!$row && $this->_json(0, dr_lang('站点数据不存在'));

		$v = $row['disabled'] ? 0 : 1;
		\Phpcmf\Service::M('Site')->table('site')->update($id, ['disabled' => $v]);

		exit($this->_json(1, dr_lang($v ? '站点已被禁用' : '站点已被启用'), ['value' => $v]));
	}
	
	public function del() {

		$ids = \Phpcmf\Service::L('Input')->get_post_ids();
		!$ids && exit($this->_json(0, dr_lang('你还没有选择呢')));
		in_array(1, $ids) && exit($this->_json(0, dr_lang('主站不能删除')));

		$rt = \Phpcmf\Service::M('Site')->delete_site($ids);
		!$rt['code'] && exit($this->_json(0, $rt['msg']));
		
		\Phpcmf\Service::L('Input')->system_log('批量删除站点: '. @implode(',', $ids));

		exit($this->_json(1, dr_lang('操作成功'), ['ids' => $ids]));
	}

	public function edit() {

		$ids = \Phpcmf\Service::L('Input')->get_post_ids();
		!$ids && exit($this->_json(0, dr_lang('你还没有选择呢')));

		$data = \Phpcmf\Service::M()->db->table('site')->whereIn('id', $ids)->get()->getResultArray();
        $value = \Phpcmf\Service::L('Input')->post('data', true);
		foreach ($data as $t) {
		    $id = $t['id'];
            $t['setting'] = dr_string2array($t['setting']);
            $t['setting']['webpath'] = $id > 1 ? $value[$id]['webpath'] : '';
            \Phpcmf\Service::M()->db->table('site')->where('id', $id)->update([
                'name' => $value[$id]['name'] ? $value[$id]['name'] : '未知',
                'domain' => $value[$id]['domain'] ? $value[$id]['domain'] : 'null',
                'setting' => dr_array2string($t['setting'])
            ]);
        }

		\Phpcmf\Service::L('Input')->system_log('批量修改站点: '. @implode(',', $ids));

		exit($this->_json(1, dr_lang('操作成功')));
	}

    public function bang_index() {
        \Phpcmf\Service::V()->display('site_bang.html');
    }
	
	// 验证数据
	private function _validation($data) {

		list($data, $return) = \Phpcmf\Service::L('Form')->validation($data, $this->form);
		$return && exit($this->_json(0, $return['error'], ['field' => $return['name']]));
		if (!$data['webpath']) {
            exit($this->_json(0, dr_lang('本站Web目录未填写'), ['field' => 'webpath']));
        }
        $path = dr_get_dir_path($data['webpath']);
        if (!is_dir($path)) {
            $this->_json(0, dr_lang('目录[%s]不存在', $path));
        }

	}

}

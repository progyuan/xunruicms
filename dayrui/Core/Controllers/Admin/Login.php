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



class Login extends \Phpcmf\Common
{

	public function index() {

		$url = pathinfo(\Phpcmf\Service::L('Input')->get('go') ? urldecode(\Phpcmf\Service::L('Input')->get('go')) :\Phpcmf\Service::L('Router')->url('home'));
		$url = $url['basename'] ? $url['basename'] :\Phpcmf\Service::L('Router')->url('home/index');

		if (IS_AJAX_POST) {
			$data = \Phpcmf\Service::L('Input')->post('data', true);
			if (SYS_ADMIN_CODE && !\Phpcmf\Service::L('form')->check_captcha('code')) {
				$this->_json(0, dr_lang('验证码不正确'));
			} elseif (empty($data['username']) || empty($data['password'])) {
				$this->_json(0, dr_lang('账号或密码必须填写'));
			} else {
				$login = \Phpcmf\Service::M('auth')->login($data['username'], $data['password']);
                $this->admin['uid'] = 0;
                $this->admin['username'] = $data['username'];
                if ($login['code']) {
                    // 登录成功
                    $sync = [];
                    // 写入日志
                    \Phpcmf\Service::L('Input')->system_log('登录后台成功', 1);
                    $this->_json(1, 'ok', ['sync' => $sync, 'url' => \Phpcmf\Service::L('Input')->xss_clean($url)]);
                } else {
                    // 写入日志
                    \Phpcmf\Service::L('Input')->system_log($login['msg'].'（密码'.$data['password'].'）', 1);
                    $this->_json(0, $login['msg']);
                }
			}
		}

		\Phpcmf\Service::V()->assign(array(
			'form' => dr_form_hidden(),
		));
		\Phpcmf\Service::V()->display('login.html');exit;
	}

	public function ajax() {



	}

	public function out() {
		$this->session()->remove('admin');
		$this->session()->remove('siteid');
		$this->_json(1, dr_lang('您已经安全退出系统了'));
	}

}

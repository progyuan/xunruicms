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



class Member_payconfig extends \Phpcmf\Common
{

    public function __construct(...$params) {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
            [
                '支付设置' => ['member_payconfig/index', 'fa fa-cog'],
            ]
        ));
    }

    public function index() {

        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'pay')->get()->getRowArray();
        $data = dr_string2array($data['value']);

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('Input')->post('data', true);
            \Phpcmf\Service::M()->db->table('member_setting')->replace([
                'name' => 'pay',
                'value' => dr_array2string($post)
            ]);
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display('member_payconfig.html');
    }


}

<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Member_setting extends \Phpcmf\Common
{

    public function index() {

        $page = intval(\Phpcmf\Service::L('Input')->get('page'));

        // 获取会员全部配置信息
        $data = [];
        $result = \Phpcmf\Service::M()->db->table('member_setting')->get()->getResultArray();
        if ($result) {
            foreach ($result as $t) {
                $data[$t['name']] = dr_string2array($t['value']);
            }
        }

        if (IS_AJAX_POST) {
            $save = ['register', 'login', 'oauth', 'config'];
            $post = \Phpcmf\Service::L('Input')->post('data', true);
            foreach ($save as $name) {
                \Phpcmf\Service::M()->db->table('member_setting')->replace([
                    'name' => $name,
                    'value' => dr_array2string($post[$name])
                ]);
            }
            $this->_json(1, dr_lang('操作成功'));
        }

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户设置' => ['member_setting/index', 'fa fa-cog'],
                ]
            ),
            'group' => \Phpcmf\Service::M()->table('member_group')->getAll(),
            'synurl' => \Phpcmf\Service::M('member')->get_sso_url(),
        ]);
        \Phpcmf\Service::V()->display('member_setting.html');
    }


}
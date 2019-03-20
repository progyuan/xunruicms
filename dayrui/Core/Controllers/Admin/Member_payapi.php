<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Member_payapi extends \Phpcmf\Common
{

    public function index() {

        $data = \Phpcmf\Service::M()->db->table('member_setting')->where('name', 'payapi')->get()->getRowArray();
        $data = dr_string2array($data['value']);

        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('Input')->post('data', true);
            \Phpcmf\Service::M()->db->table('member_setting')->replace([
                'name' => 'payapi',
                'value' => dr_array2string($post)
            ]);
            $this->_json(1, dr_lang('操作成功'));
        }

        $pay = [];
        $local = dr_dir_map(ROOTPATH.'api/pay/', 1);
        foreach ($local as $dir) {
            $dir != 'finecms' && is_file(ROOTPATH.'api/pay/'.$dir.'/config.php') && $pay[$dir] = require ROOTPATH.'api/pay/'.$dir.'/config.php';
        }

        \Phpcmf\Service::V()->assign([
            'pay' => $pay,
            'data' => $data,
            'form' => dr_form_hidden(),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '支付接口' => ['member_payapi/index', 'fa fa-code'],
                    'help' => [387]
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('member_payapi.html');
    }


}

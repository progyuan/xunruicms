<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

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

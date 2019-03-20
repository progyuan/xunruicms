<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Site_domain extends \Phpcmf\Common
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '域名设置' => ['site_domain/index', 'fa fa-cog'],
                    '域名绑定说明' => ['site_domain/bang_index', 'fa fa-code'],
                    'help' => ['407'],
                ]
            ),
        ]);
    }

    public function index() {

        if (IS_AJAX_POST) {
            \Phpcmf\Service::M('Site')->domain(\Phpcmf\Service::L('Input')->post('data', true));
            \Phpcmf\Service::L('Input')->system_log('设置网站域名参数');
            exit($this->_json(1, dr_lang('操作成功')));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        list($module, $data) = \Phpcmf\Service::M('Site')->domain();

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'module' => $module
        ]);
        \Phpcmf\Service::V()->display('site_domain.html');
    }

    public function bang_index() {

        list($module, $data) = \Phpcmf\Service::M('Site')->domain();

        \Phpcmf\Service::V()->assign([
            'data' => $data,
            'module' => $module
        ]);
        \Phpcmf\Service::V()->display('site_domain_bang.html');
    }

    public function edit() {

        if (IS_POST) {
            $domain = trim(\Phpcmf\Service::L('Input')->post('domain', true));
            if (!$domain) {
                exit($this->_json(0, dr_lang('域名不能为空')));
            }

            \Phpcmf\Service::M('Site')->edit_domain($domain);
            \Phpcmf\Service::L('Input')->system_log('变更网站授权域名');
            exit($this->_json(1, dr_lang('操作成功，请重新下载授权证书')));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display('site_domain_edit.html');exit;
    }

}


<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

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

<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Site_param extends \Phpcmf\Common
{
	public function index() {

		if (IS_AJAX_POST) {
			$rt = \Phpcmf\Service::M('Site')->config(
			    SITE_ID,
                'param',
                \Phpcmf\Service::L('Input')->post('data', true)
            );
            !is_array($rt) && $this->_json(0, dr_lang('网站信息(#%s)不存在', SITE_ID));
			\Phpcmf\Service::L('Input')->system_log('设置网站自定义参数');
			exit($this->_json(1, dr_lang('操作成功')));
		}

		$page = intval(\Phpcmf\Service::L('Input')->get('page'));
		$data = \Phpcmf\Service::M('Site')->config(SITE_ID);

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['param'],
			'form' => dr_form_hidden(['page' => $page]),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '网站参数' => ['site_param/index', 'fa fa-cog'],
                ]
            ),
		]);
		\Phpcmf\Service::V()->display('site_param.html');
	}

	
}

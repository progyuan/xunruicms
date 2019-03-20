<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Site_watermark extends \Phpcmf\Common
{
	
	public function __construct(...$params) {
		parent::__construct(...$params);
		\Phpcmf\Service::V()->assign('menu', \Phpcmf\Service::M('auth')->_admin_menu(
			[
				'图片水印' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-photo'],
                'help' => [507],
			]
		));
	}

	public function index() {

        if (IS_AJAX_POST) {
            \Phpcmf\Service::M('Site')->config(
                SITE_ID,
                'watermark',
                \Phpcmf\Service::L('Input')->post('data', true)
            );
            \Phpcmf\Service::L('Input')->system_log('设置网站图片水印参数');
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('Input')->get('page'));
        $data = \Phpcmf\Service::M('Site')->config(SITE_ID);

        $locate = [

            'left-top' => '左上',
            'center-top' => '中上',
            'right-top' => '右上',

            'left-middle' => '左中',
            'center-middle' => '正中',
            'right-middle' => '右中',

            'left-bottom' => '左下',
            'center-bottom' => '中下',
            'right-bottom' => '右下',

        ];

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data['watermark'],
            'form' => dr_form_hidden(['page' => $page]),
            'locate' => $locate,
            'waterfont' => dr_file_map(WEBPATH.'config/font/', 1),
            'waterfile' => dr_file_map(WEBPATH.'config/watermark/', 1),
        ]);
        \Phpcmf\Service::V()->display('site_watermark.html');
	}

	
}

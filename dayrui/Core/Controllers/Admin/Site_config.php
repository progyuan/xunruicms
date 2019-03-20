<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Site_config extends \Phpcmf\Common
{
	public function index() {

        $data = \Phpcmf\Service::M('Site')->config(SITE_ID);
        $field = [
            'logo' => [
                'ismain' => 1,
                'fieldtype' => 'File',
                'fieldname' => 'logo',
                'setting' => ['option' => ['ext' => 'jpg,gif,png,jpeg', 'size' => 10, 'input' => 1]]
            ]
        ];

		if (IS_AJAX_POST) {

		    $tj = $_POST['data']['SITE_TONGJI'];
            $post = \Phpcmf\Service::L('input')->post('data', true);
            $post['SITE_TONGJI'] = $tj;
            $rt = \Phpcmf\Service::M('Site')->config(SITE_ID, 'config', $post);
			!is_array($rt) && $this->_json(0, dr_lang('网站信息(#%s)不存在', SITE_ID));

			\Phpcmf\Service::L('input')->system_log('设置网站参数');

            // 附件归档
            if (SYS_ATTACHMENT_DB) {
                list($post, $return, $attach) = \Phpcmf\Service::L('form')->validation($post, null, $field);
                $attach && \Phpcmf\Service::M('Attachment')->handle($this->member['id'], \Phpcmf\Service::M()->dbprefix('site'), $attach);
            }

            $this->_json(1, dr_lang('操作成功'));
		}

		$page = intval(\Phpcmf\Service::L('input')->get('page'));

		\Phpcmf\Service::V()->assign([
			'page' => $page,
			'data' => $data['config'],
			'form' => dr_form_hidden(['page' => $page]),
			'lang' => dr_dir_map(ROOTPATH.'config/language/', 1),
			'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '网站设置' => ['site_config/index', 'fa fa-cog'],
                    'help' => [505],
                ]
            ),
			'theme' => dr_get_theme(),
			'is_theme' => strpos($data['SITE_THEME'], 'http://') === 0 ? 1 : 0,
            'logofield' => dr_fieldform($field['logo'], $data['config']['logo']),
			'template_path' => dr_dir_map(TPLPATH.'pc/', 1),
		]);
		\Phpcmf\Service::V()->display('site_config.html');
	}

}

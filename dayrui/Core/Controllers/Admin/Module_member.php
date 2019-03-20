<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Module_member extends \Phpcmf\Common
{

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');

        // 设置url
        if ($module) {
            foreach ($module as $dir => $t) {
                if ($t['hlist'] == 1) {
                    unset($module[$dir]);
                    continue;
                }
                $module[$dir]['url'] =\Phpcmf\Service::L('Router')->url($dir.'/member/index');
            }
        }

        $one = $tmp['system'] = [
            'icon' => 'fa fa-cog',
            'title' => '网站',
            'name' => '网站',
            'dirname' => 'system',
            'url' =>\Phpcmf\Service::L('Router')->url('site_member/index'),
        ];

        $module = dr_array22array($tmp, $module);

        // 只存在一个项目
        dr_count($module) == 1 && dr_redirect($one['url']);

        $dirname = $one['dirname'];

        \Phpcmf\Service::V()->assign([
            'url' => $one['url'],
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '内容权限' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-user'],
                ]
            ),
            'module' => $module,
            'dirname' => $dirname,
        ]);
        \Phpcmf\Service::V()->display('iframe_content.html');exit;
    }

}

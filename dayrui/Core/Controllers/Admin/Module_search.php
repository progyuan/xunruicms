<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Module_search extends \Phpcmf\Common
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
                $module[$dir]['url'] = dr_url(\Phpcmf\Service::L('Router')->class.'/show_index', ['dir' => $dir]);
            }
        } else {
            $this->_admin_msg(0, dr_lang('系统没有安装内容模块'));
        }

        $one = reset($module);

        // 只存在一个项目
        dr_count($module) == 1 && dr_redirect($one['url']);

        $dirname = $one['dirname'];

        \Phpcmf\Service::V()->assign([
            'url' => $one['url'],
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '搜索设置' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-search'],
                ]
            ),
            'module' => $module,
            'dirname' => $dirname,
        ]);
        \Phpcmf\Service::V()->display('iframe_content.html');exit;
    }

    public function show_index() {

        $dir = \Phpcmf\Service::L('input')->get('dir');
        $cache = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        if (!$cache[$dir]) {
            $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
        }

        $all = \Phpcmf\Service::M('Module')->get_module_info();
        if (!$all[$dir]) {
            $this->_admin_msg(0, dr_lang('模块#%s不存在', $dir));
        }

        if (IS_POST) {

            $data = \Phpcmf\Service::L('Input')->post('data', true);
            foreach ($data as $dir => $t) {
                $module[$dir]['setting']['search'] = $t;
                \Phpcmf\Service::M()->db->table('module')->where('dirname', $dir)->update([
                    'setting' => dr_array2string($module[$dir]['setting']),
                ]);
            }

            $this->_json(1, '操作成功');

        }

        $data = $all[$dir];

        // 搜索字段
        $data['search_field'] = [
            'keyword' => dr_lang('关键词'),
            'order' => dr_lang('排序'),
            'page' => dr_lang('分页'),
        ];
        $field = \Phpcmf\Service::M()->db->table('field')
            ->where('disabled', 0)
            ->where('ismain', 1)
            ->where('relatedname', 'module')
            ->where('relatedid', $data['id'])
            ->orderBy('displayorder ASC,id ASC')
            ->get()->getResultArray();
        foreach ($field as $f) {
            $data['search_field'][$f['fieldname']] = $f['name'];
        }

        \Phpcmf\Service::V()->assign([
            'page' => $dir,
            'module' => [$dir => $data],
            'save_url' => dr_url(\Phpcmf\Service::L('Router')->class.'/edit', ['dir' => $dir]),
            'site_name' => $this->site_info[SITE_ID]['SITE_NAME'],
        ]);
        \Phpcmf\Service::V()->display('module_search.html');
    }

}

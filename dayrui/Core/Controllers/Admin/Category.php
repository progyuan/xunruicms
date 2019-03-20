<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Category extends \Phpcmf\Admin\Category
{

    public function index() {
        $this->_Admin_List();
    }

    public function all_add() {
        $this->_Admin_All_Add();
    }

    public function add() {
        $this->_Admin_Add();
    }

    public function edit() {
        $this->_Admin_Edit();
    }

    public function url_edit() {
        $this->_Admin_Url_Edit();
    }

    public function move_edit() {
        $this->_Admin_Move_Edit();
    }
    
    public function show_edit() {
        $this->_Admin_Show_Edit();
    }
    
    public function displayorder_edit() {
        $this->_Admin_Order();
    }
    
    public function html_edit() {
        $this->_Admin_Html_Edit();
    }

    public function htmlall_edit() {
        $this->_Admin_Html_All_Edit();
    }

    public function phpall_edit() {
        $this->_Admin_Php_All_Edit();
    }

    public function del() {
        $this->_Admin_Del();
    }

    // 编辑单页内容
    public function content_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
        !$row && $this->_json(0, dr_lang('栏目数据不存在'));

        if (IS_POST) {
            $post = \Phpcmf\Service::L('input')->post('data');
            \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['content' => ($post['content'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目内容: '. $row['name'] . '['. $id.']');
            $this->_json(1, dr_lang('操作成功'));
            exit;
        }

        $field = [
            'name' => dr_lang('栏目内容'),
            'ismain' => 1,
            'fieldtype' => 'Ueditor',
            'fieldname' => 'content',
            'setting' => array(
                'option' => array(
                    'mode' => 1,
                    'height' => 300,
                    'width' => '100%'
                )
            ),
        ];

        \Phpcmf\Service::V()->assign([
            'myfield' => dr_fieldform($field, $row['content']),
        ]);
        \Phpcmf\Service::V()->display('share_category_content.html');exit;

    }

    // 编辑外链
    public function link_edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $row = \Phpcmf\Service::M('Category')->init($this->init)->get($id);
        !$row && $this->_json(0, dr_lang('栏目数据不存在'));
        $row['setting'] = dr_string2array($row['setting']);
        if (IS_POST) {
            $row['setting']['linkurl'] = \Phpcmf\Service::L('input')->post('url');
            \Phpcmf\Service::M('Category')->init($this->init)->update($id, ['setting' => dr_array2string($row['setting'])]);
            \Phpcmf\Service::L('input')->system_log('修改栏目外链地址: '. $row['name'] . '['. $id.']');
            $this->_json(1, dr_lang('操作成功'));
            exit;
        }

        \Phpcmf\Service::V()->assign([
            'myurl' => $row['setting']['linkurl'],
        ]);
        \Phpcmf\Service::V()->display('share_category_linkurl.html');exit;

    }
}

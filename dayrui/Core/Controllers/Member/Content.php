<?php namespace Phpcmf\Controllers\Member;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Content extends \Phpcmf\Table
{
    public $module;
    public $my_module;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        $this->module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        if (!$this->module) {
            $this->_msg(0, dr_lang('系统还没有可用的内容模块'));
            exit;
        }
        $dir = \Phpcmf\Service::L('Input')->get('module');
        if (!$dir || !isset($this->module[$dir])) {
            $one = reset($this->module);
            $this->my_module = $one['dirname'];
        } else {
            $this->my_module = $dir;
        }
    }

    // 模板显示
    private function _display($data) {

        // 初始化变量
        unset($data['param']['module']);
        unset($data['param']['total']);
        unset($data['param']['order']);

        // 列出内容模块
        foreach ($this->module as $i => $t) {
            $data['param']['module'] = $i;
            $this->module[$i]['url'] =\Phpcmf\Service::L('Router')->member_url('member/content/'.\Phpcmf\Service::L('Router')->method, $data['param']);
        }

        $list = [];
        if ($data['list']) {
            foreach ($data['list'] as $i => $t) {
                $t['url'] = dr_url_prefix($t['url'], $this->my_module);
                $list[] = $t;
            }
        }

        \Phpcmf\Service::V()->assign([
            'list' => $list,
            'module' => $this->module,
            'my_module' => $this->my_module,
            'del_url' =>\Phpcmf\Service::L('Router')->member_url('member/content/delete', [
                'module' => $this->my_module,
                'action' =>\Phpcmf\Service::L('Router')->method
            ])
        ]);
        \Phpcmf\Service::V()->display('content_'.\Phpcmf\Service::L('Router')->method.'.html');
    }



    /**
     * 评论
     */
    public function comment() {

        $table = dr_module_table_prefix($this->my_module);
        $this->_init([
            'table' => $table.'_comment',
            'select_list' => $table.'_comment.*,'.$table.'.title,'.$table.'.url',
            'order_by' => $table.'_comment.inputtime desc',
            'join_list' => [$table, $table.'.id='.$table.'_comment.cid', 'left'],
            'where_list' => $table.'_comment.uid='.$this->uid.'',
        ]);

        list($tpl, $data) = $this->_List();

        $this->_display($data);
    }

}

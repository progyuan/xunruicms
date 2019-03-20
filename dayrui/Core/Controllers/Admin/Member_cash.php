<?php namespace Phpcmf\Controllers\Admin;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// 提现申请
class Member_cash extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->my_field = array(
            'username' => array(
                'ismain' => 1,
                'name' => dr_lang('账户'),
                'fieldname' => 'username',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
            ),
            'uid' => array(
                'ismain' => 1,
                'name' => dr_lang('uid'),
                'fieldname' => 'uid',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
            ),
        );
        // 表单显示名称
        $this->name = dr_lang('提醒申请');
        // 初始化数据表
        $this->_init([
            'table' => 'member_cashlog',
            'field' => $this->my_field,
            'sys_field' => [],
            'order_by' => 'inputtime desc',
            'date_field' => 'inputtime',
            'list_field' => [],
        ]);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '提醒申请' => [ \Phpcmf\Service::L('Router')->class.'/index', 'fa fa-credit-card'],
                    '详情' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                ]
            ),
            'field' => $this->my_field,
        ]);
    }

    // index
    public function index() {
        $this->_List();
        \Phpcmf\Service::V()->display('member_cash_list.html');
    }

    // edit
    public function edit() {

        list($tpl, $data) = $this->_Post((int)\Phpcmf\Service::L('Input')->get('id'), [], 1);
        !$data && $this->_admin_msg(0, dr_lang('申请记录不存在'));

        \Phpcmf\Service::V()->display('member_cash_post.html');
    }

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $func    格式化提交的数据
     * */
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {

        $old['status'] && $this->_json(0, dr_lang('此记录已经被处理过了'));

        \Phpcmf\Service::M('member')->todo_admin_notice('member_cash/edit:id/'.$id);

        $post = \Phpcmf\Service::L('Input')->post('data');
        if ($post['status']) {
            // 提现成功
            $rt =  \Phpcmf\Service::M('Pay')->cash_successs($id, $post, $old);
            !$rt['code'] && $this->_json(0, $rt['msg']);
        } else {
            // 审核拒绝
            $rt =  \Phpcmf\Service::M('Pay')->cash_fail($id, $post, $old);
            !$rt['code'] && $this->_json(0, $rt['msg']);
        }

        $member = \Phpcmf\Service::M('member')->member_info($old['uid']);
        $member['verify_money'] = $old['value'];
        $member['verify_status'] = $post['status'] ? dr_lang('成功') : dr_lang('被拒绝');
        $member['verify_content'] = $post['result'];

        // 通知 钩子
        \Phpcmf\Service::L('Notice')->send_notice('member_verify_cash', $member);
        \Phpcmf\Hooks::trigger('member_verify_cash_after', $member);
        $this->_json(1, dr_lang('操作成功'));
    }

    // 删除
    public function del() {
        $this->_Del(\Phpcmf\Service::L('Input')->get_post_ids());
    }
    
}

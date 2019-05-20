<?php namespace Phpcmf\Controllers\Member;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * http://www.tianruixinxi.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



class Cash extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 表单显示名称
        $this->name = dr_lang('提现申请');
        // 初始化数据表
        $this->_init([
            'table' => 'member_cashlog',
            'order_by' => 'inputtime desc',
            'date_field' => 'inputtime',
            'where_list' => 'uid='.$this->uid,
        ]);
    }

    // index
    public function index() {

        $this->_List();

        \Phpcmf\Service::V()->display('cash_index.html');
    }
    
    public function add() {

        $this->_Post();
        \Phpcmf\Service::V()->display('cash_post.html');
    }

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $func    格式化提交的数据
     * */
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {

        $post = \Phpcmf\Service::L('Input')->post('data');
        $post['value'] = abs(floatval($post['value']));

        if (!$post['value']) {
            $this->_json(0, dr_lang('提现金额必须填写'));
        } elseif (!$post['content']) {
            $this->_json(0, dr_lang('收款信息必须填写'));
        } elseif ($this->member['money'] - $post['value'] < 0) {
            $this->_json(0, dr_lang('账户余额不足'));
        } elseif ($this->member_cache['pay']['cash']['min']
            && $post['value'] < $this->member_cache['pay']['cash']['min']) {
            $this->_json(0, dr_lang('提现金额不得小于%s元', $this->member_cache['pay']['cash']['min']));
        }

        $rt = \Phpcmf\Service::M('Pay')->add_cash($post);
        !$rt['code'] && $this->_json(0, $rt['msg']);

        // 提醒管理员
        \Phpcmf\Service::M('member')->admin_notice(0, 'pay', $this->member, dr_lang('提现申请'), 'member_cash/edit:id/'.$rt['code']);

        $this->_json(1, dr_lang('操作成功'));
    }
    
}

<?php namespace Phpcmf\Controllers\Api;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// 交易下单
class Buy extends \Phpcmf\Common {

    public function index() {

        $id = (int)\Phpcmf\Service::L('Input')->get('id');
        $fid = (int)\Phpcmf\Service::L('Input')->get('fid');
        (!$fid || !$id) && exit($this->_msg(0, dr_lang('支付参数不完整')));

        $num = max(1, (int)\Phpcmf\Service::L('Input')->get('num'));
        $sku = dr_safe_replace(\Phpcmf\Service::L('Input')->get('sku'), 'undefined');

        $field = $this->get_cache('table-field', $fid);
        !$field && exit($this->_msg(0, dr_lang('支付字段不存在')));

        // 获取付款价格
        $rt = \Phpcmf\Service::M('pay')->get_pay_info($id, $field, $num, $sku);
        isset($rt['code']) && !$rt['code'] && exit($this->_msg(0, $rt['msg']));

        // 挂钩点 购买商品之前
        \Phpcmf\Hooks::trigger('member_buy', $rt);

        \Phpcmf\Service::V()->assign($rt);
        \Phpcmf\Service::V()->assign([
            'num' => $rt['num'],
            'price' => $rt['price'],
            'total' => $rt['total'],
            'payform' => dr_payform($rt['mid'], $rt['total'], $rt['title'].$rt['sku_string'], $rt['url']),
            'meta_title' => dr_lang('在线付款').SITE_SEOJOIN.SITE_NAME,
            'meta_keywords' => $this->get_cache('site', SITE_ID, 'config', 'SITE_KEYWORDS'),
            'meta_description' => $this->get_cache('site', SITE_ID, 'config', 'SITE_DESCRIPTION')
        ]);
        \Phpcmf\Service::V()->display('buy.html');
    }
}

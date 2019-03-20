<?php namespace Phpcmf\Controllers\Api;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// 付款
class Pay extends \Phpcmf\Common
{

	// 付款
	public function index() {

		$id = (int)\Phpcmf\Service::L('Input')->get('id');
		$data = \Phpcmf\Service::M('pay')->table('member_paylog')->get($id);
		if (!$data) {
			$this->_msg(0, dr_lang('该账单不存在'));exit;
		} elseif ($data['status'] == 1) {
			$this->_msg(0, dr_lang('该账单已被支付'));exit;
		}

		$apifile = ROOTPATH.'api/pay/'.$data['type'].'/pay.php';
		if (!is_file($apifile)) {
			$this->_msg(0, dr_lang('支付接口文件（%s）不存在', $data['type']));exit;
		}

		// 发起支付
		$rt = \Phpcmf\Service::M('pay')->dopay($apifile, $data);
		if (!$rt['code']) {
			$this->_msg(0, $rt['msg'], $rt['data']['url']);
			exit;
		} elseif (strlen($rt['data']['rturl']) > 10) {
			$this->_msg(1, $rt['msg'], $rt['data']['rturl']);
			exit;
		}
		
		$data['html'] = $rt['data'];

		\Phpcmf\Service::V()->assign([
			'pay' => $data,
			'pay_name' => dr_pay_type_html($data['type']),
			'meta_title' => $data['title']
		]);
        \Phpcmf\Service::V()->module('api');
		\Phpcmf\Service::V()->display('pay.html');exit;
	}

	/**
	 * 支付接口js-ajax回调
	 */
	public function ajax() {

		$id = (int)\Phpcmf\Service::L('Input')->get('id');
		$data = \Phpcmf\Service::M()->table('member_paylog')->get($id);
		!$data && $this->_jsonp(0, dr_lang('支付记录不存在'));
		$data['status'] && $this->_jsonp(1, dr_lang('已经支付完成'));

		// 调用接口
		$apifile = ROOTPATH.'api/pay/'.$data['type'].'/notify_js.php';
		!is_file($apifile) && $this->_jsonp(0, dr_lang('支付接口文件不存在'));

		$return = [];
		$result = dr_string2array($data['result']);

		// 接口配置参数
		$config = $this->member_cache['payapi'][$data['type']];

		require $apifile;

		$this->_jsonp($return['code'], $return['msg']);
		exit;
	}

    /**
     * 支付接口返回
     */
    public function call() {

        $id = (int)\Phpcmf\Service::L('Input')->get('id');
        $data = \Phpcmf\Service::M()->table('member_paylog')->get($id);
        !$data && $this->_msg(0, dr_lang('支付记录不存在'));

        // 支付回调钩子
        \Phpcmf\Hooks::trigger('pay_call', $data);

        if (!$this->uid) {
            $this->_msg(1, dr_lang('支付成功'));
        }

        // 获取支付回调地址
        $url = \Phpcmf\Service::M('pay')->paycall_url($data);

        $this->_msg(1, dr_lang('支付成功'), $url);
    }
}

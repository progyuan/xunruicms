<?php namespace Phpcmf\Controllers\Api;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// 快捷登录接口
class Oauth extends \Phpcmf\Common
{

	/**
	 * 快捷登录
	 */
	public function index() {

		$name = dr_safe_replace(\Phpcmf\Service::L('Input')->get('name'));
		$type = dr_safe_replace(\Phpcmf\Service::L('Input')->get('type'));
		$action = dr_safe_replace(\Phpcmf\Service::L('Input')->get('action'));

		// 非授权登录时必须验证登录状态
		$type != 'login' && !$this->uid && exit($this->_msg(0, dr_lang('你还没有登录')));

		// 请求参数
		$appid = $this->member_cache['oauth'][$name]['id'];
		$appkey = $this->member_cache['oauth'][$name]['value'];
		$callback_url = ROOT_URL.'index.php?s=api&c=oauth&m=index&action=callback&name='.$name.'&type='.$type;

		switch ($name) {

			case 'weixin':

				if ($action == 'callback') {
					// 表示回调返回
					if (isset($_REQUEST['code'])) {
						// 获取access_token
						$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appkey.'&code='.$_REQUEST['code'].'&grant_type=authorization_code';
						$token = json_decode(dr_catcher_data($url), true);
						!$token && exit($this->_msg(0, dr_lang('无法获取到远程信息')));
						$token['errmsg'] && $this->_msg(0, $token['errmsg']);
						// 获取用户信息
						$url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$token['access_token'].'&openid='.$token['openid'];
						$user = json_decode(dr_catcher_data($url), true);
						!$user && exit($this->_msg(0, dr_lang('无法获取到用户信息')));
						$user['errmsg'] && $this->_msg(0, $user['errmsg']);
						$rt = \Phpcmf\Service::M('member')->insert_oauth($this->uid, $type, [
							'oid' => $token['openid'],
							'oauth' => 'weixin',
							'avatar' => $user['headimgurl'],
							'nickname' => dr_emoji2html($user['nickname']),
							'expire_at' => $token['expires_in'] + SYS_TIME,
							'access_token' => $token['access_token'],
							'refresh_token' => $token['refresh_token'],
						]);
						if (!$rt['code']) {
							$this->_msg(0, $rt['msg']);exit;
						} else {
							dr_redirect($rt['msg']);
						}
					} else {
						$this->_msg(0, dr_lang('回调参数code不存在'));exit;
					}
				} else {
					// 跳转授权页面
					$url = 'https://open.weixin.qq.com/connect/qrconnect?appid='.$appid.'&redirect_uri='.urlencode($callback_url).'&response_type=code&scope=snsapi_login&state=STATE#wechat_redirect';
					dr_redirect($url);
				}
				break;

			case 'qq':

				define("CLASS_PATH", FCPATH."ThirdParty/Qq/");
				require FCPATH.'ThirdParty/Qq/QC.class.php';
				$qc = new \QC();
				if ($action == 'callback') {
					// 表示回调返回
					if (isset($_REQUEST['code'])) {
						$rt = $qc->qq_callback($appid, $appkey, $callback_url, $_REQUEST['code']);
						if (is_array($rt)) {
							// 回调成功
							$open = $qc->get_openid($rt['access_token']);
							!is_array($open) && exit($this->_msg(0, $open)); // 获取失败
							$user = $qc->init($appid, $rt['access_token'], $open['openid'])->get_user_info();
							if (is_array($user)) {
								// 入库oauth表
								$rt = \Phpcmf\Service::M('member')->insert_oauth($this->uid, $type, [
									'oid' => $open['openid'],
									'oauth' => 'qq',
									'avatar' => $user['figureurl_qq_2'] ? $user['figureurl_qq_2'] : $user['figureurl_qq_1'],
									'nickname' => dr_emoji2html($user['nickname']),
									'expire_at' => $rt['expires_in'] + SYS_TIME,
									'access_token' => $rt['access_token'],
									'refresh_token' => $rt['refresh_token'],
								]);
								if (!$rt['code']) {
									$this->_msg(0, $rt['msg']);exit;
								} else {
									dr_redirect($rt['msg']);
								}
							} else {
								$this->_msg(0, dr_lang('获取QQ用户信息失败: '.$user));exit;
							}
						} else {
							$this->_msg(0, $rt);exit;
						}
					} else {
						$this->_msg(0, dr_lang('回调参数code不存在'));exit;
					}
				} else {
					// 跳转授权页面
					$rt = $qc->qq_login($appid, $callback_url);
                    !$rt = $this->_msg(0, dr_lang('授权执行失败'));
				}
				break;

			case 'weibo':

				define("WB_AKEY", $appid);
				define("WB_SKEY", $appkey);
				require FCPATH.'ThirdParty/Weibo/saetv2.ex.class.php';
				$o = new \SaeTOAuthV2(WB_AKEY, WB_SKEY);
				if ($action == 'callback') {
					// 表示回调返回
					if (isset($_REQUEST['code'])) {
						$keys = [];
						$keys['code'] = $_REQUEST['code'];
						$keys['redirect_uri'] = $callback_url;
						$token = $o->getAccessToken('code', $keys);
						if (is_array($token)) {
							// 回调成功
							$c = new \SaeTClientV2(WB_AKEY, WB_SKEY, $token['access_token']);
							$user = $c->show_user_by_id($token['uid']); //根据ID获取用户等基本信息
							if ($user) {
								// 入库oauth表
								$rt = \Phpcmf\Service::M('member')->insert_oauth($this->uid, $type, [
									'oid' => $token['uid'],
									'oauth' => 'weibo',
									'avatar' => $user['avatar_large'] ? $user['avatar_large'] : $user['profile_image_url'],
									'nickname' => dr_emoji2html($user['name']),
									'expire_at' => SYS_TIME + $token['expires_in'],
									'access_token' => $token['access_token'],
									'refresh_token' => '',
								]);
								if (!$rt['code']) {
									$this->_msg(0, $rt['msg']);exit;
								} else {
									dr_redirect($rt['msg']);
								}
							} else {
								$this->_msg(0, dr_lang('获取微博用户信息失败'));exit;
							}
						} else {
							// 回调失败
							$this->_msg(0, $token);exit;
						}
					} else {
						$this->_msg(0, dr_lang('回调参数code不存在'));exit;
					}
				} else {
					// 跳转授权页面
					dr_redirect($o->getAuthorizeURL($callback_url));
				}
				break;

			default:
				exit('未定义的接口');

		}
	}

	// 微信公众号登录
    public function wxmp() {

        exit;
    }

	// 微信小程序登录
    public function xcx() {


    }
}

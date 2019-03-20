<?php namespace Phpcmf\Controllers;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

class Show extends \Phpcmf\Home\Module
{

	public function index() {
		// 共享模块通过id查找内容
		$id = (int)\Phpcmf\Service::L('Input')->get('id');
		$row = \Phpcmf\Service::M()->table(SITE_ID.'_share_index')->get($id);
		$mid = $row['mid'];
		!$mid && exit($this->goto_404_page(dr_lang('无法通过id找到共享模块的模块目录')));
		// 初始化模块
		$this->_module_init($mid);
		// 调用内容方法
		$this->_Show($id, null, max(1, (int)\Phpcmf\Service::L('Input')->get('page')));
	}

}

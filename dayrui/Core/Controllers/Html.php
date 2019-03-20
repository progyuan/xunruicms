<?php namespace Phpcmf\Controllers;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// 共享栏目生成静态
class Html extends \Phpcmf\Home\Module
{

	// 生成栏目
	public function category() {
		parent::_Category_Html();
	}

	// 生成内容
	public function show() {
		parent::_Show_Html();
	}

	// 生成栏目单页
	public function categoryfile() {
		parent::_Category_Html_File();
	}

	// 生成内容单页
	public function showfile() {
		parent::_Show_Html_File();
	}

	
}

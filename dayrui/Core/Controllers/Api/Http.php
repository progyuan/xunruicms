<?php namespace Phpcmf\Controllers\Api;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// Http接口处理
class Http extends \Phpcmf\Common
{

    /**
     * 调用接口
     */
    public function index() {

        $this->_api_auth();

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        if (!$id) {
            $this->_json(0, '未获取到接口id');
        }

        $data = $this->get_cache('api_http', $id);
        if (!$data) {
            $this->_json(0, '接口数据【'.$id.'】不存在');
        }

        $rt = \Phpcmf\Service::M('api')->get_api_data($data);
        $this->_json($rt['code'], $rt['msg'], $rt['data']);

        exit;
    }

    /**
     * 接口测试
     */
    public function test() {
        $this->_api_auth();
        $this->_json(1, 'ok');
    }

}

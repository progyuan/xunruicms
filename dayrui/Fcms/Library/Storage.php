<?php namespace Phpcmf\Library;

/**
 * 存储
 */
class Storage {

    public $ci;

    // 存储对象
    protected $object;

    private function _init($attachment) {

        // 选择存储策略
        switch ($attachment['type']) {

            case 1:
                $this->object = new \Phpcmf\ThirdParty\Storage\Ftp();
                break;

            case 2:
                $this->object = new \Phpcmf\ThirdParty\Storage\Oss();
                break;

            case 3:
                $this->object = new \Phpcmf\ThirdParty\Storage\Qcloud();
                break;

            case 4:
                $this->object = new \Phpcmf\ThirdParty\Storage\Baidu();
                break;

            case 5:
                $this->object = new \Phpcmf\ThirdParty\Storage\Qiniu();
                break;

            default:
                $this->object = new \Phpcmf\ThirdParty\Storage\Local();
                break;
        }

    }

    // 文件上传
    public function upload($type, $data, $file_path, $attachment, $watermarkk) {

        $this->_init($attachment);
        return $this->object->init($attachment, $file_path)->upload($type, $data, $watermarkk);
    }

    // 文件删除
    public function delete($attachment, $filename) {

        $this->_init($attachment);
        return $this->object->init($attachment, $filename)->delete();
    }
}
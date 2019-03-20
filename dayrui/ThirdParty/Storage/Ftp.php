<?php namespace Phpcmf\ThirdParty\Storage;

// Ftp文件存储
class Ftp {

    // 存储内容
    protected $data;

    // 文件存储路径
    protected $filename;

    // 文件存储目录
    protected $filepath;

    // 附件存储的信息
    protected $attachment;

    // 是否进行图片水印
    protected $watermark;

    // 完整的文件目录
    protected $fullpath;

    // 完整的文件路径
    protected $fullname;


    // 初始化参数
    public function init($attachment, $filename) {

        $this->filename = trim($filename, DIRECTORY_SEPARATOR);
        $this->filepath = dirname($filename);
        $this->filepath == '.' && $this->filepath = '';
        $attachment['value']['path'] = rtrim($attachment['value']['path'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $this->attachment = $attachment;
        $this->fullpath = $this->attachment['value']['path'].$this->filepath;
        $this->fullname = $this->attachment['value']['path'].$this->filename;

        return $this;
    }

        // 文件上传模式
    public function upload($type = 0, $data, $watermark) {

        $this->data = $data;
        $this->watermark = $watermark;

        // ftp同步
        if (FALSE === ($conn_id = @ftp_connect($this->attachment['value']['host'], $this->attachment['value']['port']))) {
            return dr_return_data(0, dr_lang('FTP服务器连接失败'));
        }
        if (@ftp_login($conn_id, $this->attachment['value']['username'], $this->attachment['value']['password']) === FALSE) {
            return dr_return_data(0, dr_lang('FTP服务器账号认证失败'));
        }
        $this->attachment['value']['pasv'] && @ftp_pasv($conn_id, TRUE);
        @ftp_mkdir($conn_id, $this->fullpath);
        @ftp_chmod($conn_id, '0775', $this->fullpath);
        // 本地临时文件
        $locpath = WRITEPATH.'attach/'.md5($this->fullname);
        // 存储文件 移动上传或者内容存储
        if ($type) {
            // 移动失败
            if (!(move_uploaded_file($this->data, $locpath) || !is_file($locpath))) {
                return dr_return_data(0, dr_lang('文件移动失败'));
            }
        } else {
            $filesize = file_put_contents($locpath, $this->data);
            if (!$filesize || !is_file($locpath)) {
                return dr_return_data(0, dr_lang('文件创建失败'));
            }
        }
        if (FALSE === ($result = @ftp_put($conn_id, $this->fullpath, $locpath, ($this->attachment['value']['mode'] === 'ascii') ? FTP_ASCII : FTP_BINARY))) {
            @unlink($locpath);
            return dr_return_data(0, dr_lang('FTP服务器上传失败'));
        }
        @ftp_close($conn_id);
        // 强制水印
        if ($this->watermark) {
            $config = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark');
            $config['source_image'] = $locpath;
            $config['dynamic_output'] = false;
            \Phpcmf\Service::L('Image')->watermark($config);
        }
        $md5 = md5_file($locpath);
        @unlink($locpath);
        // 上传成功
        return dr_return_data(1, 'ok', [
            'url' => $this->attachment['url'].$this->filename,
            'md5' => $md5,
        ]);
    }

    // 删除文件
    public function delete() {
        @unlink($this->fullname);
        //log_message('info', 'CSRF token verified');
    }


}
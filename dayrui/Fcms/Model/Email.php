<?php namespace Phpcmf\Model;

class Email extends \Phpcmf\Model
{


    // 缓存
    public function cache() {

        $data = $this->table('mail_smtp')->getAll();
        $cache = [];
        if ($data) {
            foreach ($data as $t) {
                $cache[$t['id']] = $t;
            }
        }

        \Phpcmf\Service::L('cache')->set_file('email', $cache);

    }
}
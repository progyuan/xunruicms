<?php namespace Phpcmf\Model;

// 模型类

class Urlrule extends \Phpcmf\Model
{


    // 缓存
    public function cache() {

        $data = $this->table('urlrule')->getAll();
        $cache = [];
        if ($data) {
            foreach ($data as $t) {
                $t['value'] = dr_string2array($t['value']);
                $cache[$t['id']] = $t;
            }
        }

        \Phpcmf\Service::L('cache')->set_file('urlrule', $cache);
        return;
    }
    
}
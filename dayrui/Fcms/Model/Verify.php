<?php namespace Phpcmf\Model;
// 模型类

class Verify extends \Phpcmf\Model
{

    // 缓存
    public function cache() {

        $data = $this->table('admin_verify')->getAll();
        $cache = [];
        if ($data) {
            foreach ($data as $t) {
                $t['value'] = dr_string2array($t['verify']);
                unset($t['verify']);
                $cache[$t['id']] = $t;
            }
        }

        \Phpcmf\Service::L('cache')->set_file('verify', $cache);
        return;
    }
    
}
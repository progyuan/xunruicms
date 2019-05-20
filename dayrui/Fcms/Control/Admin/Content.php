<?php namespace Phpcmf\Admin;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * http://www.tianruixinxi.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */




// 内容维护
class Content extends \Phpcmf\Common
{
    public $module; // 模块信息

    public function __construct(...$params) {
        parent::__construct(...$params);
        // 初始化模块
        APP_DIR && $this->_module_init(APP_DIR);
    }

    // ========================

    // 综合
    protected function _Index() {
        \Phpcmf\Service::V()->assign([
            'bm' => [
                array('name' => dr_lang('主表'), 'table' => $this->content_model->mytable),
                array('name' => dr_lang('附表'), 'table' => $this->content_model->mytable.'_data_{id}'),
                array('name' => dr_lang('栏目主表'), 'table' => $this->content_model->mytable.'_category_data'),
                array('name' => dr_lang('栏目附表'), 'table' => $this->content_model->mytable.'_category_data_{id}'),
                array('name' => dr_lang('评论表'), 'table' => $this->content_model->mytable.'_comment'),
            ],
            'form' =>  dr_form_hidden(),
            'select' => \Phpcmf\Service::L('Tree')->select_category($this->module['category'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
        ]);
        \Phpcmf\Service::V()->display('share_content_index.html');
    }

    // 更新内容url
    protected function _Url() {

        $page = max(1, (int)\Phpcmf\Service::L('Input')->get('page'));
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('Input')->get('total');

        // 计算数量
        !$total && $total = \Phpcmf\Service::M()->db->table($this->content_model->mytable.'_index')->where('status', 9)->countAllResults();
        !$total && $this->_html_msg(0, dr_lang('无可用内容更新'));

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        $page > $tpage && $this->_html_msg(1, dr_lang('更新完成'));

        $data = \Phpcmf\Service::M()->db->table($this->content_model->mytable)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $t) {
            if ($t['link_id'] && $t['link_id'] >= 0) {
                // 同步栏目的数据
                $i = $t['id'];
                $t = \Phpcmf\Service::M()->db->table($this->content_model->mytable)->where('id', (int)$t['link_id'])->get()->getRowArray();
                if (!$t) {
                    continue;
                }
                $url =\Phpcmf\Service::L('Router')->show_url($this->module, $t);
                $t['id'] = $i; // 替换成当前id
            } else {
                $url =\Phpcmf\Service::L('Router')->show_url($this->module, $t);
            }
            $this->content_model->update_url($t, $url);
        }

        $this->_html_msg(
            1,
            dr_lang('正在执行中【%s】...', "$tpage/$page"),
           \Phpcmf\Service::L('Router')->url(APP_DIR.'/content/'.\Phpcmf\Service::L('Router')->method, array('total' => $total, 'page' => $page + 1))
        );
        
    }


    // 提取tag
    protected function _Tag() {

        $page = max(1, (int)\Phpcmf\Service::L('Input')->get('page'));
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('Input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('Input')->get('catid');

        $url =\Phpcmf\Service::L('Router')->url(APP_DIR.'/content/'.\Phpcmf\Service::L('Router')->method);
        
        // 获取生成栏目
        if ($catid) {
            $cat = '';
            foreach ($catid as $i) {
                $i && $cat.= intval($i).',';
                $i && $url.= '&catid[]='.intval($i);
            }
            $cat && $where.= ' AND catid IN ('.trim($cat, ',').')';
        }

        $keyword = \Phpcmf\Service::L('Input')->get('keyword');
        $keyword && $where.= ' AND keywords=""';
        $url.= '&keywords='.$keyword;

        // 计算数量
        !$total && $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
        !$total && $this->_html_msg(0, dr_lang('无可用内容更新'));

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        $page > $tpage && $this->_html_msg(1, dr_lang('更新完成'));

        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $t) {
            $tag = dr_get_keywords($t['title'].' '.$t['description']);
            if ($tag) {
                \Phpcmf\Service::M()->db->table($table)->where('id', $t['id'])->update(array(
                    'keywords' => $tag
                ));
                if (\Phpcmf\Service::C()->module['setting']['auto_save_tag']) {
                    $this->content_model->auto_save_tag($tag);
                }
            }
        }

        $this->_html_msg(
            1,
            dr_lang('正在执行中【%s】...', "$tpage/$page"),
            $url.'&total='.$total.'&page='.($page+1)
        );

    }


    // 提取缩略图
    protected function _Thumb() {

        $page = max(1, (int)\Phpcmf\Service::L('Input')->get('page'));
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('Input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('Input')->get('catid');

        $url =\Phpcmf\Service::L('Router')->url(APP_DIR.'/content/'.\Phpcmf\Service::L('Router')->method);

        // 获取生成栏目
        if ($catid) {
            $cat = '';
            foreach ($catid as $i) {
                $i && $cat.= intval($i).',';
                $i && $url.= '&catid[]='.intval($i);
            }
            $cat && $where.= ' AND catid IN ('.trim($cat, ',').')';
        }

        $thumb = \Phpcmf\Service::L('Input')->get('thumb');
        $thumb && $where.= ' AND thumb=""';
        $url.= '&thumb='.$thumb;

        // 计算数量
        !$total && $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
        !$total && $this->_html_msg(0, dr_lang('无可用内容更新'));

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        $page > $tpage && $this->_html_msg(1, dr_lang('更新完成'));

        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $t) {
            $row = \Phpcmf\Service::M()->db->table($table.'_data_'.$t['tableid'])->select('content')->where('id', $t['id'])->get()->getRowArray();
            if ($row && preg_match("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|png))\\2/i", $row['content'], $m)) {
                \Phpcmf\Service::M()->db->table($table)->where('id', $t['id'])->update(array(
                    'thumb' => $m[3]
                ));
            }

        }

        $this->_html_msg(
            1,
            dr_lang('正在执行中【%s】...', "$tpage/$page"),
            $url.'&total='.$total.'&page='.($page+1)
        );

    }

    // 模块下的内容维护
    protected function _Replace_Module() {

        $tables = [];
        $bm = \Phpcmf\Service::L('Input')->post('bm');
        if (strpos($bm, '{id}')) {
            for ($i = 0; $i < 200; $i ++) {
                $table = str_replace('{id}', $i, $bm);
                if (!\Phpcmf\Service::M()->db->query("SHOW TABLES LIKE '".\Phpcmf\Service::M()->dbprefix($table)."'")->getRowArray()) {
                    break;
                }
                $tables[$table] = \Phpcmf\Service::M()->db->getFieldNames($table);
            }
        } else {
            $tables[$bm] = \Phpcmf\Service::M()->db->getFieldNames($bm);;
        }

        $this->_Replace_Table($tables);
    }

    // 共享的内容维护
    protected function _Replace() {

        $tables = [];
        $bm = \Phpcmf\Service::L('Input')->post('bm');
        !$bm && $this->_json(0, dr_lang('表名称必须填写'));
        !\Phpcmf\Service::M()->db->tableExists($bm) && $this->_json(0, dr_lang('表名[%s]不存在', $bm));
        $tables[$bm] = \Phpcmf\Service::M()->db->getFieldNames($bm);

        $this->_Replace_Table($tables);
    }

    // 内容维护处理
    protected function _Replace_Table($tables) {

        $t1 = \Phpcmf\Service::L('Input')->post('t1');
        $t2 = \Phpcmf\Service::L('Input')->post('t2');
        $fd = dr_safe_replace(\Phpcmf\Service::L('Input')->post('fd'));

        !$fd && $this->_json(0, dr_lang('待替换字段必须填写'));
        !$t1 && $this->_json(0, dr_lang('被替换内容必须填写'));
        !$tables && $this->_json(0, dr_lang('表名称必须填写'));
        $fd == 'id' && $this->_json(0, dr_lang('主键不支持替换'));

        $count = 0;
        $replace = '`'.$fd.'`=REPLACE(`'.$fd.'`, \''.addslashes($t1).'\', \''.addslashes($t2).'\')';

        foreach ($tables as $table => $fields) {

            !in_array($fd, $fields) && $this->_json(0, dr_lang('字段[%s]不存在', $fd));

            \Phpcmf\Service::M()->db->query('UPDATE `'.\Phpcmf\Service::M()->dbprefix($table).'` SET '.$replace);
            $count = \Phpcmf\Service::M()->db->affectedRows();

        }

        if ($count < 0) {
            $this->_json(0, dr_lang('执行错误'));
        }

        $this->_json(1, dr_lang('本次替换%s条数据', $count));
    }

    // 执行sql
    protected function _Sql() {
        
        $sql = \Phpcmf\Service::L('Input')->post('sql');
        if (preg_match('/select(.*)into outfile(.*)/i', $sql)) {
            $this->_json(0, dr_lang('存在非法select'));
        } elseif (preg_match('/select(.*)into dumpfile(.*)/i', $sql)) {
            $this->_json(0, dr_lang('存在非法select'));
        } elseif (stripos($sql, 'select ') === 0) {
            // 查询语句
            $db = \Phpcmf\Service::M()->db->query($sql);
            !$db && $this->_json(0, dr_lang('查询出错'));
            $rt = $db->getResultArray();
            $rt && $this->_json(1, var_export($rt, true));
            $rt = \Phpcmf\Service::M()->db->error();
            \Phpcmf\Service::L('File')->add_sql_cache($sql);
            $this->_json(0, $rt['message']);
        } else {
            // 执行语句
            $rt = \Phpcmf\Service::M('Table')->_query($sql);
            !$rt['code'] && $this->_json(0, $rt['msg']);
            list($count, $sqls) = $rt['data'];
            foreach ($sqls as $sql) {
                \Phpcmf\Service::L('File')->add_sql_cache($sql);
            }
            $this->_json(1, dr_lang('本次执行%s条语句', $count));
        }
    }

}

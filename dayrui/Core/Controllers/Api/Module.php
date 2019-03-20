<?php namespace Phpcmf\Controllers\Api;

/**
 * PHPCMF框架文件
 * 二次开发时请勿修改本文件
 * 成都天睿信息技术有限公司 www.phpcmf.net
 */

// 模块ajax操作接口
class Module extends \Phpcmf\Common
{
    private $siteid;
    private $dirname;
    private $tablename;

    protected $content_model;

    public function __construct(...$params) {
        parent::__construct(...$params);
        // 初始化模块
        $this->siteid = (int)\Phpcmf\Service::L('Input')->get('siteid');
        !$this->siteid && $this->siteid = SITE_ID;
        $this->dirname = dr_safe_replace(\Phpcmf\Service::L('Input')->get('app'));
        if (!$this->dirname || !dr_is_app_dir(($this->dirname))) {
            $this->_msg(0, dr_lang('模块目录[%s]不存在', $this->dirname));
            exit;
        }
        $this->tablename = $this->siteid.'_'.$this->dirname;
        $this->content_model = \Phpcmf\Service::M('Content', $this->dirname);
        $this->_module_init($this->dirname, $this->siteid);
    }

    public function index() {
        exit('module api');
    }

    /**
     * 阅读数统计
     */
    public function hits() {

        $id = (int)\Phpcmf\Service::L('Input')->get('id');
        !$id && $this->_jsonp(0, dr_lang('阅读统计: id参数不完整'));

        $data = \Phpcmf\Service::M()->db->table($this->tablename)->where('id', $id)->select('hits,updatetime')->get()->getRowArray();
        !$data && $this->_jsonp(0, dr_lang('阅读统计: 模块内容不存在'));

        $hits = (int)$data['hits'] + 1;

        // 更新主表
        \Phpcmf\Service::M()->db->table($this->tablename)->where('id', $id)->set('hits', $hits)->update();

        // 获取统计数据
        $total = \Phpcmf\Service::M()->db->table($this->tablename.'_hits')->where('id', $id)->get()->getRowArray();
        !$total && $total['day_hits'] = $total['week_hits'] = $total['month_hits'] = $total['year_hits'] = 1;

        // 更新到统计表
        \Phpcmf\Service::M()->db->table($this->tablename.'_hits')->where('id', $id)->update([
            'id' => $id,
            'hits' => $hits,
            'day_hits' => (date('Ymd', $data['updatetime']) == date('Ymd', SYS_TIME)) ? $hits : 1,
            'week_hits' => (date('YW', $data['updatetime']) == date('YW', SYS_TIME)) ? ($total['week_hits'] + 1) : 1,
            'month_hits' => (date('Ym', $data['updatetime']) == date('Ym', SYS_TIME)) ? ($total['month_hits'] + 1) : 1,
            'year_hits' => (date('Ymd', $data['updatetime']) == date('Ymd', strtotime('-1 day'))) ? $hits : $total['year_hits'],
        ]);

        // 输出
        $this->_jsonp(1, $hits);
    }

    /**
     * 收藏模块内容
     */
    public function favorite() {

        if (!dr_is_app('favorite')) {
            $this->_json(0, dr_lang('插件[模块内容收藏]未安装'));
        }

        if (!in_array('favorites', \Phpcmf\Service::M('table')->get_cache_field($this->tablename)) ) {
            $this->_json(0, dr_lang('插件[模块内容收藏]未安装到本模块[%s]', $this->dirname));
        }

        $id = (int)\Phpcmf\Service::L('Input')->get('id');

        !$this->uid && $this->_json(0, dr_lang('还没有登录'));
        !$id && $this->_json(0, dr_lang('id参数不完整'));

        $data = \Phpcmf\Service::M()->db->table($this->tablename.'_index')->where('id', $id)->countAllResults();
        !$data && $this->_json(0, dr_lang('模块内容不存在'));

        $favorite = \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->where('cid', $id)->where('uid', $this->uid)->get()->getRowArray();
        if ($favorite) {
            // 已经收藏了,我们就删除它
            \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->where('id', intval($favorite['id']))->delete();
            $msg = dr_lang('取消收藏');
        } else {
            \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->insert(array(
                'cid' => $id,
                'uid' => $this->uid
            ));
            $msg = dr_lang('收藏成功');
        }

        // 更新数量
        $c = \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->where('cid', $id)->countAllResults();
        \Phpcmf\Service::M()->db->table($this->tablename)->where('id', $id)->set('favorites', $c)->update();
        \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_show_id_'.$id);

        // 返回结果
        $this->_json(1, $msg, $c);
    }

    /**
     * 是否收藏模块内容
     */
    public function is_favorite() {

        if (!dr_is_app('favorite')) {
            $this->_json(0, dr_lang('插件[模块内容收藏]未安装'));
        }

        if (!in_array('favorites', \Phpcmf\Service::M('table')->get_cache_field($this->tablename)) ) {
            $this->_json(0, dr_lang('插件[模块内容收藏]未安装到本模块[%s]', $this->dirname));
        }

        $id = (int)\Phpcmf\Service::L('Input')->get('id');

        !$this->uid && $this->_json(0, dr_lang('还没有登录'));
        !$id && $this->_json(0, dr_lang('id参数不完整'));

        $favorite = \Phpcmf\Service::M()->db->table($this->tablename.'_favorite')->where('cid', $id)->where('uid', $this->uid)->countAllResults();
        if ($favorite) {
            $this->_json(1, '已经收藏');
        } else {
            $this->_json(0, '没有收藏');
        }
    }

    /**
     * 模块内容支持与反对
     */
    public function digg() {

        if (!dr_is_app('zan')) {
            $this->_json(0, dr_lang('插件[模块内容点赞]未安装'));
        }

        if (!in_array('support', \Phpcmf\Service::M('table')->get_cache_field($this->tablename)) ) {
            $this->_json(0, dr_lang('插件[模块内容点赞]未安装到本模块[%s]', $this->dirname));
        }

        if (!in_array('oppose', \Phpcmf\Service::M('table')->get_cache_field($this->tablename)) ) {
            $this->_json(0, dr_lang('插件[模块内容点赞]未安装到本模块[%s]', $this->dirname));
        }

        $id = (int)\Phpcmf\Service::L('Input')->get('id');
        $value = (int)\Phpcmf\Service::L('Input')->get('value');

        !$this->uid && $this->_json(0, dr_lang('还没有登录'));
        !$id && $this->_json(0, dr_lang('id参数不完整'));

        $data = \Phpcmf\Service::M()->db->table($this->tablename.'_index')->where('id', $id)->countAllResults();
        !$data && $this->_json(0, dr_lang('模块内容不存在'));

        $field = $value ? 'support' : 'oppose';
        $table = $this->tablename.'_'.$field;
        $result = \Phpcmf\Service::M()->db->table($table)->where('cid', $id)->where('uid', $this->uid)->get()->getRowArray();

        if ($result) {
            // 已经操作了,我们就删除它
            \Phpcmf\Service::M()->db->table($table)->where('id', intval($result['id']))->delete();
            $msg = dr_lang('操作取消');
        } else {
            \Phpcmf\Service::M()->db->table($table)->insert(array(
                'cid' => $id,
                'uid' => $this->uid
            ));
            $msg = dr_lang('操作成功');
        }

        // 更新数量
        $c = \Phpcmf\Service::M()->db->table($table)->where('cid', $id)->countAllResults();
        \Phpcmf\Service::M()->db->table($this->tablename)->where('id', $id)->set($field, $c)->update();
        \Phpcmf\Service::L('cache')->clear('module_'.MOD_DIR.'_show_id_'.$id);

        // 返回结果
        $this->_json(1, $msg, $c);
    }



}

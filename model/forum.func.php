<?php

// hook model_forum_start.php

// ------------> 最原生的 CURD，无关联其他数据。

function forum__create($arr)
{
    // hook model_forum__create_start.php
    $r = db_create('forum', $arr);
    // hook model_forum__create_end.php
    return $r;
}

function forum__update($fid, $arr)
{
    // hook model_forum__update_start.php
    $r = db_update('forum', array('fid' => $fid), $arr);
    // hook model_forum__update_end.php
    return $r;
}

function forum__read($fid)
{
    // hook model_forum__read_start.php
    $forum = db_find_one('forum', array('fid' => $fid));
    // hook model_forum__read_end.php
    return $forum;
}

function forum__delete($fid)
{
    // hook model_forum__delete_start.php
    $r = db_delete('forum', array('fid' => $fid));
    // hook model_forum__delete_end.php
    return $r;
}

function forum__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 1000)
{
    // hook model_forum__find_start.php
    $forumlist = db_find('forum', $cond, $orderby, $page, $pagesize, 'fid');
    // hook model_forum__find_end.php
    return $forumlist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

function forum_create($arr)
{
    // hook model_forum_create_start.php
    $r = forum__create($arr);
    forum_list_cache_delete();
    // hook model_forum_create_end.php
    return $r;
}

function forum_update($fid, $arr)
{
    // hook model_forum_update_start.php
    $r = forum__update($fid, $arr);
    forum_list_cache_delete();
    // hook model_forum_update_end.php
    return $r;
}

function forum_read($fid)
{
    global $conf, $forumlist;
    // hook model_forum_read_start.php
    if ($conf['cache']['enable']) {
        return empty($forumlist[$fid]) ? array() : $forumlist[$fid];
    } else {
        $forum = forum__read($fid);
        forum_format($forum);
        return $forum;
    }
    // hook model_forum_read_end.php
}

// 关联数据删除
function forum_delete($fid)
{
    global $forumlist;
    //  把板块下所有的内容都查找出来，此处数据量大可能会超时，所以不要删除帖子特别多的板块
    if (empty($fid)) return FALSE;

    $forum = $forumlist[$fid];
    if (empty($forum)) return FALSE;

    // hook model_forum_delete_start.php

    $cond = array('fid' => $fid);
    // 分类 0论坛 1cms
    if ($forum['type'] == 1) {
        $threadlist = thread_tid__find($cond, array(), 1, 1000000, 'tid', array('tid', 'uid'));
        if ($threadlist) {
            foreach ($threadlist as $thread) {
                well_thread_delete_all($thread['tid']);
            }
        }
    }

    // hook model_forum_delete_before.php

    $forum['fup'] AND forum_update($forum['fup'], array('son-' => 1));

    $r = forum__delete($fid);

    forum_access_delete_by_fid($fid);

    forum_list_cache_delete();
    // hook model_forum_delete_end.php
    return $r;
}

function forum_find($cond = array(), $orderby = array('rank' => -1), $page = 1, $pagesize = 1000)
{
    // hook model_forum_find_start.php
    $forumlist = forum__find($cond, $orderby, $page, $pagesize);
    if ($forumlist) {
        foreach ($forumlist as $key => &$forum) {
            forum_format($forum);
            // hook model_forum_find_format.php
        }
    }
    // hook model_forum_find_end.php
    return $forumlist;
}

// ------------> 其他方法

function forum_format(&$forum)
{
    global $conf;

    if (empty($forum)) return;

    // hook model_forum_format_start.php
    $forum['create_date_fmt'] = date('Y-n-j', $forum['create_date']);
    $forum['icon_url'] = $forum['icon'] ? forum_file_path() . "forum/$forum[fid].png" : forum_view_path() . 'img/forum.png';
    $forum['accesslist'] = $forum['accesson'] ? forum_access_find_by_fid($forum['fid']) : array();

    $forum['modlist'] = array();
    if ($forum['moduids']) {
        $modlist = user_find_by_uids($forum['moduids']);
        foreach ($modlist as &$mod) $mod = user_safe_info($mod);
        $forum['modlist'] = $modlist;
    }

    // hook model_forum_format_before.php

    // type = 0BBS 1CMS
    if ($forum['type']) {
        // CMS需要格式化的
        if ($forum['flagstr']) {
            $flaglist = flag_forum_show($forum['fid']);
            if ($flaglist) {
                foreach ($flaglist as $key => $val) {
                    unset($val['fid']);
                    unset($val['rank']);
                    unset($val['count']);
                    unset($val['number']);
                    unset($val['display']);
                    unset($val['create_date']);
                    unset($val['create_date_text']);
                    unset($val['display_text']);
                    unset($val['forum_name']);
                    unset($val['title']);
                    unset($val['keywords']);
                    unset($val['description']);
                    unset($val['forum_url']);
                    unset($val['i']);
                    unset($val['tpl']);
                }
                $forum['flagstr_text'] = array_multisort_key($flaglist, 'rank', FALSE, 'flagid');
            }
        }

        $forum['thumbnail'] = $forum['thumbnail'] ? json_decode($forum['thumbnail'], true) : '';

        // hook model_forum_format_center.php
        if ($forum['model'] == 0){
            if ($forum['category'] == 0) {
                $forum['url'] = url('list-' . $forum['fid']);
            } elseif ($forum['category'] == 1) {
                $forum['url'] = url('category-' . $forum['fid']);
            } elseif ($forum['category'] == 2) {
                $forum['url'] = $forum['threads'] ? url('read-' . trim($forum['brief'])) : url('list-' . $forum['fid']);
            } elseif ($forum['category'] == 3) {
                $forum['url'] = url('list-' . $forum['fid']);
            }
            // hook model_forum_format_url.php
        }

        // hook model_forum_format_model_after.php
    }

    // hook model_forum_format_end.php
}

function forum_view_path()
{
    static $path = '';
    if ($path) return $path;
    $conf = _SERVER('conf');
    if ($conf['view_url'] == 'view/') {
        $path = $conf['url_rewrite_on'] > 1 ? $conf['path'] . $conf['view_url'] : $conf['view_url'];
    } else {
        $path = $conf['view_url']; // 云储存
    }
    return $path;
}

function forum_file_path()
{
    static $path = '';
    if ($path) return $path;
    $conf = _SERVER('conf');
    if ($conf['attach_on'] == 0) {
        // 本地
        $path = $conf['url_rewrite_on'] > 1 ? file_path() : $conf['upload_url'];
    } elseif ($conf['attach_on'] == 1) {
        // 云储存
        $path = file_path();
    } elseif ($conf['attach_on'] == 2) {
        // 云储存
        $path = file_path();
    }
    return $path;
}

function forum_admin_format($forumlist)
{
    global $conf;
    if (empty($forumlist)) return array();
    // hook model_forum_admin_format_start.php
    foreach ($forumlist as &$forum) {
        $forum['icon_url'] = $forum['icon'] ? admin_file_path() . "forum/$forum[fid].png" : admin_view_path() . 'img/forum.png';
        // hook model_forum_admin_format_before.php
    }
    // hook model_forum_admin_format_end.php
    return $forumlist;
}

function forum_count($cond = array())
{
    // hook model_forum_count_start.php
    $n = db_count('forum', $cond);
    // hook model_forum_count_end.php
    return $n;
}

function forum_maxid()
{
    // hook model_forum_maxid_start.php
    $n = db_maxid('forum', 'fid');
    // hook model_forum_maxid_end.php
    return $n;
}

// 从缓存中读取 forum_list 数据x
function forum_list_cache()
{
    global $conf;
    // hook model_forum_list_cache_start.php
    if ($conf['cache']['type'] == 'mysql') {
        $forumlist = forum_list_get();
    } else {
        $forumlist = cache_get('forumlist');
        if ($forumlist === NULL) {
            $forumlist = forum_find();
            cache_set('forumlist', $forumlist, 7200);
        }
    }
    // hook model_forum_list_cache_end.php
    return $forumlist;
}

// 更新 forumlist 缓存
function forum_list_cache_delete()
{
    global $conf;
    static $deleted = FALSE;
    if ($deleted) return;
    // hook model_forum_list_cache_delete_start.php
    $conf['cache']['type'] == 'mysql' ? forum_list_delete_cache() : cache_delete('forumlist');
    $deleted = TRUE;
    // 删除首页所有缓存
    cache_delete('portal_index_thread');
    // hook model_forum_list_cache_delete_end.php
}

// 对 $forumlist 权限过滤，查看权限没有，则隐藏
function forum_list_access_filter($forumlist, $gid, $allow = 'allowread')
{
    global $grouplist;

    // hook model_website_forum_list_access_filter_start.php

    if (empty($forumlist)) return array();

    // hook model_forum_list_access_filter_before.php

    if ($gid == 1) return $forumlist;

    $forumlist_filter = $forumlist;
    $group = $grouplist[$gid];

    // hook model_forum_list_access_filter_start.php

    foreach ($forumlist_filter as $fid => $forum) {

        // hook model_forum_list_access_filter_foreach_start.php

        if (empty($forum['accesson']) && empty($group[$allow]) || !empty($forum['accesson']) && empty($forum['accesslist'][$gid][$allow])) {

            // hook model_forum_list_access_filter_foreach_before.php

            unset($forumlist_filter[$fid]);
            unset($forumlist_filter[$fid]['modlist']);

            // hook model_forum_list_access_filter_foreach_after.php
        }
        unset($forumlist_filter[$fid]['accesslist']);
        // hook model_forum_list_access_filter_foreach_end.php
    }
    // hook model_forum_list_access_filter_end.php
    return $forumlist_filter;
}

function forum_filter_moduid($moduids)
{
    $moduids = trim($moduids);
    if (empty($moduids)) return '';
    $arr = explode(',', $moduids);
    $r = array();
    foreach ($arr as $_uid) {
        $_uid = intval($_uid);
        $_user = user_read($_uid);
        if (empty($_user)) continue;
        if ($_user['gid'] > 4) continue;
        $r[] = $_uid;
    }
    return implode(',', $r);
}

function forum_safe_info($forum)
{
    // hook model_forum_safe_info_start.php
    //unset($forum['moduids']);
    // hook model_forum_safe_info_end.php
    return $forum;
}

function forum_filter($forumlist)
{
    // hook model_forum_filter_start.php
    foreach ($forumlist as &$val) {
        unset($val['brief']);
        unset($val['moduids']);
        unset($val['announcement']);
        unset($val['threads']);
        unset($val['tops']);
        unset($val['seo_title']);
        unset($val['seo_keywords']);
        unset($val['create_date_fmt']);
        unset($val['accesslist']);
        unset($val['icon_url']);
        unset($val['modlist']);
        unset($val['create_date_text']);
        // hook model_forum_filter_after.php
    }
    // hook model_forum_filter_end.php
    return $forumlist;
}

function forum_format_url($forum)
{
    global $conf;
    // hook model_forum_format_url_start.php
    if ($forum['category'] == 0) {
        // 列表URL
        // hook model_forum_format_url_list_before.php
        $url = url('list-' . $forum['fid']);
        // hook model_forum_format_url_list_after.php
    } elseif ($forum['category'] == 1) {
        // 频道
        // hook model_forum_format_url_category_before.php
        $url = url('category-' . $forum['fid']);
        // hook model_forum_format_url_category_after.php
    } elseif ($forum['category'] == 2) {
        // 单页
        // hook model_forum_format_url_read_before.php
        $url = url('read-' . trim($forum['brief']));
        // hook model_forum_format_url_read_after.php
    }
    // hook model_forum_format_url_end.php
    return $url;
}

//--------------------------kv + cache--------------------------

$g_forumlist = FALSE;
function forum_list_get()
{
    global $g_forumlist;
    $g_forumlist === FALSE AND $g_forumlist = website_get('forumlist');
    if (empty($g_forumlist)) {
        $g_forumlist = forum_find();
        $g_forumlist AND forum_list_set($g_forumlist);
    }
    return $g_forumlist;
}

// set kv cache
function forum_list_set($val)
{
    global $g_forumlist;
    $g_forumlist = $val;
    return website_set('forumlist', $g_forumlist);
}

function forum_list_delete_cache()
{
    website_set('forumlist', '');
    return TRUE;
}

// hook model_forum_end.php

?>

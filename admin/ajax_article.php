<?php
header('Content-Type:application/json; charset=utf-8');
include_once("../include/common.php");

if (!isset($islogin) || $islogin !== 1) {
    exit(json_encode(array('code' => 100, 'msg' => '未登录或登录已过期！')));
}
$submit = isset($_GET['submit']) ? $_GET['submit'] : null;
$e = 0;

// 统一 JSON 输出，避免响应头与内容格式不一致导致前端解析失败
function out_json($msg, $code = 200)
{
    exit(json_encode(array('code' => $code, 'msg' => $msg), JSON_UNESCAPED_UNICODE));
}

// 按字符数截断文本并追加省略号，防止超出数据库字段长度
function truncate_text($str, $max)
{
    $str = (string)$str;
    $max = max(1, (int)$max);
    if (function_exists('mb_strlen')) {
        if (mb_strlen($str, 'UTF-8') > $max) {
            return mb_substr($str, 0, max(0, $max - 3), 'UTF-8') . '...';
        }
    } elseif (strlen($str) > $max) {
        return substr($str, 0, max(0, $max - 3)) . '...';
    }
    return $str;
}

// 重算指定文章的已通过评论数(含归零), 保证 art_comments 与实际一致
function article_recalc_comments($artIds)
{
    global $DB;
    $artIds = array_values(array_filter(array_map('intval', (array)$artIds)));
    if (empty($artIds)) {
        return;
    }
    $list = implode(',', $artIds);
    $DB->query("UPDATE `lylme_article` SET `art_comments` = (SELECT COUNT(*) FROM `lylme_article_comment` WHERE `lylme_article_comment`.`art_id` = `lylme_article`.`art_id` AND `com_status` = 1) WHERE `art_id` IN ($list)");
}

// 取出指定评论集合涉及的 art_id 列表(用于变更前采集影响范围)
function article_comment_art_ids($id_list)
{
    global $DB;
    $affected = array();
    $rows = $DB->query("SELECT DISTINCT `art_id` FROM `lylme_article_comment` WHERE `com_id` IN ($id_list) OR `com_pid` IN ($id_list)");
    while ($rows && ($r = $DB->fetch($rows))) {
        $affected[] = intval($r['art_id']);
    }
    return $affected;
}

switch ($submit) {

    /* ==================== 文章管理 ==================== */

    case 'add_article':
        $title = daddslashes($_POST['art_title']);
        if ($title == null or $title == '') {
            out_json('保存错误，文章标题不能为空！', 100);
        } else {
            $cat_id = intval(isset($_POST['cat_id']) ? $_POST['cat_id'] : 0);
            $art_slug = daddslashes(trim($_POST['art_slug']));
            $art_author = daddslashes($_POST['art_author'] ?: '管理员');
            $art_content = daddslashes($_POST['art_content']);
            $art_excerpt = truncate_text(daddslashes($_POST['art_excerpt']), 500);
            $art_cover = daddslashes($_POST['art_cover']);
            $art_keywords = truncate_text(daddslashes($_POST['art_keywords']), 255);
            $art_description = truncate_text(daddslashes($_POST['art_description']), 255);
            $art_top = intval(isset($_POST['art_top']) ? $_POST['art_top'] : 0);
            $art_status = intval(isset($_POST['art_status']) ? $_POST['art_status'] : 1);
            $art_allow_comment = intval(isset($_POST['art_allow_comment']) ? $_POST['art_allow_comment'] : 1);
            $art_time = daddslashes(str_replace('T', ' ', $_POST['art_time']));
            if (empty($art_time) || $art_time == '') {
                $art_time = date('Y-m-d H:i:s');
            }
            $sql = "INSERT INTO `lylme_article` (`art_id`, `cat_id`, `art_title`, `art_slug`, `art_author`, `art_content`, `art_excerpt`, `art_cover`, `art_keywords`, `art_description`, `art_views`, `art_likes`, `art_comments`, `art_top`, `art_status`, `art_allow_comment`, `art_time`, `art_update`) VALUES (NULL, '$cat_id', '$title', '$art_slug', '$art_author', '$art_content', '$art_excerpt', '$art_cover', '$art_keywords', '$art_description', 0, 0, 0, '$art_top', '$art_status', '$art_allow_comment', '$art_time', '$art_time');";
            if ($DB->query($sql)) {
                out_json('添加文章 ' . $title . ' 成功！');
            } else {
                out_json('添加文章失败', 100);
            }
        }
        break;

    case 'edit_article':
        $id = intval($_GET['id']);
        $rows2 = $DB->query("select * from lylme_article where art_id='$id' limit 1");
        $rows = $DB->fetch($rows2);
        if (!$rows) {
            out_json('该条记录不存在！', 100);
        }
        $title = daddslashes($_POST['art_title']);
        if ($title == null or $title == '') {
            out_json('保存错误，文章标题不能为空！', 100);
        } else {
            $cat_id = intval(isset($_POST['cat_id']) ? $_POST['cat_id'] : 0);
            $art_slug = daddslashes(trim($_POST['art_slug']));
            $art_author = daddslashes($_POST['art_author'] ?: '管理员');
            $art_content = daddslashes($_POST['art_content']);
            $art_excerpt = truncate_text(daddslashes($_POST['art_excerpt']), 500);
            $art_cover = daddslashes($_POST['art_cover']);
            $art_keywords = truncate_text(daddslashes($_POST['art_keywords']), 255);
            $art_description = truncate_text(daddslashes($_POST['art_description']), 255);
            $art_top = intval(isset($_POST['art_top']) ? $_POST['art_top'] : 0);
            $art_status = intval(isset($_POST['art_status']) ? $_POST['art_status'] : 1);
            $art_allow_comment = intval(isset($_POST['art_allow_comment']) ? $_POST['art_allow_comment'] : 1);
            $art_time = daddslashes(str_replace('T', ' ', $_POST['art_time']));
            if (empty($art_time) || $art_time == '') {
                $art_time = date('Y-m-d H:i:s');
            }
            $art_update = date('Y-m-d H:i:s');
            $sql = "UPDATE `lylme_article` SET `cat_id`='$cat_id', `art_title`='$title', `art_slug`='$art_slug', `art_author`='$art_author', `art_content`='$art_content', `art_excerpt`='$art_excerpt', `art_cover`='$art_cover', `art_keywords`='$art_keywords', `art_description`='$art_description', `art_top`='$art_top', `art_status`='$art_status', `art_allow_comment`='$art_allow_comment', `art_time`='$art_time', `art_update`='$art_update' WHERE `art_id`='$id';";
            if ($DB->query($sql)) {
                out_json('修改文章 ' . $title . ' 成功！');
            } else {
                out_json('修改文章失败', 100);
            }
        }
        break;

    case 'del_article':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要删除的文章', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("DELETE FROM `lylme_article` WHERE `art_id` IN ($id_list)")) {
            $DB->query("DELETE FROM `lylme_article_comment` WHERE `art_id` IN ($id_list)");
            out_json('删除文章成功！');
        } else {
            out_json('删除文章失败', 100);
        }
        break;

    /* ==================== 独立页面管理 ==================== */

    case 'add_page':
        $title = daddslashes($_POST['page_title']);
        if ($title == null or $title == '') {
            out_json('保存错误，页面标题不能为空！', 100);
        } else {
            $slug = daddslashes(trim($_POST['page_slug']));
            if ($slug == '') {
                $slug = daddslashes(trim($title));
            }
            $page_content = daddslashes($_POST['page_content']);
            $page_excerpt = truncate_text(daddslashes($_POST['page_excerpt']), 500);
            $page_status = intval(isset($_POST['page_status']) ? $_POST['page_status'] : 1);
            $page_order = intval(isset($_POST['page_order']) ? $_POST['page_order'] : 10);
            $time = date('Y-m-d H:i:s');
            $sql = "INSERT INTO `lylme_article_page` (`page_id`, `page_title`, `page_slug`, `page_content`, `page_excerpt`, `page_status`, `page_order`, `page_time`, `page_update`) VALUES (NULL, '$title', '$slug', '$page_content', '$page_excerpt', '$page_status', '$page_order', '$time', '$time')";
            if ($DB->query($sql)) {
                out_json('添加页面 ' . $title . ' 成功！');
            } else {
                out_json('添加页面失败', 100);
            }
        }
        break;

    case 'edit_page':
        $pid = intval($_GET['id']);
        $prows = $DB->query("SELECT * FROM `lylme_article_page` WHERE `page_id` = '{$pid}' LIMIT 1");
        $prow = $DB->fetch($prows);
        if (!$prow) {
            out_json('该页面不存在！', 100);
        }
        $title = daddslashes($_POST['page_title']);
        if ($title == null or $title == '') {
            out_json('保存错误，页面标题不能为空！', 100);
        } else {
            $slug = daddslashes(trim($_POST['page_slug']));
            if ($slug == '') {
                $slug = daddslashes(trim($title));
            }
            $page_content = daddslashes($_POST['page_content']);
            $page_excerpt = truncate_text(daddslashes($_POST['page_excerpt']), 500);
            $page_status = intval(isset($_POST['page_status']) ? $_POST['page_status'] : 1);
            $page_order = intval(isset($_POST['page_order']) ? $_POST['page_order'] : 10);
            $time = date('Y-m-d H:i:s');
            $sql = "UPDATE `lylme_article_page` SET `page_title` = '$title', `page_slug` = '$slug', `page_content` = '$page_content', `page_excerpt` = '$page_excerpt', `page_status` = '$page_status', `page_order` = '$page_order', `page_update` = '$time' WHERE `page_id` = '{$pid}'";
            if ($DB->query($sql)) {
                out_json('修改页面 ' . $title . ' 成功！');
            } else {
                out_json('修改页面失败', 100);
            }
        }
        break;

    case 'del_page':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要删除的页面', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("DELETE FROM `lylme_article_page` WHERE `page_id` IN ($id_list)")) {
            out_json('删除页面成功！');
        } else {
            out_json('删除页面失败', 100);
        }
        break;

    case 'on_page':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要发布的页面', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("UPDATE `lylme_article_page` SET `page_status` = 1 WHERE `page_id` IN ($id_list)")) {
            out_json('页面已发布！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    case 'off_page':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要转为草稿的页面', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("UPDATE `lylme_article_page` SET `page_status` = 0 WHERE `page_id` IN ($id_list)")) {
            out_json('页面已转为草稿！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    case 'on_article':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要发布的文章', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("UPDATE `lylme_article` SET `art_status`=1 WHERE `art_id` IN ($id_list)")) {
            out_json('文章已发布！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    case 'off_article':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要转为草稿的文章', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("UPDATE `lylme_article` SET `art_status`=0 WHERE `art_id` IN ($id_list)")) {
            out_json('文章已转为草稿！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    case 'top_article':
        $art_top = intval(isset($_GET['art_top']) ? $_GET['art_top'] : 1);
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要操作的文章', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("UPDATE `lylme_article` SET `art_top`='$art_top' WHERE `art_id` IN ($id_list)")) {
            out_json($art_top == 1 ? '文章已置顶！' : '文章已取消置顶！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    /* ==================== 分类管理 ==================== */

    case 'add_cat':
        $name = daddslashes($_POST['cat_name']);
        if ($name == null or $name == '') {
            out_json('保存错误，分类名称不能为空！', 100);
        } else {
            $cat_alias = daddslashes(trim($_POST['cat_alias']));
            $cat_desc = truncate_text(daddslashes($_POST['cat_desc']), 255);
            $cat_order = intval(isset($_POST['cat_order']) ? $_POST['cat_order'] : 10);
            $cat_status = intval(isset($_POST['cat_status']) ? $_POST['cat_status'] : 1);
            $cat_time = date('Y-m-d H:i:s');
            $sql = "INSERT INTO `lylme_article_cat` (`cat_id`, `cat_name`, `cat_alias`, `cat_desc`, `cat_order`, `cat_status`, `cat_time`) VALUES (NULL, '$name', '$cat_alias', '$cat_desc', '$cat_order', '$cat_status', '$cat_time');";
            if ($DB->query($sql)) {
                out_json('添加分类 ' . $name . ' 成功！');
            } else {
                out_json('添加分类失败', 100);
            }
        }
        break;

    case 'edit_cat':
        $id = intval($_GET['id']);
        $rows2 = $DB->query("select * from lylme_article_cat where cat_id='$id' limit 1");
        $rows = $DB->fetch($rows2);
        if (!$rows) {
            out_json('该条记录不存在！', 100);
        }
        $name = daddslashes($_POST['cat_name']);
        if ($name == null or $name == '') {
            out_json('保存错误，分类名称不能为空！', 100);
        } else {
            $cat_alias = daddslashes(trim($_POST['cat_alias']));
            $cat_desc = truncate_text(daddslashes($_POST['cat_desc']), 255);
            $cat_order = intval(isset($_POST['cat_order']) ? $_POST['cat_order'] : 10);
            $cat_status = intval(isset($_POST['cat_status']) ? $_POST['cat_status'] : 1);
            $sql = "UPDATE `lylme_article_cat` SET `cat_name`='$name', `cat_alias`='$cat_alias', `cat_desc`='$cat_desc', `cat_order`='$cat_order', `cat_status`='$cat_status' WHERE `cat_id`='$id';";
            if ($DB->query($sql)) {
                out_json('修改分类 ' . $name . ' 成功！');
            } else {
                out_json('修改分类失败', 100);
            }
        }
        break;

    case 'del_cat':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要删除的分类', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("DELETE FROM `lylme_article_cat` WHERE `cat_id` IN ($id_list)")) {
            $DB->query("UPDATE `lylme_article` SET `cat_id`=0 WHERE `cat_id` IN ($id_list)");
            out_json('删除分类成功！');
        } else {
            out_json('删除分类失败', 100);
        }
        break;

    case 'on_cat':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要启用的分类', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("UPDATE `lylme_article_cat` SET `cat_status`=1 WHERE `cat_id` IN ($id_list)")) {
            out_json('分类已启用！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    case 'off_cat':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要禁用的分类', 100); }
        $id_list = implode(',', $ids);
        if ($DB->query("UPDATE `lylme_article_cat` SET `cat_status`=0 WHERE `cat_id` IN ($id_list)")) {
            out_json('分类已禁用！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    /* ==================== 评论管理 ==================== */

    case 'on_comment':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要审核通过的评论', 100); }
        $id_list = implode(',', $ids);
        $affected = article_comment_art_ids($id_list);
        if ($DB->query("UPDATE `lylme_article_comment` SET `com_status`=1 WHERE `com_id` IN ($id_list)")) {
            article_recalc_comments($affected);
            out_json('评论已审核通过！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    case 'off_comment':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要取消审核的评论', 100); }
        $id_list = implode(',', $ids);
        $affected = article_comment_art_ids($id_list);
        if ($DB->query("UPDATE `lylme_article_comment` SET `com_status`=0 WHERE `com_id` IN ($id_list)")) {
            article_recalc_comments($affected);
            out_json('评论已取消审核！');
        } else {
            out_json('操作失败', 100);
        }
        break;

    case 'del_comment':
        $ids = array_filter(array_map('intval', explode(',', $_GET['id'])));
        if (empty($ids)) { out_json('请选择要删除的评论', 100); }
        $id_list = implode(',', $ids);
        $affected = article_comment_art_ids($id_list);
        if ($DB->query("DELETE FROM `lylme_article_comment` WHERE `com_id` IN ($id_list) OR `com_pid` IN ($id_list)")) {
            article_recalc_comments($affected);
            out_json('删除评论成功！');
        } else {
            out_json('删除评论失败', 100);
        }
        break;

    case 'reply_comment':
        // 管理员回复评论
        $pid = intval(isset($_POST['com_id']) ? $_POST['com_id'] : 0);
        $replyContent = trim(isset($_POST['reply_content']) ? $_POST['reply_content'] : '');
        if ($pid <= 0) { out_json('参数错误', 100); }
        if ($replyContent === '') { out_json('回复内容不能为空', 100); }
        $parentRow = $DB->get_row("SELECT * FROM `lylme_article_comment` WHERE `com_id` = {$pid} LIMIT 1");
        if (!$parentRow) { out_json('被回复的评论不存在', 100); }
        // 读取管理员显示名称配置，兜底为"管理员"
        $adminName = trim((string)$DB->get_column("SELECT `v` FROM `lylme_article_config` WHERE `k` = 'article_name'"));
        if ($adminName === '') { $adminName = '管理员'; }
        $artId = intval($parentRow['art_id']);
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $insertData = array(
            'art_id'     => $artId,
            'com_pid'    => $pid,
            'com_name'   => $adminName,
            'com_email'  => '',
            'com_url'    => '',
            'com_ip'     => $ip,
            'com_agent'  => $agent,
            'com_content'=> $replyContent,
            'com_status' => 1,
            'com_time'   => date('Y-m-d H:i:s'),
        );
        if ($DB->insert_array('lylme_article_comment', $insertData)) {
            // 更新文章评论数
            $commentCount = intval($DB->get_column(
                "SELECT COUNT(*) FROM `lylme_article_comment` WHERE `art_id` = {$artId} AND `com_status` = 1"
            ));
            $DB->query("UPDATE `lylme_article` SET `art_comments` = {$commentCount} WHERE `art_id` = {$artId}");
            out_json('回复成功！');
        } else {
            out_json('回复失败', 100);
        }
        break;

    /* ==================== 文章配置 ==================== */

    case 'save_config':
        // 仅保存前端实际提交的字段，未提交的字段保留数据库原值，
        // 避免直接访问本接口或表单缺字段时误将配置重置为默认值（如 article_status 被改为 0）
        $configs = array();
        if (isset($_POST['article_status'])) {
            $configs['article_status'] = intval($_POST['article_status']);
        }
        if (isset($_POST['article_theme'])) {
            $configs['article_theme'] = daddslashes($_POST['article_theme']);
        }
        if (isset($_POST['article_perpage'])) {
            $configs['article_perpage'] = intval($_POST['article_perpage']);
        }
        if (isset($_POST['article_comment'])) {
            $cm = intval($_POST['article_comment']);
            $configs['article_comment'] = in_array($cm, [0, 1, 2], true) ? $cm : 1;
        }
        if (isset($_POST['article_audit'])) {
            $configs['article_audit'] = intval($_POST['article_audit']);
        }
        if (isset($_POST['article_web_title'])) {
            $configs['article_web_title'] = daddslashes(trim($_POST['article_web_title']));
        }
        if (isset($_POST['article_web_keywords'])) {
            $configs['article_web_keywords'] = daddslashes(trim($_POST['article_web_keywords']));
        }
        if (isset($_POST['article_web_description'])) {
            $configs['article_web_description'] = daddslashes(trim($_POST['article_web_description']));
        }
        if (isset($_POST['article_url_style'])) {
            $urlStyle = $_POST['article_url_style'];
            $configs['article_url_style'] = in_array($urlStyle, array('default', 'post_id', 'id', 'slug', 'custom')) ? $urlStyle : 'default';
        }
        if (isset($_POST['article_url_custom'])) {
            $configs['article_url_custom'] = daddslashes(trim($_POST['article_url_custom']));
        }
        if (isset($_POST['article_name'])) {
            $configs['article_name'] = daddslashes(trim($_POST['article_name']));
        }
        if (isset($_POST['article_member_status'])) {
            $configs['article_member_status'] = intval($_POST['article_member_status']) === 1 ? 1 : 0;
        }
        if (isset($_POST['article_member_verify'])) {
            $configs['article_member_verify'] = intval($_POST['article_member_verify']) === 1 ? 1 : 0;
        }
        if (isset($_POST['article_member_role'])) {
            $configs['article_member_role'] = in_array((string)$_POST['article_member_role'], array('subscriber', 'contributor'), true) ? (string)$_POST['article_member_role'] : 'subscriber';
        }
        if (isset($_POST['article_post_status'])) {
            $configs['article_post_status'] = intval($_POST['article_post_status']) === 1 ? 1 : 0;
        }
        // ---- 邮件服务(SMTP) 配置 ----
        if (isset($_POST['article_mail_status'])) {
            $configs['article_mail_status'] = intval($_POST['article_mail_status']) === 1 ? 1 : 0;
        }
        if (isset($_POST['article_mail_host'])) {
            $configs['article_mail_host'] = daddslashes(trim($_POST['article_mail_host']));
        }
        if (isset($_POST['article_mail_port'])) {
            $port = intval($_POST['article_mail_port']);
            $configs['article_mail_port'] = ($port >= 1 && $port <= 65535) ? $port : 465;
        }
        if (isset($_POST['article_mail_secure'])) {
            $configs['article_mail_secure'] = in_array((string)$_POST['article_mail_secure'], array('ssl', 'tls', 'none'), true) ? (string)$_POST['article_mail_secure'] : 'ssl';
        }
        if (isset($_POST['article_mail_user'])) {
            $configs['article_mail_user'] = daddslashes(trim($_POST['article_mail_user']));
        }
        // 密码/授权码: 仅在提交了非空值时才覆盖, 留空保留库中原值(不回显)
        if (isset($_POST['article_mail_pass']) && trim((string)$_POST['article_mail_pass']) !== '') {
            $configs['article_mail_pass'] = daddslashes(trim((string)$_POST['article_mail_pass']));
        }
        if (isset($_POST['article_mail_from'])) {
            $configs['article_mail_from'] = daddslashes(trim($_POST['article_mail_from']));
        }
        if (isset($_POST['article_mail_from_name'])) {
            $configs['article_mail_from_name'] = daddslashes(trim($_POST['article_mail_from_name']));
        }
        if (empty($configs)) {
            out_json('没有需要保存的配置项', 100);
        }
        foreach ($configs as $k => $v) {
            $v = $DB->escape($v);
            $DB->query("INSERT INTO `lylme_article_config` (`k`, `v`) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE `v` = '$v'");
        }
        out_json('保存设置成功！');
        break;

    case 'test_mail':
        // 使用已保存的 SMTP 配置发送一封测试邮件
        $testTo = isset($_POST['to']) ? trim((string)$_POST['to']) : '';
        if ($testTo === '' || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            out_json('收件邮箱格式不正确', 100);
        }
        $mc = array();
        $mres = $DB->query("SELECT `k`, `v` FROM `lylme_article_config` WHERE `k` LIKE 'article\_mail\_%'");
        if ($mres) {
            while ($mr = $DB->fetch($mres)) { $mc[$mr['k']] = $mr['v']; }
        }
        $mHost = isset($mc['article_mail_host']) ? trim($mc['article_mail_host']) : '';
        $mFrom = isset($mc['article_mail_from']) ? trim($mc['article_mail_from']) : '';
        $mStatus = intval(isset($mc['article_mail_status']) ? $mc['article_mail_status'] : 0);
        if ($mStatus !== 1 || $mHost === '' || $mFrom === '') {
            out_json('邮件服务未启用或未配置（需开启“启用 SMTP”并填写服务器与发件邮箱）', 100);
        }
        if (!defined('__TYPECHO_ROOT_DIR__')) {
            define('__TYPECHO_ROOT_DIR__', dirname(__DIR__) . '/article/');
        }
        require_once dirname(__DIR__) . '/article/compat/Mailer.php';
        $mName = isset($mc['article_mail_from_name']) ? trim($mc['article_mail_from_name']) : '';
        if ($mName === '') { $mName = 'LyLme Spage'; }
        $mailer = new \Compat\Mailer(array(
            'host'   => $mHost,
            'port'   => intval(isset($mc['article_mail_port']) ? $mc['article_mail_port'] : 465),
            'secure' => isset($mc['article_mail_secure']) ? $mc['article_mail_secure'] : 'ssl',
            'user'   => isset($mc['article_mail_user']) ? $mc['article_mail_user'] : '',
            'pass'   => isset($mc['article_mail_pass']) ? $mc['article_mail_pass'] : '',
        ));
        $html = '<div style="font-family:Arial,Microsoft YaHei,sans-serif;line-height:1.8;color:#333">'
            . '<p>这是一封来自 <b>' . htmlspecialchars($mName, ENT_QUOTES, 'UTF-8') . '</b> 的 SMTP 测试邮件。</p>'
            . '<p>如果你收到本邮件，说明邮件发送服务配置正确，会员邮箱验证邮件也可以正常送达。</p>'
            . '<p style="color:#888;font-size:13px">发送时间：' . date('Y-m-d H:i:s') . '</p></div>';
        if ($mailer->send($mFrom, $mName, $testTo, 'SMTP 测试邮件 - ' . $mName, $html)) {
            out_json('测试邮件已发送至 ' . $testTo . '，请查收（含垃圾箱）');
        }
        out_json('发送失败：' . $mailer->getError(), 100);
        break;

    /* ==================== 主题自定义配置 ==================== */

    case 'save_theme_config':
        // 主题名白名单过滤，防止路径穿越
        $theme = preg_replace('/[^a-zA-Z0-9_-]/', '', isset($_POST['theme']) ? (string)$_POST['theme'] : '');
        if ($theme === '') {
            // 未传主题名时回退到当前已配置主题
            $theme = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$DB->get_column("SELECT `v` FROM `lylme_article_config` WHERE `k` = 'article_theme'")));
            if ($theme === '') $theme = 'default';
        }
        $themeFunctions = dirname(__DIR__) . '/article/theme/' . $theme . '/functions.php';
        if (!is_file($themeFunctions)) {
            out_json('主题 ' . $theme . ' 不存在！', 100);
        }

        // 初始化 Typecho 兼容层 (仅需类定义与 _t, 无需 App::init)
        if (!defined('__TYPECHO_ROOT_DIR__')) {
            define('__TYPECHO_ROOT_DIR__', dirname(__DIR__) . '/article/');
        }
        require_once dirname(__DIR__) . '/article/compat/bootstrap.php';

        // 构建表单，获取主题声明的配置项
        $form = new \Typecho\Widget\Helper\Form();
        require_once $themeFunctions;
        if (function_exists('themeConfig')) {
            themeConfig($form);
        }

        // 按表单字段收集 POST 值 (提交值不做 SQL 转义，直接写入 JSON)
        $themeConfigData = array();
        foreach ($form->getItems() as $item) {
            $name = $item->getName();
            if ($name === '') continue;
            if ($item->type === 'submit') continue;

            if ($item->multiMode) {
                // 多选 checkbox (name[] 提交为数组)
                $values = isset($_POST[$name]) ? (array)$_POST[$name] : array();
                $options = (array)$item->getOptions();
                if (!empty($options)) {
                    $values = array_values(array_intersect($values, array_keys($options)));
                }
                $themeConfigData[$name] = $values;
            } else {
                $themeConfigData[$name] = isset($_POST[$name]) ? $_POST[$name] : '';
            }
        }

        // 写入 article/config/theme/{theme}.json
        $configDir = dirname(__DIR__) . '/article/config/theme';
        if (!is_dir($configDir)) {
            if (!@mkdir($configDir, 0755, true)) {
                out_json('保存失败，无法创建配置目录！', 100);
            }
        }
        $configJson = $configDir . '/' . $theme . '.json';
        $json = json_encode($themeConfigData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false || @file_put_contents($configJson, $json) === false) {
            out_json('保存失败，请检查 article/config/theme/' . $theme . '.json 目录写入权限！', 100);
        }
        out_json('保存设置成功！');
        break;

    /* ==================== 插件管理 ==================== */

    case 'activate_plugin':
    case 'deactivate_plugin':
        $plugin = isset($_POST['plugin']) ? (string)$_POST['plugin'] : '';
        $plugin = preg_replace('/[^a-zA-Z0-9_-]/', '', $plugin);
        if ($plugin === '') {
            out_json('插件名称不合法', 100);
        }
        // 初始化兼容层以使用 PluginManager
        if (!defined('__TYPECHO_ROOT_DIR__')) {
            define('__TYPECHO_ROOT_DIR__', dirname(__DIR__) . '/article/');
        }
        require_once dirname(__DIR__) . '/article/compat/bootstrap.php';
        if ($submit === 'activate_plugin') {
            if (\Compat\PluginManager::activate($plugin)) {
                out_json('插件 ' . $plugin . ' 已启用');
            }
            out_json('启用失败', 100);
        } else {
            \Compat\PluginManager::deactivate($plugin);
            out_json('插件 ' . $plugin . ' 已禁用');
        }
        break;

    case 'save_plugin_config':
        $plugin = isset($_POST['plugin']) ? (string)$_POST['plugin'] : '';
        $plugin = preg_replace('/[^a-zA-Z0-9_-]/', '', $plugin);
        if ($plugin === '') {
            out_json('插件名称不合法', 100);
        }
        if (!defined('__TYPECHO_ROOT_DIR__')) {
            define('__TYPECHO_ROOT_DIR__', dirname(__DIR__) . '/article/');
        }
        require_once dirname(__DIR__) . '/article/compat/bootstrap.php';

        $form = \Compat\PluginManager::buildConfigForm($plugin);
        if ($form === null) {
            out_json('插件 ' . $plugin . ' 不存在！', 100);
        }
        $pluginConfigData = array();
        foreach ($form->getItems() as $item) {
            $name = $item->getName();
            if ($name === '' || $item->type === 'submit') {
                continue;
            }
            if ($item->multiMode) {
                $values = isset($_POST[$name]) ? (array)$_POST[$name] : array();
                $options = (array)$item->getOptions();
                if (!empty($options)) {
                    $values = array_values(array_intersect($values, array_keys($options)));
                }
                $pluginConfigData[$name] = $values;
            } else {
                $pluginConfigData[$name] = isset($_POST[$name]) ? $_POST[$name] : '';
            }
        }
        if (!\Compat\PluginManager::saveConfig($plugin, $pluginConfigData)) {
            out_json('保存失败，请检查 article/config/plugins/ 目录写入权限！', 100);
        }
        out_json('保存设置成功！');
        break;

    /* ==================== 会员管理 ==================== */

    case 'add_member':
        $username = isset($_POST['username']) ? trim(strip_tags((string) $_POST['username'])) : '';
        $email    = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $nickname = isset($_POST['nickname']) ? trim(strip_tags((string) $_POST['nickname'])) : '';
        $role     = (isset($_POST['role']) && in_array((string) $_POST['role'], array('subscriber', 'contributor', 'editor'), true)) ? (string) $_POST['role'] : 'subscriber';
        $status   = (isset($_POST['status']) && intval($_POST['status']) === 0) ? 0 : 1;

        if (!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]{3,30}$/u', $username)) {
            out_json('用户名需为 3-30 位字母、数字、下划线或中文', 100);
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            out_json('邮箱格式不正确', 100);
        }
        if (strlen($password) < 6 || strlen($password) > 64) {
            out_json('密码长度需为 6-64 位', 100);
        }
        $uEsc = $DB->escape($username);
        $eEsc = $DB->escape($email);
        if ($DB->get_row("SELECT `uid` FROM `lylme_member` WHERE `username` = '{$uEsc}' LIMIT 1")) {
            out_json('该用户名已存在', 100);
        }
        if ($DB->get_row("SELECT `uid` FROM `lylme_member` WHERE `email` = '{$eEsc}' LIMIT 1")) {
            out_json('该邮箱已被使用', 100);
        }
        if ($nickname === '') {
            $nickname = $username;
        }
        if (function_exists('mb_strlen') && mb_strlen($nickname, 'UTF-8') > 60) {
            $nickname = mb_substr($nickname, 0, 60, 'UTF-8');
        }
        $insert = array(
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nickname' => $nickname,
            'email'    => $email,
            'url'      => '',
            'role'     => $role,
            'status'   => $status,
            'reg_ip'   => isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 64) : '',
            'reg_time' => date('Y-m-d H:i:s'),
        );
        $uid = $DB->insert_array('lylme_member', $insert);
        if ($uid) {
            out_json('会员添加成功，UID: ' . intval($uid));
        }
        out_json('添加失败，请重试', 100);
        break;

    case 'set_member_status':
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $status = (isset($_POST['status']) && intval($_POST['status']) === 1) ? 1 : 0;
        if ($uid <= 0) {
            out_json('参数错误', 100);
        }
        if ($DB->query("UPDATE `lylme_member` SET `status` = {$status}" . ($status === 1 ? ", `verify_token` = ''" : '') . " WHERE `uid` = {$uid}")) {
            out_json($status === 1 ? '已启用' : '已禁用');
        }
        out_json('操作失败', 100);
        break;

    case 'set_member_role':
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $role = (isset($_POST['role']) && in_array((string) $_POST['role'], array('subscriber', 'contributor', 'editor'), true)) ? (string) $_POST['role'] : 'subscriber';
        if ($uid <= 0) {
            out_json('参数错误', 100);
        }
        if ($DB->query("UPDATE `lylme_member` SET `role` = '{$role}' WHERE `uid` = {$uid}")) {
            out_json('角色已更新');
        }
        out_json('操作失败', 100);
        break;

    case 'reset_member_pwd':
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        if ($uid <= 0) {
            out_json('参数错误', 100);
        }
        if (strlen($password) < 6 || strlen($password) > 64) {
            out_json('密码长度需为 6-64 位', 100);
        }
        $hash = $DB->escape(password_hash($password, PASSWORD_DEFAULT));
        if ($DB->query("UPDATE `lylme_member` SET `password` = '{$hash}' WHERE `uid` = {$uid}")) {
            out_json('密码已重置');
        }
        out_json('操作失败', 100);
        break;

    case 'del_member':
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        if ($uid <= 0) {
            out_json('参数错误', 100);
        }
        if ($DB->query("DELETE FROM `lylme_member` WHERE `uid` = {$uid}")) {
            // 历史评论保留 com_uid 作为足迹，不改写(向后兼容)
            out_json('删除成功');
        }
        out_json('删除失败', 100);
        break;

    default:
        out_json('操作不存在', 100);
}

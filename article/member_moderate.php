<?php
/**
 * 文章模块 - 前台编辑审核台 (仅 editor 角色可达)
 * 由 member.php 在 ?member=moderate|audit_post|audit_comment 时引入 (单入口)
 * 依赖 member.php 作用域内的 $DB / $conf / $action / $isPost 与
 *   member_e / member_page / member_safe_redirect, Compat\App, Compat\Member
 *
 * 能力边界: 仅操作 lylme_article / lylme_article_comment; 绝不触及会员、设置、/admin。
 */
if (!defined('ARTICLE_INDEX')) {
    exit;
}

use Compat\App;
use Compat\Member;

if (!function_exists('member_mod_recalc')) {
    /** 重算指定文章的已通过评论数(镜像后台口径) */
    function member_mod_recalc($artIds)
    {
        global $DB;
        $artIds = array_values(array_filter(array_map('intval', (array) $artIds)));
        if (empty($artIds)) {
            return;
        }
        $list = implode(',', $artIds);
        $DB->query("UPDATE `lylme_article` SET `art_comments` = (SELECT COUNT(*) FROM `lylme_article_comment` WHERE `lylme_article_comment`.`art_id` = `lylme_article`.`art_id` AND `com_status` = 1) WHERE `art_id` IN ({$list})");
    }
}

if (!function_exists('member_mod_flash')) {
    function member_mod_flash($msg, $type = 'posts', $ok = 1)
    {
        $_SESSION['member_notice'] = $msg;
        $_SESSION['member_notice_ok'] = $ok ? 1 : 0;
        member_safe_redirect('', App::$articleUrl . '?member=moderate&type=' . rawurlencode($type));
    }
}

if (!function_exists('member_moderate_pager')) {
    function member_moderate_pager($pages, $page, $base, $query)
    {
        if ($pages <= 1) {
            return '';
        }
        $html = '';
        for ($i = 1; $i <= $pages; $i++) {
            if ($i === $page) {
                $html .= '<span class="btn">' . $i . '</span>';
            } else {
                $html .= '<a href="' . member_e($base . '?' . $query . '&page=' . $i) . '">' . $i . '</a>';
            }
        }
        return '<div class="pager">' . $html . '</div>';
    }
}

// ---------- 鉴权: 仅可审核角色 ----------
$member = Member::current();
if (!$member) {
    member_safe_redirect('', App::$articleUrl . '?member=login');
    exit;
}
if (!Member::can('moderate', $member)) {
    member_page('无权限', '<p>仅编辑/管理员可访问审核台。</p>', 'center', '需要编辑角色');
    exit;
}

$base = App::$articleUrl;
$notice = isset($_SESSION['member_notice']) ? (string) $_SESSION['member_notice'] : '';
$noticeOk = isset($_SESSION['member_notice_ok']) ? intval($_SESSION['member_notice_ok']) : 0;
unset($_SESSION['member_notice'], $_SESSION['member_notice_ok']);

// ---------- 审核文章: 通过/驳回 ----------
if ($action === 'audit_post') {
    if (!$isPost) {
        member_safe_redirect('', $base . '?member=moderate&type=posts');
        exit;
    }
    if (!Member::checkCsrf(isset($_POST['_csrf']) ? $_POST['_csrf'] : '')) {
        member_mod_flash('会话已过期，请重试', 'posts', 0);
    }
    $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
    $act = isset($_POST['act']) ? (string) $_POST['act'] : '';
    $row = $id > 0 ? $DB->get_row("SELECT `art_id`,`art_status` FROM `lylme_article` WHERE `art_id` = {$id} LIMIT 1") : null;
    if (!$row) {
        member_mod_flash('文章不存在', 'posts', 0);
    }
    if ($act === 'approve') {
        $DB->query("UPDATE `lylme_article` SET `art_status` = 1, `art_update` = `art_update` WHERE `art_id` = {$id}");
        member_mod_flash('已通过发布', 'posts', 1);
    } elseif ($act === 'reject') {
        $DB->query("UPDATE `lylme_article` SET `art_status` = 0 WHERE `art_id` = {$id}");
        member_mod_flash('已驳回并退回草稿', 'posts', 1);
    } else {
        member_mod_flash('未知操作', 'posts', 0);
    }
}

// ---------- 审核评论: 通过/删除 ----------
if ($action === 'audit_comment') {
    if (!$isPost) {
        member_safe_redirect('', $base . '?member=moderate&type=comments');
        exit;
    }
    if (!Member::checkCsrf(isset($_POST['_csrf']) ? $_POST['_csrf'] : '')) {
        member_mod_flash('会话已过期，请重试', 'comments', 0);
    }
    $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
    $act = isset($_POST['act']) ? (string) $_POST['act'] : '';
    $row = $id > 0 ? $DB->get_row("SELECT `com_id`,`art_id` FROM `lylme_article_comment` WHERE `com_id` = {$id} LIMIT 1") : null;
    if (!$row) {
        member_mod_flash('评论不存在', 'comments', 0);
    }
    $artId = intval($row['art_id']);
    if ($act === 'approve') {
        $DB->query("UPDATE `lylme_article_comment` SET `com_status` = 1 WHERE `com_id` = {$id}");
        member_mod_recalc([$artId]);
        member_mod_flash('评论已通过', 'comments', 1);
    } elseif ($act === 'delete') {
        $DB->query("DELETE FROM `lylme_article_comment` WHERE `com_id` = {$id} OR `com_pid` = {$id}");
        member_mod_recalc([$artId]);
        member_mod_flash('评论已删除', 'comments', 1);
    } else {
        member_mod_flash('未知操作', 'comments', 0);
    }
}

// ---------- 审核台列表 (moderate) ----------
$type = isset($_GET['type']) ? strtolower(trim((string) $_GET['type'])) : 'posts';
if (!in_array($type, ['posts', 'comments', 'all'], true)) {
    $type = 'posts';
}
$pendPosts = intval($DB->count("SELECT COUNT(*) FROM `lylme_article` WHERE `art_status` = 2"));
$pendComments = intval($DB->count("SELECT COUNT(*) FROM `lylme_article_comment` WHERE `com_status` = 0"));

$tabs = '<div class="actions" style="margin-bottom:16px">'
    . '<a href="' . member_e($base . '?member=moderate&type=posts') . '" class="btn' . ($type === 'posts' ? ' pri' : '') . '">待审投稿（' . $pendPosts . '）</a>'
    . '<a href="' . member_e($base . '?member=moderate&type=comments') . '" class="btn' . ($type === 'comments' ? ' pri' : '') . '">待审评论（' . $pendComments . '）</a>'
    . '<a href="' . member_e($base . '?member=moderate&type=all') . '" class="btn' . ($type === 'all' ? ' pri' : '') . '">全部文章</a>'
    . '</div>';

$size = 15;
$page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
$off = ($page - 1) * $size;
$csrf = member_e(Member::csrfToken());

$body = '';
if ($type === 'posts' || $type === 'all') {
    $where = $type === 'posts' ? "WHERE `art_status` = 2" : '';
    $total = intval($DB->count("SELECT COUNT(*) FROM `lylme_article` {$where}"));
    $pages = (int) ceil($total / $size);
    $rowsHtml = '';
    $result = $DB->query(
        "SELECT `art_id`,`art_title`,`art_author`,`art_status`,`art_time`,`art_update` FROM `lylme_article` {$where} "
        . "ORDER BY " . ($type === 'posts' ? "`art_update` ASC, `art_id` ASC" : "`art_time` DESC, `art_id` DESC") . " LIMIT {$off}, {$size}"
    );
    $n = 0;
    while ($result && ($r = $DB->fetch($result))) {
        $n++;
        $rid = intval($r['art_id']);
        $st = intval($r['art_status']);
        $act = '';
        $act .= '<a href="' . member_e($base . '?member=preview&id=' . $rid) . '" class="btn">预览</a> ';
        if ($st === 2) {
            $act .= '<form method="post" action="' . member_e($base . '?member=audit_post') . '" style="display:inline">'
                . '<input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . $rid . '">'
                . '<button type="submit" name="act" value="approve" class="btn pri">通过</button></form> '
                . '<form method="post" action="' . member_e($base . '?member=audit_post') . '" style="display:inline">'
                . '<input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . $rid . '">'
                . '<button type="submit" name="act" value="reject" class="btn">驳回</button></form> ';
        }
        if ($type === 'all') {
            $act .= '<form method="post" action="' . member_e($base . '?member=delete') . '" style="display:inline" onsubmit="return confirm(\'确定删除该文章及其评论？\')">'
                . '<input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . $rid . '">'
                . '<button type="submit" class="btn danger">删除</button></form>';
        }
        $badge = $st === 1 ? '<span class="badge pub">已发布</span>' : ($st === 2 ? '<span class="badge pend">待审核</span>' : '<span class="badge draft">草稿</span>');
        $rowsHtml .= '<tr>'
            . '<td>' . $rid . '</td>'
            . '<td>' . member_e($r['art_title']) . '<br><small class="muted">作者: ' . member_e((string) $r['art_author']) . ' · ' . member_e((string) $r['art_time']) . '</small></td>'
            . '<td>' . $badge . '</td>'
            . '<td><div class="actions">' . $act . '</div></td>'
            . '</tr>';
    }
    if ($n === 0) {
        $empty = $type === 'posts' ? '暂无待审核投稿。' : '暂无文章。';
        $rowsHtml = '<tr><td colspan="4" class="muted" style="text-align:center;padding:24px">' . $empty . '</td></tr>';
    }
    $pager = member_moderate_pager($pages, $page, $base, 'moderate&type=' . $type);
    $body = '<table><thead><tr><th>ID</th><th>标题</th><th>状态</th><th>操作</th></tr></thead><tbody>' . $rowsHtml . '</tbody></table>' . $pager;
} else {
    // comments
    $total = intval($DB->count("SELECT COUNT(*) FROM `lylme_article_comment` WHERE `com_status` = 0"));
    $pages = (int) ceil($total / $size);
    $rowsHtml = '';
    $result = $DB->query(
        "SELECT c.`com_id`,c.`art_id`,c.`com_name`,c.`com_content`,c.`com_time`,a.`art_title` "
        . "FROM `lylme_article_comment` c LEFT JOIN `lylme_article` a ON c.`art_id` = a.`art_id` "
        . "WHERE c.`com_status` = 0 ORDER BY c.`com_time` ASC LIMIT {$off}, {$size}"
    );
    $n = 0;
    while ($result && ($r = $DB->fetch($result))) {
        $n++;
        $cid = intval($r['com_id']);
        $aid = intval($r['art_id']);
        $act = '<a href="' . member_e($base . '?id=' . $aid) . '" target="_blank" class="btn">看文章</a> ';
        $act .= '<form method="post" action="' . member_e($base . '?member=audit_comment') . '" style="display:inline">'
            . '<input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . $cid . '">'
            . '<button type="submit" name="act" value="approve" class="btn pri">通过</button></form> '
            . '<form method="post" action="' . member_e($base . '?member=audit_comment') . '" style="display:inline" onsubmit="return confirm(\'确定删除该评论？\')">'
            . '<input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . $cid . '">'
            . '<button type="submit" name="act" value="delete" class="btn danger">删除</button></form>';
        $rowsHtml .= '<tr>'
            . '<td>' . $cid . '</td>'
            . '<td><b>' . member_e((string) $r['com_name']) . '</b> <span class="muted">于《' . member_e((string) $r['art_title']) . '》</span>'
            . '<br><span>' . nl2br(member_e(mb_substr((string) $r['com_content'], 0, 200, 'UTF-8'))) . '</span>'
            . '<br><small class="muted">' . member_e((string) $r['com_time']) . '</small></td>'
            . '<td><div class="actions">' . $act . '</div></td>'
            . '</tr>';
    }
    if ($n === 0) {
        $rowsHtml = '<tr><td colspan="3" class="muted" style="text-align:center;padding:24px">暂无待审核评论。</td></tr>';
    }
    $pager = member_moderate_pager($pages, $page, $base, 'moderate&type=comments');
    $body = '<table><thead><tr><th>ID</th><th>评论内容</th><th>操作</th></tr></thead><tbody>' . $rowsHtml . '</tbody></table>' . $pager;
}

member_page('审核台', $tabs . $body, 'moderate', $notice, $noticeOk === 1);

<?php
/**
 * 文章模块 - 前台投稿侧 (我的文章 / 新建 / 编辑 / 删除 / 预览)
 * 由 member.php 在 ?member=post|write|delete|preview 时引入 (单入口)
 * 依赖 member.php 作用域内的 $DB / $conf / $action / $isPost 与
 *   member_e / member_page / member_home / member_safe_redirect, Compat\App, Compat\Member
 */
if (!defined('ARTICLE_INDEX')) {
    exit;
}

use Compat\App;
use Compat\Member;

if (!function_exists('member_post_badge')) {
    function member_post_badge($st)
    {
        $st = intval($st);
        if ($st === 1) {
            return '<span class="badge pub">已发布</span>';
        }
        if ($st === 2) {
            return '<span class="badge pend">待审核</span>';
        }
        return '<span class="badge draft">草稿</span>';
    }
}

if (!function_exists('member_post_flash')) {
    /** 写入一次性提示后跳转回列表 */
    function member_post_flash($msg, $ok = 0)
    {
        $_SESSION['member_notice'] = $msg;
        $_SESSION['member_notice_ok'] = $ok ? 1 : 0;
        member_safe_redirect('', App::$articleUrl . '?member=post');
    }
}

if (!function_exists('member_post_cats')) {
    function member_post_cats()
    {
        global $DB;
        $cats = [];
        $res = $DB->query("SELECT `cat_id`, `cat_name` FROM `lylme_article_cat` WHERE `cat_status` = 1 ORDER BY `cat_order` ASC, `cat_id` ASC");
        while ($res && ($c = $DB->fetch($res))) {
            $cats[] = $c;
        }
        return $cats;
    }
}

if (!function_exists('member_post_form')) {
    /** 渲染投稿/编辑表单 */
    function member_post_form($d, $isEditor, $flash = '')
    {
        $base = App::$articleUrl;
        $id = intval(isset($d['art_id']) ? $d['art_id'] : 0);
        $csrf = member_e(Member::csrfToken());
        $cats = member_post_cats();
        $catSel = '<option value="0">未分类</option>';
        foreach ($cats as $c) {
            $sel = (intval($d['cat_id']) === intval($c['cat_id'])) ? ' selected' : '';
            $catSel .= '<option value="' . intval($c['cat_id']) . '"' . $sel . '>' . member_e($c['cat_name']) . '</option>';
        }
        $allow = intval($d['art_allow_comment']) === 1 ? 1 : 0;
        $titleVal = member_e($d['art_title']);
        $slugVal = member_e($d['art_slug']);
        $exVal = member_e($d['art_excerpt']);
        $coverVal = member_e($d['art_cover']);
        $bodyVal = member_e($d['art_content']);

        $html = '<form method="post" action="' . member_e($base . '?member=write') . '" id="postForm">'
            . '<input type="hidden" name="_csrf" value="' . $csrf . '">'
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<div class="field"><label>标题 *</label><input type="text" name="art_title" value="' . $titleVal . '" required maxlength="200"></div>'
            . '<div class="row2">'
            . '<div class="field"><label>分类</label><select name="cat_id">' . $catSel . '</select></div>'
            . '<div class="field"><label>别名（可空，用于 URL）</label><input type="text" name="art_slug" value="' . $slugVal . '" maxlength="200"></div>'
            . '</div>'
            . '<div class="field"><label>摘要</label><textarea name="art_excerpt" rows="2" maxlength="500">' . $exVal . '</textarea></div>'
            . '<div class="field"><label>封面图 URL</label><input type="text" name="art_cover" value="' . $coverVal . '" maxlength="255"></div>'
            . '<div class="field"><label>正文（支持 Markdown）</label>'
            . '<textarea id="art_content" name="art_content" style="display:none">' . $bodyVal . '</textarea>'
            . '<div class="vditor-wrap"><div id="vditor"></div></div></div>'
            . '<div class="field"><label>允许评论</label>'
            . '<label class="btn" style="margin-right:8px"><input type="radio" name="art_allow_comment" value="1"' . ($allow === 1 ? ' checked' : '') . ' style="width:auto"> 允许</label>'
            . '<label class="btn"><input type="radio" name="art_allow_comment" value="0"' . ($allow === 0 ? ' checked' : '') . ' style="width:auto"> 不允许</label>'
            . '</div>'
            . '<div class="actions">';
        if ($isEditor) {
            $html .= '<button type="submit" name="op" value="publish" class="btn pri">直接发布</button>';
        } else {
            $html .= '<button type="submit" name="op" value="submit" class="btn pri">提交审核</button>';
        }
        $html .= '<button type="submit" name="op" value="draft" class="btn">存草稿</button>'
            . '<a href="' . member_e($base . '?member=post') . '" class="btn">返回</a>'
            . '</div>'
            . '<p class="tip">' . ($isEditor ? '编辑可直接发布；也可存为草稿稍后再发。' : '投稿者提交的稿件需经编辑/管理员审核后才对外可见；存草稿不会提交。') . '</p>'
            . '</form>';
        member_page($id > 0 ? '编辑文章' : '新建投稿', $html, 'post', $flash);
    }
}

// ---------- 统一鉴权 ----------
$member = Member::current();
if (!$member) {
    member_safe_redirect('', App::$articleUrl . '?member=login');
    exit;
}
if (!Member::can('submit', $member)) {
    member_page('无权限', '<p>当前账号没有投稿权限，请联系管理员。</p>', 'center', '需要投稿者或编辑角色');
    exit;
}

$uid = intval($member['uid']);
$isEditor = Member::can('publish_direct', $member);
$canModerate = Member::can('moderate', $member);
$postOpen = Member::postOpen();
$base = App::$articleUrl;

// 读取上一跳提示
$notice = isset($_SESSION['member_notice']) ? (string) $_SESSION['member_notice'] : '';
$noticeOk = isset($_SESSION['member_notice_ok']) ? intval($_SESSION['member_notice_ok']) : 0;
unset($_SESSION['member_notice'], $_SESSION['member_notice_ok']);

// ---------- 删除 ----------
if ($action === 'delete') {
    if (!$isPost) {
        member_safe_redirect('', $base . '?member=post');
        exit;
    }
    if (!Member::checkCsrf(isset($_POST['_csrf']) ? $_POST['_csrf'] : '')) {
        member_post_flash('会话已过期，请重试', 0);
    }
    $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
    $row = $id > 0 ? $DB->get_row("SELECT `art_id`,`art_author_uid` FROM `lylme_article` WHERE `art_id` = {$id} LIMIT 1") : null;
    if (!$row) {
        member_post_flash('文章不存在', 0);
    }
    if (intval($row['art_author_uid']) !== $uid && !$isEditor) {
        member_post_flash('无权删除该文章', 0);
    }
    $DB->query("DELETE FROM `lylme_article` WHERE `art_id` = {$id}");
    $DB->query("DELETE FROM `lylme_article_comment` WHERE `art_id` = {$id}");
    member_post_flash('已删除', 1);
}

// ---------- 预览(仅本人或编辑) ----------
if ($action === 'preview') {
    $id = intval(isset($_GET['id']) ? $_GET['id'] : 0);
    $row = $id > 0 ? $DB->get_row("SELECT * FROM `lylme_article` WHERE `art_id` = {$id} LIMIT 1") : null;
    if (!$row) {
        member_page('预览', '<p class="muted">文章不存在。</p>', 'post', '未找到该文章');
        exit;
    }
    if (intval($row['art_author_uid']) !== $uid && !$isEditor) {
        member_page('预览', '<p>无权查看该文章。</p>', 'post', '无权限');
        exit;
    }
    $html = '<p>' . member_post_badge($row['art_status']) . ' &nbsp;<span class="muted">' . member_e((string) $row['art_time']) . ' · ' . intval($row['art_comments']) . ' 评论</span></p>'
        . '<div class="content-preview">' . \Compat\Markdown::convert((string) $row['art_content']) . '</div>'
        . '<div class="actions"><a href="' . member_e($base . '?member=post') . '" class="btn">返回我的文章</a>'
        . ($postOpen ? '<a href="' . member_e($base . '?member=write&id=' . intval($row['art_id'])) . '" class="btn">编辑</a>' : '')
        . '</div>';
    member_page(member_e($row['art_title']), $html, 'post');
    exit;
}

// ---------- 新建 / 编辑 ----------
if ($action === 'write') {
    if (!$postOpen) {
        member_post_flash('前台投稿功能当前已关闭', 0);
    }
    $id = $isPost
        ? intval(isset($_POST['id']) ? $_POST['id'] : 0)
        : intval(isset($_GET['id']) ? $_GET['id'] : 0);

    $d = [
        'art_id' => 0, 'art_title' => '', 'cat_id' => 0, 'art_slug' => '',
        'art_excerpt' => '', 'art_cover' => '', 'art_content' => '', 'art_allow_comment' => 1,
        'art_status' => 0, 'art_time' => '',
    ];
    if ($id > 0) {
        $row = $DB->get_row("SELECT * FROM `lylme_article` WHERE `art_id` = {$id} LIMIT 1");
        if (!$row) {
            member_post_flash('文章不存在', 0);
        }
        if (intval($row['art_author_uid']) !== $uid && !$isEditor) {
            member_post_flash('无权编辑该文章', 0);
        }
        $d = array_merge($d, $row);
    }

    if ($isPost) {
        if (!Member::checkCsrf(isset($_POST['_csrf']) ? $_POST['_csrf'] : '')) {
            member_post_form($d, $isEditor, '会话已过期，请重新提交');
            exit;
        }
        $op = isset($_POST['op']) ? (string) $_POST['op'] : 'draft';
        $title = trim(strip_tags((string) (isset($_POST['art_title']) ? $_POST['art_title'] : '')));
        if (function_exists('mb_strlen') && mb_strlen($title, 'UTF-8') > 200) {
            $title = mb_substr($title, 0, 200, 'UTF-8');
        }
        $slug = trim((string) (isset($_POST['art_slug']) ? $_POST['art_slug'] : ''));
        $slug = preg_replace('/[^A-Za-z0-9_\-]/', '', $slug);
        $catId = intval(isset($_POST['cat_id']) ? $_POST['cat_id'] : 0);
        $excerpt = trim(strip_tags((string) (isset($_POST['art_excerpt']) ? $_POST['art_excerpt'] : '')));
        if (function_exists('mb_strlen') && mb_strlen($excerpt, 'UTF-8') > 500) {
            $excerpt = mb_substr($excerpt, 0, 500, 'UTF-8');
        }
        $cover = trim((string) (isset($_POST['art_cover']) ? $_POST['art_cover'] : ''));
        if (function_exists('mb_strlen') && mb_strlen($cover, 'UTF-8') > 255) {
            $cover = mb_substr($cover, 0, 255, 'UTF-8');
        }
        $content = (string) (isset($_POST['art_content']) ? $_POST['art_content'] : '');
        $allowComment = (isset($_POST['art_allow_comment']) && intval($_POST['art_allow_comment']) === 0) ? 0 : 1;

        $errors = [];
        if ($title === '') {
            $errors[] = '标题不能为空';
        }
        if ($content === '' || trim($content) === '') {
            $errors[] = '正文不能为空';
        }
        if ($catId > 0 && !$DB->get_row("SELECT `cat_id` FROM `lylme_article_cat` WHERE `cat_id` = {$catId} LIMIT 1")) {
            $catId = 0;
        }
        if ($slug !== '') {
            $slugEsc = $DB->escape($slug);
            $dupeWhere = $id > 0 ? " AND `art_id` <> {$id}" : '';
            if ($DB->get_row("SELECT `art_id` FROM `lylme_article` WHERE `art_slug` = '{$slugEsc}'{$dupeWhere} LIMIT 1")) {
                $errors[] = '别名已被使用，请换一个';
            }
        }

        // 目标状态: 草稿0 / (投稿者)提交审核2 / (编辑)发布1; 投稿者永远无法直接置1
        if ($op === 'draft') {
            $newStatus = 0;
        } elseif ($op === 'publish') {
            $newStatus = $isEditor ? 1 : 2;
        } elseif ($op === 'submit') {
            $newStatus = $isEditor ? 1 : 2;
        } else {
            $newStatus = 0;
        }

        if (!empty($errors)) {
            $d['art_title'] = $title;
            $d['cat_id'] = $catId;
            $d['art_slug'] = $slug;
            $d['art_excerpt'] = $excerpt;
            $d['art_cover'] = $cover;
            $d['art_content'] = $content;
            $d['art_allow_comment'] = $allowComment;
            member_post_form($d, $isEditor, implode('；', $errors));
            exit;
        }

        $authorName = trim(strip_tags((string) ($member['nickname'] !== '' ? $member['nickname'] : $member['username'])));
        if (function_exists('mb_strlen') && mb_strlen($authorName, 'UTF-8') > 60) {
            $authorName = mb_substr($authorName, 0, 60, 'UTF-8');
        }
        $now = date('Y-m-d H:i:s');

        if ($id > 0) {
            // 更新(保留原作者与发布时间)
            $sets = [
                "`cat_id` = {$catId}",
                "`art_title` = '" . $DB->escape($title) . "'",
                "`art_slug` = '" . $DB->escape($slug) . "'",
                "`art_excerpt` = '" . $DB->escape($excerpt) . "'",
                "`art_cover` = '" . $DB->escape($cover) . "'",
                "`art_content` = '" . $DB->escape($content) . "'",
                "`art_allow_comment` = {$allowComment}",
                "`art_status` = {$newStatus}",
                "`art_update` = '{$now}'",
            ];
            $DB->query("UPDATE `lylme_article` SET " . implode(', ', $sets) . " WHERE `art_id` = {$id}");
            $savedId = $id;
        } else {
            $insert = [
                'cat_id' => $catId,
                'art_title' => $title,
                'art_slug' => $slug,
                'art_author' => $authorName,
                'art_author_uid' => $uid,
                'art_content' => $content,
                'art_excerpt' => $excerpt,
                'art_cover' => $cover,
                'art_keywords' => '',
                'art_description' => '',
                'art_views' => 0,
                'art_likes' => 0,
                'art_comments' => 0,
                'art_top' => 0,
                'art_status' => $newStatus,
                'art_allow_comment' => $allowComment,
                'art_time' => $now,
                'art_update' => $now,
            ];
            $savedId = intval($DB->insert_array('lylme_article', $insert));
        }

        $noticeMsg = $newStatus === 1 ? '已发布' : ($newStatus === 2 ? '已提交，等待审核' : '草稿已保存');
        if (!$savedId) {
            $noticeMsg = '保存失败，请重试';
        }
        member_post_flash($noticeMsg, $savedId ? 1 : 0);
    }

    // GET: 展示表单
    member_post_form($d, $isEditor, $notice);
    exit;
}

// ---------- 我的文章列表(默认) ----------
$size = 10;
$page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
$total = intval($DB->count("SELECT COUNT(*) FROM `lylme_article` WHERE `art_author_uid` = {$uid}"));
$pages = $size > 0 ? (int) ceil($total / $size) : 0;
$off = ($page - 1) * $size;

$rowsHtml = '';
$result = $DB->query(
    "SELECT `art_id`,`art_title`,`art_status`,`art_time`,`art_update`,`art_comments`,`art_views` "
    . "FROM `lylme_article` WHERE `art_author_uid` = {$uid} ORDER BY `art_update` DESC, `art_id` DESC LIMIT {$off}, {$size}"
);
$count = 0;
while ($result && ($r = $DB->fetch($result))) {
    $count++;
    $rid = intval($r['art_id']);
    $csrfDel = member_e(Member::csrfToken());
    $actions = '<a href="' . member_e($base . '?member=preview&id=' . $rid) . '" class="btn">预览</a>';
    if ($postOpen) {
        $actions .= ' <a href="' . member_e($base . '?member=write&id=' . $rid) . '" class="btn">编辑</a>';
    }
    $actions .= '<form method="post" action="' . member_e($base . '?member=delete') . '" style="display:inline" onsubmit="return confirm(\'确定删除该文章？其评论将一并删除。\')">'
        . '<input type="hidden" name="_csrf" value="' . $csrfDel . '">'
        . '<input type="hidden" name="id" value="' . $rid . '">'
        . '<button type="submit" class="btn danger">删除</button></form>';
    $rowsHtml .= '<tr>'
        . '<td>' . $rid . '</td>'
        . '<td>' . member_e($r['art_title']) . '</td>'
        . '<td>' . member_post_badge($r['art_status']) . '</td>'
        . '<td class="muted">' . intval($r['art_views']) . ' / ' . intval($r['art_comments']) . '</td>'
        . '<td class="muted">' . member_e((string) $r['art_update']) . '</td>'
        . '<td><div class="actions">' . $actions . '</div></td>'
        . '</tr>';
}
if ($count === 0) {
    $rowsHtml = '<tr><td colspan="6" class="muted" style="text-align:center;padding:24px">还没有文章' . ($postOpen ? '，点击「新建投稿」开始写作。' : '。') . '</td></tr>';
}

$topBtn = $postOpen
    ? '<div class="actions" style="margin-bottom:14px"><a href="' . member_e($base . '?member=write') . '" class="btn pri">+ 新建投稿</a></div>'
    : '<p class="tip">前台投稿功能当前已由管理员关闭，你仍可预览或删除已有文章。</p>';

$pager = '';
if ($pages > 1) {
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === $page) {
            $pager .= '<span class="btn">' . $i . '</span>';
        } else {
            $pager .= '<a href="' . member_e($base . '?member=post&page=' . $i) . '">' . $i . '</a>';
        }
    }
    $pager = '<div class="pager">' . $pager . '</div>';
}

$table = '<table><thead><tr><th>ID</th><th>标题</th><th>状态</th><th>浏览/评论</th><th>更新时间</th><th>操作</th></tr></thead><tbody>'
    . $rowsHtml . '</tbody></table>' . $pager;

member_page('我的文章', $topBtn . $table, 'post', $notice, $noticeOk === 1);

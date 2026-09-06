<?php
/**
 * 文章模块 - 评论提交处理
 * 由 index.php 在 ?comment=ID + POST 时引入
 */
if (!defined('ARTICLE_INDEX')) {
    exit;
}

use Compat\App;
use Compat\Member;

if (!function_exists('article_is_https')) {
    /** 判断当前请求是否为 HTTPS */
    function article_is_https()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }
        return false;
    }
}

if (!function_exists('article_redirect')) {
    /** 统一跳转回文章页(支持 URL fragment)并结束请求 */
    function article_redirect($artId, $fragment = '')
    {
        global $DB;
        $artId  = intval($artId);
        $target = App::$articleUrl;
        if ($artId > 0) {
            // 只给 art_id 时, App::postUrl() 在 slug / custom 风格下会退化成 id 形式 URL,
            // 与文章自身的固定链接不是同一个地址; 这里把构造 URL 需要的字段补齐
            $row = (is_object($DB) && method_exists($DB, 'get_row'))
                ? $DB->get_row("SELECT `art_id`, `art_slug`, `art_title`, `art_time` FROM `lylme_article` WHERE `art_id` = {$artId}")
                : false;
            $target = App::postUrl(is_array($row) ? $row : ['art_id' => $artId]);
        }
        header('Location: ' . $target . $fragment);
        exit;
    }
}

if (!function_exists('article_str_len')) {
    /** UTF-8 安全的字符串长度 */
    function article_str_len($str)
    {
        return function_exists('mb_strlen') ? mb_strlen((string) $str, 'UTF-8') : strlen((string) $str);
    }
}

if (!function_exists('article_str_cut')) {
    /** UTF-8 安全截断 */
    function article_str_cut($str, $max)
    {
        $str = (string) $str;
        $max = (int) $max;
        return function_exists('mb_substr') ? mb_substr($str, 0, $max, 'UTF-8') : substr($str, 0, $max);
    }
}

$artId = isset($_GET['comment']) ? intval($_GET['comment']) : 0;

// 仅接受 POST 提交, 其他方法直接跳回文章页
if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
    article_redirect($artId, '#comments');
}

// 校验文章存在且允许评论
$article = $DB->get_row(
    "SELECT `art_id` FROM `lylme_article` WHERE `art_id` = {$artId} AND `art_status` = 1 AND `art_allow_comment` = 1"
);
if (!$article) {
    $_SESSION['article_comment_flash'] = '评论失败：文章不存在或不允许评论';
    article_redirect(0);
}

// 全局评论开关: 0=关闭, 1=免登录评论, 2=仅登录会员评论
$cmode = intval(isset($conf['article_comment']) ? $conf['article_comment'] : 1);
if ($cmode === 0) {
    $_SESSION['article_comment_flash'] = '评论功能已关闭';
    article_redirect($artId, '#comments');
}

// 收集评论数据(insert_array 会统一做 SQL 转义, 此处不再预转义, 避免双重转义)
$author = isset($_POST['author']) ? trim(strip_tags((string) $_POST['author'])) : '';
$mail   = isset($_POST['mail']) ? trim((string) $_POST['mail']) : '';
$url    = isset($_POST['url']) ? trim((string) $_POST['url']) : '';
$text   = isset($_POST['text']) ? trim(preg_replace("/[\x00-\x08\x0B\x0C\x0E-\x1F]/u", '', (string) $_POST['text'])) : '';
$parent = isset($_POST['parent']) ? intval($_POST['parent']) : 0;

// 会员/管理员登录态: 评论身份取自账号, 不信任前端提交的昵称/邮箱/网址
$member = Member::current();
$isAdmin = Member::isAdminLoggedIn();
$comUid = 0;
$isGuest = true;
if (is_array($member) && !empty($member['uid'])) {
    $comUid  = intval($member['uid']);
    $author  = (isset($member['nickname']) && $member['nickname'] !== '') ? $member['nickname'] : (string) $member['username'];
    $mail    = isset($member['email']) ? (string) $member['email'] : '';
    $url     = isset($member['url']) ? (string) $member['url'] : '';
    $isGuest = false;
} elseif ($isAdmin) {
    $author  = !empty($conf['article_name']) ? (string) $conf['article_name'] : '管理员';
    $mail    = '';
    $url     = '';
    $comUid  = 0;
    $isGuest = false;
}

// 仅登录可评论(mode=2): 拒绝未登录游客提交
if ($cmode === 2 && $isGuest) {
    $_SESSION['article_comment_flash'] = '请先登录后再发表评论';
    article_redirect($artId, '#comment-form');
}

// 长度上限(与数据库字段/产品体验对齐)
if ($author !== '' && article_str_len($author) > 30) {
    $author = article_str_cut($author, 30);
}
if (article_str_len($mail) > 128) {
    $mail = article_str_cut($mail, 128);
}
if (article_str_len($url) > 255) {
    $url = article_str_cut($url, 255);
}
if ($text !== '' && article_str_len($text) > 2000) {
    $_SESSION['article_comment_flash'] = '评论内容过长(最多 2000 字)';
    article_redirect($artId, '#comment-form');
}

// 基础校验
if ($author === '' || $text === '') {
    $_SESSION['article_comment_flash'] = '请填写昵称和评论内容';
    article_redirect($artId, '#comment-form');
}

if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['article_comment_flash'] = '邮箱格式不正确';
    article_redirect($artId, '#comment-form');
}

// 网址仅接受 http/https, 拦截 javascript:/data: 等危险协议(XSS)
if ($url !== '') {
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'http://' . $url;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $url)) {
        $url = '';
    }
}

// 父评论校验
if ($parent > 0) {
    $parentRow = $DB->get_row(
        "SELECT `com_id` FROM `lylme_article_comment` WHERE `com_id` = {$parent} AND `art_id` = {$artId} LIMIT 1"
    );
    if (!$parentRow) {
        $parent = 0;
    }
}

// 获取客户端信息
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$agent = substr(isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '', 0, 512);

// 服务端频率限制: 同一 IP 两次评论的最小间隔(秒), 可由配置 article_flood 覆盖(默认 15)
$flood = isset($conf['article_flood']) ? intval($conf['article_flood']) : 15;
if ($flood < 0) {
    $flood = 15;
}
$ipEsc = $DB->escape($ip);
if ($isGuest && $flood > 0 && $ip !== '') {
    $last = $DB->get_row("SELECT `com_time` FROM `lylme_article_comment` WHERE `com_ip` = '{$ipEsc}' ORDER BY `com_id` DESC LIMIT 1");
    if ($last && !empty($last['com_time'])) {
        $elapsed = time() - strtotime($last['com_time']);
        if ($elapsed >= 0 && $elapsed < $flood) {
            $_SESSION['article_comment_flash'] = '评论过于频繁，请 ' . ($flood - $elapsed) . ' 秒后再试';
            article_redirect($artId, '#comment-form');
        }
    }
    // 重复内容拦截: 同 IP 在 5 分钟内提交完全相同内容视为刷屏
    $contentEsc = $DB->escape($text);
    $windowStart = date('Y-m-d H:i:s', time() - 300);
    $dup = $DB->get_row("SELECT `com_id` FROM `lylme_article_comment` WHERE `com_ip` = '{$ipEsc}' AND `com_content` = '{$contentEsc}' AND `com_time` > '{$windowStart}' LIMIT 1");
    if ($dup) {
        $_SESSION['article_comment_flash'] = '请勿重复提交相同内容';
        article_redirect($artId, '#comment-form');
    }
}

// 判断是否需要审核(管理员评论直接通过, 无需审核)
$audit = intval(isset($conf['article_audit']) ? $conf['article_audit'] : 0) === 1;
$comStatus = ($audit && !$isAdmin) ? 0 : 1;

// 入库(值由 insert_array 统一转义)
$insertData = [
    'art_id'     => $artId,
    'com_pid'    => $parent,
    'com_name'   => $author,
    'com_email'  => $mail,
    'com_url'    => $url,
    'com_uid'    => $comUid,
    'com_ip'     => $ip,
    'com_agent'  => $agent,
    'com_content'=> $text,
    'com_status' => $comStatus,
    'com_time'   => date('Y-m-d H:i:s'),
];

$inserted = $DB->insert_array('lylme_article_comment', $insertData);
if ($inserted) {
    // 更新文章评论数(仅统计已通过)
    $commentCount = intval($DB->get_column(
        "SELECT COUNT(*) FROM `lylme_article_comment` WHERE `art_id` = {$artId} AND `com_status` = 1"
    ));
    $DB->query("UPDATE `lylme_article` SET `art_comments` = {$commentCount} WHERE `art_id` = {$artId}");

    // 记住访客信息(cookie): 仅游客; 登录会员/管理员身份取自账号无需记忆
    if ($isGuest) {
        $cookieOpts = [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'secure'   => article_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        setcookie('lylme_comment_author', $author, $cookieOpts);
        setcookie('lylme_comment_mail', $mail, $cookieOpts);
        setcookie('lylme_comment_url', $url, $cookieOpts);
    }

    // 提交结果提示: 区分是否需要审核
    $_SESSION['article_comment_flash'] = $comStatus === 0
        ? '评论已提交，待管理员审核通过后显示'
        : '评论发表成功，感谢您的参与';
} else {
    $_SESSION['article_comment_flash'] = '评论提交失败，请稍后重试';
}

// 跳转回文章页
article_redirect($artId, $comStatus === 0 ? '#comment-form' : '#comments');

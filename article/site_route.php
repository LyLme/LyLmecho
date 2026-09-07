<?php
/**
 * 导航链接详情页路由(由 article/index.php 在 ?site=N 时引入)
 * 复用博客主题与评论系统, 不污染 lylme_article 表。
 * 作用域: 与 index.php 共享 $DB / $conf / $archive / $siteId / $_SESSION。
 */
if (!defined('ARTICLE_INDEX')) {
    exit;
}

use Compat\App;

$siteId = isset($_GET['site']) ? intval($_GET['site']) : 0;
if ($siteId <= 0) {
    $archive->setArchive('404', [], false);
    $archive->archiveTitleStr = '404';
    http_response_code(404);
    return;
}

// 分组密码访问控制(沿用 site/common.php 逻辑)
$pwdList = isset($_SESSION['list']) && is_array($_SESSION['list']) ? array_map('intval', $_SESSION['list']) : [];
$whereClause = '';
if (!empty($pwdList)) {
    $whereClause = '(`link_pwd` = 0';
    foreach ($pwdList as $pwdId) {
        $whereClause .= ' OR `link_pwd` = ' . intval($pwdId);
    }
    $whereClause .= ') AND';
} else {
    $whereClause = '(`link_pwd` = 0 ) AND';
}

$linkRow = $DB->get_row("SELECT * FROM `lylme_links` WHERE {$whereClause} `id` = {$siteId} LIMIT 1");
if (!$linkRow) {
    $archive->setArchive('404', [], false);
    $archive->archiveTitleStr = '404';
    http_response_code(404);
    return;
}

// 二次校验分组密码(无密码分组 group_pwd = 0 始终放行)
$groupRow = $DB->get_row("SELECT `group_pwd` FROM `lylme_groups` WHERE `group_id` = " . intval($linkRow['group_id']) . " LIMIT 1");
$groupPwd = $groupRow ? intval($groupRow['group_pwd']) : 0;
$checkList = array_merge([0], $pwdList);
if (!in_array($groupPwd, $checkList, true)) {
    $archive->setArchive('404', [], false);
    $archive->archiveTitleStr = '404';
    http_response_code(404);
    return;
}

// 首次访问自动采集描述/关键词并写回(沿用 site/common.php 逻辑)
$info = [];
if (!empty($linkRow['link_desc']) && !empty($linkRow['link_keywords'])) {
    $info = [
        'title'       => $linkRow['name'],
        'description' => $linkRow['link_desc'],
        'keywords'    => $linkRow['link_keywords'],
        'url'         => $linkRow['url'],
    ];
} else {
    if (function_exists('get_head')) {
        $info = get_head($linkRow['url'], true);
    }
    $saveDesc = !empty($info['description']) ? trim(strip_tags((string) $info['description'])) : '无';
    $saveKw   = !empty($info['keywords']) ? trim(strip_tags((string) $info['keywords'])) : '无';
    if (function_exists('mb_substr')) {
        $saveDesc = mb_substr($saveDesc, 0, 255);
        $saveKw   = mb_substr($saveKw, 0, 512);
    } else {
        $saveDesc = substr($saveDesc, 0, 255);
        $saveKw   = substr($saveKw, 0, 512);
    }
    $saveKw = str_replace(['、', '，', ' '], ',', $saveKw);
    $saveKw = trim(preg_replace('/,+/', ',', $saveKw));

    $sets = [];
    if (empty($linkRow['link_desc'])) {
        $sets[] = "`link_desc` = '" . $DB->escape($saveDesc) . "'";
        $linkRow['link_desc'] = $saveDesc;
    }
    if (empty($linkRow['link_keywords'])) {
        $sets[] = "`link_keywords` = '" . $DB->escape($saveKw) . "'";
        $linkRow['link_keywords'] = $saveKw;
    }
    if (!empty($sets)) {
        $DB->query("UPDATE `lylme_links` SET " . implode(', ', $sets) . " WHERE `id` = {$siteId}");
    }
}

// 图标 HTML(沿用 site/common.php 处理规则)
$icon = isset($linkRow['icon']) ? $linkRow['icon'] : '';
if (empty($icon)) {
    $icon = '<img src="/assets/img/default-icon.png" alt="' . htmlspecialchars(strip_tags($linkRow['name']), ENT_QUOTES, 'UTF-8') . '" />';
} elseif (!preg_match('/^<svg*/i', $icon)) {
    $icon = '<img src="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars(strip_tags($linkRow['name']), ENT_QUOTES, 'UTF-8') . '" />';
}

// 分组名称
$group = $DB->get_row("SELECT `group_name` FROM `lylme_groups` WHERE `group_id` = " . intval($linkRow['group_id']) . " LIMIT 1");
$groupName = $group ? $group['group_name'] : '';

// 评论数(仅统计已通过且 type=1 的评论)
$commentCount = intval($DB->get_column(
    "SELECT COUNT(*) FROM `lylme_article_comment` WHERE `art_id` = {$siteId} AND `com_type` = 1 AND `com_status` = 1"
));

// 快照 API(后台"详情页快照API"配置)
$snapshotApi = isset($conf['snapshot']) ? trim((string) $conf['snapshot']) : '';

// 链接卡片 HTML(注入 art_content, 让任意主题的 post.php 都能以"文章正文"形式渲染链接信息)
// 注意: 整段必须无空行, 否则 Markdown 块级 HTML 解析会在空行处截断
$siteName = htmlspecialchars($linkRow['name'], ENT_QUOTES, 'UTF-8');
$siteUrl  = htmlspecialchars($linkRow['url'], ENT_QUOTES, 'UTF-8');
$siteDesc = htmlspecialchars($linkRow['link_desc'], ENT_QUOTES, 'UTF-8');
$siteKw   = htmlspecialchars($linkRow['link_keywords'], ENT_QUOTES, 'UTF-8');
$siteCard = '<div class="lylme-site-card" style="border:1px solid #e3e8ef;border-radius:12px;padding:20px;margin:0 0 20px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.04);">'
    . '<div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">'
    . '<div style="width:56px;height:56px;flex:0 0 56px;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;background:#f3f5f9;">' . str_replace(array("\r\n", "\r", "\n"), '', $icon) . '</div>'
    . '<div style="min-width:0;"><div style="font-size:20px;font-weight:600;color:#1f2329;">' . $siteName . '</div>'
    . ($siteDesc !== '' ? '<div style="color:#8a94a6;font-size:14px;margin-top:4px;word-break:break-all;">' . $siteDesc . '</div>' : '')
    . '</div></div>'
    . '<div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:16px;">'
    . '<a href="' . $siteUrl . '" target="_blank" rel="nofollow noopener" style="display:inline-flex;align-items:center;gap:6px;background:#4f7cf7;color:#fff;padding:9px 20px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:500;">立即访问</a>'
    . '<img src="/include/qrcode.php?text=' . urlencode($linkRow['url']) . '" alt="二维码" width="96" height="96" style="border:1px solid #e3e8ef;border-radius:8px;padding:6px;background:#fff;" />'
    . '</div>';
$siteInfo = [];
$siteInfo[] = '<li style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid #f0f2f5;"><span style="color:#8a94a6;flex:0 0 84px;">链接地址</span><span style="word-break:break-all;color:#1f2329;">' . $siteUrl . '</span></li>';
if ($groupName !== '') {
    $siteInfo[] = '<li style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid #f0f2f5;"><span style="color:#8a94a6;flex:0 0 84px;">所属分组</span><span style="color:#1f2329;">' . htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') . '</span></li>';
}
if ($siteKw !== '') {
    $siteInfo[] = '<li style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid #f0f2f5;"><span style="color:#8a94a6;flex:0 0 84px;">网站关键词</span><span style="word-break:break-all;color:#1f2329;">' . $siteKw . '</span></li>';
}
if ($siteDesc !== '') {
    $siteInfo[] = '<li style="display:flex;gap:10px;padding:9px 0;"><span style="color:#8a94a6;flex:0 0 84px;">网站描述</span><span style="word-break:break-all;color:#1f2329;">' . $siteDesc . '</span></li>';
}
$siteCard .= '<ul style="list-style:none;margin:0;padding:0;font-size:14px;">' . implode('', $siteInfo) . '</ul>';
if ($snapshotApi !== '') {
    $siteCard .= '<div style="margin-top:16px;"><img loading="lazy" src="' . htmlspecialchars($snapshotApi, ENT_QUOTES, 'UTF-8') . urlencode($linkRow['url']) . '" alt="网页快照" style="max-width:100%;border:1px solid #e3e8ef;border-radius:8px;" /></div>';
}
$siteCard .= '</div>';
// 注入导航核心图标 sprite 脚本(供分组/链接/搜索引擎的 <svg><use> 图标显示)
// 非 lylme 主题不会加载该脚本, 此处随正文注入以保证图标在任意主题下均可见
$siteCard .= '<script src="/assets/js/icon.js"></script>';

// 链接详情长文(以 Markdown 保存): 转为 HTML 后渲染在卡片正文下方, 无内容则忽略
$linkContent = isset($linkRow['link_content']) ? trim((string) $linkRow['link_content']) : '';
if ($linkContent !== '') {
    if (class_exists('Compat\\Markdown')) {
        $linkContentHtml = \Compat\Markdown::convert($linkContent);
    } else {
        $linkContentHtml = nl2br(htmlspecialchars($linkContent, ENT_QUOTES, 'UTF-8'));
    }
    $siteCard .= '<div class="lylme-site-article" style="margin-top:20px;">' . $linkContentHtml . '</div>';
}

// 映射为 Archive 可识别的 art_* 字段(供 post 风格组件/评论复用)
$row = [
    'art_id'           => $linkRow['id'],
    'art_title'        => $linkRow['name'],
    'art_content'      => $siteCard,
    'art_excerpt'      => $linkRow['link_desc'],
    'art_description'  => $linkRow['link_desc'],
    'art_keywords'     => $linkRow['link_keywords'],
    'art_author'       => '',
    'art_time'         => '',
    'art_update'       => '',
    'art_views'        => 0,
    'art_comments'     => $commentCount,
    'art_allow_comment'=> 1,
    'art_slug'         => '',
    'cat_id'           => 0,
    // 链接详情页专用字段(被 post.php 渲染时通过 $this->row 使用)
    'link_id'          => $linkRow['id'],
    'link_url'         => $linkRow['url'],
    'link_icon'        => $icon,
    'link_title'       => isset($info['title']) ? $info['title'] : $linkRow['name'],
    'link_desc'        => $linkRow['link_desc'],
    'link_keywords'    => $linkRow['link_keywords'],
    'link_group'       => $groupName,
    'link_snapshot'    => $snapshotApi,
];

$archive->setArchive('site', [$row], true);
$archive->setTotal(1);
$archive->archiveTitleStr   = $linkRow['name'];
$archive->archiveKeywords   = $linkRow['link_keywords'];
$archive->archiveDescription = $linkRow['link_desc'];

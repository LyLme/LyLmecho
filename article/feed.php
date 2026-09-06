<?php
/**
 * 文章模块 - RSS / Atom 订阅输出
 * 由 index.php 在 ?feed=rss|atom|comments 时引入
 * 遵循 comment.php 的引入约定: 依赖已加载的 $DB / $conf / Compat\App
 */
if (!defined('ARTICLE_INDEX')) {
    exit;
}

use Compat\App;

$type = strtolower((string) ($_GET['feed'] ?? 'rss'));
if (!in_array($type, ['rss', 'atom', 'comments', 'rdf'], true)) {
    $type = 'rss';
}
$limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;

/** XML 文本转义 */
function article_xml($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

$siteTitle = (string) App::$options->get('title', '博客');
$siteDesc  = (string) App::$options->get('description', '');
$selfLink  = App::$articleUrl . 'index.php?feed=' . $type;
$now       = time();

header('Content-Type: application/rss+xml; charset=UTF-8');

if ($type === 'atom') {
    header('Content-Type: application/atom+xml; charset=UTF-8');
}

// ---------- 收集条目 ----------
$items = [];

if ($type === 'comments') {
    $sql = "SELECT cm.*, a.art_title, a.art_slug, a.art_id, a.cat_id "
        . "FROM `lylme_article_comment` cm "
        . "JOIN `lylme_article` a ON cm.art_id = a.art_id "
        . "WHERE cm.com_status = 1 AND a.art_status = 1 "
        . "ORDER BY cm.com_id DESC LIMIT " . intval($limit);
    $result = $DB->query($sql);
    if ($result) {
        while ($r = $DB->fetch($result)) {
            $link = App::postUrl($r) . '#comment-' . intval($r['com_id']);
            $items[] = [
                'title'   => $r['com_name'] . ' 评论于《' . $r['art_title'] . '》',
                'link'    => $link,
                'guid'    => $link,
                'date'    => strtotime((string) $r['com_time']),
                'author'  => $r['com_name'],
                'content' => (string) $r['com_content'],
            ];
        }
    }
    $feedTitle = $siteTitle . ' - 最新评论';
} else {
    $sql = "SELECT a.*, c.cat_name FROM `lylme_article` a "
        . "LEFT JOIN `lylme_article_cat` c ON a.cat_id = c.cat_id "
        . "WHERE a.art_status = 1 ORDER BY a.art_time DESC, a.art_id DESC LIMIT " . intval($limit);
    $result = $DB->query($sql);
    if ($result) {
        while ($r = $DB->fetch($result)) {
            $link = App::postUrl($r);
            $body = (string) $r['art_content'];
            if (strpos($body, '<!--more-->') !== false) {
                $body = substr($body, 0, strpos($body, '<!--more-->'));
            }
            $excerpt = trim((string) $r['art_excerpt']);
            $items[] = [
                'title'   => (string) $r['art_title'],
                'link'    => $link,
                'guid'    => $link,
                'date'    => strtotime((string) $r['art_time']),
                'author'  => (string) $r['art_author'],
                'category'=> (string) ($r['cat_name'] ?? ''),
                'summary' => $excerpt !== '' ? $excerpt : mb_substr(strip_tags($body), 0, 200, 'UTF-8'),
                'content' => Compat\Markdown::convert($body),
            ];
        }
    }
    $feedTitle = $siteTitle;
}

$lastBuild = $items ? max(array_column($items, 'date')) : $now;

// ---------- 输出 ----------
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

if ($type === 'atom') {
    echo '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n";
    echo '  <title>' . article_xml($feedTitle) . '</title>' . "\n";
    echo '  <link href="' . article_xml(App::$articleUrl) . '" />' . "\n";
    echo '  <link rel="self" href="' . article_xml($selfLink) . '" />' . "\n";
    echo '  <id>' . article_xml(App::$articleUrl) . '</id>' . "\n";
    echo '  <updated>' . gmdate('c', $lastBuild) . '</updated>' . "\n";
    echo '  <generator>' . article_xml(App::$options->get('generator', 'LyLme Spage')) . '</generator>' . "\n";
    foreach ($items as $it) {
        echo '  <entry>' . "\n";
        echo '    <title>' . article_xml($it['title']) . '</title>' . "\n";
        echo '    <link href="' . article_xml($it['link']) . '" />' . "\n";
        echo '    <id>' . article_xml($it['guid']) . '</id>' . "\n";
        echo '    <updated>' . gmdate('c', $it['date']) . '</updated>' . "\n";
        if (!empty($it['author'])) {
            echo '    <author><name>' . article_xml($it['author']) . '</name></author>' . "\n";
        }
        echo '    <summary type="html">' . article_xml($it['summary'] ?? '') . '</summary>' . "\n";
        echo '    <content type="html">' . article_xml($it['content'] ?? '') . '</content>' . "\n";
        echo '  </entry>' . "\n";
    }
    echo '</feed>' . "\n";
} else {
    echo '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n";
    echo '  <channel>' . "\n";
    echo '    <title>' . article_xml($feedTitle) . '</title>' . "\n";
    echo '    <link>' . article_xml(App::$articleUrl) . '</link>' . "\n";
    echo '    <description>' . article_xml($siteDesc !== '' ? $siteDesc : $feedTitle) . '</description>' . "\n";
    echo '    <language>zh-CN</language>' . "\n";
    echo '    <lastBuildDate>' . gmdate('r', $lastBuild) . '</lastBuildDate>' . "\n";
    echo '    <generator>' . article_xml(App::$options->get('generator', 'LyLme Spage')) . '</generator>' . "\n";
    foreach ($items as $it) {
        echo '    <item>' . "\n";
        echo '      <title>' . article_xml($it['title']) . '</title>' . "\n";
        echo '      <link>' . article_xml($it['link']) . '</link>' . "\n";
        echo '      <guid isPermaLink="true">' . article_xml($it['guid']) . '</guid>' . "\n";
        echo '      <pubDate>' . gmdate('r', $it['date']) . '</pubDate>' . "\n";
        if (!empty($it['author'])) {
            echo '      <dc:creator>' . article_xml($it['author']) . '</dc:creator>' . "\n";
        }
        if (!empty($it['category'])) {
            echo '      <category>' . article_xml($it['category']) . '</category>' . "\n";
        }
        echo '      <description>' . article_xml($it['summary'] ?? $it['content'] ?? '') . '</description>' . "\n";
        echo '    </item>' . "\n";
    }
    echo '  </channel>' . "\n";
    echo '</rss>' . "\n";
}

exit;

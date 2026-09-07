<?php
include_once("../include/common.php");
header("Content-type: text/xml");
header('HTTP/1.1 200 OK');
echo '<?xml version="1.0" encoding="utf-8"?>'."\n".'<urlset>'."\n";
?>
    <url>
        <loc><?php echo siteurl()?></loc>
        <lastmod><?php echo date('Y-m-d');?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo siteurl().'/apply'?></loc>
        <lastmod><?php echo date('Y-m');?>-01</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo siteurl().'/about'?></loc>
        <lastmod><?php echo date('Y-m');?>-01</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
    </url>
    <?php
    $sites = $DB->query("SELECT `id` FROM `lylme_links` WHERE `link_pwd` = 0");
    while ( $site = $DB->fetch($sites)) { ?>
    <url>
        <loc><?php echo(siteurl().'/site-'.$site['id'].'.html');?></loc>
        <lastmod><?php echo date('Y-m');?>-01</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php } ?>

<?php
// ---------- 博客文章 ----------
// 复用文章模块固定链接规则(App::postUrl 同款), 不在 sitemap 内引入整个文章模块以避免副作用
try {
    $acRes = $DB->query("SELECT `k`,`v` FROM `lylme_article_config`");
    if ($acRes) {
        while ($acRow = $DB->fetch($acRes)) { $articleConf[$acRow['k']] = $acRow['v']; }
    }
} catch (\Exception $e) { /* 文章配置表缺失则忽略 */ }
$articleConf = isset($articleConf) ? $articleConf : [];
$articleEnabled = intval(isset($articleConf['article_status']) ? $articleConf['article_status'] : 1) === 1;
if ($articleEnabled) {
    $articleBase = rtrim(siteurl(), '/') . '/article/';
    $urlStyle = !empty($articleConf['article_url_style']) ? $articleConf['article_url_style'] : 'default';
    $urlCustom = !empty($articleConf['article_url_custom']) ? $articleConf['article_url_custom'] : '';
    try {
        $posts = $DB->query(
            "SELECT `art_id`,`art_slug`,`art_title`,`art_time`,`art_update` "
            . "FROM `lylme_article` WHERE `art_status` = 1 ORDER BY `art_time` DESC, `art_id` DESC"
        );
        while ($p = $DB->fetch($posts)) {
            $id = intval($p['art_id']);
            $slug = trim((string) $p['art_slug']);
            if ($urlStyle === 'id') {
                $u = $articleBase . $id . '.html';
            } elseif ($urlStyle === 'slug') {
                $u = $articleBase . ($slug !== '' ? $slug : $id) . '.html';
            } elseif ($urlStyle === 'custom') {
                $tpl = $urlCustom !== '' ? $urlCustom : 'article/post{id}.html';
                $ts = !empty($p['art_time']) ? strtotime($p['art_time']) : 0;
                $rep = array(
                    '{id}'    => $id,
                    '{slug}'  => $slug !== '' ? $slug : $id,
                    '{title}' => rawurlencode($p['art_title']),
                    '{year}'  => $ts > 0 ? date('Y', $ts) : '',
                    '{month}' => $ts > 0 ? date('m', $ts) : '',
                    '{day}'   => $ts > 0 ? date('d', $ts) : '',
                );
                $url = str_replace(array_keys($rep), array_values($rep), $tpl);
                if (preg_match('#^https?://#i', $url)) {
                    $u = $url;
                } else {
                    $url = ltrim($url, '/');
                    $siteRoot = rtrim(substr($articleBase, 0, -8), '/');
                    $u = strpos($url, 'article/') === 0 ? $siteRoot . '/' . $url : $articleBase . $url;
                }
            } else { // post_id / default
                $u = $articleBase . 'post' . $id . '.html';
            }
            $lm = !empty($p['art_update'])
                ? date('Y-m-d', strtotime($p['art_update']))
                : (!empty($p['art_time']) ? date('Y-m-d', strtotime($p['art_time'])) : date('Y-m-d'));
            ?>
    <url>
        <loc><?php echo htmlspecialchars($u, ENT_XML1, 'UTF-8');?></loc>
        <lastmod><?php echo $lm;?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php
        }
    } catch (\Exception $e) { /* 忽略文章查询异常 */ }
}
?>
</urlset>
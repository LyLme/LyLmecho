<?php
/**
 * LyLmecho 默认主题 lylmeblog —— 辅助函数库
 *
 * 引入方式：index.php 顶部
 *     require_once __DIR__ . '/functions.php';
 *
 * 本主题的视觉底座直接复用「博客模块默认主题 LyLme Modern」的皮肤
 * （css/base.css 与 js/script.js 与之同源），因此页面视觉与 /article/ 完全一致；
 * 在此之上实现导航能力与「导航 ⇄ 博客」联动（最新文章 / 热门 / 分类 / 站内检索等）。
 *
 * 约定：所有函数以 theme_ 前缀，避免与核心函数冲突；全部兼容 PHP 5.4+。
 */

/** 读取 theme.ini 的 theme_version，用于 css/js 缓存版本参数 */
function theme_version()
{
    static $version = null;
    if ($version === null) {
        $ini = @file_get_contents(__DIR__ . '/theme.ini');
        $info = is_string($ini) ? json_decode($ini, true) : array();
        $raw = (is_array($info) && isset($info['theme_version'])) ? $info['theme_version'] : '1.0.0';
        $version = preg_replace('/[^a-zA-Z0-9]/', '', $raw);
        if ($version === '') {
            $version = '100';
        }
    }
    return $version;
}

/** 输出 css 标签（自动附加版本参数） */
function theme_css($path = 'css/style.css')
{
    global $templatepath;
    $href = rtrim($templatepath, '/') . '/' . ltrim($path, '/') . '?v=' . theme_version();
    echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

/** 输出 js 脚本标签（defer 加载，自动附加版本参数） */
function theme_js($path = 'js/nav.js')
{
    global $templatepath;
    $src = rtrim($templatepath, '/') . '/' . ltrim($path, '/') . '?v=' . theme_version();
    echo '<script defer src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}

/** HTML 转义 */
function theme_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** 站点根目录相对地址，形如 '/' 或 '/sub/'（按入口脚本位置推导） */
function theme_site_base()
{
    static $base = null;
    if ($base === null) {
        $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '/index.php';
        // 统一分隔符为 '/'，避免 Windows 下出现反斜杠
        $script = str_replace('\\', '/', $script);
        $pos = strrpos($script, '/');
        $dir = ($pos === false) ? '' : substr($script, 0, $pos);
        // 清理目录两侧的 '/' 与 '\'，根目录情形得到空串
        $dir = trim($dir, '/\\');
        $base = ($dir === '') ? '/' : '/' . $dir . '/';
    }
    return $base;
}

/** 站点本地静态资源绝对地址（后台同款基座：bootstrap / mdi / lightyear） */
function theme_asset($rel = '')
{
    return theme_site_base() . ltrim($rel, '/');
}

/** 博客模块根地址，形如 '/article/' */
function theme_blog_base()
{
    return theme_site_base() . 'article/';
}

/** 获取导航菜单（导航标签），替代样板代码 */
function theme_tags()
{
    global $site, $DB;
    $tags = array();
    if (!isset($site)) {
        return $tags;
    }
    $result = $site->getTags();
    while ($row = $DB->fetch($result)) {
        $tags[] = array(
            'id'    => $row['tag_id'],
            'name'  => $row['tag_name'],
            'link'  => $row['tag_link'],
            'blank' => ((int) $row['tag_target'] === 1),
        );
    }
    return $tags;
}

/** 获取启用的搜索引擎（移动端优先 sou_waplink） */
function theme_sou()
{
    global $site, $DB;
    $list = array();
    if (!isset($site)) {
        return $list;
    }
    $result = $site->getSou();
    while ($row = $DB->fetch($result)) {
        if ((int) $row['sou_st'] !== 1) {
            continue;
        }
        $link = $row['sou_link'];
        if (checkmobile() && !empty($row['sou_waplink'])) {
            $link = $row['sou_waplink'];
        }
        $list[] = array(
            'alias' => $row['sou_alias'],
            'name'  => $row['sou_name'],
            'hint'  => $row['sou_hint'],
            'icon'  => $row['sou_icon'],
            'color' => $row['sou_color'],
            'link'  => $link,
        );
    }
    return $list;
}

/** 输出 ICP 备案链接 */
function theme_icp()
{
    $icp = isset($GLOBALS['conf']['icp']) ? trim((string) $GLOBALS['conf']['icp']) : '';
    if ($icp === '') {
        return;
    }
    echo '<a class="icp" href="https://beian.miit.gov.cn/" target="_blank" rel="nofollow noopener">'
        . theme_e($icp) . '</a>' . "\n";
}

/** 输出公安备案链接（主题配置项留空则不输出） */
function theme_security_filing($record = 'gonganbei')
{
    $record = trim((string) theme_config($record, ''));
    if ($record === '') {
        return;
    }
    preg_match_all('/\d+/', $record, $gab);
    $code = isset($gab[0][0]) ? $gab[0][0] : '';
    echo '<a class="gonganbei" href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode='
        . theme_e($code) . '" target="_blank" rel="nofollow noopener">'
        . theme_e($record) . '</a>' . "\n";
}

/* =========================================================
 * 通用取数：每次返回一个结果行数组
 * ========================================================= */

/** 导航收录规模统计（分组 / 链接数） */
function theme_nav_counts()
{
    static $counts = null;
    if ($counts === null) {
        $counts = array('groups' => 0, 'links' => 0);
        global $DB;
        if (isset($DB)) {
            try {
                $res = $DB->query("SELECT (SELECT COUNT(*) FROM `lylme_groups` WHERE `group_status` <> 0) AS g, (SELECT COUNT(*) FROM `lylme_links` WHERE `link_status` <> 0) AS l");
                if ($res && ($row = $DB->fetch($res))) {
                    $counts['groups'] = (int) $row['g'];
                    $counts['links'] = (int) $row['l'];
                }
            } catch (Exception $e) {
                // 忽略，保持 0
            }
        }
    }
    return $counts;
}

/** 轻量分组列表（首页 Hero「快速定位」chips 用），仅取前 $limit 个分组 */
function theme_group_quick($limit = 8)
{
    static $quick = null;
    if ($quick === null) {
        $quick = array();
        global $DB;
        if (isset($DB)) {
            try {
                $n = max(1, min(30, (int) $limit));
                $res = $DB->query("SELECT g.`group_id`, g.`group_name`, (SELECT COUNT(*) FROM `lylme_links` l WHERE l.`group_id` = g.`group_id` AND l.`link_status` <> 0) AS link_count FROM `lylme_groups` g WHERE g.`group_status` <> 0 ORDER BY g.`group_order` ASC, g.`group_id` ASC LIMIT " . $n);
                if ($res) {
                    while ($row = $DB->fetch($res)) {
                        $quick[] = array(
                            'id'    => (int) $row['group_id'],
                            'name'  => (string) $row['group_name'],
                            'count' => (int) $row['link_count'],
                        );
                    }
                }
            } catch (Exception $e) {
                $quick = array();
            }
        }
    }
    return $quick;
}

/* =========================================================
 * 博客模块联动（表不存在 / 模块关闭时全部优雅降级）
 * ========================================================= */

/** 博客模块是否开启（文章模块开关 + 对应表存在） */
function theme_blog_enabled()
{
    static $on = null;
    if ($on !== null) {
        return $on;
    }
    global $DB;
    $on = false;
    if (!isset($DB)) {
        return $on;
    }
    try {
        $res = $DB->query("SELECT `v` FROM `lylme_article_config` WHERE `k` = 'article_status' LIMIT 1");
        if ($res && ($row = $DB->fetch($res))) {
            $on = (intval($row['v']) === 1);
        }
    } catch (Exception $e) {
        $on = false;
    }
    return $on;
}

/** 博客规模统计：文章 / 分类 / 评论 */
function theme_blog_stats()
{
    static $stats = null;
    if ($stats === null) {
        $stats = array('posts' => 0, 'cats' => 0, 'comments' => 0);
        global $DB;
        if (isset($DB) && theme_blog_enabled()) {
            try {
                $res = $DB->query("SELECT (SELECT COUNT(*) FROM `lylme_article` WHERE `art_status` = 1) AS p, (SELECT COUNT(*) FROM `lylme_article_cat` WHERE `cat_status` = 1) AS c, (SELECT COUNT(*) FROM `lylme_article_comment` WHERE `com_status` = 1) AS m");
                if ($res && ($row = $DB->fetch($res))) {
                    $stats['posts'] = (int) $row['p'];
                    $stats['cats'] = (int) $row['c'];
                    $stats['comments'] = (int) $row['m'];
                }
            } catch (Exception $e) {
                // 忽略
            }
        }
    }
    return $stats;
}

/** 拉取 n 条最新已发布文章 */
function theme_blog_latest($limit = 6)
{
    static $latest = null;
    if ($latest === null) {
        $latest = array();
        global $DB;
        if (isset($DB) && theme_blog_enabled()) {
            try {
                $n = max(1, min(20, (int) $limit));
                $sql = "SELECT a.`art_id`, a.`art_title`, a.`art_time`, a.`art_views`, a.`cat_id`, c.`cat_name` AS cat_name, c.`cat_alias` AS cat_alias FROM `lylme_article` a LEFT JOIN `lylme_article_cat` c ON c.`cat_id` = a.`cat_id` WHERE a.`art_status` = 1 ORDER BY a.`art_top` DESC, a.`art_time` DESC LIMIT " . $n;
                $res = $DB->query($sql);
                $base = theme_blog_base();
                if ($res) {
                    while ($row = $DB->fetch($res)) {
                        $latest[] = array(
                            'id'     => (int) $row['art_id'],
                            'title'  => (string) $row['art_title'],
                            'views'  => (int) $row['art_views'],
                            'time'   => (string) $row['art_time'],
                            'cat'    => isset($row['cat_name']) ? (string) $row['cat_name'] : '',
                            'url'    => $base . '?id=' . (int) $row['art_id'],
                            'catUrl' => (isset($row['cat_alias']) && $row['cat_alias'] !== '') ? $base . '?cat=' . rawurlencode((string) $row['cat_alias']) : '',
                        );
                    }
                }
            } catch (Exception $e) {
                $latest = array();
            }
        }
    }
    return $latest;
}

/** 拉取 n 条热门文章（按浏览数） */
function theme_blog_hot($limit = 5)
{
    static $hot = null;
    if ($hot === null) {
        $hot = array();
        global $DB;
        if (isset($DB) && theme_blog_enabled()) {
            try {
                $n = max(1, min(20, (int) $limit));
                $sql = "SELECT `art_id`, `art_title`, `art_views` FROM `lylme_article` WHERE `art_status` = 1 ORDER BY `art_views` DESC, `art_time` DESC LIMIT " . $n;
                $res = $DB->query($sql);
                $base = theme_blog_base();
                if ($res) {
                    while ($row = $DB->fetch($res)) {
                        $hot[] = array(
                            'id'    => (int) $row['art_id'],
                            'title' => (string) $row['art_title'],
                            'views' => (int) $row['art_views'],
                            'url'   => $base . '?id=' . (int) $row['art_id'],
                        );
                    }
                }
            } catch (Exception $e) {
                $hot = array();
            }
        }
    }
    return $hot;
}

/** 拉取 n 个文章分类（含文章数），默认全部可用分类 */
function theme_blog_cats($limit = 12)
{
    static $cats = null;
    if ($cats === null) {
        $cats = array();
        global $DB;
        if (isset($DB) && theme_blog_enabled()) {
            try {
                $n = max(1, min(50, (int) $limit));
                $sql = "SELECT c.`cat_id`, c.`cat_name`, c.`cat_alias`, (SELECT COUNT(*) FROM `lylme_article` a WHERE a.`cat_id` = c.`cat_id` AND a.`art_status` = 1) AS cnt FROM `lylme_article_cat` c WHERE c.`cat_status` = 1 ORDER BY cnt DESC, c.`cat_order` ASC, c.`cat_id` ASC LIMIT " . $n;
                $res = $DB->query($sql);
                $base = theme_blog_base();
                if ($res) {
                    while ($row = $DB->fetch($res)) {
                        $alias = (isset($row['cat_alias']) && $row['cat_alias'] !== '') ? (string) $row['cat_alias'] : (string) $row['cat_id'];
                        $cats[] = array(
                            'id'    => (int) $row['cat_id'],
                            'name'  => (string) $row['cat_name'],
                            'count' => (int) $row['cnt'],
                            'url'   => $base . '?cat=' . rawurlencode($alias),
                        );
                    }
                }
            } catch (Exception $e) {
                $cats = array();
            }
        }
    }
    return $cats;
}

/** 人类可读的相对时间（供侧栏「最新文章」显示） */
function theme_time_ago($datetime)
{
    $t = is_string($datetime) ? strtotime($datetime) : 0;
    if (!$t) {
        return '';
    }
    $diff = time() - $t;
    if ($diff < 0) {
        $diff = 0;
    }
    if ($diff < 60) {
        return '刚刚';
    }
    if ($diff < 3600) {
        return intval($diff / 60) . ' 分钟前';
    }
    if ($diff < 86400) {
        return intval($diff / 3600) . ' 小时前';
    }
    if ($diff < 172800) {
        return '昨天';
    }
    if ($diff < 604800) {
        return intval($diff / 86400) . ' 天前';
    }
    return date('Y-m-d', $t);
}

/* =========================================================
 * 主题主色 → 皮肤变量（与博客主题同套规则，保证双端一致）
 * ========================================================= */

/** 十六进制颜色转数组 [r,g,b] */
function theme_hex_rgb($hex)
{
    $hex = ltrim(trim((string) $hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $val = hexdec($hex);
    return array(($val >> 16) & 255, ($val >> 8) & 255, $val & 255);
}

/** RGB(0-255) -> [H 0-360, S 0-1, L 0-1]（与博客 lylme_rgb_to_hsl 一致） */
function theme_rgb_to_hsl($r, $g, $b)
{
    $r /= 255;
    $g /= 255;
    $b /= 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l   = ($max + $min) / 2;
    $d   = $max - $min;
    $h = 0.0;
    $s = 0.0;
    if ($d > 0.000001) {
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        if ($max === $r) {
            $h = ((($g - $b) / $d) + ($g < $b ? 6 : 0)) * 60;
        } elseif ($max === $g) {
            $h = ((($b - $r) / $d) + 2) * 60;
        } else {
            $h = ((($r - $g) / $d) + 4) * 60;
        }
    }
    return array($h, $s, $l);
}

/** H 0-360, S/L 0-1 -> #rrggbb（与博客 lylme_hsl_to_hex 一致） */
function theme_hsl_to_hex($h, $s, $l)
{
    $h = fmod($h, 360);
    if ($h < 0) {
        $h += 360;
    }
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;
    if ($h < 60) {
        list($r, $g, $b) = array($c, $x, 0);
    } elseif ($h < 120) {
        list($r, $g, $b) = array($x, $c, 0);
    } elseif ($h < 180) {
        list($r, $g, $b) = array(0, $c, $x);
    } elseif ($h < 240) {
        list($r, $g, $b) = array(0, $x, $c);
    } elseif ($h < 300) {
        list($r, $g, $b) = array($x, 0, $c);
    } else {
        list($r, $g, $b) = array($c, 0, $x);
    }
    return sprintf(
        '#%02x%02x%02x',
        max(0, min(255, (int) round(($r + $m) * 255))),
        max(0, min(255, (int) round(($g + $m) * 255))),
        max(0, min(255, (int) round(($b + $m) * 255)))
    );
}

/**
 * 由主题色生成与博客 LyLme Modern 完全一致的调色变量集
 * （复刻 article/theme/lylme/functions.php 的 lylme_palette：色相 +32° 得渐变副色, 明度下调得深色）
 */
function theme_palette($hex = '#4f7cf7')
{
    $rgb = theme_hex_rgb($hex);
    $r = $rgb[0];
    $g = $rgb[1];
    $b = $rgb[2];
    $base = sprintf('#%02x%02x%02x', $r, $g, $b);

    list($h, $s, $l) = theme_rgb_to_hsl($r, $g, $b);
    // 色相旋转 +32° 得到渐变副色; 明度下调得到 hover 深色的（与博客一致）
    $accent = theme_hsl_to_hex(fmod($h + 32, 360), min(1, $s * 1.04), max(0.32, min(0.72, $l * 0.96)));
    $ar     = theme_hex_rgb($accent);
    $deep   = theme_hsl_to_hex($h, min(1, $s * 1.1), max(0.18, $l * 0.74));

    return array(
        'base'      => $base,
        'rgb'       => $r . ',' . $g . ',' . $b,
        'deep'      => $deep,
        'accent'    => $accent,
        'accentRgb' => $ar[0] . ',' . $ar[1] . ',' . $ar[2],
        'soft'      => 'rgba(' . $r . ',' . $g . ',' . $b . ',0.10)',
        'line'      => 'rgba(' . $r . ',' . $g . ',' . $b . ',0.30)',
    );
}

/**
 * 读取博客模块的主题主色（lylmePrimary）作为导航强调色的唯一来源。
 * 博客未配置 / 模块关闭时回退到导航自身 theme_config('color')。
 */
function theme_blog_primary($fallback = '#4f7cf7')
{
    global $DB;
    $theme = 'lylme';
    if (isset($DB)) {
        try {
            $res = $DB->query("SELECT `v` FROM `lylme_article_config` WHERE `k` = 'article_theme' LIMIT 1");
            if ($res && ($row = $DB->fetch($res)) && trim((string) $row['v']) !== '') {
                $t = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string) $row['v']));
                if ($t !== '') {
                    $theme = $t;
                }
            }
        } catch (Exception $e) {
            // 忽略，走回退
        }
    }
    $root = defined('ROOT') ? rtrim(ROOT, '/\\') : dirname(__DIR__, 2);
    $file = $root . '/article/config/theme/' . $theme . '.json';
    if (is_file($file)) {
        $dec = json_decode((string) file_get_contents($file), true);
        if (is_array($dec)) {
            foreach (array('lylmePrimary', 'primary', 'color') as $k) {
                if (isset($dec[$k]) && preg_match('/^#?[0-9a-fA-F]{6}$/', (string) $dec[$k])) {
                    $c = (string) $dec[$k];
                    return strpos($c, '#') === 0 ? $c : '#' . $c;
                }
            }
        }
    }
    return $fallback;
}

/** 颜色线性混合：$from 与 $to 按 $weight($to 比重) 混合 */
function theme_mix($fromHex, $toHex, $weight = 0.5)
{
    $f = theme_hex_rgb($fromHex);
    $t = theme_hex_rgb($toHex);
    $w = max(0, min(1, (float) $weight));
    $out = array(
        (int) round($f[0] + ($t[0] - $f[0]) * $w),
        (int) round($f[1] + ($t[1] - $f[1]) * $w),
        (int) round($f[2] + ($t[2] - $f[2]) * $w),
    );
    return sprintf('#%02x%02x%02x', $out[0], $out[1], $out[2]);
}

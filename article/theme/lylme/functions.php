<?php
/**
 * LyLme Modern 主题 - 函数与主题配置
 *
 * 说明:
 *   - 复用后台 (Lightyear Admin) 的 Bootstrap 5 + MDI + style.min.css 设计基座,
 *     再以 style.css 覆写为更现代的圆角/玻璃/微交互风格。
 *   - themeConfig 中定义的项, 后台"文章主题 -> 主题自定义设置"表单会自动渲染,
 *     保存至 article/config/lylme.json, 由 Compat\App 注入 Options,
 *     模板中可通过 $this->options->{name} 读取。
 *
 * @package LyLmeSpage
 * @author  LyLme
 * @version 1.0
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

if (!function_exists('themeConfig')) {
    /**
     * 主题自定义设置表单 (由后台 article_theme.php 渲染)
     */
    function themeConfig($form)
    {
        $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
            'lylmeLogo',
            null,
            null,
            _t('站点 LOGO 地址'),
            _t('建议正方形 PNG/SVG; 留空则自动使用「网站基本设置」中的站点 LOGO, 两者均为空时显示羽毛图标。')
        );
        $form->addInput($logoUrl->addRule('url', _t('请填写合法的 URL 地址')));

        $logoMatte = new \Typecho\Widget\Helper\Form\Element\Radio(
            'lylmeLogoMatte',
            [
                'white' => _t('白色圆角托底'),
                'flat'  => _t('不加底 (透明 logo 直接用)'),
            ],
            'white',
            _t('LOGO 托底'),
            _t('标志为不透明方图时选白色托底; 若 logo 本身是白色线条的透明图, 白底会把它隐没, 改选不加底。')
        );
        $form->addInput($logoMatte);

        $coverUrl = new \Typecho\Widget\Helper\Form\Element\Text(
            'lylmeCover',
            null,
            null,
            _t('侧栏顶部封面图'),
            _t('显示在左侧导航顶部的背景图, 建议使用较宽的横图; 留空使用渐变占位。')
        );
        $form->addInput($coverUrl->addRule('url', _t('请填写合法的 URL 地址')));

        $primary = new \Typecho\Widget\Helper\Form\Element\Text(
            'lylmePrimary',
            null,
            '#4f7cf7',
            _t('主题主色'),
            _t('形如 #4f7cf7, 用于按钮 / 徽标 / 高亮条 / 渐变强调色。')
        );
        $form->addInput($primary);

        $scheme = new \Typecho\Widget\Helper\Form\Element\Radio(
            'lylmeScheme',
            [
                'auto'    => _t('跟随系统'),
                'default' => _t('浅色'),
                'dark'    => _t('深色'),
            ],
            'auto',
            _t('默认配色方案'),
            _t('用户可在顶栏再次切换并记忆; 与后台管理共用 localStorage 键 theme。')
        );
        $form->addInput($scheme);

        $blocks = new \Typecho\Widget\Helper\Form\Element\Checkbox(
            'lylmeBlocks',
            [
                'ShowHero'        => _t('首页顶部大标题 (Hero)'),
                'ShowCover'       => _t('列表文章封面缩略图'),
                'ShowToc'         => _t('文章页目录 (TOC)'),
                'ShowHotPosts'    => _t('侧栏热门内容'),
                'ShowRecentComs'  => _t('侧栏最新评论'),
                'ShowCategories'  => _t('侧栏分类'),
                'ShowTags'        => _t('侧栏标签云'),
                'ShowStats'       => _t('侧栏站点统计'),
            ],
            [
                'ShowHero', 'ShowCover', 'ShowToc',
                'ShowHotPosts', 'ShowRecentComs', 'ShowCategories', 'ShowTags', 'ShowStats',
            ],
            _t('模块开关')
        );
        $form->addInput($blocks->multiMode());

        $hotLimit = new \Typecho\Widget\Helper\Form\Element\Text(
            'lylmeHotLimit',
            null,
            '6',
            _t('热门内容数量'),
            _t('侧栏「热门内容」显示的文章数, 默认 6。')
        );
        $form->addInput($hotLimit);

        $footerText = new \Typecho\Widget\Helper\Form\Element\Textarea(
            'lylmeFooter',
            null,
            null,
            _t('页脚自定义文案'),
            _t('支持简单 HTML; 留空则使用默认「© {年份} {站点名}」并附「由 LyLme Spage 驱动」。')
        );
        $form->addInput($footerText);

        $icp = new \Typecho\Widget\Helper\Form\Element\Text(
            'lylmeIcp',
            null,
            null,
            _t('备案号'),
            _t('例如: 京ICP备xxxxxxxx号; 会在页脚以链接形式展示。')
        );
        $form->addInput($icp);
    }
}

// =================================================================
// 主题辅助函数 (供模板调用)
// =================================================================

if (!function_exists('lylme_opt')) {
    /**
     * 读取主题配置 (带默认值)
     */
    function lylme_opt($archive, $key, $default = null)
    {
        $val = null;
        if (isset($archive->options) && property_exists($archive->options, 'data')) {
            // Options::__get 已支持数据/动态映射, 直接取属性即可
            $val = $archive->options->{$key};
        } elseif (isset($archive->options)) {
            $val = $archive->options->{$key};
        }
        if ($val === null || $val === '') {
            return $default;
        }
        return $val;
    }
}

if (!function_exists('lylme_site_base')) {
    /**
     * 站点根路径 (相对域名的 path, 兼容子目录部署).
     * 由 themeUrl 反推: /sub/article/theme/lylme/ -> /sub/
     */
    function lylme_site_base($archive)
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $themeUrl = (string) (isset($archive->options->themeUrl) ? $archive->options->themeUrl : '');
        $path = $themeUrl !== '' ? (string) parse_url($themeUrl, PHP_URL_PATH) : '/article/theme/lylme/';
        $base = preg_replace('#article/theme/[^/]+/#', '', $path, 1);
        if ($base === null || $base === '') {
            $base = '/';
        }
        if (substr($base, -1) !== '/') {
            $base .= '/';
        }
        $cache = $base;
        return $cache;
    }
}

if (!function_exists('lylme_asset')) {
    /**
     * 站点根资源 URL (用于挂载 /assets/admin 与 /assets/img 等既有静态资源)
     */
    function lylme_asset($archive, $rel)
    {
        return lylme_site_base($archive) . ltrim((string) $rel, '/');
    }
}

if (!function_exists('lylme_logo')) {
    /**
     * 站点 LOGO 地址: 优先取主题自定义 (lylmeLogo), 留则回退全站「网站基本设置」的 $conf['logo']。
     *
     * 全站值在后台存的是文档相对路径 (默认 ./assets/img/logo.png), 首页能解析,
     * 但在 /article/xxx.html 下会被解成 /article/assets/... 导致裂图,
     * 所以统一归一化为站点根绝对路径; 完整 URL / 协议相对 URL 原样保留。
     */
    function lylme_logo($archive)
    {
        $logo = trim((string) lylme_opt($archive, 'lylmeLogo', ''));
        if ($logo === '') {
            $logo = trim((string) (isset($archive->options->logo) ? $archive->options->logo : ''));
        }
        // 去掉空白与仅表示当前目录的 ./
        $logo = preg_replace('#^\./#', '', $logo);
        if ($logo === '' || $logo === '.') {
            return '';
        }
        // 绝对 URL / 协议相对 URL / 站点根绝对路径: 原样使用
        if (preg_match('#^(https?:)?//#i', $logo) || substr($logo, 0, 1) === '/') {
            return $logo;
        }
        return lylme_asset($archive, $logo);
    }
}

if (!function_exists('lylme_origin')) {
    /**
     * 当前请求的来源 (scheme://host), 兼容反向代理与子目录部署。
     */
    function lylme_origin()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower(trim((string) $_SERVER['HTTP_X_FORWARDED_PROTO'])) : '';
        if ($proto === '') {
            $https = isset($_SERVER['HTTPS']) ? strtolower((string) $_SERVER['HTTPS']) : '';
            $proto = ($https !== '' && $https !== 'off') ? 'https' : 'http';
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            $proto = 'https';
        }
        $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? (string) $_SERVER['SERVER_NAME'] : '');
        // Host 头可被外部影响, 只保留合法字符
        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $host);
        $cache = ($host === '') ? '' : ($proto . '://' . $host);
        return $cache;
    }
}

if (!function_exists('lylme_self_url')) {
    /**
     * 当前页面的绝对 URL, 用于 canonical / og:url。
     * 取 path + query 原样拼绝对化: 每个分页/搜索结果都有各自 URL, 因此是自指 canonical,
     * 不会把列表第 2 页错误归并到首页。
     */
    function lylme_self_url()
    {
        $origin = lylme_origin();
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $uri = preg_replace('/[^\x20-\x7E]/', '', $uri); // 去控制字符
        // fragment 不会出现在请求里; 去掉避免拼接出无法匹配的 URL
        $hash = strpos($uri, '#');
        if ($hash !== false) {
            $uri = substr($uri, 0, $hash);
        }
        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/' . ltrim($uri, '/');
        }
        return $origin . $uri;
    }
}

if (!function_exists('lylme_abs_url')) {
    /**
     * 把站点根相对/文档相对的路径绝对化 (og:image 等社交抓取必须是完整 URL)。
     */
    function lylme_abs_url($archive, $rel)
    {
        $rel = trim((string) $rel);
        if ($rel === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $rel)) {
            return $rel;
        }
        $origin = lylme_origin();
        if (substr($rel, 0, 1) === '/') {
            return $origin . $rel;
        }
        return $origin . lylme_asset($archive, $rel);
    }
}

if (!function_exists('lylme_og_image')) {
    /**
     * 分享缩略图: 单篇优先用文章封面, 无封面时回退站点 LOGO。
     */
    function lylme_og_image($archive)
    {
        $img = lylme_cover_of($archive);
        if ($img === '') {
            $img = (string) lylme_logo($archive);
        }
        return lylme_abs_url($archive, $img);
    }
}

if (!function_exists('lylme_asset_ver')) {
    /**
     * 主题静态资源版本号; 改 style.css / main.js 后抬一位即可踢掉旧缓存。
     * 集中一处, 避免样式与脚本的版本号飘移。
     */
    function lylme_asset_ver()
    {
        return '20260905o';
    }
}

if (!function_exists('lylme_uri')) {
    /**
     * 主题内资源 URL (style.css / main.js 等)
     */
    function lylme_uri($archive, $rel)
    {
        $themeUrl = rtrim((string) (isset($archive->options->themeUrl) ? $archive->options->themeUrl : ''), '/') . '/';
        return $themeUrl . ltrim((string) $rel, '/');
    }
}

if (!function_exists('lylme_e')) {
    /** 简写 HTML 转义 */
    function lylme_e($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lylme_blocks')) {
    /**
     * 模块开关集合; 缺省时全部启用
     */
    function lylme_blocks($archive)
    {
        $list = lylme_opt($archive, 'lylmeBlocks', null);
        if (!is_array($list) || empty($list)) {
            $list = [
                'ShowHero', 'ShowCover', 'ShowToc',
                'ShowHotPosts', 'ShowRecentComs', 'ShowCategories', 'ShowTags', 'ShowStats',
            ];
        }
        $map = [];
        foreach ($list as $k) {
            $map[(string) $k] = true;
        }
        return $map;
    }
}

if (!function_exists('lylme_block_on')) {
    /** 单开关查询 */
    function lylme_block_on($archive, $key)
    {
        $b = lylme_blocks($archive);
        return isset($b[$key]);
    }
}

if (!function_exists('lylme_excerpt')) {
    /**
     * 从当前行取一段摘要 (优先 art_excerpt, 回退正文首段), 限长并清洗 HTML.
     */
    function lylme_excerpt($archive, $len = 140)
    {
        $row = isset($archive->row) && is_array($archive->row) ? $archive->row : [];
        $txt = isset($row['art_excerpt']) ? trim((string) $row['art_excerpt']) : '';
        if ($txt === '' && isset($row['art_content'])) {
            // 剥离 <!--more--> 之后的内容, 再粗略去 Markdown 标记
            $raw = (string) $row['art_content'];
            $pos = strpos($raw, '<!--more-->');
            if ($pos !== false) {
                $raw = substr($raw, 0, $pos);
            }
            $txt = preg_replace('/```[\s\S]*?```/u', '', $raw);
            $txt = preg_replace('/`[^`]*`/u', '', (string) $txt);
            $txt = preg_replace('/!\[[^\]]*\]\([^)]*\)/u', '', (string) $txt);
            $txt = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', (string) $txt);
            $txt = preg_replace('/^[#>\-\*\+\s]+/mu', '', (string) $txt);
            $txt = strip_tags((string) $txt);
        }
        $txt = trim(preg_replace('/\s+/u', ' ', $txt));
        if ($len > 0 && function_exists('mb_strlen') && mb_strlen($txt, 'UTF-8') > $len) {
            $txt = mb_substr($txt, 0, $len, 'UTF-8') . '…';
        }
        return $txt;
    }
}

if (!function_exists('lylme_img_dims')) {
    /**
     * 本地图片的固有尺寸 [w, h]; 拿不到时返回 []。
     *
     * 只用于给 img 补 width/height (避免图片未加载时的布局跳动):
     * 只解本地文件头, 不对远程 URL 发请求; realpath 再校一次必须落在站点根内。
     */
    function lylme_img_dims($archive, $url)
    {
        static $cache = [];
        $url = trim((string) $url);
        if ($url === '' || array_key_exists($url, $cache)) {
            return array_key_exists($url, $cache) ? $cache[$url] : [];
        }
        if (!defined('__TYPECHO_ROOT_DIR__') || !preg_match('~^(https?://([^/]*))?(/[^?#]*)(?:[?#].*)?$~i', $url, $m)) {
            return $cache[$url] = [];   // 渐变占位 / data: / 非法形式 一律不算
        }
        // 写了主机名的就必须是当前主机, 否则不看本地文件 (避免把外站 URL 往本地路径上映)
        if (($m[2] ?? '') !== '' && strcasecmp((string) $m[2], (string) ($_SERVER['HTTP_HOST'] ?? '')) !== 0) {
            return $cache[$url] = [];
        }
        $articleDir = realpath((string) __TYPECHO_ROOT_DIR__);   // <站点根>/article/
        if ($articleDir === false || !is_dir($articleDir)) {
            return $cache[$url] = [];
        }
        $root    = dirname($articleDir);                        // URL "/" 对应的文件位置
        $base    = rtrim(lylme_site_base($archive), '/');        // 子目录部署时的 URL 前缀
        $rel     = rawurldecode($m[3]);
        if ($base !== '' && strpos($rel, $base) === 0) {
            $rel = substr($rel, strlen($base));
        }
        if ($rel === '' || $rel[0] !== '/' || strpos($rel, '..') !== false) {
            return $cache[$url] = [];
        }
        $path = $root . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (!is_file($path)) {
            return $cache[$url] = [];
        }
        $real = realpath($path);
        if ($real === false || strpos($real, $root . DIRECTORY_SEPARATOR) !== 0) {
            return $cache[$url] = [];
        }
        $size = @getimagesize($real);
        if (!is_array($size) || empty($size[0]) || empty($size[1])) {
            return $cache[$url] = [];   // SVG 等非位图格式读不到
        }
        return $cache[$url] = [intval($size[0]), intval($size[1])];
    }
}

if (!function_exists('lylme_cover_of')) {
    /**
     * 当前行的封面 URL; 未填时使用占位渐变.
     */
    function lylme_cover_of($archive)
    {
        $row = isset($archive->row) && is_array($archive->row) ? $archive->row : [];
        $cover = isset($row['art_cover']) ? trim((string) $row['art_cover']) : '';
        if ($cover === '' || !preg_match('~^(https?://|/|#|data:image/)~i', $cover)) {
            return '';
        }
        return $cover;
    }
}

if (!function_exists('lylme_is_top')) {
    /** 是否为置顶文章 */
    function lylme_is_top($archive)
    {
        $row = isset($archive->row) && is_array($archive->row) ? $archive->row : [];
        return isset($row['art_top']) && intval($row['art_top']) === 1;
    }
}

if (!function_exists('lylme_time_ago')) {
    /**
     * 相对时间 (刚刚 / x 分钟前 / x 小时前 / x 天前 / 绝对日期).
     */
    function lylme_time_ago($str)
    {
        if (!$str) {
            return '';
        }
        $ts = is_numeric($str) ? (int) $str : strtotime((string) $str);
        if (!$ts) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return '刚刚';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' 分钟前';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' 小时前';
        }
        if ($diff < 86400 * 30) {
            return floor($diff / 86400) . ' 天前';
        }
        return date('Y-m-d', $ts);
    }
}

if (!function_exists('lylme_archive_kind')) {
    /**
     * 当前归档页的语义标签 (首页 / 分类 / 搜索 / 月份 / 单篇 / 404).
     */
    function lylme_archive_kind($archive)
    {
        // 404 必须优先判: 它不属于下面任何类型, 旧实现会回退成 'index',
        // 导致错误页把“首页”导航项置为 active
        if (isset($archive->archiveType) && $archive->archiveType === '404') {
            return '404';
        }
        if (method_exists($archive, 'is')) {
            if ($archive->is('single')) return 'single';
            if ($archive->is('search')) return 'search';
            if ($archive->is('category')) return 'category';
            if ($archive->is('index')) return 'index';
        }
        return 'index';
    }
}

if (!function_exists('lylme_month_of')) {
    /**
     * 月份归档的友好标题 ("2026 年 9 月"); 非归档页返回 ''。
     *
     * 兼容层把 month 归档也归为 index 类型, 但首页会把 archiveTitleStr 置空
     * (article/index.php), 所以 archiveTitleStr 形如 "YYYY-MM ..." 即唯一命中归档页。
     */
    function lylme_month_of($archive)
    {
        if (lylme_archive_kind($archive) !== 'index') {
            return '';
        }
        $t = trim((string) (method_exists($archive, 'getArchiveTitle') ? $archive->getArchiveTitle() : ''));
        if (preg_match('/^(\d{4})-(\d{2})/', $t, $m)) {
            return intval($m[1]) . ' 年 ' . intval($m[2]) . ' 月';
        }
        return '';
    }
}

if (!function_exists('lylme_hot_posts')) {
    /**
     * 热门文章 (按 art_views 排序, 无访问统计时回退时间序).
     * @return array<int, array{title:string,url:string,views:int,time:string}>
     */
    function lylme_hot_posts($limit = 6)
    {
        $limit = max(1, min(20, (int) $limit));
        if (!class_exists('\\Compat\\App') || empty(\Compat\App::$db)) {
            return [];
        }
        $db = \Compat\App::$db;
        $out = [];
        try {
            $result = $db->query(
                "SELECT `art_id`, `art_title`, `art_views`, `art_time`, `art_slug` "
                . "FROM `lylme_article` WHERE `art_status` = 1 "
                . "ORDER BY `art_views` DESC, `art_time` DESC, `art_id` DESC LIMIT {$limit}"
            );
            if ($result) {
                while ($row = $db->fetch($result)) {
                    $out[] = [
                        'title' => (string) $row['art_title'],
                        'url'   => \Compat\App::postUrl($row),
                        'views' => intval($row['art_views']),
                        'time'  => lylme_time_ago($row['art_time']),
                    ];
                }
            }
        } catch (\Throwable $e) {
            return [];
        }
        return $out;
    }
}

if (!function_exists('lylme_tag_cloud')) {
    /**
     * 标签云 (从 art_keywords 拆分, 每标签附计数).
     * @return array<int, array{name:string,url:string,count:int}>
     */
    function lylme_tag_cloud($limit = 40)
    {
        $limit = max(1, min(100, (int) $limit));
        if (!class_exists('\\Compat\\App') || empty(\Compat\App::$db)) {
            return [];
        }
        $db = \Compat\App::$db;
        $map = [];
        try {
            $result = $db->query("SELECT `art_keywords` FROM `lylme_article` WHERE `art_status` = 1 AND `art_keywords` <> '' LIMIT 500");
            if ($result) {
                while ($row = $db->fetch($result)) {
                    foreach (explode(',', (string) $row['art_keywords']) as $t) {
                        $t = trim(strip_tags($t));
                        if ($t === '') {
                            continue;
                        }
                        $map[$t] = isset($map[$t]) ? $map[$t] + 1 : 1;
                    }
                }
            }
        } catch (\Throwable $e) {
            return [];
        }
        arsort($map);
        $out = [];
        $i = 0;
        foreach ($map as $name => $count) {
            if ($i++ >= $limit) break;
            $out[] = [
                'name'  => $name,
                'url'   => lylme_search_url($name),
                'count' => $count,
            ];
        }
        return $out;
    }
}

if (!function_exists('lylme_hex_to_rgb')) {
    /** #4f7cf7 -> "79, 124, 247"; 非法输入返回 null */
    function lylme_hex_to_rgb($hex)
    {
        $hex = trim((string) $hex);
        if (!preg_match('/^#?([0-9a-f]{6})$/i', $hex, $m)) {
            return null;
        }
        $int = hexdec($m[1]);
        return (($int >> 16) & 255) . ', ' . (($int >> 8) & 255) . ', ' . ($int & 255);
    }
}

if (!function_exists('lylme_palette')) {
    /**
     * 由主色推导整套色板, 让后台「主题主色」真正驱动渐变 / 阴影 / 描边。
     * @return array{base:string,rgb:string,accent:string,accentRgb:string,deep:string,soft:string,line:string}
     */
    function lylme_palette($hex)
    {
        $fallback = [
            'base'      => '#4f7cf7',
            'rgb'       => '79, 124, 247',
            'accent'    => '#7b5cf0',
            'accentRgb' => '123, 92, 240',
            'deep'      => '#3a5fd0',
            'soft'      => 'rgba(79, 124, 247, 0.10)',
            'line'      => 'rgba(79, 124, 247, 0.30)',
        ];
        $rgb = lylme_hex_to_rgb($hex);
        if ($rgb === null) {
            return $fallback;
        }
        $parts = array_map('intval', explode(',', $rgb));
        $base  = sprintf('#%02x%02x%02x', $parts[0], $parts[1], $parts[2]);

        // 色相旋转 +32° 得到渐变副色; 明度下调得到 hover 深色的
        list($h, $s, $l) = lylme_rgb_to_hsl($parts[0], $parts[1], $parts[2]);
        // $h 为浮点: % 会先隐式转 int 再取模, 丢失精度且 PHP 8.1+ 报 deprecated, 改用 fmod 保持浮点
        $accent   = lylme_hsl_to_hex(fmod($h + 32, 360), min(1, $s * 1.04), max(0.32, min(0.72, $l * 0.96)));
        $accentI  = lylme_hex_to_rgb($accent);
        $deep     = lylme_hsl_to_hex($h, min(1, $s * 1.1), max(0.18, $l * 0.74));

        return [
            'base'      => $base,
            'rgb'       => $rgb,
            'accent'    => $accent,
            'accentRgb' => $accentI === null ? $fallback['accentRgb'] : $accentI,
            'deep'      => $deep,
            'soft'      => 'rgba(' . $rgb . ', 0.10)',
            'line'      => 'rgba(' . $rgb . ', 0.30)',
        ];
    }
}

if (!function_exists('lylme_rgb_to_hsl')) {
    /** RGB(0-255) -> [H 0-360, S 0-1, L 0-1] */
    function lylme_rgb_to_hsl($r, $g, $b)
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
        return [$h, $s, $l];
    }
}

if (!function_exists('lylme_hsl_to_hex')) {
    /** H 0-360, S/L 0-1 -> #rrggbb */
    function lylme_hsl_to_hex($h, $s, $l)
    {
        $h = fmod($h, 360);
        if ($h < 0) {
            $h += 360;
        }
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;
        if ($h < 60) {
            list($r, $g, $b) = [$c, $x, 0];
        } elseif ($h < 120) {
            list($r, $g, $b) = [$x, $c, 0];
        } elseif ($h < 180) {
            list($r, $g, $b) = [0, $c, $x];
        } elseif ($h < 240) {
            list($r, $g, $b) = [0, $x, $c];
        } elseif ($h < 300) {
            list($r, $g, $b) = [$x, 0, $c];
        } else {
            list($r, $g, $b) = [$c, 0, $x];
        }
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, (int) round(($r + $m) * 255))),
            max(0, min(255, (int) round(($g + $m) * 255))),
            max(0, min(255, (int) round(($b + $m) * 255)))
        );
    }
}

if (!function_exists('lylme_site_stats')) {
    /**
     * 站点统计: 文章/分类/评论 计数.
     */
    function lylme_site_stats()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = ['posts' => 0, 'cats' => 0, 'comments' => 0];
        if (!class_exists('\\Compat\\App') || empty(\Compat\App::$db)) {
            return $cache;
        }
        $db = \Compat\App::$db;
        try {
            $r = $db->get_row("SELECT COUNT(*) AS c FROM `lylme_article` WHERE `art_status` = 1");
            if ($r) $cache['posts'] = intval($r['c']);
            $r = $db->get_row("SELECT COUNT(*) AS c FROM `lylme_article_cat` WHERE `cat_status` = 1");
            if ($r) $cache['cats'] = intval($r['c']);
            $r = $db->get_row("SELECT COUNT(*) AS c FROM `lylme_article_comment` WHERE `com_status` = 1");
            if ($r) $cache['comments'] = intval($r['c']);
        } catch (\Throwable $e) {
            // 静默降级
        }
        return $cache;
    }
}

if (!function_exists('lylme_plain_len')) {
    /**
     * 当前行正文的纯文本长度与阅读时长.
     * 中文按 340 字/分, 拉丁按 200 词/分的折中速率估算。
     * @return array{chars:int,minutes:int}
     */
    function lylme_read_stat($archive)
    {
        static $cache = [];
        $id = isset($archive->row['art_id']) ? intval($archive->row['art_id']) : 0;
        if (isset($cache[$id])) {
            return $cache[$id];
        }
        $row = isset($archive->row) && is_array($archive->row) ? $archive->row : [];
        $raw = isset($row['art_content']) ? (string) $row['art_content'] : '';
        $raw = preg_replace('/```[\s\S]*?```/u', ' ', $raw);
        $raw = preg_replace('/!\[[^\]]*\]\([^)]*\)/u', ' ', (string) $raw);
        $raw = preg_replace('/<[^>]+>/', ' ', (string) $raw);
        $text = trim(preg_replace('/\s+/u', '', strip_tags((string) $raw)));
        $chars = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        $minutes = $chars > 0 ? max(1, (int) ceil($chars / 340)) : 1;
        return $cache[$id] = ['chars' => $chars, 'minutes' => $minutes];
    }
}

if (!function_exists('lylme_search_url')) {
    /**
     * 搜索/标签的统一落地 URL: 文章列表页 + ?s=关键字.
     * 旧实现拼的是 index.php?s=, 与搜索表单 action 提交的 /article/?s= 是同一内容的两条 URL,
     * 会导致 canonical 互相矛盾; 这里收拢为单一形态。
     */
    function lylme_search_url($keyword)
    {
        $url = class_exists('\\Compat\\App') && \Compat\App::$articleUrl ? \Compat\App::$articleUrl : '/article/';
        return $url . '?s=' . urlencode((string) $keyword);
    }
}

if (!function_exists('lylme_keywords_of')) {
    /**
     * 当前行的标签 (取 art_keywords 前 N 个), 链接指向搜索页.
     * @return array<int, array{name:string,url:string}>
     */
    function lylme_keywords_of($archive, $limit = 3)
    {
        $row = isset($archive->row) && is_array($archive->row) ? $archive->row : [];
        $kw  = isset($row['art_keywords']) ? trim((string) $row['art_keywords']) : '';
        if ($kw === '') {
            return [];
        }
        $out = [];
        foreach (explode(',', $kw) as $t) {
            $t = trim(strip_tags($t));
            if ($t === '') {
                continue;
            }
            $out[] = ['name' => $t, 'url' => lylme_search_url($t)];
            if (count($out) >= max(1, (int) $limit)) {
                break;
            }
        }
        return $out;
    }
}

if (!function_exists('lylme_related_posts')) {
    /**
     * 同分类相关文章 (排除自己), 热度优先.
     * @return array<int, array{title:string,url:string,time:string,views:int,cover:string}>
     */
    function lylme_related_posts($row, $limit = 4)
    {
        $limit = max(2, min(8, (int) $limit));
        $self  = intval(isset($row['art_id']) ? $row['art_id'] : 0);
        $catId = intval(isset($row['cat_id']) ? $row['cat_id'] : 0);
        if ($self <= 0 || !class_exists('\\Compat\\App') || empty(\Compat\App::$db)) {
            return [];
        }
        $db = \Compat\App::$db;
        $out = [];
        $sql = 'SELECT `art_id`, `art_title`, `art_slug`, `art_time`, `art_views`, `art_cover` '
             . 'FROM `lylme_article` WHERE `art_status` = 1 AND `art_id` <> ' . $self;
        if ($catId > 0) {
            $sql .= ' AND `cat_id` = ' . $catId;
        }
        $sql .= " ORDER BY `art_views` DESC, `art_time` DESC, `art_id` DESC LIMIT {$limit}";
        try {
            $result = $db->query($sql);
            if ($result) {
                while ($r = $db->fetch($result)) {
                    $cover = trim((string) (isset($r['art_cover']) ? $r['art_cover'] : ''));
                    $out[] = [
                        'title'  => (string) $r['art_title'],
                        'url'    => \Compat\App::postUrl($r),
                        'time'   => lylme_time_ago(isset($r['art_time']) ? $r['art_time'] : ''),
                        'views'  => intval(isset($r['art_views']) ? $r['art_views'] : 0),
                        'cover'  => ($cover !== '' && preg_match('~^(https?://|/|data:image/)~i', $cover)) ? $cover : '',
                    ];
                }
            }
        } catch (\Throwable $e) {
            return [];
        }
        // 同分类无结果时回退到近期文章, 保证区块不空
        if (empty($out) && $catId > 0) {
            return lylme_related_posts(['art_id' => $self, 'cat_id' => 0], $limit);
        }
        return $out;
    }
}

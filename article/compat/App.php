<?php
/**
 * Typecho 兼容层 - 全局应用容器
 * 保存数据库连接与全局配置
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class App
{
    /** @var \DB 数据库连接 */
    public static $db;

    /** @var Options 站点配置对象 */
    public static $options;

    /** @var array 原始配置数组 */
    public static $config = [];

    /** @var string 文章根 URL */
    public static $articleUrl = '';

    /** @var string 主题目录(带结尾斜杠) */
    public static $themeDir = '';

    /** @var string 主题 URL(带结尾斜杠) */
    public static $themeUrl = '';

    /** @var string 全局错误页(主题不存在/主模板缺失时显示, 独立于各主题目录) */
    public static $errorPage = '';

    /** @var string 文章 URL 风格: default|post_id|id|slug|custom */
    public static $urlStyle = 'default';

    /** @var string 自定义 URL 模板 */
    public static $urlCustom = '';

    /**
     * 初始化兼容层
     */
    public static function init($db, $conf)
    {
        self::$db = $db;
        self::$config = $conf;

        // 计算站点根地址
        $siteUrl = self::detectSiteUrl();

        // 文章模块地址
        self::$articleUrl = rtrim($siteUrl, '/') . '/article/';

        // 主题目录 (默认 typecho, 与后台文章主题设置保持一致)
        $themeBase = ROOT . 'article/theme/';
        $theme = !empty($conf['article_theme']) ? trim((string)$conf['article_theme']) : 'typecho';
        $theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme);
        // 校验主题主模板 index.php 是否存在; 缺失(主题被删除/文件丢失)则交由 render() 渲染全局错误页
        if ($theme === '' || !is_file($themeBase . $theme . '/index.php')) {
            $theme = '';
        }
        self::$themeDir = $theme !== '' ? $themeBase . $theme . '/' : '';
        self::$themeUrl = $theme !== '' ? self::$articleUrl . 'theme/' . $theme . '/' : self::$articleUrl;
        // 全局错误页(独立于各主题目录, 主题/主模板缺失时显示)
        self::$errorPage = $themeBase . 'error.php';

        // 读取主题自定义配置
        // 1) 先取 themeConfig() 表单声明的默认值, 保证未保存后台时也"开箱即用" (与 Typecho 原生一致)
        // 2) 再用后台保存的 article/config/theme/{theme}.json 覆盖, 已保存配置优先级最高
        $themeConfigData = [];
        $themeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme);
        $themeFunctions = ROOT . 'article/theme/' . $themeName . '/functions.php';
        if (is_file($themeFunctions)) {
            require_once $themeFunctions;
            if (function_exists('themeConfig')) {
                $form = new \Typecho\Widget\Helper\Form();
                themeConfig($form);
                foreach ($form->getItems() as $item) {
                    if (!is_object($item) || !method_exists($item, 'getName')) {
                        continue;
                    }
                    $name = $item->getName();
                    if ($name === '' || $item->type === 'submit') {
                        continue;
                    }
                    $themeConfigData[$name] = $item->getValue();
                }
            }
        }
        $themeConfigFile = ROOT . 'article/config/theme/' . $themeName . '.json';
        if (is_file($themeConfigFile)) {
            $decoded = json_decode((string) file_get_contents($themeConfigFile), true);
            if (is_array($decoded)) {
                $themeConfigData = array_merge($themeConfigData, $decoded);
            }
        }

        // URL 重写风格
        self::$urlStyle = !empty($conf['article_url_style']) ? $conf['article_url_style'] : 'default';
        self::$urlCustom = !empty($conf['article_url_custom']) ? $conf['article_url_custom'] : '';

        // 构建 Options 对象
        // 注意: $conf 在前, 文章模块映射值在后覆盖, 避免全局配置(lylme_config 的 title/description/keywords)覆盖文章配置
        $data = array_merge($conf, [
            'charset'               => 'UTF-8',
            'title'                 => !empty($conf['article_web_title']) ? $conf['article_web_title'] : '博客',
            'description'           => !empty($conf['article_web_description']) ? $conf['article_web_description'] : '',
            'keywords'              => !empty($conf['article_web_keywords']) ? $conf['article_web_keywords'] : '',
            'siteUrl'               => self::$articleUrl,
            'rootUrl'               => rtrim(substr(self::$articleUrl, 0, -8), '/'),
            'siteDomain'            => parse_url(self::$articleUrl, PHP_URL_HOST),
            'theme'                 => $theme,
            'themeUrl'              => self::$themeUrl,
            'index'                 => self::$articleUrl,
            'feedUrl'               => self::$articleUrl . '?feed=rss',
            'feedRssUrl'            => self::$articleUrl . '?feed=rss',
            'feedAtomUrl'           => self::$articleUrl . '?feed=atom',
            'commentsFeedUrl'       => self::$articleUrl . '?feed=comments',
            'adminUrl'              => rtrim($siteUrl, '/') . '/admin/',
            'profileUrl'            => self::$articleUrl . '?member=center',
            'loginUrl'              => self::$articleUrl . '?member=login',
            'registerUrl'           => self::$articleUrl . '?member=register',
            'logoutUrl'             => self::$articleUrl . '?logout=1',
            'memberRegisterOpen'    => intval(isset($conf['article_member_status']) ? $conf['article_member_status'] : 0) === 1,
            'logoUrl'               => '',
            'sidebarBlock'          => ['ShowRecentPosts', 'ShowRecentComments', 'ShowCategory', 'ShowArchive', 'ShowOther'],
            'commentsRequireMail'   => false,
            'commentsRequireUrl'    => false,
            'commentsThreaded'      => true,
            'commentsMaxNestingLevels' => 5,
            'commentsPageBreak'     => false,
            'commentsPageSize'      => 10,
            'commentsPostTimeout'   => 60,
            'commentsShowCommentOnly' => false,
            'commentsOrder'         => 'ASC',
            'commentsAntiSpam'      => false,
            'commentDateFormat'     => 'Y-m-d H:i',
            'allowXmlRpc'           => 0,
            'generator'             => 'LyLme Spage / Typecho Compat',
            'xmlRpcUrl'             => '',
            'postsListSize'         => 10,
            'pageSize'              => !empty($conf['article_perpage']) ? intval($conf['article_perpage']) : 10,
            'time'                  => time(),
            'timezone'              => 8 * 3600,
            'serverTimezone'        => 0,
            'frontPage'             => 'recent',
            'frontArchive'          => true,
        ]);

        // 主题自定义配置覆盖默认值 (优先级最高)
        if (!empty($themeConfigData)) {
            $data = array_merge($data, $themeConfigData);
        }

        self::$options = new Options($data);

        // 全局别名，兼容主题中直接引用 $options / $this->options
        if (!isset($GLOBALS['options'])) {
            $GLOBALS['options'] = self::$options;
        }
    }

    /**
     * 自动检测站点根地址
     */
    protected static function detectSiteUrl()
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            $host = 'localhost';
        }

        // 去掉入口文件路径部分
        $scriptDir = rtrim(str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/article/index.php')), '/');

        $siteUrl = $scheme . '://' . $host . $scriptDir;

        // 如果当前在 article 子目录, 则回退到站点根
        if (substr($siteUrl, -8) === '/article') {
            $siteUrl = substr($siteUrl, 0, -8);
        }

        return rtrim($siteUrl, '/') . '/';
    }

    /**
     * 是否启用 URL 重写
     */
    public static function isRewrite()
    {
        return self::$urlStyle !== '' && self::$urlStyle !== 'default';
    }

    /**
     * 生成文章固定链接
     * @param array $row 文章行(art_id, art_slug, art_time, art_title)
     */
    public static function postUrl($row)
    {
        $id = intval(is_array($row) ? (isset($row['art_id']) ? $row['art_id'] : 0) : 0);

        if (!self::isRewrite()) {
            return self::$articleUrl . 'index.php?id=' . $id;
        }

        switch (self::$urlStyle) {
            case 'id':
                $url = self::$articleUrl . $id . '.html';
                break;
            case 'slug':
                $slug = trim((string) (is_array($row) ? (isset($row['art_slug']) ? $row['art_slug'] : '') : ''));
                $url = self::$articleUrl . ($slug !== '' ? $slug : $id) . '.html';
                break;
            case 'custom':
                $url = self::customPostUrl($row);
                break;
            case 'post_id':
            default:
                $url = self::$articleUrl . 'post' . $id . '.html';
        }

        return $url;
    }

    /**
     * 生成独立页面固定链接
     * @param array $row 页面行(page_slug / art_slug)
     */
    public static function pageUrl($row)
    {
        $slug = trim((string) (is_array($row)
            ? (isset($row['page_slug']) ? $row['page_slug'] : (isset($row['art_slug']) ? $row['art_slug'] : ''))
            : ''));
        if ($slug === '') {
            return self::$articleUrl;
        }
        if (!self::isRewrite()) {
            return self::$articleUrl . 'index.php?page=' . rawurlencode($slug);
        }
        return self::$articleUrl . 'page/' . rawurlencode($slug) . '.html';
    }

    /**
     * 按 cid (art_id) 获取单篇文章行
     * 供 Widget_Archive@alias 兼容 (clarity prev/next 等)
     */
    public static function getPostById($cid)
    {
        $cid = intval($cid);
        if ($cid <= 0 || !(self::$db instanceof \DB)) {
            return null;
        }
        try {
            $row = self::$db->get_row(
                "SELECT * FROM `lylme_article` WHERE `art_id` = {$cid} AND `art_status` = 1 LIMIT 1"
            );
        } catch (\Exception $e) {
            return null;
        }
        return is_array($row) && $row ? $row : null;
    }

    /**
     * 自定义模板生成文章链接
     * 支持占位符: {id} {slug} {title} {year} {month} {day}
     */
    protected static function customPostUrl($row)
    {
        $tpl = trim((string) self::$urlCustom);
        if ($tpl === '') {
            $tpl = 'article/post{id}.html';
        }

        $id = intval(isset($row['art_id']) ? $row['art_id'] : 0);
        $slug = trim((string) (isset($row['art_slug']) ? $row['art_slug'] : ''));
        $year = $month = $day = '';
        if (!empty($row['art_time'])) {
            $ts = strtotime($row['art_time']);
            if ($ts > 0) {
                $year = date('Y', $ts);
                $month = date('m', $ts);
                $day = date('d', $ts);
            }
        }
        $replace = [
            '{id}'    => $id,
            '{slug}'  => $slug !== '' ? $slug : $id,
            '{title}' => isset($row['art_title']) ? rawurlencode($row['art_title']) : '',
            '{year}'  => $year,
            '{month}' => $month,
            '{day}'   => $day,
        ];
        $url = str_replace(array_keys($replace), array_values($replace), $tpl);

        // 绝对地址原样返回
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $siteRoot = rtrim(substr(self::$articleUrl, 0, -8), '/');
        $url = ltrim($url, '/');

        // 模板含 article/ 前缀时按站点根拼接, 否则视为 article 目录内相对路径
        if (strpos($url, 'article/') === 0) {
            return $siteRoot . '/' . $url;
        }

        return self::$articleUrl . $url;
    }

    /**
     * 生成分类链接
     */
    public static function categoryUrl($slug)
    {
        $slug = trim((string) $slug);
        if (self::isRewrite()) {
            return self::$articleUrl . 'category-' . rawurlencode($slug) . '.html';
        }
        return self::$articleUrl . 'index.php?cat=' . urlencode($slug);
    }

    /**
     * 生成归档链接(YYYY-MM)
     */
    public static function archiveUrl($month)
    {
        $month = trim((string) $month);
        if (self::isRewrite()) {
            return self::$articleUrl . 'archive-' . $month . '.html';
        }
        return self::$articleUrl . 'index.php?month=' . urlencode($month);
    }

    /**
     * 生成分页链接(返回带页码的完整 URL)
     */
    public static function pagerUrl($page, $type = 'index', $slug = '', $keyword = '')
    {
        $page = max(1, intval($page));
        if (self::isRewrite()) {
            switch ($type) {
                case 'category':
                    return self::$articleUrl . 'category-' . rawurlencode($slug) . '-page-' . $page . '.html';
                case 'search':
                    // 与 Archive::pagerBaseUrl() 一致: 伪静态下搜索也用 ?s= 形式
                    return self::$articleUrl . '?s=' . urlencode($keyword) . '&page=' . $page;
                default:
                    return self::$articleUrl . 'page-' . $page . '.html';
            }
        }
        switch ($type) {
            case 'category':
                return self::$articleUrl . 'index.php?cat=' . urlencode($slug) . '&page=' . $page;
            case 'search':
                return self::$articleUrl . 'index.php?s=' . urlencode($keyword) . '&page=' . $page;
            default:
                return self::$articleUrl . 'index.php?page=' . $page;
        }
    }
}

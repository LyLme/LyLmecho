<?php
/**
 * Typecho 兼容层 - Archive 主组件
 * 对应 Typecho 的 \Widget\Archive, 是主题模板中的 $this
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Archive extends BaseWidget
{
    /** @var string 归档类型: index / post / category / search / 404 */
    public $archiveType = 'index';

    /** @var bool 是否单篇文章 */
    public $archiveSingle = false;

    /** @var string 当前分类别名 */
    public $archiveSlug = '';

    /** @var string 归档标题 */
    public $archiveTitleStr = '';

    /** @var string 搜索关键词 */
    public $archiveKeywords = '';

    /** @var string 当前页码 */
    public $currentPage = 1;

    /** @var int 总记录数 */
    public $total = 0;

    /** @var int 每页条数 */
    public $pageSize = 10;

    /** @var User 当前用户(兼容层模拟) */
    public $user;

    /** @var string 归档描述 */
    public $archiveDescription = '';

    /** @var string 归档 URL */
    public $archiveUrl = '';

    /** @var string 主题文件 */
    public $themeFile = '';

    /** @var string 主题目录 */
    public $themeDir = '';

    /** @var string 分页查询 SQL */
    protected $countSql = '';

    /** @var array 分类缓存 */
    protected $catCache = [];

    public function __construct($parameter = null, $request = null)
    {
        $this->user = new User();
        parent::__construct($parameter);

        // 尽早加载主题 functions.php 并调用 themeInit(),
        // 使主题可以在数据加载前修改 parameter->pageSize 等参数
        if (is_file(App::$themeDir . 'functions.php')) {
            include_once App::$themeDir . 'functions.php';
        }
        if (function_exists('themeInit')) {
            themeInit($this);
        }

        // 兼容 Widget_Archive@alias 按 cid 拉取单篇 (clarity prev/next 等)
        if (empty($this->stack)) {
            $requestArr = [];
            if (is_string($request)) {
                parse_str($request, $requestArr);
            } elseif (is_array($request)) {
                $requestArr = $request;
            }
            if (!empty($requestArr['cid'])) {
                $row = App::getPostById(intval($requestArr['cid']));
                if ($row) {
                    $cat = $this->getCategory(intval($row['cat_id']));
                    if ($cat) {
                        $row['cat_name'] = $cat['cat_name'];
                        $row['cat_alias'] = $cat['cat_alias'];
                    }
                    $this->setArchive('post', [$row], true);
                    $this->setTotal(1);
                    $this->archiveTitleStr = isset($row['art_title']) ? $row['art_title'] : '';
                    $this->archiveSlug = isset($row['art_slug']) ? $row['art_slug'] : '';
                }
            }
        }
    }

    /**
     * 根据数据库行初始化归档
     */
    public function setArchive($type, $rows, $single = false, $slug = '')
    {
        $this->archiveType = $type;
        $this->archiveSingle = $single;
        $this->archiveSlug = $slug;
        $this->stack = $rows;
        $this->length = count($rows);
        // 优先级: themeInit 设置的 parameter->pageSize > options->pageSize > 默认 10
        if (isset($this->parameter->pageSize) && intval($this->parameter->pageSize) > 0) {
            $this->pageSize = intval($this->parameter->pageSize);
        } else {
            $this->pageSize = intval($this->options->pageSize) ?: 10;
        }
        if ($single) {
            $this->currentPage = 1;
            // 单篇文章直接预置当前行, 兼容模板直接使用 $this 而不调用 next() 的情况
            if (!empty($rows)) {
                $this->row = $rows[0];
            }
        }
        // 同步页面类型给 \Typecho\Request (robes 等主题通过 Request::is() 判断)
        if (class_exists('\\Typecho\\Request')) {
            \Typecho\Request::setType($type);
        }
    }

    /**
     * 设置总数(分页用)
     */
    public function setTotal($total)
    {
        $this->total = intval($total);
    }

    /**
     * 获取总记录数 (Typecho API: getTotal())
     */
    public function getTotal(): int
    {
        return (int) $this->total;
    }

    /**
     * 获取当前页码
     */
    public function getCurrentPage()
    {
        return (int) $this->currentPage;
    }

    /**
     * 输出分页导航
     */
    public function pageNav($prev = '&laquo;', $next = '&raquo;', $splitPage = 3, $splitWord = '...', $template = '')
    {
        $totalPage = max(1, (int) ceil($this->total / $this->pageSize));
        if ($totalPage <= 1) {
            return;
        }

        $base = $this->pagerBaseUrl();

        echo '<ol class="page-navigator">';

        if ($this->currentPage > 1) {
            echo '<li class="prev"><a href="' . $base . ($this->currentPage - 1) . '">' . $prev . '</a></li>';
        }

        // 计算页码范围
        $start = max(1, $this->currentPage - $splitPage);
        $end = min($totalPage, $this->currentPage + $splitPage);
        if ($start > 1) {
            echo '<li><a href="' . $base . '1">1</a></li>';
            if ($start > 2) {
                echo '<li><span>' . $splitWord . '</span></li>';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $this->currentPage) {
                echo '<li class="current"><a href="' . $base . $i . '">' . $i . '</a></li>';
            } else {
                echo '<li><a href="' . $base . $i . '">' . $i . '</a></li>';
            }
        }
        if ($end < $totalPage) {
            if ($end < $totalPage - 1) {
                echo '<li><span>' . $splitWord . '</span></li>';
            }
            echo '<li><a href="' . $base . $totalPage . '">' . $totalPage . '</a></li>';
        }

        if ($this->currentPage < $totalPage) {
            echo '<li class="next"><a href="' . $base . ($this->currentPage + 1) . '">' . $next . '</a></li>';
        }

        echo '</ol>';
    }

    /**
     * 分页基础 URL
     */
    protected function pagerBaseUrl()
    {
        $base = App::$articleUrl;
        if (App::isRewrite()) {
            switch ($this->archiveType) {
                case 'category':
                    $base .= 'category-' . rawurlencode($this->archiveSlug) . '-page-';
                    break;
                case 'search':
                    // 与未开伪静态时保持同一种形态 (文章列表页 + ?s=), 避免同一内容出现两条 URL
                    $base .= '?s=' . urlencode($this->archiveKeywords) . '&page=';
                    break;
                default:
                    $base .= 'page-';
            }
            return $base;
        }
        switch ($this->archiveType) {
            case 'category':
                $base .= '?cat=' . urlencode($this->archiveSlug) . '&page=';
                break;
            case 'search':
                $base .= '?s=' . urlencode($this->archiveKeywords) . '&page=';
                break;
            default:
                $base .= '?page=';
        }
        return $base;
    }

    /**
     * 输出归档标题
     * $this->archiveTitle(['category' => _t('分类 %s 下的文章'), ...], '', ' - ')
     */
    public function archiveTitle($defines = [], $before = '', $after = '')
    {
        $title = $this->archiveTitleStr;
        if (isset($defines[$this->archiveType])) {
            $title = $defines[$this->archiveType];
            if ($this->archiveSingle) {
                $title = str_replace('%s', isset($this->row['art_title']) ? $this->row['art_title'] : '', $title);
            } elseif ($this->archiveType === 'search') {
                $title = str_replace('%s', $this->archiveKeywords, $title);
            } elseif ($this->archiveSlug !== '') {
                $title = str_replace('%s', $this->archiveSlug, $title);
            }
        } elseif ($this->archiveSingle) {
            $title = isset($this->row['art_title']) ? $this->row['art_title'] : $title;
        }
        // 归档标题为空(首页/未匹配类型)时不输出多余分隔符, 避免出现 " - 站点名" / "| 站点名" 形式的空白前缀
        if (trim((string) $title) === '') {
            return;
        }
        echo $before . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . $after;
    }

    /**
     * 是否匹配当前类型
     */
    public function is($type, $slug = null)
    {
        $result = false;
        switch ($type) {
            case 'index':
                $result = $this->archiveType === 'index';
                break;
            case 'archive':
                $result = in_array($this->archiveType, ['category', 'search']);
                break;
            case 'single':
            case 'post':
                $result = $this->archiveSingle && in_array($this->archiveType, ['post', 'site'], true);
                break;
            case 'category':
                $result = $this->archiveType === 'category';
                break;
            case 'search':
                $result = $this->archiveType === 'search';
                break;
            case 'page':
                $result = $this->archiveSingle && $this->archiveType === 'page';
                break;
        }
        if ($result && $slug !== null) {
            $result = $slug === $this->archiveSlug;
        }
        return $result;
    }

    /**
     * 引入主题模板
     */
    public function need($file)
    {
        if (App::$themeDir === '') {
            return;
        }
        $path = App::$themeDir . $file;
        if (is_file($path)) {
            include $path;
            return;
        }
        // 当前主题缺少该局部模板时, 尝试回退到默认 typecho 主题
        $fallback = ROOT . 'article/theme/typecho/' . $file;
        if (is_file($fallback)) {
            include $fallback;
            return;
        }
        echo '<!-- template not found: ' . htmlspecialchars($file) . ' -->';
    }

    /**
     * 输出当前文章完整内容
     */
    public function content($more = null)
    {
        $content = isset($this->row['art_content']) ? $this->row['art_content'] : '';
        // 列表页截断到 <!--more--> 标签
        if (!$this->archiveSingle && strpos($content, '<!--more-->') !== false) {
            $pos = strpos($content, '<!--more-->');
            $excerpt = substr($content, 0, $pos);
            echo Markdown::convert($excerpt);
            if ($more !== null) {
                $this->contentMore($more);
            }
            return;
        }
        echo Markdown::convert($content);
    }

    /**
     * 列表页 "阅读剩余部分" 链接
     */
    protected function contentMore($more)
    {
        echo '<a href="' . $this->getPermalink() . '" class="more-link">' . $more . '</a>';
    }

    /**
     * 输出文章摘要 (Typecho API: $this->excerpt(长度, 后缀))
     */
    public function excerpt($length = 100, $last = '...')
    {
        // 优先使用后台填写的摘要
        $text = isset($this->row['art_excerpt']) ? trim($this->row['art_excerpt']) : '';
        if ($text === '') {
            $text = isset($this->row['art_content']) ? $this->row['art_content'] : '';
            $pos = strpos($text, '<!--more-->');
            if ($pos !== false) {
                $text = substr($text, 0, $pos);
            }
            // Markdown 渲染后去除标签提取纯文本
            $text = strip_tags(Markdown::convert($text));
        }
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text !== '' && mb_strlen($text, 'UTF-8') > $length) {
            $text = mb_substr($text, 0, $length, 'UTF-8') . $last;
        }
        echo $text;
        return $this;
    }

    /**
     * 输出文章固定链接 (Typecho 语义: echo 输出)
     */
    public function permalink($slugs = '')
    {
        echo $this->getPermalink($slugs);
        return $this;
    }

    /**
     * 获取文章固定链接 (字符串形式, 供内部拼接使用)
     */
    public function getPermalink($slugs = '')
    {
        // 导航链接详情页: 使用链接固定链接(/article/siteN.html)
        if ($this->archiveType === 'site') {
            return App::siteUrl($this->row);
        }
        // 独立页面: 始终使用页面链接(与所处归档上下文无关)
        if ($this->archiveType === 'page') {
            return App::pageUrl($this->row);
        }
        // Typecho 语义: permalink() 始终指向当前内容项(文章), 与所处归档(分类/搜索)无关。
        // 仅当没有当前文章行(归档级调用)时, 才回退到归档 URL。
        if (!empty($this->row['art_id'])) {
            return App::postUrl($this->row);
        }
        if ($this->archiveSingle) {
            return App::postUrl($this->row);
        } elseif ($this->archiveType === 'category') {
            return App::categoryUrl($this->archiveSlug);
        } elseif ($this->archiveType === 'search') {
            return App::$articleUrl . '?s=' . urlencode($this->archiveKeywords);
        }
        return App::postUrl($this->row);
    }

    /**
     * 输出标题
     */
    public function title($last = '')
    {
        echo htmlspecialchars(isset($this->row['art_title']) ? $this->row['art_title'] : '', ENT_QUOTES, 'UTF-8') . $last;
    }

    /**
     * 输出作者名
     */
    public function author($last = '')
    {
        echo htmlspecialchars(isset($this->row['art_author']) ? $this->row['art_author'] : '', ENT_QUOTES, 'UTF-8') . $last;
    }

    /**
     * 属性访问: $this->author 返回作者对象
     */
    public function __get($name)
    {
        if ($name === 'author') {
            return new Author(isset($this->row['art_author']) ? $this->row['art_author'] : '', App::$articleUrl);
        }
        if ($name === 'permalink') {
            return $this->getPermalink();
        }
        if ($name === 'date') {
            // Typecho 语义: $this->date 返回 Typecho\Date 对象 (主题常用 $this->date->format(...))
            $time = isset($this->row['art_time']) ? $this->row['art_time'] : '';
            if ($time === '') {
                $time = isset($this->row['art_update']) ? $this->row['art_update'] : '';
            }
            return $time === '' ? null : new \Typecho\Date($time);
        }
        if ($name === 'modified') {
            // Typecho 语义: created / modified 均为 int 时间戳 (与 BaseWidget 保持一致)。
            // 主题常用 (int) $this->modified / date('Y-m-d', $this->modified),
            // 若返回 Date 对象, (int) 转换会报 "Object of class Typecho\Date could not be converted to int" 并得到 0。
            $time = isset($this->row['art_update']) ? $this->row['art_update'] : '';
            if ($time === '') {
                $time = isset($this->row['art_time']) ? $this->row['art_time'] : '';
            }
            if ($time === '') {
                return 0;
            }
            return is_numeric($time) ? (int) $time : (int) strtotime((string) $time);
        }
        if ($name === 'fields') {
            return new \ArticleFields($this->row);
        }
        if ($name === '_currentPage') {
            // 兼容主题中 $this->_currentPage (Typecho 原版下划线属性风格)
            return $this->currentPage;
        }
        if ($name === 'tags') {
            // 标签: 系统无独立标签表, 从 art_keywords (逗号分隔) 拆分, 链接指向搜索页
            $tags = [];
            $keywords = isset($this->row['art_keywords']) ? trim((string) $this->row['art_keywords']) : '';
            if ($keywords !== '') {
                foreach (explode(',', $keywords) as $tagName) {
                    $tagName = trim($tagName);
                    if ($tagName !== '') {
                        $tags[] = [
                            'name'      => $tagName,
                            // 统一走文章列表页的 ?s= 查询, 与搜索表单/分页输出同一 URL
                            'permalink' => App::$articleUrl . '?s=' . urlencode($tagName),
                        ];
                    }
                }
            }
            return $tags;
        }
        if ($name === 'hidden') { return false; }
        if ($name === 'isMarkdown') { $content = isset($this->row['art_content']) ? $this->row['art_content'] : ''; return strpos($content, '<!--markdown-->') === 0 || preg_match('/^#{1,6}\s/', trim($content)); }
        if ($name === 'path') { return $this->getPermalink(); }
        if ($name === 'url') { return $this->getPermalink(); }
        if ($name === 'feedUrl') { return App::$articleUrl . 'feed/'; }
        if ($name === 'feedRssUrl') { return App::$articleUrl . 'feed/rss/'; }
        if ($name === 'feedAtomUrl') { return App::$articleUrl . 'feed/atom/'; }
        if ($name === 'respondId') { return 'respond-post-' . intval(isset($this->row['art_id']) ? $this->row['art_id'] : 0); }
        if ($name === 'trackbackUrl') { return ''; }
        if ($name === 'responseUrl') { return App::$articleUrl . '?comment=' . intval(isset($this->row['art_id']) ? $this->row['art_id'] : 0); }
        if ($name === 'year') { $time = isset($this->row['art_time']) ? $this->row['art_time'] : ''; return $time ? (int) date('Y', is_numeric($time) ? (int) $time : strtotime($time)) : 0; }
        if ($name === 'month') { $time = isset($this->row['art_time']) ? $this->row['art_time'] : ''; return $time ? (int) date('n', is_numeric($time) ? (int) $time : strtotime($time)) : 0; }
        if ($name === 'day') { $time = isset($this->row['art_time']) ? $this->row['art_time'] : ''; return $time ? (int) date('j', is_numeric($time) ? (int) $time : strtotime($time)) : 0; }
        if ($name === 'dateWord') { $time = isset($this->row['art_time']) ? $this->row['art_time'] : ''; return $time ? date('M d, Y', is_numeric($time) ? (int) $time : strtotime($time)) : ''; }
        if ($name === 'summary') { $content = isset($this->row['art_content']) ? $this->row['art_content'] : ''; $html = Markdown::convert($content); if (preg_match('/<p>(.*?)<\/p>/s', $html, $m)) { return strip_tags($m[1]); } return ''; }
        if ($name === 'plainExcerpt') { $text = isset($this->row['art_excerpt']) ? trim($this->row['art_excerpt']) : ''; if ($text === '') { $text = strip_tags(Markdown::convert(isset($this->row['art_content']) ? $this->row['art_content'] : '')); } return trim(preg_replace('/\s+/u', ' ', mb_substr($text, 0, 200, 'UTF-8'))); }
        if ($name === 'theId') { return $this->archiveType . '-' . intval(isset($this->row['art_id']) ? $this->row['art_id'] : 0); }
        if ($name === 'attachment') { return null; }
        if ($name === 'template') { return ''; }
        if ($name === 'response') {
            // RoricalTheme 等主题在 themeInit 中通过 $this->response->setStatus(403) 设置 HTTP 状态码
            return new class {
                public function setStatus($code) { http_response_code((int) $code); }
                public function setHeader($name, $value) { header($name . ': ' . $value); }
            };
        }
        if ($name === 'content' || $name === 'text') {
            // Typecho 语义: $this->content / $this->text 返回当前文章渲染后的 HTML 正文
            $raw = isset($this->row['art_content']) ? $this->row['art_content'] : '';
            if (!$this->archiveSingle && strpos($raw, '<!--more-->') !== false) {
                $raw = substr($raw, 0, strpos($raw, '<!--more-->'));
            }
            return Markdown::convert($raw);
        }
        if ($name === 'categories') {
            $categories = [];
            $catId = intval(isset($this->row['cat_id']) ? $this->row['cat_id'] : 0);
            $cat = $this->getCategory($catId);
            if ($cat) {
                $categories[] = [
                    'name'      => isset($cat['cat_name']) ? $cat['cat_name'] : '',
                    'permalink' => App::categoryUrl(isset($cat['cat_alias']) ? $cat['cat_alias'] : ''),
                ];
            }
            return $categories;
        }
        return parent::__get($name);
    }

    /**
     * 属性 isset 检测 (主题常用 isset($this->fields) / isset($this->xxx) 等)
     */
    public function __isset($name)
    {
        // Archive 特有的虚拟属性
        $virtualProps = [
            'author', 'permalink', 'date', 'modified', 'fields', 'tags',
            '_currentPage', 'hidden', 'isMarkdown', 'path', 'url',
            'feedUrl', 'feedRssUrl', 'feedAtomUrl', 'respondId',
            'trackbackUrl', 'responseUrl', 'response', 'year', 'month', 'day',
            'dateWord', 'summary', 'plainExcerpt', 'theId', 'attachment',
            'template', 'content', 'text', 'categories',
        ];
        if (in_array($name, $virtualProps, true)) {
            return true;
        }
        // 委托父类 (fields/parameter/alias/row)
        return parent::__isset($name);
    }

    /**
     * 输出日期
     */
    public function date($format = 'Y-m-d H:i:s', $last = '')
    {
        $time = isset($this->row['art_time']) ? $this->row['art_time'] : '';
        if (is_numeric($time)) {
            $timestamp = intval($time);
        } else {
            $timestamp = strtotime($time ?: 'now');
        }
        echo date($format, $timestamp) . $last;
    }

    /**
     * 输出文章所属分类
     */
    public function category($split = ',', $last = '')
    {
        $catId = intval(isset($this->row['cat_id']) ? $this->row['cat_id'] : 0);
        $cat = $this->getCategory($catId);
        if ($cat) {
            echo '<a href="' . App::categoryUrl($cat['cat_alias']) . '">'
                . htmlspecialchars($cat['cat_name'], ENT_QUOTES, 'UTF-8') . '</a>';
        }
        echo $last;
    }

    /**
     * 输出评论数
     */
    public function commentsNum($zero = '0', $one = '1', $more = '%d')
    {
        $num = intval(isset($this->row['art_comments']) ? $this->row['art_comments'] : 0);
        if ($num == 0) {
            echo $zero;
        } elseif ($num == 1) {
            echo $one;
        } else {
            echo sprintf($more, $num);
        }
    }

    /**
     * 输出标签链接(无标签表, 从 art_keywords 拆分, 链接指向搜索页)
     */
    public function tags($split = ',', $flat = false, $default = '')
    {
        $tags = $this->__get('tags');
        if (empty($tags)) {
            echo $default;
            return;
        }
        $links = [];
        foreach ($tags as $tag) {
            $links[] = '<a href="' . htmlspecialchars($tag['permalink'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') . '</a>';
        }
        echo implode($split, $links);
    }

    /**
     * 上一篇
     */
    public function thePrev($format = '%s', $default = '')
    {
        $id = intval(isset($this->row['art_id']) ? $this->row['art_id'] : 0);
        $row = $this->db->get_row(
            "SELECT * FROM `lylme_article` WHERE `art_status`=1 AND `art_id` < {$id} ORDER BY `art_id` DESC LIMIT 1"
        );
        if ($row) {
            echo sprintf($format, '<a href="' . App::postUrl($row) . '" title="'
                . htmlspecialchars($row['art_title'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($row['art_title'], ENT_QUOTES, 'UTF-8') . '</a>');
        } else {
            echo $default;
        }
    }

    /**
     * 下一篇
     */
    public function theNext($format = '%s', $default = '')
    {
        $id = intval(isset($this->row['art_id']) ? $this->row['art_id'] : 0);
        $row = $this->db->get_row(
            "SELECT * FROM `lylme_article` WHERE `art_status`=1 AND `art_id` > {$id} ORDER BY `art_id` ASC LIMIT 1"
        );
        if ($row) {
            echo sprintf($format, '<a href="' . App::postUrl($row) . '" title="'
                . htmlspecialchars($row['art_title'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($row['art_title'], ENT_QUOTES, 'UTF-8') . '</a>');
        } else {
            echo $default;
        }
    }

    /**
     * 输出 SEO 关键词
     */
    public function keywords($split = ',', $default = '')
    {
        echo isset($this->row['art_keywords']) ? $this->row['art_keywords'] : $default;
    }

    /**
     * 输出 SEO 描述
     */
    public function description($length = 150, $last = '')
    {
        $desc = isset($this->row['art_description']) ? $this->row['art_description'] : '';
        if (mb_strlen($desc, 'UTF-8') > $length) {
            $desc = mb_substr($desc, 0, $length, 'UTF-8') . '...';
        }
        echo $desc;
    }

    /**
     * 输出 header
     *
     * 兼容 Typecho 语义: $rule 形如 'viewport=&keywords=&description=', 值为空的键表示
     * "主题已自行输出, 请跳过头"。不传参时行为与旧版一致, 存量主题零回归。
     */
    public function header($rule = null, $delims = '&')
    {
        $skip = [];
        if (is_string($rule) && $rule !== '') {
            foreach (preg_split('/' . preg_quote((string) $delims, '/') . '/', $rule) as $pair) {
                if (strpos($pair, '=') === false) {
                    continue;
                }
                list($key, $val) = explode('=', $pair, 2);
                if ($val === '') {
                    $skip[strtolower(trim($key))] = true;
                }
            }
        } elseif (is_array($rule)) {
            foreach ($rule as $key => $val) {
                if ($val === '' || $val === null) {
                    $skip[strtolower(trim((string) $key))] = true;
                }
            }
        }

        if (!isset($skip['charset'])) {
            echo '<meta http-equiv="Content-Type" content="text/html; charset='
                . $this->options->charset . '" />' . "\n";
        }
        if (!isset($skip['viewport'])) {
            // 不再强制 maximum-scale / user-scalable=no: 那会禁掉双指缩放, 违反 WCAG 1.4.4
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n";
        }
        if ($this->archiveSingle) {
            $kw = isset($this->row['art_keywords']) ? trim((string) $this->row['art_keywords']) : '';
            $de = isset($this->row['art_description']) ? trim((string) $this->row['art_description']) : '';
        } else {
            $kw = trim((string) $this->options->keywords);
            $de = trim((string) $this->options->description);
        }
        // 空值不输出: 否则会把主题已生成的更完整 description 覆盖成空
        if ($kw !== '' && !isset($skip['keywords'])) {
            echo '<meta name="keywords" content="' . htmlspecialchars($kw) . '" />' . "\n";
        }
        if ($de !== '' && !isset($skip['description'])) {
            echo '<meta name="description" content="' . htmlspecialchars($de) . '" />' . "\n";
        }
        if (!isset($skip['generator'])) {
            echo '<meta name="generator" content="' . $this->options->generator . '" />' . "\n";
        }

        // 触发 Typecho 插件 header 钩子 (Widget_Archive.header)
        echo \Typecho\Plugin::export('Widget_Archive', 'header', $this);
    }

    /**
     * 输出 footer
     */
    public function footer()
    {
        // 触发 Typecho 插件 footer 钩子 (Widget_Archive.footer)
        echo \Typecho\Plugin::export('Widget_Archive', 'footer', $this);
        echo "\n";
    }

    /**
     * 输出评论组件
     */
    public function comments()
    {
        // 全局评论开关: 0=关闭; 2=仅登录(游客视同关闭, 不展示发表/回复入口); 1=免登录
        $cmode = intval(isset(App::$config['article_comment']) ? App::$config['article_comment'] : 1);
        $canPost = ($cmode === 0) ? false : !($cmode === 2 && !($this->user && $this->user->hasLogin()));
        $allowComment = $canPost
            && (intval(isset($this->row['art_allow_comment']) ? $this->row['art_allow_comment'] : 1) === 1);

        $comments = new CommentsWidget([
            'parentId' => intval(isset($this->row['art_id']) ? $this->row['art_id'] : 0),
            'allowComment' => $allowComment ? 1 : 0,
            'type' => ($this->archiveType === 'site') ? 1 : 0,
            'parentContent' => $this,
        ]);
        return $comments;
    }

    /**
     * 是否允许(评论等)
     */
    public function allow($name)
    {
        if ($name === 'comment') {
            // 全局评论开关: 0=关闭; 2=仅登录(游客返回 false→主题自动隐藏发表表单); 1=免登录
            $cmode = intval(isset(App::$config['article_comment']) ? App::$config['article_comment'] : 1);
            if ($cmode === 0) {
                return false;
            }
            if ($cmode === 2 && !($this->user && $this->user->hasLogin())) {
                return false;
            }
            return intval(isset($this->row['art_allow_comment']) ? $this->row['art_allow_comment'] : 1) === 1;
        }
        return false;
    }

    /**
     * 评论回复区 ID
     */
    public function respondId()
    {
        echo 'respond-post-' . intval(isset($this->row['art_id']) ? $this->row['art_id'] : 0);
    }

    /**
     * 评论提交地址
     */
    public function commentUrl()
    {
        echo App::$articleUrl . '?comment=' . intval(isset($this->row['art_id']) ? $this->row['art_id'] : 0);
    }

    /**
     * 记住用户信息(cookie)
     */
    public function remember($name, $return = false)
    {
        $value = isset($_COOKIE['lylme_comment_' . $name]) ? $_COOKIE['lylme_comment_' . $name] : '';
        if ($return) {
            return $value;
        }
        echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    // === Getter/Setter 方法 ===
    public function getArchiveTitle() { return $this->archiveTitleStr; }
    public function setArchiveTitle($title) { $this->archiveTitleStr = (string) $title; }
    public function addArchiveTitle($title) { $this->archiveTitleStr .= $title; }
    public function getArchiveSlug() { return $this->archiveSlug; }
    public function setArchiveSlug($slug) { $this->archiveSlug = (string) $slug; }
    public function getArchiveType() { return $this->archiveType; }
    public function setArchiveType($type) { $this->archiveType = (string) $type; }
    public function getArchiveUrl() { return $this->archiveUrl; }
    public function setArchiveUrl($url) { $this->archiveUrl = (string) $url; }
    public function getArchiveDescription() { return $this->archiveDescription; }
    public function setArchiveDescription($desc) { $this->archiveDescription = (string) $desc; }
    public function getArchiveKeywords() { return $this->archiveKeywords; }
    public function setArchiveKeywords($kw) { $this->archiveKeywords = (string) $kw; }
    public function getArchiveFeedUrl() { return App::$articleUrl . 'feed/'; }
    public function setArchiveFeedUrl($url) {}
    public function getArchiveFeedRssUrl() { return App::$articleUrl . 'feed/rss/'; }
    public function setArchiveFeedRssUrl($url) {}
    public function getArchiveFeedAtomUrl() { return App::$articleUrl . 'feed/atom/'; }
    public function setArchiveFeedAtomUrl($url) {}
    public function getCountSql() { return $this->countSql; }
    public function setCountSql($sql) { $this->countSql = (string) $sql; }
    public function getThemeFile() { return $this->themeFile; }
    public function setThemeFile($file) { $this->themeFile = (string) $file; }
    public function getThemeDir() { return App::$themeDir; }
    public function setThemeDir($dir) {}
    public function getTotalPage() { return max(1, (int) ceil($this->total / max(1, $this->pageSize))); }

    // === 额外方法 ===
    public function pings($limit = 0, $offset = 0) { return new \Typecho\PlaceholderWidget(); }
    /**
     * 附件/封面图 (Typecho 语义: 返回可遍历附件的 Widget, 其 ->attachment 指向当前附件)
     * 本站无独立附件表, 若有 art_cover 则将其作为封面附件暴露, 供主题 showThumb() 等取图。
     */
    public function attachments($limit = 0, $offset = 0)
    {
        $cover = isset($this->row['art_cover']) ? trim((string) $this->row['art_cover']) : '';
        $w = new \Typecho\PlaceholderWidget();
        if ($cover !== '') {
            $w->row['attachment'] = (object) [
                'isImage' => 1,
                'url'     => $cover,
                'path'    => $cover,
                'name'    => basename($cover),
                'size'    => 0,
            ];
        }
        return $w;
    }
    public function related($limit = 5, $type = null) { return new \Typecho\PlaceholderWidget(); }
    public function pageLink($word, $page) { echo '<a href="' . $this->pagerBaseUrl() . $page . '">' . $word . '</a>'; }
    public function theLink($content, $format = '%s', $default = '', $custom = null) { echo $default; }
    public function query($select = null) { return $this; }
    public function select(...$fields) { return $this; }

    /**
     * 获取分类信息(带缓存)
     */
    public function getCategory($catId)
    {
        if ($catId <= 0) {
            return null;
        }
        if (!isset($this->catCache[$catId])) {
            $this->catCache[$catId] = $this->db->get_row(
                "SELECT * FROM `lylme_article_cat` WHERE `cat_id` = " . intval($catId)
            );
        }
        return $this->catCache[$catId];
    }

    /**
     * 渲染主题模板(入口)
     */
    public function render()
    {
        $file = 'index.php';
        if ($this->archiveSingle && $this->archiveType === 'post') {
            $file = 'post.php';
            if (!is_file(App::$themeDir . 'post.php')) {
                $file = 'index.php';
            }
        } elseif ($this->archiveType === 'site') {
            $file = 'site.php';
            if (!is_file(App::$themeDir . 'site.php')) {
                // 主题未提供 site.php 时, 回退到文章模板(post.php → index.php),
                // 复用主题标准 comments.php, 保证链接评论在任意主题下都可用
                $file = (is_file(App::$themeDir . 'post.php')) ? 'post.php' : 'index.php';
            }
        } elseif ($this->archiveType === 'category' || $this->archiveType === 'search') {
            $file = 'index.php';
        } elseif ($this->archiveType === 'page') {
            $file = 'page.php';
            // Typecho 官方模板回退顺序: page.php → post.php → single/archive → index.php
            // 许多 Typecho 主题不为独立页面单独建 page.php, 此时用 post.php 排版正文
            if (!is_file(App::$themeDir . 'page.php')) {
                $file = (is_file(App::$themeDir . 'post.php')) ? 'post.php' : 'index.php';
            }
        } elseif ($this->archiveType === '404') {
            $file = '404.php';
            if (!is_file(App::$themeDir . '404.php')) {
                $file = 'index.php';
            }
        }

        // functions.php 已在构造函数中加载, 此处不再重复
        // 主模板缺失(主题被删除/文件丢失): 渲染全局错误页并返回 404, 避免空白
        if (App::$themeDir === '' || !is_file(App::$themeDir . $file)) {
            if (!empty(App::$errorPage) && is_file(App::$errorPage)) {
                http_response_code(404);
                include App::$errorPage;
                return;
            }
            echo '<!-- template not found: ' . htmlspecialchars($file) . ' -->';
            return;
        }
        $this->need($file);
    }
}

<?php
/**
 * 文章模块 - 入口
 * 兼容 Typecho 主题运行:
 *   ?id=xx         单篇文章 (post.php)
 *   ?cat=xx        分类列表 (index.php)
 *   ?s=xx          搜索 (index.php)
 *   ?month=xx      按月归档 (index.php)
 *   ?page=N        分页
 *   ?comment=xx    POST 提交评论
 */

// 定义入口常量并初始化
define('ARTICLE_INDEX', true);
// 开启输出缓冲: 使主题在渲染中途调用的 setcookie()/header()/http_response_code()
// 在响应真正发送前仍然有效 (Typecho 原生前端亦采用缓冲), 脚本结束时自动冲刷。
ob_start();
require __DIR__ . '/common.php';

use Compat\App;
use Compat\Archive;

// ---------- 生产级前台错误边界 ----------
// DEBUG 关闭时: 捕获未处理异常/致命错误, 丢弃部分输出并渲染友好错误页(堆栈仅写日志);
// DEBUG 开启时: 交由默认处理直接展示错误, 便于开发调试。
if (!defined('DEBUG') || DEBUG !== true) {
    $article_error_boundary = static function ($reason) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        error_log('[article] ' . $reason);
        echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>暂时无法访问</title><style>body{font-family:-apple-system,'
            . '"Microsoft YaHei",sans-serif;background:#f5f7fa;color:#4a5568;display:flex;'
            . 'align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center}'
            . '.box{padding:40px}h1{font-size:20px;margin:0 0 12px}p{font-size:14px;color:#8a94a6;margin:0 0 24px}'
            . 'a{color:#4f7cf7;text-decoration:none}</style></head><body><div class="box">'
            . '<h1>内容暂时无法显示</h1><p>服务器遇到点问题，请稍后再试。</p><a href="/">返回首页</a>'
            . '</div></body></html>';
    };
    set_exception_handler(static function ($e) use ($article_error_boundary) {
        $article_error_boundary('exception: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    });
    register_shutdown_function(static function () use ($article_error_boundary) {
        $last = error_get_last();
        if ($last && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            $article_error_boundary('fatal: ' . $last['message']);
        }
    });
}

// ---------- 浏览量统计(按会话去重, 避免刷新刷量) ----------
if (!function_exists('article_count_view')) {
    function article_count_view($artId)
    {
        global $DB;
        $artId = intval($artId);
        if ($artId <= 0) {
            return;
        }
        if (!isset($_SESSION['article_viewed']) || !is_array($_SESSION['article_viewed'])) {
            $_SESSION['article_viewed'] = [];
        }
        if (in_array($artId, $_SESSION['article_viewed'], true)) {
            return;
        }
        if (count($_SESSION['article_viewed']) > 500) {
            $_SESSION['article_viewed'] = array_slice($_SESSION['article_viewed'], -300);
        }
        $_SESSION['article_viewed'][] = $artId;
        $DB->query("UPDATE `lylme_article` SET `art_views` = `art_views` + 1 WHERE `art_id` = {$artId}");
    }
}

// ---------- 评论提交处理 ----------
if (isset($_GET['comment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/comment.php';
    exit;
}

// ---------- RSS / Atom 订阅输出 ----------
if (isset($_GET['feed']) && trim((string) $_GET['feed']) !== '') {
    require __DIR__ . '/feed.php';
    exit;
}

// ---------- 会员登录/注册/退出/个人中心 ----------
if (
    (isset($_GET['member']) && trim((string) $_GET['member']) !== '')
    || isset($_GET['logout'])
) {
    require __DIR__ . '/member.php';
    exit;
}

// 兼容 Typecho 搜索表单 (method=post) 提交: 将 POST 的 s 合并到 GET, 供下方路由分支使用
if (isset($_POST['s']) && trim((string) $_POST['s']) !== '' && !isset($_GET['s'])) {
    $_GET['s'] = trim((string) $_POST['s']);
}

// ---------- 构建 Archive 组件 ----------
$archive = new Archive();

// ---------- 伪静态兜底规则解析 ----------
if (isset($_GET['rewrite']) && trim((string) $_GET['rewrite']) !== '') {
    $rewritePath = trim((string) $_GET['rewrite']);
    // 兼容 try_files $uri 带 /article/ 前缀的写法
    $rewritePath = preg_replace('#^/?article/#i', '', $rewritePath);
    // 兼容简化伪静态规则: 去除末尾 .html 后缀
    $rewritePath = preg_replace('/\.html$/i', '', $rewritePath);
    if (preg_match('/^category-(.+)-page-(\d+)$/i', $rewritePath, $m)) {
        $_GET['cat'] = $m[1];
        $_GET['page'] = intval($m[2]);
    } elseif (preg_match('/^category-(.+)$/i', $rewritePath, $m)) {
        $_GET['cat'] = $m[1];
    } elseif (preg_match('/^archive-(\d{4}-\d{2})-page-(\d+)$/i', $rewritePath, $m)) {
        $_GET['month'] = $m[1];
        $_GET['page'] = intval($m[2]);
    } elseif (preg_match('/^archive-(\d{4}-\d{2})$/i', $rewritePath, $m)) {
        $_GET['month'] = $m[1];
    } elseif (preg_match('/^page-(\d+)$/i', $rewritePath, $m)) {
        $_GET['page'] = intval($m[1]);
    } elseif (preg_match('#^page/(.+)$#i', $rewritePath, $m)) {
        // 独立页面(伪静态 /page/{slug}.html, 重写段已去除 .html 后缀)
        $_GET['page'] = $m[1];
    } elseif (preg_match('/^post(\d+)$/i', $rewritePath, $m)) {
        $_GET['id'] = intval($m[1]);
    } elseif (preg_match('/^site(\d+)$/i', $rewritePath, $m)) {
        $_GET['site'] = intval($m[1]);
    } elseif (preg_match('/^\d+$/', $rewritePath)) {
        $_GET['id'] = intval($rewritePath);
    } else {
        // 自定义模板(可能含目录), 取最后一段识别
        $segments = explode('/', $rewritePath);
        $last = (string) end($segments);
        if (preg_match('/^post(\d+)$/i', $last, $m)) {
            $_GET['id'] = intval($m[1]);
        } elseif (preg_match('/^\d+$/', $last)) {
            $_GET['id'] = intval($last);
        } else {
            $_GET['slug'] = $last;
        }
    }
}

// ---------- 路由 ----------
$siteId = isset($_GET['site']) ? intval($_GET['site']) : 0;
$artId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($siteId > 0) {
    // 导航链接详情页(由博客模块渲染, 复用主题与评论系统)
    require __DIR__ . '/site_route.php';
} elseif ($artId > 0) {
    // 单篇文章
    $row = $DB->get_row(
        "SELECT * FROM `lylme_article` WHERE `art_id` = {$artId} AND `art_status` = 1"
    );
    if ($row) {
        // 浏览量统计(按会话去重)
        article_count_view($artId);

        // 关联分类信息
        $cat = $archive->getCategory(intval($row['cat_id']));
        if ($cat) {
            $row['cat_name'] = $cat['cat_name'];
            $row['cat_alias'] = $cat['cat_alias'];
        }

        $archive->setArchive('post', [$row], true);
        $archive->setTotal(1);
        $archive->archiveTitleStr = $row['art_title'];
        $archive->archiveSlug = '';
    } else {
        $archive->setArchive('404', [], false);
        $archive->archiveTitleStr = '404';
        http_response_code(404);
    }
} elseif (isset($_GET['slug']) && trim((string) $_GET['slug']) !== '') {
    // 伪静态 slug 单篇文章
    $slugParam = trim((string) $_GET['slug']);
    $slugEscaped = $DB->escape($slugParam);
    $row = $DB->get_row(
        "SELECT * FROM `lylme_article` WHERE `art_slug` = '{$slugEscaped}' AND `art_status` = 1 LIMIT 1"
    );
    if ($row) {
        // 浏览量统计(按会话去重)
        article_count_view($row['art_id']);

        // 关联分类信息
        $cat = $archive->getCategory(intval($row['cat_id']));
        if ($cat) {
            $row['cat_name'] = $cat['cat_name'];
            $row['cat_alias'] = $cat['cat_alias'];
        }

        $archive->setArchive('post', [$row], true);
        $archive->setTotal(1);
        $archive->archiveTitleStr = $row['art_title'];
        $archive->archiveSlug = '';
    } else {
        $archive->setArchive('404', [], false);
        $archive->archiveTitleStr = '404';
        http_response_code(404);
    }
} elseif (isset($_GET['cat']) && $_GET['cat'] !== '') {
    // 分类列表
    $catParam = trim((string) $_GET['cat']);
    $catParamEscaped = $DB->escape($catParam);

    $cat = $DB->get_row(
        "SELECT * FROM `lylme_article_cat` WHERE `cat_alias` = '{$catParamEscaped}' OR `cat_id` = '{$catParamEscaped}' LIMIT 1"
    );

    if (!$cat) {
        $archive->setArchive('404', [], false);
        $archive->archiveTitleStr = '404';
        http_response_code(404);
    } else {
        $catId = intval($cat['cat_id']);
        $page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
        $pageSize = max(1, intval(isset($conf['article_perpage']) ? $conf['article_perpage'] : 10));
        $offset = ($page - 1) * $pageSize;

        $total = intval($DB->get_column(
            "SELECT COUNT(*) FROM `lylme_article` WHERE `cat_id` = {$catId} AND `art_status` = 1"
        ));

        $rows = [];
        $result = $DB->query(
            "SELECT * FROM `lylme_article` WHERE `cat_id` = {$catId} AND `art_status` = 1 "
            . "ORDER BY `art_top` DESC, `art_time` DESC, `art_id` DESC LIMIT {$offset}, {$pageSize}"
        );
        if ($result) {
            while ($r = $DB->fetch($result)) {
                $r['cat_name'] = $cat['cat_name'];
                $r['cat_alias'] = $cat['cat_alias'];
                $rows[] = $r;
            }
        }

        $archive->setArchive('category', $rows, false, $cat['cat_alias']);
        $archive->setTotal($total);
        $archive->currentPage = $page;
        $archive->pageSize = $pageSize;
        $archive->archiveTitleStr = $cat['cat_name'];
        $archive->archiveKeywords = '';
    }
} elseif (isset($_GET['s']) && trim((string) $_GET['s']) !== '') {
    // 搜索
    $keyword = trim(strip_tags((string) $_GET['s']));
    $keywordEscaped = $DB->escape($keyword);
    // 转义 LIKE 通配符, 使 % 与 _ 按普通字符匹配 (MySQL LIKE 默认转义符为反斜杠)
    $likeKeyword = str_replace(['%', '_'], ['\\%', '\\_'], $keywordEscaped);
    $page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
    $pageSize = max(1, intval(isset($conf['article_perpage']) ? $conf['article_perpage'] : 10));
    $offset = ($page - 1) * $pageSize;

    $where = "`art_status` = 1 AND (`art_title` LIKE '%{$likeKeyword}%' "
        . "OR `art_content` LIKE '%{$likeKeyword}%' OR `art_keywords` LIKE '%{$likeKeyword}%')";

    $total = intval($DB->get_column("SELECT COUNT(*) FROM `lylme_article` WHERE {$where}"));

    $rows = [];
    $result = $DB->query(
        "SELECT * FROM `lylme_article` WHERE {$where} ORDER BY `art_time` DESC, `art_id` DESC LIMIT {$offset}, {$pageSize}"
    );
    if ($result) {
        while ($r = $DB->fetch($result)) {
            $cat = $archive->getCategory(intval($r['cat_id']));
            $r['cat_name'] = isset($cat['cat_name']) ? $cat['cat_name'] : '';
            $r['cat_alias'] = isset($cat['cat_alias']) ? $cat['cat_alias'] : '';
            $rows[] = $r;
        }
    }

    $archive->setArchive('search', $rows, false, '');
    $archive->setTotal($total);
    $archive->currentPage = $page;
    $archive->pageSize = $pageSize;
    $archive->archiveTitleStr = '搜索: ' . $keyword;
    $archive->archiveKeywords = $keyword;
} elseif (isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $_GET['month'])) {
    // 按月归档
    $month = (string) $_GET['month'];
    $page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
    $pageSize = max(1, intval(isset($conf['article_perpage']) ? $conf['article_perpage'] : 10));
    $offset = ($page - 1) * $pageSize;

    $total = intval($DB->get_column(
        "SELECT COUNT(*) FROM `lylme_article` WHERE `art_status` = 1 AND DATE_FORMAT(`art_time`, '%Y-%m') = '{$month}'"
    ));

    $rows = [];
    $result = $DB->query(
        "SELECT * FROM `lylme_article` WHERE `art_status` = 1 AND DATE_FORMAT(`art_time`, '%Y-%m') = '{$month}' "
        . "ORDER BY `art_time` DESC, `art_id` DESC LIMIT {$offset}, {$pageSize}"
    );
    if ($result) {
        while ($r = $DB->fetch($result)) {
            $cat = $archive->getCategory(intval($r['cat_id']));
            $r['cat_name'] = isset($cat['cat_name']) ? $cat['cat_name'] : '';
            $r['cat_alias'] = isset($cat['cat_alias']) ? $cat['cat_alias'] : '';
            $rows[] = $r;
        }
    }

    $archive->setArchive('index', $rows, false, '');
    $archive->setTotal($total);
    $archive->currentPage = $page;
    $archive->pageSize = $pageSize;
    $archive->archiveTitleStr = $month . ' 的归档文章';
    $archive->archiveKeywords = '';
} elseif (isset($_GET['page']) && trim((string) $_GET['page']) !== '' && !preg_match('/^\d+$/', (string) $_GET['page'])) {
    // 独立页面 (slug, 非数字以避免与首页分页 ?page=N 冲突)
    $pageSlug = trim((string) $_GET['page']);
    $pageSlugEscaped = $DB->escape($pageSlug);
    $prow = $DB->get_row("SELECT * FROM `lylme_article_page` WHERE `page_slug` = '{$pageSlugEscaped}' AND `page_status` = 1 LIMIT 1");
    if ($prow) {
        // 映射为 Archive 可识别的 art_* 字段
        $row = [
            'art_id'      => $prow['page_id'],
            'art_title'   => $prow['page_title'],
            'art_slug'    => $prow['page_slug'],
            'art_content' => $prow['page_content'],
            'art_excerpt' => isset($prow['page_excerpt']) ? $prow['page_excerpt'] : '',
            'art_author'  => '管理员',
            'art_time'    => $prow['page_time'],
            'art_update'  => $prow['page_update'],
            'cat_id'      => 0,
        ];
        $archive->setArchive('page', [$row], true);
        $archive->setTotal(1);
        $archive->archiveTitleStr = $prow['page_title'];
        $archive->archiveSlug = $prow['page_slug'];
    } else {
        $archive->setArchive('404', [], false);
        $archive->archiveTitleStr = '404';
        http_response_code(404);
    }
} else {
    // 首页文章列表
    $page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
    $pageSize = max(1, intval(isset($conf['article_perpage']) ? $conf['article_perpage'] : 10));
    $offset = ($page - 1) * $pageSize;

    $total = intval($DB->get_column(
        "SELECT COUNT(*) FROM `lylme_article` WHERE `art_status` = 1"
    ));

    $rows = [];
    $result = $DB->query(
        "SELECT * FROM `lylme_article` WHERE `art_status` = 1 "
        . "ORDER BY `art_top` DESC, `art_time` DESC, `art_id` DESC LIMIT {$offset}, {$pageSize}"
    );
    if ($result) {
        while ($r = $DB->fetch($result)) {
            $cat = $archive->getCategory(intval($r['cat_id']));
            $r['cat_name'] = isset($cat['cat_name']) ? $cat['cat_name'] : '';
            $r['cat_alias'] = isset($cat['cat_alias']) ? $cat['cat_alias'] : '';
            $rows[] = $r;
        }
    }

    $archive->setArchive('index', $rows, false, '');
    $archive->setTotal($total);
    $archive->currentPage = $page;
    $archive->pageSize = $pageSize;
    $archive->archiveTitleStr = '';
    $archive->archiveKeywords = '';
}

// 渲染主题模板
$archive->render();

// 评论提交结果提示 (一次性 flash, 页面加载后弹出, 刷新不再重复)
if (isset($_SESSION['article_comment_flash'])) {
    $flashMsg = (string) $_SESSION['article_comment_flash'];
    unset($_SESSION['article_comment_flash']);
    echo '<script>window.addEventListener("DOMContentLoaded",function(){alert(' . json_encode($flashMsg) . ');});</script>';
}

<?php

/**
 * LyLmecho 默认主题 lylmeblog —— 首页（唯一页面）
 *
 * 视觉与博客模块默认主题 LyLme Modern 同源：
 *   后台 Lightyear 基座 + css/base.css 皮肤 + js/script.js 交互
 * 在此之上承载导航站能力：全网搜索 / 站内检索 / 分组直达，
 * 并内嵌「博客联动」区块，让博客成为导航首页的第二信息流。
 *
 * 依赖：css/base.css、css/style.css、js/script.js、js/nav.js
 */
global $conf, $DB, $site;
require_once __DIR__ . '/functions.php';

/* ---------- 站点基础信息 ---------- */
$title        = isset($conf['title']) ? (string) $conf['title'] : 'LyLmecho';
$mtitle       = isset($conf['title']) ? explode("-", $conf['title'])[0] : 'LyLmecho';
$desc         = isset($conf['description']) ? (string) $conf['description'] : '';
$logo         = isset($conf['logo']) ? (string) $conf['logo'] : '';
$homeBase     = theme_site_base();
$homeUrl      = $homeBase;

/* ---------- 主题配置 ---------- */
// 主色跟随博客主题主色（lylmePrimary）；博客未配置时回退到导航自身 color 设置
$colorRaw     = theme_blog_primary(theme_config('color', '#4f7cf7'));
$color        = preg_match('/^#?[0-9a-fA-F]{6}$/', (string) $colorRaw) ? (string) $colorRaw : '#4f7cf7';
if (strpos($color, '#') !== 0) {
    $color = '#' . $color;
}
$palette      = theme_palette($color);
$scheme       = in_array(theme_config('scheme', 'auto'), array('auto', 'default', 'dark'), true) ? theme_config('scheme', 'auto') : 'auto';
$linkCols     = max(2, min(7, (int) theme_config('link_cols', 5)));
$modulesRaw   = theme_config('modules', array('blog', 'hot'));
$modules      = is_array($modulesRaw) ? $modulesRaw : array($modulesRaw);
$blogShow     = theme_blog_enabled() && in_array('blog', $modules);
$hotShow      = $blogShow && in_array('hot', $modules);
$clockShow    = in_array('clock', $modules);
$notice       = (string) theme_config('notice', '');
$blogLatestN  = max(3, min(10, (int) theme_config('blog_latest', 6)));

/* ---------- 导航统计与博客联动数据 ---------- */
$navStats     = theme_nav_counts();
$groupsQuick  = theme_group_quick(8);
$blogBase     = theme_blog_base();
$blogFeed     = $blogBase . '?feed=rss';
$blogStats    = $blogShow ? theme_blog_stats() : array('posts' => 0, 'cats' => 0, 'comments' => 0);
$blogLatest   = $blogShow ? theme_blog_latest($blogLatestN) : array();
$blogHot      = $hotShow ? theme_blog_hot(5) : array();
$blogCats     = $blogShow ? theme_blog_cats(12) : array();
$adminUrl     = $homeBase . ADMIN_PATH;

/* ---------- 搜索引擎 ---------- */
$engines      = theme_sou();
$builtinList  = array();
/* 内置引擎：博客文章（站内跳转）+ 站内检索（即时过滤，不跳页） */
if ($blogShow) {
    $builtinList[] = array(
        'alias' => 'blog',
        'name'  => '博客文章',
        'hint'  => '搜索博客文章…',
        'mode'  => 'go',
        'link'  => $blogBase,
        'color' => '',
        'icon'  => '<i class="mdi mdi-post-outline"></i>',
    );
}
/* 站内检索：filter 模式，选中后在搜索框输入即实时过滤收录链接（见 nav.js） */
$builtinList[] = array(
    'alias' => 'local',
    'name'  => '站内',
    'hint'  => '筛选收录链接…',
    'mode'  => 'filter',
    'link'  => '',
    'color' => '',
    'icon'  => '<i class="mdi mdi-link-variant"></i>',
);

/* 下拉选择列表 = 内置功能（站内检索 / 博客文章）+ 数据库引擎；
 * 默认选中仍沿用首个数据库引擎（无数据库引擎时才落到站内检索） */
$souAll = array_merge($builtinList, $engines);
$souDefault = isset($engines[0]) && is_array($engines[0]) ? $engines[0] : null;
$souDefaultAlias = ($souDefault !== null && isset($souDefault['alias']) && $souDefault['alias'] !== '') ? $souDefault['alias'] : 'nav';
$souDefaultName = ($souDefault !== null && isset($souDefault['name']) && $souDefault['name'] !== '') ? $souDefault['name'] : '站内检索';
$souDefaultIcon = $souDefault !== null ? lylmeblog_engine_icon($souDefault) : '<i class="mdi mdi-text-search"></i>';
$souDefaultHint = ($souDefault !== null && isset($souDefault['hint']) && $souDefault['hint'] !== '') ? $souDefault['hint'] : '输入关键词 或 直接输入网址';

/* 搜索引擎渲染：输入 + 图标 + 名称 */
function lylmeblog_engine_icon($engine)
{
    $icon = isset($engine['icon']) ? (string) $engine['icon'] : '';
    if ($icon === '') {
        return '<i class="mdi mdi-web"></i>';
    }
    if (strpos($icon, '<') !== false) {
        return $icon;
    }
    return '<img src="' . theme_e($icon) . '" alt="" loading="lazy">';
}
?>

<!DOCTYPE html>
<html lang="zh-CN" data-default-color-scheme="<?php echo theme_e($scheme); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo theme_e($title); ?><?php echo $desc !== '' ? ' - ' . theme_e($desc) : ''; ?></title>
    <meta name="description" content="<?php echo theme_e($desc !== '' ? $desc : '个人导航 / 快捷收录与博客'); ?>">
    <meta name="renderer" content="webkit">
    <meta name="force-rendering" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no">
    <meta name="x5-page-mode" content="app">
    <meta name="theme-color" id="lylme-theme-color" content="#f4f6fb">
    <?php if ($logo !== ''): ?>
        <link rel="icon" href="<?php echo theme_e($logo); ?>">
        <link rel="apple-touch-icon" href="<?php echo theme_e($logo); ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?php echo theme_e($homeBase); ?>">
    <?php if ($blogShow): ?>
        <link rel="alternate" type="application/rss+xml" title="<?php echo theme_e($title); ?>" href="<?php echo theme_e($blogFeed); ?>">
    <?php endif; ?>

    <!-- 后台同款基座: Bootstrap 5 + Material Design Icons + Lightyear -->
    <link rel="stylesheet" href="<?php echo theme_e(theme_asset('assets/admin/css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo theme_e(theme_asset('assets/admin/css/materialdesignicons.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo theme_e(theme_asset('assets/admin/css/style.min.css')); ?>">

    <!-- 博客模块同款皮肤 + 导航页专属样式 -->
    <?php theme_css('css/base.css'); ?>
    <?php theme_css('css/style.css'); ?>

    <style>
        :root {
            --lylme-primary: <?php echo theme_e($palette['base']); ?>;
            --lylme-primary-rgb: <?php echo theme_e($palette['rgb']); ?>;
            --lylme-primary-deep: <?php echo theme_e($palette['deep']); ?>;
            --lylme-accent: <?php echo theme_e($palette['accent']); ?>;
            --lylme-accent-rgb: <?php echo theme_e($palette['accentRgb']); ?>;
            --lylme-primary-soft: <?php echo theme_e($palette['soft']); ?>;
            --lylme-primary-line: <?php echo theme_e($palette['line']); ?>;
        }

        .lnav-tile-desc:empty {
            display: none;
        }

        .lnav-tile-ico:empty,
        .lnav-group-ico:empty {
            display: none;
        }
    </style>

    <!-- 预设配色: 在 body 绘制前设定, 避免主题闪烁 (与博客/后台共用 localStorage.theme 键) -->
    <script>
        (function() {
            var pref = <?php echo json_encode($scheme); ?>;
            var saved = null;
            try {
                saved = localStorage.getItem('theme');
            } catch (e) {}
            var t;
            if (saved === 'dark' || saved === 'default') {
                t = saved;
            } else if (pref === 'dark' || pref === 'default') {
                t = pref;
            } else if (pref === 'auto' && window.matchMedia) {
                t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'default';
            } else {
                t = 'default';
            }
            document.documentElement.setAttribute('data-theme', t);
            var tc = document.getElementById('lylme-theme-color');
            if (tc) {
                tc.setAttribute('content', t === 'dark' ? '#12141f' : '#f4f6fb');
            }
        })();
    </script>
</head>

<body class="lylme-body lylme-kind-index">
    <script>
        // body 上色 (style.min.css 使用 body[data-theme='dark'] 选择器)
        document.body.setAttribute('data-theme', document.documentElement.getAttribute('data-theme') || 'default');
    </script>

    <!-- 键盘用户跳过导航直达正文 -->
    <a class="lylme-skip" href="#lylme-main">跳到主要内容</a>

    <!-- 全站氛围层 -->
    <div class="lylme-bg-deco" aria-hidden="true"><span class="lylme-bg-blob b1"></span><span class="lylme-bg-blob b2"></span><span class="lylme-bg-grid"></span></div>

    <div class="lyear-layout-web lylme-layout">
        <div class="lyear-layout-container">

            <!-- ==================== 左侧导航 ==================== -->
            <aside class="lyear-layout-sidebar lylme-sidebar" id="lylme-sidebar">
                <div class="lylme-brand">
                    <a class="lylme-brand-link" href="<?php echo theme_e($homeUrl); ?>" title="<?php echo theme_e($mtitle); ?>">
                        <?php if ($logo !== ''): ?>
                            <img class="lylme-brand-logo" src="<?php echo theme_e($logo); ?>" alt="<?php echo theme_e($mtitle); ?>" width="44" height="44">
                        <?php else: ?>
                            <span class="lylme-brand-badge"><i class="mdi mdi-compass-outline"></i></span>
                        <?php endif; ?>
                        <span class="lylme-brand-text">
                            <b><?php echo theme_e($mtitle); ?></b>
                            <?php if ($desc !== ''): ?><em><?php echo theme_e($desc); ?></em><?php endif; ?>
                        </span>
                    </a>
                </div>

                <div class="lyear-layout-sidebar-scroll lylme-sidebar-scroll">
                    <nav class="sidebar-main lylme-nav" role="navigation">
                        <ul class="nav nav-drawer">
                            <li class="nav-item active">
                                <a href="<?php echo theme_e($homeUrl); ?>" aria-current="page"><i class="mdi mdi-home-map-marker"></i>导航首页</a>
                            </li>
                            <?php if ($blogShow): ?>
                                <li class="nav-item">
                                    <a href="<?php echo theme_e($blogBase); ?>"><i class="mdi mdi-compass-outline"></i>博客文章</a>
                                </li>
                                <?php if (count($blogCats) > 0): ?>
                                    <li class="nav-item nav-item-has-subnav">
                                        <button type="button" class="nav-subnav-toggle" aria-controls="lylme-subnav-navcat" aria-expanded="false">
                                            <i class="mdi mdi-folder-outline"></i>文章分类
                                        </button>
                                        <ul class="nav nav-subnav" id="lylme-subnav-navcat">
                                            <?php foreach ($blogCats as $cat): ?>
                                                <li><a href="<?php echo theme_e($cat['url']); ?>"><?php echo theme_e($cat['name']); ?><span class="lylme-nav-count"><?php echo $cat['count']; ?></span></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($blogShow): ?>
                                <li class="nav-item">
                                    <a href="<?php echo theme_e($blogFeed); ?>" target="_blank" rel="noopener"><i class="mdi mdi-rss"></i>RSS 订阅</a>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </nav>

                    <div class="sidebar-footer lylme-sidebar-footer">
                        <p class="copyright">
                            &copy; <?php echo date('Y'); ?> <?php echo theme_e($title); ?> · LyLmecho
                        </p>
                    </div>
                </div>
            </aside>
            <!-- ==================== End 左侧导航 ==================== -->

            <!-- ==================== 顶部工具栏 ==================== -->
            <header class="lyear-layout-header lylme-topbar">
                <nav class="navbar">
                    <div class="topbar w-100">
                        <div class="topbar-left">
                            <div class="lyear-aside-toggler" title="展开/收起导航">
                                <span class="lyear-toggler-bar"></span>
                                <span class="lyear-toggler-bar"></span>
                                <span class="lyear-toggler-bar"></span>
                            </div>
                            <span class="navbar-page-title lylme-crumb-title">
                                <i class="mdi mdi-shape-outline"></i> <?php echo theme_e($mtitle); ?>
                            </span>
                        </div>

                        <?php if ($blogShow): ?>
                            <form class="lylme-topbar-search" method="post" action="<?php echo theme_e($blogBase); ?>" role="search" data-topbar="blog">
                                <i class="mdi mdi-magnify"></i>
                                <input type="text" name="s" placeholder="搜索博客文章..." autocomplete="off">
                            </form>
                        <?php endif; ?>
                        <form class="lylme-topbar-search" role="search" data-topbar="filter">
                            <i class="mdi mdi-text-search"></i>
                            <input type="text" placeholder="站内查找…" autocomplete="off">
                        </form>

                        <ul class="topbar-right">
                            <?php if ($clockShow): ?>
                                <li class="d-none d-md-flex align-items-center">
                                    <span class="lnav-clock">
                                        <b id="clock-time">--:--:--</b>
                                        <small id="clock-date"></small>
                                    </span>
                                </li>
                            <?php endif; ?>
                            <li class="lylme-scheme-item">
                                <button type="button" class="btn btn-sm lylme-scheme-toggle" id="lylme-scheme-toggle" title="切换明暗">
                                    <i class="mdi mdi-weather-night"></i>
                                </button>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <!-- ==================== End 顶部工具栏 ==================== -->

            <!-- ==================== 主内容区 ==================== -->
            <main class="lyear-layout-content lylme-content">
                <div class="container-fluid">
                    <div class="row g-4">
                        <div class="<?php echo $blogShow ? 'col-lg-8 col-12' : 'col-12'; ?>" id="lylme-main" role="main" tabindex="-1">

                            <!-- ===== Hero：搜索 + 统计 + 快速定位 ===== -->
                            <section class="lylme-hero card">
                                <div class="lylme-hero-body">
                                    <div class="lylme-hero-main">
                                        <div class="lylme-hero-eyebrow"><i class="mdi mdi-shimmer"></i><?php echo theme_e($desc !== '' ? $desc : '高效上网，价值沉淀'); ?></div>
                                        <h1 class="lylme-hero-title"><?php echo theme_e($mtitle); ?></h1>
                                        <p class="lylme-hero-lead">已收录 <b><?php echo $navStats['links']; ?></b> 个网址, <b><?php echo $navStats['groups']; ?></b> 个分组, 欢迎探索与直达。</p>
                                        <form class="lylme-hero-panel" id="search-form" role="search" autocomplete="off">
                                            <div class="lhp-row">
                                                <div class="lhp-engine-wrap" id="lhp-engine-wrap">
                                                    <button type="button" class="lhp-engine" id="lhp-engine-btn"
                                                        aria-haspopup="listbox" aria-expanded="false"
                                                        aria-label="选择搜索方式：<?php echo theme_e($souDefaultName); ?>">
                                                        <span class="lhp-engine-ico" id="lhp-engine-ico"><?php echo $souDefaultIcon; ?></span>
                                                        <span class="lhp-engine-name" id="lhp-engine-name"><?php echo theme_e($souDefaultName); ?></span>
                                                        <i class="mdi mdi-chevron-down" aria-hidden="true"></i>
                                                    </button>
                                                    <div class="lhp-layer lhp-engine-pop" id="lhp-engine-pop" role="listbox"
                                                        data-default="<?php echo theme_e($souDefaultAlias); ?>" hidden>
                                                        <?php $optI = 0;
                                                        foreach ($souAll as $engOpt): ?>
                                                            <?php
                                                            $oAlias = (isset($engOpt['alias']) && $engOpt['alias'] !== '') ? $engOpt['alias'] : 'eng' . $optI;
                                                            $oMode  = (isset($engOpt['mode']) && $engOpt['mode'] !== '') ? $engOpt['mode'] : 'external';
                                                            $oLink  = isset($engOpt['link']) ? (string) $engOpt['link'] : '';
                                                            $oHint  = (isset($engOpt['hint']) && $engOpt['hint'] !== '') ? $engOpt['hint'] : '搜索…';
                                                            $oName  = isset($engOpt['name']) ? (string) $engOpt['name'] : '搜索';
                                                            $oColor = isset($engOpt['color']) ? (string) $engOpt['color'] : '';
                                                            $oIsDef = $oAlias === $souDefaultAlias;
                                                            if ($oMode === 'filter') {
                                                                continue;
                                                            }
                                                            ?>
                                                            <button type="button" class="lhp-eng-opt<?php echo $oMode === 'filter' ? ' is-filter' : ''; ?>"
                                                                role="option"
                                                                aria-selected="<?php echo $oIsDef ? 'true' : 'false'; ?>"
                                                                data-alias="<?php echo theme_e($oAlias); ?>"
                                                                data-mode="<?php echo theme_e($oMode); ?>"
                                                                data-link="<?php echo theme_e($oLink); ?>"
                                                                data-hint="<?php echo theme_e($oHint); ?>"
                                                                data-name="<?php echo theme_e($oName); ?>"
                                                                style="<?php echo $oColor !== '' ? '--engine-color:' . theme_e($oColor) : ''; ?>">
                                                                <span class="lhp-eng-ico"><?php echo lylmeblog_engine_icon($engOpt); ?></span>
                                                                <span class="lhp-eng-name"><?php echo theme_e($oName); ?></span>
                                                                <?php if ($oMode === 'filter'): ?><em class="lhp-eng-tag">本站</em>
                                                                <?php elseif ($oMode === 'go'): ?><em class="lhp-eng-tag">博客</em><?php endif; ?>
                                                            </button>
                                                        <?php $optI++;
                                                        endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="lhp-input-wrap" id="lhp-input-wrap">
                                                    <!-- <i class="mdi mdi-magnify" aria-hidden="true"></i> -->
                                                    <input type="text" id="search-input" placeholder="<?php echo theme_e($souDefaultHint); ?>" autocomplete="off" spellcheck="false" aria-label="搜索关键词">
                                                    <button type="submit" class="lhp-search-btn"><i class="mdi mdi-send"></i>搜索</button>
                                                    <ul class="lhp-layer lhp-suggest" id="lhp-suggest" role="listbox" aria-label="搜索联想" hidden></ul>
                                                </div>
                                            </div>
                                            <div class="lnav-filterbar" id="lnav-filterbar" role="status">
                                                <i class="mdi mdi-text-search"></i>
                                                已筛选出 <b id="lnav-filtercount">0</b> 条结果
                                                <button type="button" id="lnav-filterclear"><i class="mdi mdi-close-circle-outline"></i> 清除</button>
                                            </div>
                                        </form>
                                        <?php if (count($groupsQuick) > 0): ?>
                                            <div class="lylme-hero-cats">
                                                <span class="hc-label"><i class="mdi mdi-shape-outline"></i>快速定位</span>
                                                <?php foreach ($groupsQuick as $gk): ?>
                                                    <a class="hc-chip" href="#group-<?php echo $gk['id']; ?>"><?php echo theme_e($gk['name']); ?><em><?php echo intval($gk['count']); ?></em></a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="lylme-hero-aside">
                                        <div class="lylme-hero-stats" role="list">
                                            <span class="hs-item" role="listitem"><i class="mdi mdi-web"></i>收录<b data-count="<?php echo $navStats['links']; ?>"><?php echo $navStats['links']; ?></b></span>
                                            <span class="hs-item" role="listitem"><i class="mdi mdi-tag-multiple-outline"></i>分组<b data-count="<?php echo $navStats['groups']; ?>"><?php echo $navStats['groups']; ?></b></span>
                                            <?php if ($blogShow): ?>
                                                <span class="hs-item" role="listitem"><i class="mdi mdi-post-outline"></i>文章<b data-count="<?php echo $blogStats['posts']; ?>"><?php echo $blogStats['posts']; ?></b></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="lylme-hero-deco" aria-hidden="true">
                                    <span class="ring r1"></span><span class="ring r2"></span>
                                    <span class="dot d1"></span><span class="dot d2"></span><span class="dot d3"></span>
                                    <i class="mdi mdi-feather mdi-float"></i>
                                </div>
                            </section>

                            <?php if ($notice !== ''): ?>
                                <div class="lnav-notice"><?php echo $notice; /* 由站长填写, 视为可信内容 */ ?></div>
                            <?php endif; ?>

                            <!-- ===== 导航分组：接入核心列表生成 ===== -->
                            <div class="lnav-groups">
                                <?php
                                /* lists($html) 契约：
                             * g1/g2 在每条链接之前输出, l1+l2+l3 是每条链接, g3 在分组结束时输出。
                             * 本主题将其组装为一个「分组卡片」。
                             */
                                $html = array(
                                    'g1' => '<section class="card lylme-widget lnav-group" id="group-{group_id}">',
                                    'g2' => '<div class="lnav-group-head"><span class="lnav-group-ico">{group_icon}</span><h2 class="lnav-group-name">{group_name}</h2></div><div class="lnav-grid" style="--lnav-cols:' . $linkCols . '">',
                                    'l1' => '<a class="lnav-tile" href="{link_url}" target="_blank" rel="nofollow noopener">',
                                    'l2' => '<span class="lnav-tile-ico">{link_icon}</span><span class="lnav-tile-text"><span class="lnav-tile-name">{link_name}</span><span class="lnav-tile-desc">{link_desc}</span></span>',
                                    'l3' => '</a>',
                                    'g3' => '</div></section>',
                                );
                                lists($html);
                                ?>
                            </div>

                        </div><!-- /#lylme-main .col -->

                        <?php if ($blogShow): ?>
                            <!-- ==================== 博客联动侧栏 ==================== -->
                            <div class="col-lg-4 col-12 lylme-sidecol">

                                <!-- 博客信息卡 -->
                                <div class="card lylme-widget lylme-about">
                                    <div class="lylme-about-banner"><span class="lylme-about-glow"></span></div>
                                    <div class="lylme-about-body">
                                        <div class="lylme-about-avatar">
                                            <?php if ($logo !== ''): ?>
                                                <img class="lnav-about-logo" src="<?php echo theme_e($logo); ?>" alt="<?php echo theme_e($title); ?>">
                                            <?php else: ?>
                                                <img class="lnav-about-logo" src="<?php echo theme_e(theme_asset('assets/img/logo.png')); ?>" alt="logo">
                                            <?php endif; ?>
                                        </div>
                                        <div class="lylme-about-name"><?php echo theme_e($mtitle); ?></div>
                                        <div class="lylme-about-desc"><?php echo theme_e($desc !== '' ? $desc : '高效上网，价值沉淀'); ?></div>
                                        <div class="lylme-about-mini">
                                            <i class="mdi mdi-post-outline"></i> 文章 <?php echo $blogStats['posts']; ?>
                                            <span class="sep"></span>
                                            <i class="mdi mdi-folder-outline"></i> 分类 <?php echo $blogStats['cats']; ?>
                                            <span class="sep"></span>
                                            <i class="mdi mdi-comment-outline"></i> 评论 <?php echo $blogStats['comments']; ?>
                                        </div>
                                        <div class="lylme-about-actions lnav-about-tools">
                                            <a class="btn btn-sm btn-primary" href="<?php echo theme_e($blogBase); ?>">
                                                <i class="mdi mdi-post-outline"></i> 博客
                                            </a>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo theme_e($blogFeed); ?>" target="_blank" rel="noopener">
                                                <i class="mdi mdi-rss"></i> RSS
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php if (count($blogLatest) > 0): ?>
                                    <!-- 最新文章 -->
                                    <div class="card lylme-widget">
                                        <div class="lylme-widget-head">
                                            <h4><i class="mdi mdi-pen-outline"></i>最新文章</h4>
                                            <span class="lylme-widget-badge"><?php echo count($blogLatest); ?></span>
                                        </div>
                                        <div class="lylme-widget-body">
                                            <div class="lnav-list">
                                                <?php foreach ($blogLatest as $post): ?>
                                                    <a class="lnav-entry" href="<?php echo theme_e($post['url']); ?>">
                                                        <span class="lnav-entry-ico"><i class="mdi mdi-file-document-outline"></i></span>
                                                        <span class="lnav-entry-main">
                                                            <span class="lnav-entry-title"><?php echo theme_e($post['title']); ?></span>
                                                            <span class="lnav-entry-meta">
                                                                <?php if ($post['cat'] !== ''): ?>
                                                                    <span><i class="mdi mdi-folder-outline"></i><?php echo theme_e($post['cat']); ?></span>
                                                                <?php endif; ?>
                                                                <span><i class="mdi mdi-clock-outline"></i><?php echo theme_time_ago($post['time']); ?></span>
                                                            </span>
                                                        </span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($hotShow && count($blogHot) > 0): ?>
                                    <!-- 热门文章 -->
                                    <div class="card lylme-widget">
                                        <div class="lylme-widget-head">
                                            <h4><i class="mdi mdi-fire"></i>热门文章</h4>
                                            <span class="lylme-widget-badge">TOP</span>
                                        </div>
                                        <div class="lylme-widget-body">
                                            <div class="lnav-list">
                                                <?php $rankI = 1;
                                                foreach ($blogHot as $post): ?>
                                                    <a class="lnav-entry" href="<?php echo theme_e($post['url']); ?>">
                                                        <em class="lnav-rank r<?php echo $rankI; ?>"><?php echo $rankI; ?></em>
                                                        <span class="lnav-entry-main">
                                                            <span class="lnav-entry-title"><?php echo theme_e($post['title']); ?></span>
                                                            <span class="lnav-entry-meta">
                                                                <span><i class="mdi mdi-eye-outline"></i><?php echo $post['views']; ?> 阅读</span>
                                                            </span>
                                                        </span>
                                                    </a>
                                                <?php $rankI++;
                                                endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (count($blogCats) > 0): ?>
                                    <!-- 文章分类 -->
                                    <div class="card lylme-widget">
                                        <div class="lylme-widget-head">
                                            <h4><i class="mdi mdi-folder-open-outline"></i>文章分类</h4>
                                            <span class="lylme-widget-badge"><?php echo count($blogCats); ?></span>
                                        </div>
                                        <div class="lylme-widget-body">
                                            <div class="lnav-cats">
                                                <?php foreach ($blogCats as $cat): ?>
                                                    <a class="lnav-cat" href="<?php echo theme_e($cat['url']); ?>">
                                                        <?php echo theme_e($cat['name']); ?><em><?php echo $cat['count']; ?></em>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (theme_config('lytoday', 0) == 1) {
                                    echo theme_config('lytodaycode');
                                } ?>
                            </div>
                            <!-- ==================== End 博客联动侧栏 ==================== -->
                        <?php endif; ?>

                    </div><!-- /.row g-4 -->
                </div><!-- /.container-fluid -->

                <div class="lylme-footer-bar">
                    <div class="container-fluid">
                        <div class="lylme-footer-inner">
                            <div class="lylme-footer-copy">
                                &copy; <?php echo date('Y'); ?>
                                <a href="<?php echo theme_e($homeUrl); ?>"><?php echo theme_e($title); ?></a>
                                &nbsp;·&nbsp; Powered By
                                <a href="https://github.com/LyLme/LyLmecho" target="_blank" rel="noopener">LyLmecho</a>
                                <?php echo (isset($conf['wztj']) && $conf['wztj'] !== '') ? ' &nbsp;·&nbsp; ' . $conf['wztj'] : ''; ?>
                            </div>
                            <?php if ((isset($conf['icp']) && $conf['icp'] !== '') || theme_config('gonganbei', '') !== ''): ?>
                                <div class="lylme-footer-icp">
                                    <?php theme_icp(); ?>
                                    <?php theme_security_filing(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
            <!-- ==================== End 主内容区 ==================== -->

        </div><!-- /.lyear-layout-container -->
    </div><!-- /.lyear-layout-web -->

    <button type="button" class="lylme-backtop" id="lylme-backtop" title="返回顶部" aria-label="返回顶部">
        <i class="mdi mdi-chevron-up"></i>
    </button>

    <!-- 导航核心图标 sprite (供分组/链接/搜索引擎的 <svg><use>) -->
    <script src="<?php echo theme_e(theme_asset('assets/js/icon.js')); ?>"></script>

    <!-- 博客同款脚本基座 -->
    <script src="<?php echo theme_e(theme_asset('assets/admin/js/jquery.min.js')); ?>"></script>
    <script src="<?php echo theme_e(theme_asset('assets/admin/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo theme_e(theme_asset('assets/admin/js/perfect-scrollbar.min.js')); ?>"></script>
    <script src="<?php echo theme_e(theme_asset('assets/admin/js/main.min.js')); ?>"></script>

    <!-- 主题脚本 -->
    <?php theme_js('js/script.js'); ?>
    <?php theme_js('js/nav.js'); ?>

</body>

</html>
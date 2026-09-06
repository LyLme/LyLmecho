<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * LyLme Modern 主题 - 头部
 * 复用后台 (Lightyear) 布局基座, 由 style.css 升级为现代皮肤。
 */
$lylme_home      = (string) (isset($this->options->siteUrl) ? $this->options->siteUrl : '/article/');
$lylme_title     = (string) (isset($this->options->title) ? $this->options->title : '博客');
$lylme_desc      = (string) (isset($this->options->description) ? $this->options->description : '');
$lylme_logo      = (string) lylme_logo($this);
$lylme_cover     = (string) lylme_opt($this, 'lylmeCover', '');
$lylme_primary   = (string) lylme_opt($this, 'lylmePrimary', '#4f7cf7');
$lylme_scheme    = (string) lylme_opt($this, 'lylmeScheme', 'auto');
$lylme_kind      = lylme_archive_kind($this);
$lylme_keywords  = (string) (isset($this->options->keywords) ? $this->options->keywords : '');
// 搜索词 (定义必须在使用之前; 早期版本只靠下方重复行赋值, 导致标题里 %s 被 null 填空)
$lylme_kw_param  = trim((string) (method_exists($this, 'getArchiveKeywords') ? $this->getArchiveKeywords() : (isset($this->archiveKeywords) ? $this->archiveKeywords : '')));
$lylme_archive_t = trim((string) $this->getArchiveTitle());
$lylme_month     = lylme_month_of($this);
// 分类页标题里的 %s 在兼容层被填成别名 (category-tech), 显示名其实已在 archiveTitleStr 里, 自己拼避免露别名
$lylme_cat_t     = ($lylme_kind === 'category' && $lylme_archive_t !== '')
    ? sprintf(_t('分类 %s 下的文章'), $lylme_archive_t) : '';
// 兼容层给搜索页存的是 "搜索: xx" (中英混排), 与 <title> 不一致, 这里用同一措词
$lylme_search_t  = ($lylme_kind === 'search' && $lylme_kw_param !== '')
    ? sprintf(_t('包含关键字 %s 的文章'), $lylme_kw_param) : '';
// 404 层由 index.php 直接把 archiveTitleStr 置为字面量 "404", 面向用户太硬
$lylme_err_t     = ($lylme_kind === '404') ? _t('页面不存在') : '';
$lylme_page_t    = $lylme_err_t !== ''
    ? $lylme_err_t
    : ($lylme_month !== ''
        ? sprintf(_t('%s 的归档'), $lylme_month)
        : ($lylme_cat_t !== ''
            ? $lylme_cat_t
            : ($lylme_search_t !== ''
                ? $lylme_search_t
                : ($lylme_archive_t !== '' ? $lylme_archive_t : $lylme_title))));
$lylme_is_logged = $this->user && method_exists($this->user, 'hasLogin') ? $this->user->hasLogin() : false;
$lylme_is_admin  = $lylme_is_logged && method_exists($this->user, 'isAdmin') ? $this->user->isAdmin() : false;
$lylme_screen    = $lylme_is_logged && method_exists($this->user, 'name') ? (string) $this->user->name() : '';
$lylme_row       = isset($this->row) && is_array($this->row) ? $this->row : [];
$lylme_desc_row  = isset($lylme_row['art_description']) ? trim((string) $lylme_row['art_description']) : '';
$lylme_meta_desc = $lylme_kind === 'single'
    ? ($lylme_desc_row !== '' ? $lylme_desc_row : (string) $this->__get('plainExcerpt'))
    : $lylme_desc;
$lylme_post_url  = (string) (isset($this->options->memberPostUrl) ? $this->options->memberPostUrl : $lylme_home . '?member=post');
$lylme_mod_url   = (string) (isset($this->options->memberModerateUrl) ? $this->options->memberModerateUrl : $lylme_home . '?member=moderate');
$lylme_center    = (string) (isset($this->options->profileUrl) ? $this->options->profileUrl : $lylme_home . '?member=center');
$lylme_login     = (string) (isset($this->options->loginUrl) ? $this->options->loginUrl : $lylme_home . '?member=login');
$lylme_register  = (string) (isset($this->options->registerUrl) ? $this->options->registerUrl : $lylme_home . '?member=register');
$lylme_logout    = (string) (isset($this->options->logoutUrl) ? $this->options->logoutUrl : $lylme_home . '?logout=1');
$lylme_adminurl  = (string) (isset($this->options->adminUrl) ? $this->options->adminUrl : lylme_site_base($this) . 'admin/');
$lylme_feed      = (string) (isset($this->options->feedUrl) ? $this->options->feedUrl : $lylme_home . '?feed=rss');
$lylme_reg_open  = !empty($this->options->memberRegisterOpen);
$lylme_palette   = lylme_palette($lylme_primary);
$lylme_self      = lylme_self_url();
// 单篇文章额外可 /article/4.html (id 形式) 访问, 自指 canonical 会把两种 URL 都洗成“规范”; 改指向固定链接
$lylme_canon     = $lylme_self;
if ($lylme_kind === 'single' && isset($lylme_row['art_id'])) {
    $canonUrl = trim((string) $this->getPermalink());
    if ($canonUrl !== '') {
        $lylme_canon = lylme_abs_url($this, $canonUrl);
    }
}
// 搜索页既可由 /article/?s=x (GET) 打开, 也可由搜索框 POST 到 /article/ 后渲染;
// 后者自指会得到首页 URL 作为规范, 这里统一回带关键字的 GET 形式
if ($lylme_kind === 'search' && $lylme_kw_param !== '') {
    $canonUrl = lylme_search_url($lylme_kw_param);
    $lylme_pno = method_exists($this, 'getCurrentPage') ? intval($this->getCurrentPage()) : 1;
    if ($lylme_pno > 1) {
        $canonUrl .= '&page=' . $lylme_pno;
    }
    $lylme_canon = lylme_abs_url($this, $canonUrl);
}
$lylme_og_image  = lylme_og_image($this);
$lylme_og_type   = $lylme_kind === 'single' ? 'article' : 'website';
?>
<!DOCTYPE html>
<html lang="zh-CN" data-default-color-scheme="<?php echo lylme_e($lylme_scheme); ?>">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if ($lylme_page_t !== '' && ($lylme_err_t !== '' || $lylme_month !== '' || $lylme_cat_t !== '' || $lylme_search_t !== '')): ?><?php echo lylme_e($lylme_page_t); ?> - <?php else: ?><?php $this->archiveTitle([
            'index'    => _t('首页'),
            'category' => _t('分类 %s 下的文章'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('标签 %s 下的文章'),
            'author'   => _t('%s 发布的文章'),
        ], '', ' - '); ?><?php endif; ?><?php echo lylme_e($lylme_title); ?></title>
    <?php if ($lylme_meta_desc !== ''): ?>
        <meta name="description" content="<?php echo lylme_e(mb_substr($lylme_meta_desc, 0, 160, 'UTF-8')); ?>">
    <?php endif; ?>
    <?php if ($lylme_keywords !== ''): ?>
        <meta name="keywords" content="<?php echo lylme_e($lylme_keywords); ?>">
    <?php endif; ?>
    <?php if ($lylme_logo !== ''): ?>
        <link rel="icon" href="<?php echo lylme_e($lylme_logo); ?>">
        <link rel="apple-touch-icon" href="<?php echo lylme_e($lylme_logo); ?>">
    <?php endif; ?>
    <?php if ($lylme_canon !== ''): ?>
        <link rel="canonical" href="<?php echo lylme_e($lylme_canon); ?>">
    <?php endif; ?>
    <meta name="theme-color" id="lylme-theme-color" content="#f4f6fb">
    <?php if ($lylme_self !== ''): ?>
        <meta property="og:type" content="<?php echo lylme_e($lylme_og_type); ?>">
        <meta property="og:site_name" content="<?php echo lylme_e($lylme_title); ?>">
        <meta property="og:title" content="<?php echo lylme_e($lylme_page_t); ?>">
        <meta property="og:url" content="<?php echo lylme_e($lylme_canon); ?>">
    <?php endif; ?>
    <?php if ($lylme_meta_desc !== ''): ?>
        <meta property="og:description" content="<?php echo lylme_e(mb_substr($lylme_meta_desc, 0, 200, 'UTF-8')); ?>">
    <?php endif; ?>
    <?php if ($lylme_og_image !== ''): ?>
        <meta property="og:image" content="<?php echo lylme_e($lylme_og_image); ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="<?php echo lylme_e($lylme_og_image); ?>">
    <?php else: ?>
        <meta name="twitter:card" content="summary">
    <?php endif; ?>
    <?php if ($lylme_self !== ''): ?>
        <meta name="twitter:title" content="<?php echo lylme_e($lylme_page_t); ?>">
    <?php endif; ?>
    <link rel="alternate" type="application/rss+xml" title="<?php echo lylme_e($lylme_title); ?>" href="<?php echo lylme_e($lylme_feed); ?>">

    <!-- 后台同款基座: Bootstrap 5 + Material Design Icons + Lightyear -->
    <link rel="stylesheet" href="<?php echo lylme_e(lylme_asset($this, 'assets/admin/css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo lylme_e(lylme_asset($this, 'assets/admin/css/materialdesignicons.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo lylme_e(lylme_asset($this, 'assets/admin/css/style.min.css')); ?>">

    <!-- 主题现代皮肤 -->
    <link rel="stylesheet" href="<?php echo lylme_e(lylme_uri($this, 'style.css')); ?>?v=<?php echo lylme_e(lylme_asset_ver()); ?>">

    <style>
        :root {
            --lylme-primary: <?php echo lylme_e($lylme_palette['base']); ?>;
            --lylme-primary-rgb: <?php echo lylme_e($lylme_palette['rgb']); ?>;
            --lylme-primary-deep: <?php echo lylme_e($lylme_palette['deep']); ?>;
            --lylme-accent: <?php echo lylme_e($lylme_palette['accent']); ?>;
            --lylme-accent-rgb: <?php echo lylme_e($lylme_palette['accentRgb']); ?>;
            --lylme-primary-soft: <?php echo lylme_e($lylme_palette['soft']); ?>;
            --lylme-primary-line: <?php echo lylme_e($lylme_palette['line']); ?>;
        }
    </style>

    <!-- 预设配色: 在 body 绘制前设定, 避免主题闪烁 (与后台共用 localStorage.theme 键) -->
    <script>
        (function () {
            var pref = '<?php echo lylme_e($lylme_scheme); ?>';
            var saved = null;
            try { saved = localStorage.getItem('theme'); } catch (e) {}
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
            // 浏览器地址栏/状态栏跟随页面底色 (两个 theme-color 并存时 UA 取值不确定, 故只留一个并由脚本定)
            var tc = document.getElementById('lylme-theme-color');
            if (tc) { tc.setAttribute('content', t === 'dark' ? '#12141f' : '#f4f6fb'); }
        })();
    </script>
    <?php $this->header('viewport=&keywords=&description='); ?>
</head>
<body class="lylme-body lylme-kind-<?php echo lylme_e($lylme_kind); ?><?php echo ((string) lylme_opt($this, 'lylmeLogoMatte', 'white') === 'flat') ? ' lylme-logo-flat' : ''; ?>" data-theme="<?php echo lylme_e($lylme_scheme === 'dark' ? 'dark' : 'default'); ?>"
      data-user-logged="<?php echo $lylme_is_logged ? '1' : '0'; ?>"
      data-user-admin="<?php echo $lylme_is_admin ? '1' : '0'; ?>">
<script>
    // body 上色 (style.min.css 使用 body[data-theme='dark'] 选择器)
    document.body.setAttribute('data-theme', document.documentElement.getAttribute('data-theme') || 'default');
</script>

<!-- 键盘用户跳过导航直达正文; 默认隐藏, 首次 Tab 时出现 -->
<a class="lylme-skip" href="#lylme-main"><?php _e('跳到主要内容'); ?></a>

<!-- 全站氛围层: 固定在 body 下, 避免受布局容器 transform/overflow 影响 -->
<div class="lylme-bg-deco" aria-hidden="true"><span class="lylme-bg-blob b1"></span><span class="lylme-bg-blob b2"></span><span class="lylme-bg-grid"></span></div>

<div class="lyear-layout-web lylme-layout">
    <div class="lyear-layout-container">

        <!-- ==================== 左侧导航 ==================== -->
        <aside class="lyear-layout-sidebar lylme-sidebar" id="lylme-sidebar">
            <div class="lylme-brand<?php echo $lylme_cover !== '' ? ' lylme-brand-cover' : ''; ?>" <?php if ($lylme_cover !== ''): ?>style="background-image:linear-gradient(160deg,rgba(28,30,47,.55),rgba(28,30,47,.85)),url('<?php echo lylme_e($lylme_cover); ?>')"<?php endif; ?>>
                <a class="lylme-brand-link" href="<?php echo lylme_e($lylme_home); ?>" title="<?php echo lylme_e($lylme_title); ?>">
                    <?php if ($lylme_logo !== ''): ?>
                        <img class="lylme-brand-logo" src="<?php echo lylme_e($lylme_logo); ?>" alt="<?php echo lylme_e($lylme_title); ?>"
                               width="44" height="44">
                    <?php else: ?>
                        <span class="lylme-brand-badge"><i class="mdi mdi-feather"></i></span>
                    <?php endif; ?>
                    <span class="lylme-brand-text">
                        <b><?php echo lylme_e($lylme_title); ?></b>
                        <?php if ($lylme_desc !== ''): ?><em><?php echo lylme_e($lylme_desc); ?></em><?php endif; ?>
                    </span>
                </a>
            </div>

            <div class="lyear-layout-sidebar-scroll lylme-sidebar-scroll">
                <nav class="sidebar-main lylme-nav" role="navigation">
                    <ul class="nav nav-drawer">
                        <li class="nav-item<?php echo $lylme_kind === 'index' ? ' active' : ''; ?>">
                            <a href="<?php echo lylme_e($lylme_home); ?>"><i class="mdi mdi-home-map-marker"></i><?php _e('首页'); ?></a>
                        </li>
                        <?php if (lylme_block_on($this, 'ShowCategories')): ?>
                            <?php \Widget\Metas\Category\Rows::alloc()->to($lylme_cats); ?>
                            <?php if ($lylme_cats->have()): ?>
                                <li class="nav-item nav-item-has-subnav<?php echo $lylme_kind === 'category' ? ' active open' : ''; ?>">
                                    <button type="button" class="nav-subnav-toggle" aria-controls="lylme-subnav-category"
                                            aria-expanded="<?php echo $lylme_kind === 'category' ? 'true' : 'false'; ?>">
                                        <i class="mdi mdi-folder-outline"></i><?php _e('文章分类'); ?>
                                    </button>
                                    <ul class="nav nav-subnav" id="lylme-subnav-category">
                                        <?php while ($lylme_cats->next()): ?>
                                            <li>
                                                <a href="<?php echo lylme_e($lylme_cats->permalink); ?>">
                                                    <?php echo lylme_e($lylme_cats->name); ?>
                                                    <span class="lylme-nav-count"><?php echo intval($lylme_cats->count); ?></span>
                                                </a>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="<?php echo lylme_e($lylme_feed); ?>" target="_blank" rel="noopener"><i class="mdi mdi-rss"></i><?php _e('RSS 订阅'); ?></a>
                        </li>
                        <?php if ($lylme_is_logged): ?>
                            <?php if ($lylme_is_admin): ?>
                                <li class="nav-item">
                                    <a href="<?php echo lylme_e($lylme_adminurl); ?>"><i class="mdi mdi-cog-outline"></i><?php _e('进入后台'); ?></a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item nav-item-has-subnav open">
                                    <button type="button" class="nav-subnav-toggle" aria-controls="lylme-subnav-member" aria-expanded="true">
                                        <i class="mdi mdi-account-circle-outline"></i><?php _e('我的空间'); ?>
                                    </button>
                                    <ul class="nav nav-subnav" id="lylme-subnav-member">
                                        <li><a href="<?php echo lylme_e($lylme_center); ?>"><?php _e('会员中心'); ?></a></li>
                                        <?php if (method_exists($this->user, 'pass') && $this->user->pass('contributor', true)): ?>
                                            <li><a href="<?php echo lylme_e($lylme_post_url); ?>"><?php _e('我的文章'); ?></a></li>
                                        <?php endif; ?>
                                        <?php if (method_exists($this->user, 'pass') && $this->user->pass('editor', true)): ?>
                                            <li><a href="<?php echo lylme_e($lylme_mod_url); ?>"><?php _e('编辑审核台'); ?></a></li>
                                        <?php endif; ?>
                                        <li><a href="<?php echo lylme_e($lylme_logout); ?>"><?php _e('退出登录'); ?></a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li class="nav-item">
                                <a href="<?php echo lylme_e($lylme_login); ?>"><i class="mdi mdi-login-variant"></i><?php _e('登录'); ?></a>
                            </li>
                            <?php if ($lylme_reg_open): ?>
                                <li class="nav-item">
                                    <a href="<?php echo lylme_e($lylme_register); ?>"><i class="mdi mdi-account-plus-outline"></i><?php _e('注册'); ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="sidebar-footer lylme-sidebar-footer">
                    <p class="copyright">
                        &copy; <?php echo date('Y'); ?>
                        <a href="<?php echo lylme_e($lylme_home); ?>"><?php echo lylme_e($lylme_title); ?></a>
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
                        <div class="lyear-aside-toggler" title="<?php _e('展开/收起导航'); ?>">
                            <span class="lyear-toggler-bar"></span>
                            <span class="lyear-toggler-bar"></span>
                            <span class="lyear-toggler-bar"></span>
                        </div>
                        <span class="navbar-page-title lylme-crumb-title">
                            <?php if ($lylme_kind === 'single'): ?>
                                <i class="mdi mdi-file-document-outline"></i> <?php echo lylme_e($lylme_page_t); ?>
                            <?php elseif ($lylme_kind === 'category'): ?>
                                <i class="mdi mdi-folder-outline"></i> <?php echo lylme_e($lylme_page_t); ?>
                            <?php elseif ($lylme_kind === 'search'): ?>
                                <i class="mdi mdi-magnify"></i> <?php _e('搜索'); ?>：<?php echo lylme_e($lylme_page_t); ?>
                            <?php else: ?>
                                <i class="mdi mdi-shape-outline"></i> <?php echo lylme_e($lylme_title); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <form class="lylme-topbar-search" method="post" action="<?php echo lylme_e($lylme_home); ?>" role="search">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" name="s" placeholder="<?php _e('搜索文章...'); ?>"
                               value="<?php echo lylme_e($lylme_kind === 'search' ? $lylme_kw_param : ''); ?>">
                    </form>

                    <ul class="topbar-right">
                        <li class="dropdown dropdown-profile">
                            <?php if ($lylme_is_logged): ?>
                                <a href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                                   aria-haspopup="true">
                                    <span>
                                        <i class="mdi mdi-account-circle lylme-avatar-icon"></i>
                                        <?php echo lylme_e($lylme_screen !== '' ? $lylme_screen : _t('会员中心')); ?>
                                        <i class="mdi mdi-menu-down"></i>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end lylme-profile-menu">
                                    <?php if ($lylme_is_admin): ?>
                                        <li><a class="dropdown-item" href="<?php echo lylme_e($lylme_adminurl); ?>"><i class="mdi mdi-cog-outline"></i> <?php _e('进入后台'); ?></a></li>
                                    <?php else: ?>
                                        <li><a class="dropdown-item" href="<?php echo lylme_e($lylme_center); ?>"><i class="mdi mdi-view-dashboard-outline"></i> <?php _e('会员中心'); ?></a></li>
                                        <?php if (method_exists($this->user, 'pass') && $this->user->pass('contributor', true)): ?>
                                            <li><a class="dropdown-item" href="<?php echo lylme_e($lylme_post_url); ?>"><i class="mdi mdi-file-edit-outline"></i> <?php _e('我的文章'); ?></a></li>
                                        <?php endif; ?>
                                        <?php if (method_exists($this->user, 'pass') && $this->user->pass('editor', true)): ?>
                                            <li><a class="dropdown-item" href="<?php echo lylme_e($lylme_mod_url); ?>"><i class="mdi mdi-shield-check-outline"></i> <?php _e('编辑审核台'); ?></a></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo lylme_e($lylme_logout); ?>"><i class="mdi mdi-logout-variant"></i> <?php _e('退出登录'); ?></a></li>
                                </ul>
                            <?php else: ?>
                                <a href="<?php echo lylme_e($lylme_login); ?>"><span><i class="mdi mdi-account-circle"></i> <?php _e('登录'); ?></span></a>
                            <?php endif; ?>
                        </li>
                        <li class="lylme-scheme-item">
                            <button type="button" class="btn btn-sm lylme-scheme-toggle" id="lylme-scheme-toggle" title="<?php _e('切换明暗'); ?>">
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
                    <div class="col-lg-8 col-12" id="lylme-main" role="main" tabindex="-1">

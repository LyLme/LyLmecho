<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * LyLme Blog 主题 - 首页 / 分类 / 搜索 / 月份归档 列表
 * (Compat\Archive 将 index/category/search/month 四种归档都路由到本模板)
 */
$this->need('header.php');

$lylme_kind      = lylme_archive_kind($this);
$lylme_showCover = lylme_block_on($this, 'ShowCover');
$lylme_home      = (string) (isset($this->options->siteUrl) ? $this->options->siteUrl : '/article/');
$lylme_title     = (string) (isset($this->options->title) ? $this->options->title : '博客');
$lylme_desc      = (string) (isset($this->options->description) ? $this->options->description : '');
$lylme_kw_param  = (string) (isset($this->archiveKeywords) ? $this->archiveKeywords : '');
$lylme_total     = method_exists($this, 'getTotal') ? (int) $this->getTotal() : 0;
$lylme_page      = method_exists($this, 'getCurrentPage') ? (int) $this->getCurrentPage() : 1;
// 月份归档页: 不叠 Hero (首页式统计与归档列表数量会矛盾), 改出一行归属提示
$lylme_month     = lylme_month_of($this);
$lylme_showHero  = lylme_block_on($this, 'ShowHero') && $lylme_kind === 'index' && $lylme_month === '';
?>

<?php if ($lylme_month !== ''): ?>
    <div class="lylme-archbar">
        <span class="aa-ico"><i class="mdi mdi-calendar-outline"></i></span>
        <span class="aa-text"><?php _e('时间归档'); ?>：<b><?php echo lylme_e($lylme_month); ?></b> · <?php _e('本月共'); ?> <?php echo $lylme_total; ?> <?php _e('篇'); ?></span>
        <a class="aa-back" href="<?php echo lylme_e($lylme_home); ?>"><i class="mdi mdi-arrow-left"></i> <?php _e('返回首页'); ?></a>
    </div>
<?php endif; ?>

<?php if ($lylme_showHero): ?>
    <?php $lylme_stats = lylme_site_stats(); ?>
    <section class="lylme-hero card">
        <div class="lylme-hero-body">
            <div class="lylme-hero-main">
            <div class="lylme-hero-eyebrow"><i class="mdi mdi-shimmer"></i> <?php echo lylme_e($lylme_desc !== '' ? $lylme_desc : _t('记录 · 分享 · 成长')); ?></div>
            <h1 class="lylme-hero-title"><?php echo lylme_e($lylme_title); ?></h1>
            <p class="lylme-hero-lead"><?php _e('共发布'); ?> <b><?php echo $lylme_total; ?></b> <?php _e('篇文章, 欢迎浏览与评论。'); ?></p>
            <form class="lylme-hero-search" method="post" action="<?php echo lylme_e($lylme_home); ?>">
                <i class="mdi mdi-magnify"></i>
                <input type="text" name="s" placeholder="<?php _e('搜索感兴趣的内容...'); ?>">
                <button type="submit"><?php _e('搜索'); ?></button>
            </form>
            <?php if (lylme_block_on($this, 'ShowCategories')): ?>
                <?php \Widget\Metas\Category\Rows::alloc()->to($lylme_hcats); ?>
                <?php if ($lylme_hcats->have()): ?>
                    <?php $lylme_hlist = []; while ($lylme_hcats->next()) { $lylme_hlist[] = ['name' => $lylme_hcats->name, 'url' => $lylme_hcats->permalink, 'count' => intval($lylme_hcats->count)]; } ?>
                    <div class="lylme-hero-cats">
                        <span class="hc-label"><i class="mdi mdi-tag-text-outline"></i><?php _e('快速定位'); ?></span>
                        <?php foreach ($lylme_hlist as $hc): ?>
                            <a class="hc-chip" href="<?php echo lylme_e($hc['url']); ?>"><?php echo lylme_e($hc['name']); ?><em><?php echo $hc['count']; ?></em></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            </div>
            <div class="lylme-hero-aside">
            <div class="lylme-hero-stats">
                <span class="hs-item"><i class="mdi mdi-file-document-outline"></i><?php _e('文章'); ?> <b data-count="<?php echo intval($lylme_stats['posts']); ?>"><?php echo intval($lylme_stats['posts']); ?></b></span>
                <span class="hs-item"><i class="mdi mdi-folder-outline"></i><?php _e('分类'); ?> <b data-count="<?php echo intval($lylme_stats['cats']); ?>"><?php echo intval($lylme_stats['cats']); ?></b></span>
                <span class="hs-item"><i class="mdi mdi-comment-outline"></i><?php _e('评论'); ?> <b data-count="<?php echo intval($lylme_stats['comments']); ?>"><?php echo intval($lylme_stats['comments']); ?></b></span>
            </div>
            </div>
        </div>
        <div class="lylme-hero-deco" aria-hidden="true">
            <span class="ring r1"></span><span class="ring r2"></span>
            <span class="dot d1"></span><span class="dot d2"></span><span class="dot d3"></span>
            <i class="mdi mdi-feather mdi-float"></i>
        </div>
    </section>
<?php elseif ($lylme_kind !== 'index'): ?>
    <section class="lylme-archive-head card">
        <div class="card-body lylme-archive-body">
            <h2 class="lylme-archive-title">
                <?php if ($lylme_kind === 'category'): ?>
                    <i class="mdi mdi-folder-outline"></i> <?php _e('分类'); ?>：<?php echo lylme_e($this->getArchiveTitle()); ?>
                <?php elseif ($lylme_kind === 'search'): ?>
                    <i class="mdi mdi-magnify"></i> <?php _e('搜索结果'); ?>：<b><?php echo lylme_e($lylme_kw_param); ?></b>
                <?php else: ?>
                    <i class="mdi mdi-calendar-blank"></i> <?php echo lylme_e($this->getArchiveTitle()); ?>
                <?php endif; ?>
            </h2>
            <p class="lylme-archive-meta">
                <?php _e('共'); ?> <b><?php echo $lylme_total; ?></b> <?php _e('篇'); ?>
                <?php if ($lylme_page > 1): ?> · <?php _e('第'); ?> <?php echo $lylme_page; ?> <?php _e('页'); ?><?php endif; ?>
            </p>
        </div>
    </section>
<?php endif; ?>

<?php if ($this->have()): ?>
    <?php while ($this->next()): ?>
        <?php
        $lylme_cover  = $lylme_showCover ? lylme_cover_of($this) : '';
        $lylme_top    = lylme_is_top($this);
        $lylme_read   = lylme_read_stat($this);
        $lylme_tags   = $lylme_showCover ? lylme_keywords_of($this, 3) : [];
        $lylme_like   = intval($this->row['art_likes'] ?? 0);
        $lylme_ph     = 'ph-' . (abs(crc32((string) ($this->row['art_title'] ?? 'x'))) % 6 + 1);
        $lylme_glyph  = mb_substr(trim((string) ($this->row['art_title'] ?? '文')), 0, 1, 'UTF-8');
        ?>
        <article class="card lylme-post<?php echo $lylme_cover !== '' ? ' has-cover' : ' has-ph ' . $lylme_ph; ?><?php echo $lylme_top ? ' is-top' : ''; ?>"<?php if ($lylme_top): ?> data-top="1"<?php endif; ?>>
            <a class="lylme-post-thumb" href="<?php $this->permalink(); ?>" tabindex="-1" aria-hidden="true">
                <?php if ($lylme_cover !== ''): ?>
                    <img src="<?php echo lylme_e($lylme_cover); ?>" alt="" loading="lazy">
                <?php else: ?>
                    <span class="lylme-ph-inner">
                        <span class="lylme-ph-glyph"><?php echo lylme_e($lylme_glyph === '' ? '文' : $lylme_glyph); ?></span>
                        <span class="lylme-ph-shine"></span>
                    </span>
                <?php endif; ?>
                <span class="lylme-thumb-shade"></span>
                <?php if (!empty($this->row['cat_name'])): ?>
                    <span class="lylme-cover-cat"><i class="mdi mdi-folder-outline"></i> <?php echo lylme_e($this->row['cat_name']); ?></span>
                <?php endif; ?>
            </a>
            <div class="lylme-post-main">
                <div class="lylme-post-head">
                    <h2 class="lylme-post-title">
                        <?php if ($lylme_top): ?><span class="lylme-chip lylme-chip-top"><i class="mdi mdi-lightning-bolt"></i><?php _e('置顶'); ?></span><?php endif; ?>
                        <a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a>
                    </h2>
                </div>
                <p class="lylme-post-excerpt"><?php echo lylme_e(lylme_excerpt($this, 200)); ?></p>
                <ul class="lylme-post-meta">
                    <li title="<?php _e('发布时间'); ?>"><i class="mdi mdi-calendar-blank-outline"></i> <?php echo lylme_e(lylme_time_ago($this->row['art_time'] ?? '')); ?></li>
                    <?php if (!empty($this->row['cat_name'])): ?>
                        <li><i class="mdi mdi-folder-outline"></i> <a href="<?php echo lylme_e(\Compat\App::categoryUrl(isset($this->row['cat_alias']) ? $this->row['cat_alias'] : '')); ?>"><?php echo lylme_e($this->row['cat_name']); ?></a></li>
                    <?php endif; ?>
                    <li title="<?php _e('预计阅读时长'); ?>"><i class="mdi mdi-clock-outline"></i> <?php echo $lylme_read['minutes']; ?> <?php _e('分钟'); ?></li>
                    <li><i class="mdi mdi-eye-outline"></i> <?php echo intval($this->row['art_views'] ?? 0); ?></li>
                    <?php if ($lylme_like > 0): ?>
                        <li><i class="mdi mdi-heart-outline"></i> <?php echo $lylme_like; ?></li>
                    <?php endif; ?>
                    <li><a href="<?php $this->permalink(); ?>#comments"><i class="mdi mdi-comment-outline"></i> <?php $this->commentsNum(_t('评论'), _t('1 评论'), _t('%d 评论')); ?></a></li>
                    <?php if (!empty($this->row['art_author'])): ?>
                        <li><i class="mdi mdi-account-outline"></i> <?php echo lylme_e($this->row['art_author']); ?></li>
                    <?php endif; ?>
                </ul>
                <div class="lylme-post-foot">
                    <?php if (!empty($lylme_tags)): ?>
                        <div class="lylme-post-tags">
                            <?php foreach ($lylme_tags as $lt): ?>
                                <a class="lylme-tag lylme-tag-sm" href="<?php echo lylme_e($lt['url']); ?>">#<?php echo lylme_e($lt['name']); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="lylme-post-actions">
                        <a class="btn btn-sm btn-primary lylme-btn-read" href="<?php $this->permalink(); ?>">
                            <?php _e('阅读全文'); ?> <i class="mdi mdi-arrow-right-thin"></i>
                        </a>
                    </div>
                </div>
            </div>
        </article>
    <?php endwhile; ?>

    <nav class="lylme-pager" aria-label="<?php _e('分页'); ?>">
        <?php $this->pageNav(_t('‹ 上一页'), _t('下一页 ›'), 3, '…'); ?>
    </nav>
<?php else: ?>
    <section class="card lylme-empty">
        <div class="card-body">
            <div class="lylme-empty-icon"><i class="mdi mdi-file-search-outline"></i></div>
            <h3><?php _e('还没有内容'); ?></h3>
            <p><?php
                if ($lylme_kind === 'search') {
                    _e('未找到与查询匹配的文章, 换个关键词试试吧。');
                } else {
                    _e('该分类下暂无已发布的文章。');
                }
            ?></p>
            <a class="btn btn-outline-primary btn-sm" href="<?php echo lylme_e($lylme_home); ?>">
                <i class="mdi mdi-home"></i> <?php _e('返回首页'); ?>
            </a>
        </div>
    </section>
<?php endif; ?>

                    </div><!-- /#lylme-main .col-lg-8 -->
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>

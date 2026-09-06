<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * LyLme Modern 主题 - 单篇文章
 */
$this->need('header.php');

$lylme_showToc   = lylme_block_on($this, 'ShowToc');
$lylme_cover     = lylme_cover_of($this);
$lylme_row       = isset($this->row) && is_array($this->row) ? $this->row : [];
$lylme_views     = intval(isset($lylme_row['art_views']) ? $lylme_row['art_views'] : 0);
$lylme_coms      = intval(isset($lylme_row['art_comments']) ? $lylme_row['art_comments'] : 0);
$lylme_author    = isset($lylme_row['art_author']) ? (string) $lylme_row['art_author'] : '';
$lylme_time      = isset($lylme_row['art_time']) ? (string) $lylme_row['art_time'] : '';
$lylme_updated   = isset($lylme_row['art_update']) ? (string) $lylme_row['art_update'] : '';
$lylme_home      = (string) (isset($this->options->siteUrl) ? $this->options->siteUrl : '/article/');
$lylme_read      = lylme_read_stat($this);
$lylme_desc_row  = isset($lylme_row['art_description']) ? trim((string) $lylme_row['art_description']) : '';
if ($lylme_desc_row === '') {
    $lylme_desc_row = trim((string) (isset($lylme_row['art_excerpt']) ? $lylme_row['art_excerpt'] : ''));
}
// 封面图在 CSS 里是 width:100% + 高度自适应, 不告知固有尺寸则图片加载完会顶一下页码
$lylme_cover_dims = lylme_img_dims($this, (string) $lylme_cover);
$lylme_related   = lylme_block_on($this, 'ShowHotPosts')
    ? lylme_related_posts($lylme_row, 4)
    : [];
?>

<article class="card lylme-single" id="lylme-article"
         data-views="<?php echo $lylme_views; ?>"
         data-comments="<?php echo $lylme_coms; ?>">

    <?php if ($lylme_cover !== ''): ?>
        <div class="lylme-single-cover">
            <img src="<?php echo lylme_e($lylme_cover); ?>" alt=""<?php if (!empty($lylme_cover_dims)): ?> width="<?php echo $lylme_cover_dims[0]; ?>" height="<?php echo $lylme_cover_dims[1]; ?>"<?php endif; ?>>
            <div class="lylme-single-cover-mask"></div>
        </div>
    <?php endif; ?>

    <div class="lylme-single-head<?php echo $lylme_cover !== '' ? ' with-cover' : ''; ?>">
        <div class="lylme-single-crumb">
            <a href="<?php echo lylme_e($lylme_home); ?>"><i class="mdi mdi-home-outline"></i> <?php _e('首页'); ?></a>
            <?php if (!empty($lylme_row['cat_name'])): ?>
                <i class="mdi mdi-chevron-right"></i>
                <a href="<?php echo lylme_e(\Compat\App::categoryUrl(isset($lylme_row['cat_alias']) ? $lylme_row['cat_alias'] : '')); ?>">
                    <?php echo lylme_e($lylme_row['cat_name']); ?>
                </a>
            <?php endif; ?>
            <i class="mdi mdi-chevron-right"></i>
            <span><?php _e('正文'); ?></span>
        </div>
        <h1 class="lylme-single-title"><?php $this->title(); ?></h1>
        <ul class="lylme-post-meta lylme-single-meta">
            <?php if ($lylme_author !== ''): ?>
                <li class="mm-author"><span class="mm-avatar"><?php echo lylme_e(mb_substr($lylme_author, 0, 1, 'UTF-8')); ?></span> <?php echo lylme_e($lylme_author); ?></li>
            <?php endif; ?>
            <li><i class="mdi mdi-calendar-blank-outline"></i> <?php echo lylme_e(lylme_time_ago($lylme_time)); ?></li>
            <?php if ($lylme_updated !== '' && $lylme_updated !== $lylme_time): ?>
                <li title="<?php _e('最后更新'); ?>"><i class="mdi mdi-sync"></i> <?php echo lylme_e(lylme_time_ago($lylme_updated)); ?></li>
            <?php endif; ?>
            <li title="<?php _e('预计阅读时长'); ?>"><i class="mdi mdi-clock-outline"></i> <?php echo $lylme_read['minutes']; ?> <?php _e('分钟读完'); ?></li>
            <li><i class="mdi mdi-eye-outline"></i> <?php echo $lylme_views; ?> <?php _e('次浏览'); ?></li>
            <li><i class="mdi mdi-comment-outline"></i> <?php echo $lylme_coms; ?> <?php _e('评论'); ?></li>
        </ul>
    </div>

    <?php if ($lylme_desc_row !== ''): ?>
        <div class="lylme-lead">
            <i class="mdi mdi-bookmark-outline lylme-lead-icon"></i>
            <div class="lylme-lead-text"><?php echo lylme_e(mb_substr($lylme_desc_row, 0, 180, 'UTF-8')); ?></div>
        </div>
    <?php endif; ?>

    <div class="lylme-single-body">
        <?php if ($lylme_showToc): ?>
            <nav class="lylme-toc" id="lylme-toc" hidden>
                <div class="lylme-toc-head">
                    <i class="mdi mdi-format-list-bulleted"></i> <?php _e('目录'); ?>
                    <button type="button" class="lylme-toc-toggle" title="<?php _e('折叠/展开'); ?>"><i class="mdi mdi-chevron-up"></i></button>
                </div>
                <ol class="lylme-toc-list"></ol>
            </nav>
        <?php endif; ?>

        <div class="lylme-post-content" id="lylme-post-content" itemprop="articleBody">
            <?php $this->content(); ?>
        </div>

        <div class="lylme-progress-tip" aria-hidden="true">
            <span class="lylme-progress-bar" id="lylme-progress-bar"></span>
        </div>
    </div>

    <?php
    $lylme_tags = $this->__get('tags');
    if (is_array($lylme_tags) && !empty($lylme_tags)): ?>
        <div class="lylme-single-tags">
            <i class="mdi mdi-tag-multiple"></i>
            <?php foreach ($lylme_tags as $t): ?>
                <a class="lylme-tag" href="<?php echo lylme_e($t['permalink']); ?>">#<?php echo lylme_e($t['name']); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="lylme-single-foot">
        <div class="lylme-license">
            <div class="lylme-license-title"><i class="mdi mdi-copyright"></i> <?php _e('关于本文'); ?></div>
            <ul>
                <li><?php _e('作者'); ?>：<b><?php echo lylme_e($lylme_author !== '' ? $lylme_author : (string) (isset($this->options->title) ? $this->options->title : '')); ?></b></li>
                <li><?php _e('发布于'); ?>：<b><?php echo lylme_e(date('Y-m-d', strtotime($lylme_time ?: 'now'))); ?></b>
                    &nbsp;·&nbsp; <?php _e('全文约'); ?> <b><?php echo $lylme_read['chars']; ?></b> <?php _e('字'); ?></li>
                <li><?php _e('版权属于作者, 转载请注明出处。'); ?></li>
            </ul>
        </div>
        <div class="lylme-single-actions">
            <button type="button" class="btn btn-sm btn-outline-primary lylme-share" title="<?php _e('复制链接'); ?>">
                <i class="mdi mdi-share-variant"></i> <?php _e('分享'); ?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary lylme-print" title="<?php _e('打印 / 保存 PDF'); ?>">
                <i class="mdi mdi-printer-outline"></i> <?php _e('打印'); ?>
            </button>
            <?php if (isset($lylme_row['art_slug']) && $lylme_row['art_slug'] !== ''): ?>
                <code class="lylme-slug" title="<?php _e('文章别名'); ?>/<?php echo lylme_e($lylme_row['art_slug']); ?>">
                    <i class="mdi mdi-link-variant"></i> <?php echo lylme_e($lylme_row['art_slug']); ?>
                </code>
            <?php endif; ?>
        </div>
    </div>
</article>

<nav class="lylme-near" aria-label="<?php _e('上一篇 / 下一篇'); ?>">
    <div class="lylme-near-item lylme-near-prev">
        <span class="lylme-near-label"><i class="mdi mdi-arrow-left-thin"></i> <?php _e('上一篇'); ?></span>
        <span class="lylme-near-title"><?php $this->thePrev('%s', _t('没有了')); ?></span>
    </div>
    <div class="lylme-near-item lylme-near-next">
        <span class="lylme-near-label"><?php _e('下一篇'); ?> <i class="mdi mdi-arrow-right-thin"></i></span>
        <span class="lylme-near-title"><?php $this->theNext('%s', _t('没有了')); ?></span>
    </div>
</nav>

<?php if (!empty($lylme_related)): ?>
    <section class="card lylme-widget lylme-related">
        <div class="card-header lylme-widget-head">
            <h4><i class="mdi mdi-book-open-page-variant-outline"></i> <?php _e('相关文章'); ?></h4>
            <span class="lylme-widget-badge"><?php echo count($lylme_related); ?></span>
        </div>
        <div class="card-body lylme-widget-body">
            <div class="lylme-related-grid">
                <?php foreach ($lylme_related as $rl): ?>
                    <a class="lylme-related-item" href="<?php echo lylme_e($rl['url']); ?>">
                        <?php if ($rl['cover'] !== ''): ?>
                            <span class="lylme-related-cover"><img src="<?php echo lylme_e($rl['cover']); ?>" alt="" loading="lazy"></span>
                        <?php endif; ?>
                        <span class="lylme-related-title"><?php echo lylme_e($rl['title']); ?></span>
                        <span class="lylme-related-meta"><i class="mdi mdi-eye-outline"></i> <?php echo $rl['views']; ?> · <?php echo lylme_e($rl['time']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php $this->need('comments.php'); ?>

                    </div><!-- /#lylme-main .col-lg-8 -->
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>

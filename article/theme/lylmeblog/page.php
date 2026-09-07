<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * LyLme Blog 主题 - 独立页面
 * (archiveType = page, 由 Compat\Archive 在独立页面路由时渲染)
 */
$this->need('header.php');

$lylme_showToc  = lylme_block_on($this, 'ShowToc');
$lylme_row      = isset($this->row) && is_array($this->row) ? $this->row : [];
$lylme_time     = isset($lylme_row['art_time']) ? (string) $lylme_row['art_time'] : '';
$lylme_updated  = isset($lylme_row['art_update']) ? (string) $lylme_row['art_update'] : '';
$lylme_home     = (string) (isset($this->options->siteUrl) ? $this->options->siteUrl : '/article/');
$lylme_read     = lylme_read_stat($this);
$lylme_desc_row = isset($lylme_row['art_description']) ? trim((string) $lylme_row['art_description']) : '';
if ($lylme_desc_row === '') {
    $lylme_desc_row = trim((string) (isset($lylme_row['art_excerpt']) ? $lylme_row['art_excerpt'] : ''));
}
?>

<article class="card lylme-single lylme-page" id="lylme-article">
    <div class="lylme-single-head">
        <div class="lylme-single-crumb">
            <a href="<?php echo lylme_e($lylme_home); ?>"><i class="mdi mdi-home-outline"></i> <?php _e('首页'); ?></a>
            <i class="mdi mdi-chevron-right"></i>
            <span><?php _e('页面'); ?></span>
        </div>
        <h1 class="lylme-single-title"><?php $this->title(); ?></h1>
        <ul class="lylme-post-meta lylme-single-meta">
            <?php if ($lylme_time !== ''): ?>
                <li><i class="mdi mdi-calendar-blank-outline"></i> <?php echo lylme_e(lylme_time_ago($lylme_time)); ?></li>
            <?php endif; ?>
            <?php if ($lylme_updated !== '' && $lylme_updated !== $lylme_time): ?>
                <li title="<?php _e('最后更新'); ?>"><i class="mdi mdi-sync"></i> <?php echo lylme_e(lylme_time_ago($lylme_updated)); ?></li>
            <?php endif; ?>
            <li title="<?php _e('预计阅读时长'); ?>"><i class="mdi mdi-clock-outline"></i> <?php echo $lylme_read['minutes']; ?> <?php _e('分钟读完'); ?></li>
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

    <div class="lylme-single-foot">
        <div class="lylme-license">
            <div class="lylme-license-title"><i class="mdi mdi-copyright"></i> <?php _e('关于本页'); ?></div>
            <ul>
                <li><?php _e('发布于'); ?>：<b><?php echo lylme_e(date('Y-m-d', strtotime($lylme_time ?: 'now'))); ?></b>
                    <?php if ($lylme_updated !== '' && $lylme_updated !== $lylme_time): ?>
                        &nbsp;·&nbsp; <?php _e('最后更新'); ?>：<b><?php echo lylme_e(date('Y-m-d', strtotime($lylme_updated))); ?></b>
                    <?php endif; ?>
                </li>
                <li><?php _e('本页为独立页面, 与文章同源管理。'); ?></li>
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
                <code class="lylme-slug" title="<?php _e('页面别名'); ?>/<?php echo lylme_e($lylme_row['art_slug']); ?>">
                    <i class="mdi mdi-link-variant"></i> <?php echo lylme_e($lylme_row['art_slug']); ?>
                </code>
            <?php endif; ?>
        </div>
    </div>
</article>

                    </div><!-- /#lylme-main .col-lg-8 -->
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>

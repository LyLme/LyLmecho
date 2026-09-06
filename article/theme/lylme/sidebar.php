<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * LyLme Modern 主题 - 右侧栏 (小工具区)
 * 结构: 站点/作者卡 → 分类 → 标签云 → 热门内容 → 最新评论 → 站点统计
 * 每个模块独立受 themeConfig 的 lylmeBlocks 开关控制。
 */
$lylme_home     = (string) (isset($this->options->siteUrl) ? $this->options->siteUrl : '/article/');
$lylme_title    = (string) (isset($this->options->title) ? $this->options->title : '博客');
$lylme_desc     = (string) (isset($this->options->description) ? $this->options->description : '');
$lylme_logo     = (string) lylme_logo($this);
$lylme_hotLimit = max(3, min(15, intval(lylme_opt($this, 'lylmeHotLimit', 6))));
$lylme_feed     = (string) (isset($this->options->feedUrl) ? $this->options->feedUrl : $lylme_home . '?feed=rss');
$lylme_comfeed  = (string) (isset($this->options->commentsFeedUrl) ? $this->options->commentsFeedUrl : $lylme_home . '?feed=comments');
?>
                        <div class="col-lg-4 col-12 lylme-sidecol" id="lylme-sidecol" role="complementary">

                            <!-- 站点信息卡 -->
                            <section class="card lylme-widget lylme-about">
                                <div class="lylme-about-banner" aria-hidden="true">
                                    <span class="lylme-about-glow"></span>
                                </div>
                                <div class="card-body lylme-about-body">
                                    <div class="lylme-about-avatar">
                                        <?php if ($lylme_logo !== ''): ?>
                                        <img src="<?php echo lylme_e($lylme_logo); ?>" alt="" width="74" height="74">
                                        <?php else: ?>
                                            <span class="lylme-avatar-fallback"><i class="mdi mdi-feather"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="lylme-about-name"><?php echo lylme_e($lylme_title); ?></h3>
                                    <?php if ($lylme_desc !== ''): ?>
                                        <p class="lylme-about-desc"><?php echo lylme_e($lylme_desc); ?></p>
                                    <?php endif; ?>
                                    <?php $lylme_aboutStats = lylme_site_stats(); ?>
                                    <div class="lylme-about-mini">
                                        <span><i class="mdi mdi-file-document-outline"></i> <?php echo intval($lylme_aboutStats['posts']); ?> <?php _e('篇'); ?></span>
                                        <span class="sep"></span>
                                        <span><i class="mdi mdi-comment-outline"></i> <?php echo intval($lylme_aboutStats['comments']); ?> <?php _e('条'); ?></span>
                                        <span class="sep"></span>
                                        <span><i class="mdi mdi-calendar-blank-outline"></i> <?php echo date('Y'); ?></span>
                                    </div>
                                    <div class="lylme-about-actions">
                                        <a class="btn btn-sm btn-primary lylme-btn-sub" href="<?php echo lylme_e($lylme_feed); ?>" target="_blank" rel="noopener">
                                            <i class="mdi mdi-rss"></i> <?php _e('RSS 订阅'); ?>
                                        </a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo lylme_e($lylme_comfeed); ?>" target="_blank" rel="noopener" title="<?php _e('评论订阅'); ?>">
                                            <i class="mdi mdi-comment-processing-outline"></i>
                                        </a>
                                    </div>
                                </div>
                            </section>

                            <?php if (lylme_block_on($this, 'ShowStats')):
                                $lylme_stats = lylme_site_stats(); ?>
                                <section class="card lylme-widget lylme-stats">
                                    <div class="card-body">
                                        <div class="lylme-stats-grid">
                                            <div class="lylme-stat-item">
                                                <span class="lylme-stat-num"><?php echo intval($lylme_stats['posts']); ?></span>
                                                <span class="lylme-stat-label"><i class="mdi mdi-file-document-outline"></i> <?php _e('文章'); ?></span>
                                            </div>
                                            <div class="lylme-stat-item">
                                                <span class="lylme-stat-num"><?php echo intval($lylme_stats['cats']); ?></span>
                                                <span class="lylme-stat-label"><i class="mdi mdi-folder-outline"></i> <?php _e('分类'); ?></span>
                                            </div>
                                            <div class="lylme-stat-item">
                                                <span class="lylme-stat-num"><?php echo intval($lylme_stats['comments']); ?></span>
                                                <span class="lylme-stat-label"><i class="mdi mdi-comment-outline"></i> <?php _e('评论'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            <?php endif; ?>

                            <?php if (lylme_block_on($this, 'ShowCategories')):
                                \Widget\Metas\Category\Rows::alloc()->to($lylme_cats);
                                $lylme_catRows = [];
                                if ($lylme_cats->have()) {
                                    while ($lylme_cats->next()) {
                                        $lylme_catRows[] = [
                                            'name'  => (string) $lylme_cats->name,
                                            'url'   => (string) $lylme_cats->permalink,
                                            'count' => intval($lylme_cats->count),
                                        ];
                                    }
                                }
                                $lylme_catMax = 0;
                                foreach ($lylme_catRows as $cr) { $lylme_catMax = max($lylme_catMax, $cr['count']); }
                                if ($lylme_catRows): ?>
                                    <section class="card lylme-widget">
                                        <div class="card-header lylme-widget-head">
                                            <h4><i class="mdi mdi-folder-multiple-outline"></i> <?php _e('文章分类'); ?></h4>
                                            <span class="lylme-widget-badge"><?php echo count($lylme_catRows); ?></span>
                                        </div>
                                        <div class="card-body lylme-widget-body">
                                            <ul class="lylme-cat-list">
                                                <?php foreach ($lylme_catRows as $cr): ?>
                                                    <li>
                                                        <a href="<?php echo lylme_e($cr['url']); ?>">
                                                            <i class="mdi mdi-circle-small"></i>
                                                            <span class="lylme-cat-name"><?php echo lylme_e($cr['name']); ?></span>
                                                            <span class="lylme-cat-bar" aria-hidden="true"><i style="width:<?php echo $lylme_catMax > 0 ? max(8, round($cr['count'] / $lylme_catMax * 100)) : 0; ?>%"></i></span>
                                                            <span class="lylme-cat-count"><?php echo $cr['count']; ?></span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </section>
                                <?php endif;
                            endif; ?>

                            <?php if (lylme_block_on($this, 'ShowTags')):
                                $lylme_tags = lylme_tag_cloud(30);
                                if (!empty($lylme_tags)): ?>
                                    <section class="card lylme-widget">
                                        <div class="card-header lylme-widget-head">
                                            <h4><i class="mdi mdi-tag-outline"></i> <?php _e('热门标签'); ?></h4>
                                            <span class="lylme-widget-badge"><?php echo count($lylme_tags); ?></span>
                                        </div>
                                        <div class="card-body lylme-widget-body">
                                            <div class="lylme-tagcloud">
                                                <?php foreach ($lylme_tags as $t): ?>
                                                    <a class="lylme-tag" href="<?php echo lylme_e($t['url']); ?>" title="<?php echo intval($t['count']); ?> <?php _e('篇'); ?>">
                                                        #<?php echo lylme_e($t['name']); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </section>
                                <?php endif;
                            endif; ?>

                            <?php if (lylme_block_on($this, 'ShowHotPosts')):
                                $lylme_hot = lylme_hot_posts($lylme_hotLimit);
                                if (!empty($lylme_hot)): ?>
                                    <section class="card lylme-widget">
                                        <div class="card-header lylme-widget-head">
                                            <h4><i class="mdi mdi-fire"></i> <?php _e('热门内容'); ?></h4>
                                            <span class="lylme-widget-badge lylme-badge-hot"><i class="mdi mdi-chart-line"></i> <?php _e('按浏览'); ?></span>
                                        </div>
                                        <div class="card-body lylme-widget-body">
                                            <ol class="lylme-hot-list">
                                                <?php foreach ($lylme_hot as $i => $h): ?>
                                                    <li>
                                                        <span class="lylme-hot-rank r<?php echo min($i + 1, 10); ?>"><?php echo $i + 1; ?></span>
                                                        <a class="lylme-hot-title" href="<?php echo lylme_e($h['url']); ?>"><?php echo lylme_e($h['title']); ?></a>
                                                        <span class="lylme-hot-views"><i class="mdi mdi-eye-outline"></i> <?php echo intval($h['views']); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ol>
                                        </div>
                                    </section>
                                <?php endif;
                            endif; ?>

                            <?php if (lylme_block_on($this, 'ShowRecentComs')):
                                \Widget\Comments\Recent::alloc('pageSize=6')->to($lylme_rcms);
                                if ($lylme_rcms->have()): ?>
                                    <section class="card lylme-widget">
                                        <div class="card-header lylme-widget-head">
                                            <h4><i class="mdi mdi-comment-multiple-outline"></i> <?php _e('最新评论'); ?></h4>
                                        </div>
                                        <div class="card-body lylme-widget-body">
                                            <ul class="lylme-rcomment-list">
                                                <?php while ($lylme_rcms->next()): ?>
                                                    <li>
                                                        <div class="lylme-rcomment-head">
                                                            <span class="lylme-rcomment-author"><?php $lylme_rcms->author(false); ?></span>
                                                            <span class="lylme-rcomment-time"><?php echo lylme_e(lylme_time_ago(isset($lylme_rcms->row['com_time']) ? $lylme_rcms->row['com_time'] : '')); ?></span>
                                                        </div>
                                                        <a class="lylme-rcomment-excerpt" href="<?php $lylme_rcms->permalink(); ?>">
                                                            <?php $lylme_rcms->excerpt(48, '…'); ?>
                                                        </a>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
                                        </div>
                                    </section>
                                <?php endif;
                            endif; ?>

                            <!-- 归档 -->
                            <?php \Widget\Contents\Post\Date::alloc('type=month&format=Y 年 n 月')->to($lylme_arch); ?>
                            <?php if ($lylme_arch->have()): ?>
                                <section class="card lylme-widget">
                                    <div class="card-header lylme-widget-head">
                                        <h4><i class="mdi mdi-calendar-month-outline"></i> <?php _e('时间归档'); ?></h4>
                                    </div>
                                    <div class="card-body lylme-widget-body">
                                        <ul class="lylme-archive-list">
                                            <?php while ($lylme_arch->next()): ?>
                                                <li>
                                                    <a href="<?php echo lylme_e($lylme_arch->permalink); ?>">
                                                        <i class="mdi mdi-chevron-right"></i>
                                                        <span class="lylme-arch-date"><?php echo lylme_e($lylme_arch->date); ?></span>
                                                        <span class="lylme-cat-count"><?php echo intval(isset($lylme_arch->row['cnt']) ? $lylme_arch->row['cnt'] : 0); ?></span>
                                                    </a>
                                                </li>
                                            <?php endwhile; ?>
                                        </ul>
                                    </div>
                                </section>
                            <?php endif; ?>

                        </div><!-- /#lylme-sidecol .col-lg-4 -->

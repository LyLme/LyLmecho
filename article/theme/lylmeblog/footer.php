<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * LyLme Blog 主题 - 页脚
 * 调用约定: 需先由页面模板关闭 #lylme-main (col-lg-8)、插入 sidebar (col-lg-4),
 * 再引入本文件; 本文件负责 row/container/main 的收尾与页脚/脚本。
 */
$lylme_home      = (string) (isset($this->options->siteUrl) ? $this->options->siteUrl : '/article/');
$lylme_title     = (string) (isset($this->options->title) ? $this->options->title : '博客');
$lylme_footer    = (string) lylme_opt($this, 'lylmeFooter', '');
$lylme_icp       = (string) lylme_opt($this, 'lylmeIcp', '');
?>
                    </div><!-- /.row g-4 (由 sidebar.php 关闭 col-lg-4) -->
                </div><!-- /.container-fluid -->

                <div class="lylme-footer-bar">
                    <div class="container-fluid">
                        <div class="lylme-footer-inner">
                            <div class="lylme-footer-copy">
                                <?php if ($lylme_footer !== ''): ?>
                                    <?php echo $lylme_footer; /* 由站长自行填写, 视为可信内容 */ ?>
                                <?php else: ?>
                                    &copy; <?php echo date('Y'); ?>
                                    <a href="<?php echo lylme_e($lylme_home); ?>"><?php echo lylme_e($lylme_title); ?></a>
                                    &nbsp;·&nbsp; <?php _e('Powered By'); ?>
                                    <a href="https://github.com/LyLme/lylme_spage" target="_blank" rel="noopener">LyLme&nbsp;Spage</a>
                                    <?php _e('· Supports'); ?>
                                     <a href="https://typecho.org/" target="_blank" rel="noopener">Typecho</a>
                                     <?php _e('Themes'); ?>
                                    &nbsp;·&nbsp;
                                    <a href="<?php echo lylme_e(lylme_site_base($this)); ?>">
                                        <i class="mdi mdi-compass-outline"></i> <?php _e('返回导航首页'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if ($lylme_icp !== ''): ?>
                                <div class="lylme-footer-icp">
                                    <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow">
                                        <i class="mdi mdi-shield-check-outline"></i> <?php echo lylme_e($lylme_icp); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
            <!-- ==================== End 主内容区 ==================== -->
        </div><!-- /.lyear-layout-container -->
    </div><!-- /.lyear-layout-web -->

    <button type="button" class="lylme-backtop" id="lylme-backtop" title="<?php _e('返回顶部'); ?>" aria-label="<?php _e('返回顶部'); ?>">
        <i class="mdi mdi-chevron-up"></i>
    </button>

    <!-- 后台同款脚本基座 -->
    <script src="<?php echo lylme_e(lylme_asset($this, 'assets/admin/js/jquery.min.js')); ?>"></script>
    <script src="<?php echo lylme_e(lylme_asset($this, 'assets/admin/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo lylme_e(lylme_asset($this, 'assets/admin/js/perfect-scrollbar.min.js')); ?>"></script>
    <script src="<?php echo lylme_e(lylme_asset($this, 'assets/admin/js/main.min.js')); ?>"></script>
    <!-- 主题脚本: 明暗切换 / 回复交互 / 阅读进度 / 返回顶部 / 代码复制 -->
    <script src="<?php echo lylme_e(lylme_uri($this, 'main.js')); ?>?v=<?php echo lylme_e(lylme_asset_ver()); ?>"></script>

    <?php $this->footer(); ?>
</body>
</html>

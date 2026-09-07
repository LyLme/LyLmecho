<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * LyLme Blog 主题 - 404
 */
$this->need('header.php');
$lylme_home = (string) (isset($this->options->siteUrl) ? $this->options->siteUrl : '/article/');
?>

<section class="card lylme-404">
    <div class="card-body lylme-404-body">
        <div class="lylme-404-code" aria-hidden="true">404</div>
        <h1 class="lylme-404-title"><?php _e('抱歉, 你访问的内容不存在'); ?></h1>
        <p class="lylme-404-lead"><?php _e('链接可能已失效、被移动, 或者你输入了错误的地址。'); ?></p>
        <div class="lylme-404-actions">
            <a class="btn btn-primary" href="<?php echo lylme_e($lylme_home); ?>">
                <i class="mdi mdi-home"></i> <?php _e('返回首页'); ?>
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                <i class="mdi mdi-arrow-left"></i> <?php _e('返回上一页'); ?>
            </button>
        </div>
        <form class="lylme-404-search" method="post" action="<?php echo lylme_e($lylme_home); ?>">
            <i class="mdi mdi-magnify"></i>
            <input type="text" name="s" placeholder="<?php _e('要不, 试试搜索?'); ?>">
            <button type="submit"><?php _e('搜索'); ?></button>
        </form>
    </div>
</section>

                    </div><!-- /#lylme-main .col-lg-8 -->
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>

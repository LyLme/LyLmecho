<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * LyLme Blog 主题 - 评论区
 * 依赖 Compat\CommentsWidget 生成 .comment-list / .comment-body / .comment-author / .comment-children 结构。
 * 依赖主题 main.js 中的 TypechoComment.reply / cancelReply 完成"回复挂到本条下方"的表单迁移。
 */
$lylme_cmt_open = $this->allow('comment');
$this->comments()->to($lylme_comments);
$lylme_logged   = $this->user && method_exists($this->user, 'hasLogin') ? $this->user->hasLogin() : false;
$lylme_is_admin = $lylme_logged && method_exists($this->user, 'isAdmin') ? $this->user->isAdmin() : false;
$lylme_login    = (string) (isset($this->options->loginUrl) ? $this->options->loginUrl : '?member=login');
?>
<section class="card lylme-comments" id="comments">
    <div class="card-header lylme-comments-head">
        <h3 class="lylme-comments-title">
            <i class="mdi mdi-comment-multiple-outline"></i>
            <?php $this->commentsNum(_t('发表评论'), _t('1 条评论'), _t('%d 条评论')); ?>
        </h3>
        <?php if ($lylme_comments->have()): ?>
            <span class="lylme-comments-hint"><?php _e('欢迎理性讨论, 请勿刷屏'); ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body lylme-comments-body">

        <?php if ($lylme_comments->have()): ?>
            <?php $lylme_comments->listComments(); ?>
            <?php $lylme_comments->pageNav(); ?>
        <?php else: ?>
            <div class="lylme-comments-empty">
                <i class="mdi mdi-chat-outline"></i> <?php _e('还没有评论, 沙发等你来抢 ~'); ?>
            </div>
        <?php endif; ?>

        <?php if ($lylme_cmt_open): ?>
            <div id="<?php $this->respondId(); ?>" class="lylme-respond">
                <div class="lylme-respond-cancel">
                    <?php $lylme_comments->cancelReply(_t('取消回复')); ?>
                </div>
                <h4 id="response" class="lylme-respond-title">
                    <i class="mdi mdi-square-edit-outline"></i> <?php _e('添加新评论'); ?>
                </h4>
                <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" class="lylme-comment-form" role="form">
                    <?php if ($lylme_logged): ?>
                        <p class="lylme-comment-as">
                            <i class="mdi mdi-account-check-outline"></i>
                            <?php _e('登录身份'); ?>：
                            <b><?php echo lylme_e($lylme_is_admin ? _t('管理员') : (string) $this->user->name()); ?></b>
                            <?php if (!$lylme_is_admin): ?>
                                &nbsp;<a href="<?php echo lylme_e($this->options->profileUrl); ?>"><?php _e('会员中心'); ?></a>
                            <?php endif; ?>
                            &nbsp;<a href="<?php echo lylme_e($this->options->logoutUrl); ?>"><?php _e('退出'); ?> &raquo;</a>
                        </p>
                    <?php else: ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="author" class="form-label"><?php _e('称呼'); ?> <span class="req">*</span></label>
                                <input type="text" name="author" id="author" class="form-control"
                                       value="<?php $this->remember('author'); ?>" required autocomplete="nickname">
                            </div>
                            <div class="col-md-4">
                                <label for="mail" class="form-label"><?php _e('Email'); ?></label>
                                <input type="email" name="mail" id="mail" class="form-control"
                                       value="<?php $this->remember('mail'); ?>"<?php if (!empty($this->options->commentsRequireMail)): ?> required<?php endif; ?> autocomplete="email">
                            </div>
                            <div class="col-md-4">
                                <label for="url" class="form-label"><?php _e('网站'); ?></label>
                                <input type="url" name="url" id="url" class="form-control"
                                       placeholder="<?php _e('https://'); ?>"
                                       value="<?php $this->remember('url'); ?>"<?php if (!empty($this->options->commentsRequireUrl)): ?> required<?php endif; ?> autocomplete="url">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <label for="textarea" class="form-label"><?php _e('评论内容'); ?> <span class="req">*</span></label>
                        <textarea rows="6" name="text" id="textarea" class="form-control lylme-textarea"
                                  placeholder="<?php _e('说点什么吧~'); ?>"
                                  required><?php $this->remember('text'); ?></textarea>
                    </div>

                    <input type="hidden" name="parent" id="comment-parent" value="0">

                    <div class="lylme-comment-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-send"></i> <?php _e('提交评论'); ?>
                        </button>
                        <a class="btn btn-link lylme-comment-help" href="<?php echo lylme_e($lylme_login); ?>"<?php echo $lylme_logged ? ' style="display:none"' : ''; ?>>
                            <?php _e('登录后评论更便捷'); ?>
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="lylme-comments-closed">
                <i class="mdi mdi-lock-outline"></i>
                <?php _e('评论已关闭'); ?>
            </div>
        <?php endif; ?>
    </div>
</section>

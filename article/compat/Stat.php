<?php
/**
 * Typecho 兼容层 - 统计 Widget
 * 对应 Typecho 的 \Widget\Stat, 支持 $stat->publishedPostsNum 等属性
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Stat extends BaseWidget
{
    /** @var int 已发布文章数 */
    public $publishedPostsNum = 0;

    /** @var int 草稿数 */
    public $draftPostsNum = 0;

    /** @var int 已发布页面数 */
    public $publishedPagesNum = 0;

    /** @var int 已发布评论数 */
    public $publishedCommentsNum = 0;

    /** @var int 分类数 */
    public $categoriesNum = 0;

    /** @var int 标签数 */
    public $tagsNum = 0;

    // === 补充属性 (Typecho 原版有 22 个) ===
    /** @var int 待审核文章数 */
    public $waitingPostsNum = 0;
    public $myPublishedPostsNum = 0;
    public $myWaitingPostsNum = 0;
    public $myDraftPostsNum = 0;
    public $currentPublishedPostsNum = 0;
    public $currentWaitingPostsNum = 0;
    public $currentDraftPostsNum = 0;
    public $draftPagesNum = 0;
    public $waitingCommentsNum = 0;
    public $spamCommentsNum = 0;
    public $myPublishedCommentsNum = 0;
    public $myWaitingCommentsNum = 0;
    public $mySpamCommentsNum = 0;
    public $currentCommentsNum = 0;
    public $currentPublishedCommentsNum = 0;
    public $currentWaitingCommentsNum = 0;
    public $currentSpamCommentsNum = 0;

    protected function execute()
    {
        $this->publishedPostsNum = intval($this->db->get_column(
            "SELECT COUNT(*) FROM `lylme_article` WHERE `art_status` = 1"
        ));
        $this->publishedCommentsNum = intval($this->db->get_column(
            "SELECT COUNT(*) FROM `lylme_article_comment` WHERE `com_status` = 1"
        ));
        $this->categoriesNum = intval($this->db->get_column(
            "SELECT COUNT(*) FROM `lylme_article_cat`"
        ));
        // 同步镜像属性
        $this->myPublishedPostsNum = $this->publishedPostsNum;
        $this->currentPublishedPostsNum = $this->publishedPostsNum;
        $this->currentCommentsNum = $this->publishedCommentsNum;
        $this->currentPublishedCommentsNum = $this->publishedCommentsNum;
    }
}

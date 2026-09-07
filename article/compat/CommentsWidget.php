<?php
/**
 * Typecho 兼容层 - 评论组件
 * 对应 \Widget\Comments\Archive, 支持 listComments / pageNav / cancelReply / threadedComments 等
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class CommentsWidget extends BaseWidget
{
    /** @var int 文章ID */
    protected $articleId = 0;

    /** @var int 评论类型: 0=文章, 1=链接 */
    protected $commentType = 0;

    /** @var bool 是否允许评论 */
    public $allowComment = true;

    /** @var Archive 父文章对象 */
    public $parentContent;

    /** @var array 树形评论关系 */
    protected $threadedComments = [];

    /** @var int 评论总数 */
    protected $commentTotal = 0;

    /** @var string 评论回复区锚点 */
    protected $respondId = 'respond';

    public function __construct($parameter = null)
    {
        parent::__construct($parameter);
    }

    protected function execute()
    {
        $this->articleId = intval($this->param('parentId', 0));
        $this->allowComment = intval($this->param('allowComment', 1)) === 1;
        $this->parentContent = $this->param('parentContent', null);
        $this->respondId = 'respond-post-' . $this->articleId;

        if ($this->articleId <= 0) {
            return;
        }

        $this->commentType = intval($this->param('type', 0));

        // 查询该内容下已通过审核的评论 (com_type: 0=文章 1=链接, 防止 art_id 编号重叠时串评)
        $result = $this->db->query(
            "SELECT * FROM `lylme_article_comment` WHERE `art_id` = {$this->articleId} AND `com_type` = {$this->commentType} AND `com_status` = 1 ORDER BY `com_id` ASC"
        );

        $rows = [];
        if ($result) {
            while ($row = $this->db->fetch($result)) {
                $rows[] = $row;
            }
        }

        // 按父评论构建树
        $flat = [];
        foreach ($rows as $row) {
            $flat[$row['com_id']] = $row;
        }
        $tree = [];
        foreach ($flat as $coid => $row) {
            $pid = intval($row['com_pid']);
            if ($pid > 0 && isset($flat[$pid])) {
                $tree[$pid][] = $row;
            } else {
                $tree[0][] = $row;
            }
        }
        $this->threadedComments = $tree;

        // 平铺输出顺序: 顶层在前
        $this->commentTotal = count($rows);
        $this->stack = isset($tree[0]) ? $tree[0] : [];
        $this->length = count($this->stack);
        $this->sequence = 0;
        $this->pointer = -1;
    }

    /**
     * 评论总数
     */
    public function num()
    {
        $args = func_get_args();
        if (empty($args)) {
            $args = ['%d'];
        }
        $num = $this->commentTotal;
        echo sprintf($args[($num <= count($args) - 1) ? $num : count($args) - 1], $num);
    }

    /**
     * 输出分页(兼容)
     */
    public function pageNav($prev = '&laquo;', $next = '&raquo;', $splitPage = 3, $splitWord = '...', $template = '')
    {
        // 评论暂不做分页
    }

    /**
     * 列出评论
     */
    public function listComments($singleCommentOptions = null)
    {
        $options = [
            'before'       => '<ol class="comment-list">',
            'after'        => '</ol>',
            'beforeAuthor' => '',
            'afterAuthor'  => '',
            'dateFormat'   => $this->options->commentDateFormat,
            'replyWord'    => '回复',
        ];
        if (is_string($singleCommentOptions)) {
            parse_str($singleCommentOptions, $parsed);
            $options = array_merge($options, $parsed);
        } elseif (is_array($singleCommentOptions)) {
            $options = array_merge($options, $singleCommentOptions);
        }

        if (!$this->have()) {
            echo '<ol class="comment-list"><li class="comment-body comment-empty">'
                . '还没有评论, 快来说点什么吧~</li></ol>';
            return;
        }

        echo $options['before'];
        $this->renderThreaded($this->stack, $options);
        echo $options['after'];
    }

    /**
     * 递归输出评论树
     */
    protected function renderThreaded($comments, $options)
    {
        foreach ($comments as $row) {
            $this->row = $row;
            $this->sequence++;
            $this->renderComment($options);
            $coid = intval($row['com_id']);
            if (isset($this->threadedComments[$coid])) {
                echo '<div class="comment-children" itemprop="discusses">';
                $this->renderThreaded($this->threadedComments[$coid], $options);
                echo '</div>';
            }
        }
    }

    /**
     * 渲染单条评论
     */
    protected function renderComment($options)
    {
        $coid = intval($this->row['com_id']);
        $name = htmlspecialchars(isset($this->row['com_name']) ? $this->row['com_name'] : '', ENT_QUOTES, 'UTF-8');
        $content = nl2br(htmlspecialchars(isset($this->row['com_content']) ? $this->row['com_content'] : '', ENT_QUOTES, 'UTF-8'));
        $time = strtotime($this->row['com_time'] ?: 'now');
        // 访客网址仅接受 http/https, 兜底过滤 javascript:/data: 等危险协议(防历史脏数据 XSS)
        $rawUrl = isset($this->row['com_url']) ? trim((string) $this->row['com_url']) : '';
        if ($rawUrl !== '' && !preg_match('~^https?://~i', $rawUrl)) {
            $rawUrl = '';
        }
        $url = $rawUrl !== '' ? htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8') : '';
        $authorHtml = $url ? '<a href="' . $url . '" rel="external nofollow">' . $name . '</a>' : $name;
        $articleLink = $this->parentContent ? $this->parentContent->getPermalink() : App::$articleUrl;
        $replyLink = $articleLink . '#respond';

        echo '<li itemscope itemtype="http://schema.org/UserComments" id="comment-' . $coid . '" class="comment-body comment-parent">' . "\n";
        echo '<div class="comment-author" itemprop="creator" itemscope itemtype="http://schema.org/Person">' . "\n";
        echo '<span itemprop="image">' . $this->gravatarUrl(isset($this->row['com_email']) ? $this->row['com_email'] : '') . '</span>' . "\n";
        echo '<cite class="fn" itemprop="name">' . $authorHtml . '</cite>' . "\n";
        echo '</div>' . "\n";
        echo '<div class="comment-meta">' . "\n";
        echo '<a href="' . $articleLink . '#comment-' . $coid . '">' . "\n";
        echo '<time itemprop="commentTime" datetime="' . date('c', $time) . '">' . date($options['dateFormat'], $time) . '</time>' . "\n";
        echo '</a>' . "\n";
        echo '</div>' . "\n";
        echo '<div class="comment-content" itemprop="commentText">' . $content . '</div>' . "\n";
        if ($this->allowComment) {
            echo '<div class="comment-reply">'
                . '<a href="' . $replyLink . '" rel="nofollow" onclick="return TypechoComment.reply(\'comment-' . $coid . '\', ' . $coid . ', this);">'
                . $options['replyWord'] . '</a></div>' . "\n";
        }
        echo '</li>' . "\n";
    }

    /**
     * 生成 Gravatar 头像 HTML
     */
    protected function gravatarUrl($email, $size = 32)
    {
        $hash = md5(strtolower(trim($email)));
        $url = 'https://gravatar.loli.net/avatar/' . $hash . '?s=' . $size . '&d=identicon';
        return '<img class="avatar" src="' . $url . '" alt="avatar" width="' . $size . '" height="' . $size . '" />';
    }

    /**
     * 输出头像
     */
    public function gravatar($size = 32, $default = null, $highRes = false)
    {
        echo $this->gravatarUrl(isset($this->row['com_email']) ? $this->row['com_email'] : '', $size);
    }

    /**
     * 输出作者名
     */
    public function author($autoLink = true, $last = '')
    {
        echo htmlspecialchars(isset($this->row['com_name']) ? $this->row['com_name'] : '', ENT_QUOTES, 'UTF-8') . $last;
    }

    /**
     * 输出日期
     */
    public function date($format = 'Y-m-d H:i:s')
    {
        $time = strtotime($this->row['com_time'] ?: 'now');
        echo date($format, $time);
    }

    /**
     * 输出评论内容
     */
    public function content()
    {
        echo nl2br(htmlspecialchars(isset($this->row['com_content']) ? $this->row['com_content'] : '', ENT_QUOTES, 'UTF-8'));
    }

    /**
     * 输出评论摘要
     */
    public function excerpt($length = 100, $last = '...')
    {
        $text = trim(strip_tags(isset($this->row['com_content']) ? $this->row['com_content'] : ''));
        if (mb_strlen($text, 'UTF-8') > $length) {
            $text = mb_substr($text, 0, $length, 'UTF-8') . $last;
        }
        echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 评论固定链接
     */
    public function permalink()
    {
        $articleLink = $this->parentContent ? $this->parentContent->getPermalink() : App::$articleUrl;
        echo $articleLink . '#comment-' . intval(isset($this->row['com_id']) ? $this->row['com_id'] : 0);
    }

    /**
     * 输出评论 ID 锚点
     */
    public function theId()
    {
        echo 'comment-' . intval(isset($this->row['com_id']) ? $this->row['com_id'] : 0);
    }

    /**
     * 回复链接
     */
    public function reply($word = '')
    {
        if (!$this->allowComment) {
            return;
        }
        $word = $word ?: '回复';
        $articleLink = $this->parentContent ? $this->parentContent->getPermalink() : App::$articleUrl;
        echo '<a href="' . $articleLink . '#respond" rel="nofollow" onclick="return TypechoComment.reply(\'comment-'
            . intval(isset($this->row['com_id']) ? $this->row['com_id'] : 0) . '\', ' . intval(isset($this->row['com_id']) ? $this->row['com_id'] : 0) . ', this);">' . $word . '</a>';
    }

    /**
     * 取消回复链接
     */
    public function cancelReply($word = '')
    {
        $word = $word ?: '取消回复';
        $articleLink = $this->parentContent ? $this->parentContent->getPermalink() : App::$articleUrl;
        echo '<a id="cancel-comment-reply-link" href="' . $articleLink . '#respond" rel="nofollow"'
            . (isset($_GET['replyTo']) ? '' : ' style="display:none"')
            . ' onclick="return TypechoComment.cancelReply();">' . $word . '</a>';
    }

    /**
     * 子评论递归输出(兼容旧主题)
     */
    public function threadedComments()
    {
        // 已在 listComments 中递归处理
    }

    /**
     * 深度交替样式
     */
    public function levelsAlt(...$args)
    {
        call_user_func_array(array($this, 'alt'), $args);
    }

    /**
     * 交替样式（支持多参数）
     */
    public function alt($prev = '', $next = '')
    {
        $args = func_get_args();
        if (empty($args)) {
            return;
        }
        $idx = ($this->sequence) % count($args);
        echo $args[$idx];
    }

    /**
     * 评论状态
     */
    public function status()
    {
        echo 'approved';
    }

    /**
     * 父评论ID
     */
    public function parent()
    {
        echo intval(isset($this->row['com_pid']) ? $this->row['com_pid'] : 0);
    }
}

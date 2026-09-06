<?php
/**
 * Typecho 兼容层 - 侧边栏 Widget 类
 * 以命名空间类形式提供, 对应 Typecho 的:
 *   \Widget\Contents\Post\Recent
 *   \Widget\Contents\Page\Rows
 *   \Widget\Comments\Recent
 *   \Widget\Metas\Category\Rows
 *   \Widget\Contents\Post\Date
 */
namespace Compat\Widgets;

use Compat\BaseWidget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 最新文章
 */
class RecentPosts extends BaseWidget
{
    protected function execute()
    {
        $limit = max(1, intval($this->param('pageSize', $this->param('limit', 10))));
        $result = $this->db->query(
            "SELECT a.*, c.cat_name, c.cat_alias FROM `lylme_article` a "
            . "LEFT JOIN `lylme_article_cat` c ON a.cat_id = c.cat_id "
            . "WHERE a.art_status = 1 ORDER BY a.art_time DESC, a.art_id DESC LIMIT {$limit}"
        );
        if ($result) {
            while ($row = $this->db->fetch($result)) {
                $row['permalink'] = \Compat\App::postUrl($row);
                $this->push($row);
            }
        }
    }
}

/**
 * 独立页面(对应 Widget_Contents_Page_List)
 * 列出已发布页面, 供主题导航($this->widget('Widget_Contents_Page_List'))使用
 */
class PageRows extends BaseWidget
{
    protected function execute()
    {
        $result = $this->db->query(
            "SELECT * FROM `lylme_article_page` WHERE `page_status` = 1 "
            . "ORDER BY `page_order` ASC, `page_id` ASC"
        );
        if ($result) {
            while ($row = $this->db->fetch($result)) {
                // 补全 Typecho 主题常用字段名(title/slug/text/permalink/cid)
                $row['title']     = $row['page_title'];
                $row['slug']      = $row['page_slug'];
                $row['text']      = $row['page_content'];
                $row['permalink'] = \Compat\App::pageUrl($row);
                $row['cid']       = $row['page_id'];
                $this->push($row);
            }
        }
    }
}

/**
 * 最近评论
 */
class RecentComments extends BaseWidget
{
    protected function execute()
    {
        $limit = max(1, intval($this->param('pageSize', $this->param('limit', 10))));
        $result = $this->db->query(
            "SELECT co.*, a.art_id AS aid, a.art_title, a.art_slug, a.art_time FROM `lylme_article_comment` co "
            . "LEFT JOIN `lylme_article` a ON co.art_id = a.art_id "
            . "WHERE co.com_status = 1 ORDER BY co.com_id DESC LIMIT {$limit}"
        );
        if ($result) {
            while ($row = $this->db->fetch($result)) {
                // 缺 slug 时 App::postUrl() 会回退成 <id>.html, 与文章自身固定链接不是同一个 URL;
                // 这里把 url 构造需要的字段一起传全, 让评论链指向规范形式
                $row['permalink'] = \Compat\App::postUrl([
                        'art_id'    => intval($row['aid']),
                        'art_slug'  => isset($row['art_slug']) ? $row['art_slug'] : '',
                        'art_title' => isset($row['art_title']) ? $row['art_title'] : '',
                        'art_time'  => isset($row['art_time']) ? $row['art_time'] : '',
                    ])
                    . '#comment-' . intval($row['com_id']);
                $row['title'] = isset($row['art_title']) ? $row['art_title'] : '';
                $row['author'] = isset($row['com_name']) ? $row['com_name'] : '';
                $row['excerpt'] = mb_substr(strip_tags(isset($row['com_content']) ? $row['com_content'] : ''), 0, 60, 'UTF-8');
                $row['date'] = isset($row['com_time']) ? $row['com_time'] : '';
                $row['type'] = 'comment';
                $this->push($row);
            }
        }
    }
}

/**
 * 分类列表 (对应 Widget_Metas_Category_List)
 * 提供 name/permalink/description/count/levels 等字段
 */
class CategoryList extends BaseWidget
{
    protected function execute()
    {
        $result = $this->db->query(
            "SELECT c.*, (SELECT COUNT(*) FROM `lylme_article` a WHERE a.cat_id = c.cat_id AND a.art_status = 1) AS cnt "
            . "FROM `lylme_article_cat` c WHERE c.cat_status = 1 ORDER BY c.cat_order ASC, c.cat_id ASC"
        );
        if ($result) {
            while ($row = $this->db->fetch($result)) {
                $row['permalink'] = \Compat\App::categoryUrl($row['cat_alias']);
                $row['name'] = $row['cat_name'];
                $row['slug'] = $row['cat_alias'];
                $row['description'] = isset($row['cat_desc']) ? $row['cat_desc'] : '';
                $row['count'] = intval($row['cnt']);
                $row['title'] = $row['cat_name'];
                $row['levels'] = 0;
                // Typecho 分类字段: mid 为分类 ID, parent 为父级 (本站分类扁平, 恒为 0)
                $row['mid'] = intval($row['cat_id']);
                $row['parent'] = 0;
                $this->push($row);
            }
        }
    }

    /**
     * 输出分类列表 (嵌套)
     */
    public function listCategories($format = '')
    {
        $config = [];
        if (is_string($format)) {
            parse_str($format, $config);
        } elseif (is_array($format)) {
            $config = $format;
        }
        $wrapTag = isset($config['wrapTag']) ? $config['wrapTag'] : 'ul';
        $wrapClass = isset($config['wrapClass']) ? $config['wrapClass'] : 'widget-list';
        $childTag = isset($config['childTag']) ? $config['childTag'] : 'ul';
        $parentClass = isset($config['parentClass']) ? $config['parentClass'] : '';

        echo '<' . $wrapTag . (empty($wrapClass) ? '' : ' class="' . $wrapClass . '"') . '>';
        $this->reset();
        while ($this->next()) {
            echo '<li' . (empty($parentClass) ? '' : ' class="' . $parentClass . '"') . '>'
                . '<a href="' . $this->row['permalink'] . '">' . htmlspecialchars($this->row['cat_name'], ENT_QUOTES, 'UTF-8')
                . '</a></li>';
        }
        echo '</' . $wrapTag . '>';
    }

    /**
     * 取某分类的全部子分类 mid (Typecho: Widget_Metas_Category_List::getAllChildren)
     *
     * lylme 分类表 (lylme_article_cat) 无父级字段, 为扁平结构, 恒无子分类, 返回空数组。
     * 必须返回数组: 主题 (如 dux header.php) 会以 empty($children) / foreach 使用,
     * 若沿用基类 __call 返回 $this, 会被当成数组下标访问而致命。
     */
    public function getAllChildren($mid)
    {
        return [];
    }

    /**
     * 取某分类的全部父级 mid (本站扁平结构, 恒为空数组)
     */
    public function getAllParents($mid)
    {
        return [];
    }

    /**
     * 按 mid 取单个分类数据 (Typecho 语义: 返回数组)
     * 主题以 $cats->getCategory($mid)['permalink'] 使用, 故必须返回数组而非对象。
     */
    public function getCategory($mid)
    {
        $mid = intval($mid);
        if ($mid <= 0) {
            return [];
        }
        foreach ($this->stack as $row) {
            if (intval(isset($row['cat_id']) ? $row['cat_id'] : 0) === $mid) {
                return $row;
            }
        }
        return [];
    }
}

/**
 * 标签云 (对应 Widget_Metas_Tag_Cloud)
 * 系统无标签表, 返回空
 */
class TagCloud extends BaseWidget
{
    protected function execute()
    {
        // 系统无独立标签表, 从文章的 art_keywords (逗号分隔) 聚合出标签云
        $result = $this->db->query(
            "SELECT `art_keywords` FROM `lylme_article` WHERE `art_status` = 1 AND `art_keywords` <> '' LIMIT 500"
        );
        if (!$result) {
            return;
        }
        $map = [];
        while ($row = $this->db->fetch($result)) {
            foreach (explode(',', (string) $row['art_keywords']) as $tag) {
                $tag = trim(strip_tags($tag));
                if ($tag === '') {
                    continue;
                }
                $map[$tag] = isset($map[$tag]) ? $map[$tag] + 1 : 1;
            }
        }
        arsort($map);
        foreach ($map as $name => $count) {
            $this->push([
                'name'      => $name,
                'slug'      => $name,
                'title'     => $name,
                'count'     => (int) $count,
                'permalink' => \Compat\App::$articleUrl . '?s=' . urlencode((string) $name),
            ]);
        }
    }

    /**
     * Typecho: Widget_Metas_Tag_Cloud::listTags() 输出标签链接列表
     */
    public function listTags($args = null)
    {
        $this->reset();
        while ($this->next()) {
            echo '<a href="' . htmlspecialchars($this->row['permalink'], ENT_QUOTES, 'UTF-8') . '"'
                . ' title="' . htmlspecialchars($this->row['name'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($this->row['name'], ENT_QUOTES, 'UTF-8') . '</a>';
        }
    }
}

/**
 * 分类列表
 */
class CategoryRows extends BaseWidget
{
    protected function execute()
    {
        $result = $this->db->query(
            "SELECT c.*, (SELECT COUNT(*) FROM `lylme_article` a WHERE a.cat_id = c.cat_id AND a.art_status = 1) AS cnt "
            . "FROM `lylme_article_cat` c WHERE c.cat_status = 1 ORDER BY c.cat_order ASC, c.cat_id ASC"
        );
        if ($result) {
            while ($row = $this->db->fetch($result)) {
                $row['permalink'] = \Compat\App::categoryUrl($row['cat_alias']);
                $row['name'] = $row['cat_name'];
                $row['slug'] = $row['cat_alias'];
                $row['count'] = intval($row['cnt']);
                $row['title'] = $row['cat_name'];
                $row['levels'] = 0;
                // Typecho 分类字段: mid 为分类 ID, parent 为父级 (本站分类扁平, 恒为 0)
                $row['mid'] = intval($row['cat_id']);
                $row['parent'] = 0;
                $this->push($row);
            }
        }
    }

    /**
     * 输出分类列表
     */
    public function listCategories($format = '')
    {
        $config = [];
        if (is_string($format)) {
            parse_str($format, $config);
        } elseif (is_array($format)) {
            $config = $format;
        }
        $wrapTag = isset($config['wrapTag']) ? $config['wrapTag'] : 'ul';
        $wrapClass = isset($config['wrapClass']) ? $config['wrapClass'] : 'widget-list';
        $childTag = isset($config['childTag']) ? $config['childTag'] : 'ul';
        $parentClass = isset($config['parentClass']) ? $config['parentClass'] : '';

        echo '<' . $wrapTag . (empty($wrapClass) ? '' : ' class="' . $wrapClass . '"') . '>';
        $this->reset();
        while ($this->next()) {
            echo '<li' . (empty($parentClass) ? '' : ' class="' . $parentClass . '"') . '>'
                . '<a href="' . $this->row['permalink'] . '">' . htmlspecialchars($this->row['cat_name'], ENT_QUOTES, 'UTF-8')
                . '</a></li>';
        }
        echo '</' . $wrapTag . '>';
    }

    /**
     * 取某分类的全部子分类 mid (本站分类扁平, 恒为空数组)
     */
    public function getAllChildren($mid)
    {
        return [];
    }

    /**
     * 取某分类的全部父级 mid (本站分类扁平, 恒为空数组)
     */
    public function getAllParents($mid)
    {
        return [];
    }

    /**
     * 按 mid 取单个分类数据 (Typecho 语义: 返回数组)
     */
    public function getCategory($mid)
    {
        $mid = intval($mid);
        if ($mid <= 0) {
            return [];
        }
        foreach ($this->stack as $row) {
            if (intval(isset($row['cat_id']) ? $row['cat_id'] : 0) === $mid) {
                return $row;
            }
        }
        return [];
    }
}

/**
 * 时间归档(按月)
 */
class PostDate extends BaseWidget
{
    protected function execute()
    {
        $parameter = $this->parameter;
        $type = isset($parameter->type) ? $parameter->type : 'month';
        $format = isset($parameter->format) ? $parameter->format : 'F Y';

        // 按月归档
        if ($type === 'month') {
            $result = $this->db->query(
                "SELECT DATE_FORMAT(art_time, '%Y-%m') AS ym, COUNT(*) AS cnt "
                . "FROM `lylme_article` WHERE art_status = 1 GROUP BY ym ORDER BY ym DESC"
            );
            if ($result) {
                while ($row = $this->db->fetch($result)) {
                    $ts = strtotime($row['ym'] . '-01');
                    $row['permalink'] = \Compat\App::archiveUrl($row['ym']);
                    $row['date'] = date($format, $ts);
                    $row['title'] = $row['date'];
                    $row['count'] = intval($row['cnt']);
                    $this->push($row);
                }
            }
        }
    }
}

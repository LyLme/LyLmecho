<?php
/**
 * Typecho 兼容层 - Widget 基类
 * 模拟 Typecho Widget 的行迭代机制:
 *   - alloc() 静态入口
 *   - to($var) 将自身赋值给引用变量
 *   - next() / have() 迭代
 *   - $row->field() 输出字段 / $row->field 取字段
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

abstract class BaseWidget
{
    /** @var array 当前行数据 */
    public $row = [];

    /** @var array 堆栈(所有行) */
    public $stack = [];

    /** @var int 行数 */
    public $length = 0;

    /** @var int 序列计数 */
    public $sequence = 0;

    /** @var Options 全局配置 */
    public $options;

    /** @var \Typecho\Request 请求组件 (主题常用 $this->request->getRequestUrl() 等) */
    public $request;

    /** @var Security 安全组件 (主题常用 $this->security->getToken()) */
    public $security;

    /** @var \DB 数据库 */
    protected $db;

    /** @var int 指针 */
    protected $pointer = -1;

    /** @var \stdClass 查询参数 (Typecho 主题期望以对象方式访问 parameter->pageSize 等) */
    public $parameter;

    /** @var array Typecho 列名 -> lylme 列名 */
    protected static $alias = [
        'cid' => 'art_id',
        'title' => 'art_title',
        'slug' => 'art_slug',
        'created' => 'art_time',
        'modified' => 'art_update',
        'text' => 'art_content',
        'excerpt' => 'art_excerpt',
        'status' => 'art_status',
        'commentsNum' => 'art_comments',
        'views' => 'art_views',
        'authorId' => 'art_author',
        'category' => 'cat_id',
    ];

    /** @var array Typecho 评论列名 -> lylme 评论列名 (lylme_article_comment) */
    protected static $commentAlias = [
        'coid' => 'com_id',
        'mail' => 'com_email',
        'url' => 'com_url',
        'created' => 'com_time',
        'author' => 'com_name',
        'text' => 'com_content',
        'cid' => 'art_id',
        'parent' => 'com_pid',
        'uid' => 'com_uid',
    ];

    public function __construct($parameter = null)
    {
        $this->parameter = new \stdClass();
        if (is_string($parameter)) {
            $parsed = [];
            parse_str($parameter, $parsed);
            foreach ($parsed as $k => $v) {
                $this->parameter->$k = $v;
            }
        } elseif (is_array($parameter)) {
            foreach ($parameter as $k => $v) {
                $this->parameter->$k = $v;
            }
        } elseif ($parameter instanceof \stdClass) {
            $this->parameter = $parameter;
        }
        $this->options = App::$options;
        $this->db = class_exists('\Typecho\Db') ? \Typecho\Db::get() : App::$db;
        $this->request = class_exists('\\Typecho\\Request') ? \Typecho\Request::getInstance() : null;
        $this->security = new Security();
        $this->execute();
    }

    /**
     * 兼容 Typecho 静态入口
     */
    public static function alloc($parameter = null)
    {
        return new static($parameter);
    }

    /**
     * 将当前组件赋给引用变量
     */
    public function to(&$var)
    {
        $var = $this;
        return $this;
    }

    /**
     * 解析并输出, 支持 {field} 占位符
     * 同时支持原始列名({art_title})与 Typecho 别名({title}/{slug}/{cid}/{commentsNum} 等),
     * 并补充计算字段 {permalink}/{url}/{author}/{time}, 与 Typecho 主题模板预期一致。
     */
    public function parse($format)
    {
        while ($this->next()) {
            $row = $this->row;
            $search = [];
            $replace = [];
            // 1) 原始列名
            foreach ($row as $k => $v) {
                if (is_scalar($v)) {
                    $search[] = '{' . $k . '}';
                    $replace[] = (string) $v;
                }
            }
            // 2) Typecho 列名别名
            foreach (self::$alias as $aliasKey => $realKey) {
                if (array_key_exists($realKey, $row) && is_scalar($row[$realKey])) {
                    $search[] = '{' . $aliasKey . '}';
                    $replace[] = (string) $row[$realKey];
                }
            }
            // 3) 计算字段
            $permalink = App::postUrl($row);
            $search[] = '{permalink}';
            $replace[] = $permalink;
            $search[] = '{url}';
            $replace[] = $permalink;
            if (array_key_exists('art_author', $row)) {
                $search[] = '{author}';
                $replace[] = (string) $row['art_author'];
            }
            if (array_key_exists('art_time', $row)) {
                $search[] = '{time}';
                $replace[] = (string) strtotime((string) $row['art_time']);
            }
            echo str_replace($search, $replace, $format);
        }
    }

    /**
     * 压入一行数据
     */
    public function push($value)
    {
        $this->stack[] = $value;
        $this->length++;
        return $value;
    }

    /**
     * 是否有下一行
     */
    public function next()
    {
        if ($this->pointer + 1 < $this->length) {
            $this->pointer++;
            $this->row = $this->stack[$this->pointer];
            $this->sequence++;
            return true;
        }
        return false;
    }

    /**
     * 是否有数据
     */
    public function have()
    {
        return $this->length > 0 && $this->pointer + 1 < $this->length;
    }

    /**
     * 重置指针
     */
    public function reset()
    {
        $this->pointer = -1;
        $this->sequence = 0;
    }

    /**
     * 子类实现查询逻辑
     */
    protected function execute()
    {
    }

    /**
     * 取字段值 (支持 Typecho 列名别名 / date 对象 / permalink)
     */
    public function __get($name)
    {
        // Typecho 语义: created/modified 暴露为 int 时间戳 (主题多以 date($fmt, $post->created) 使用)
        if ($name === 'created' || $name === 'modified') {
            $keys = $name === 'created'
                ? ['created', 'art_time', 'com_time']
                : ['modified', 'art_update', 'art_time', 'com_time'];
            foreach ($keys as $k) {
                if (isset($this->row[$k]) && $this->row[$k] !== '') {
                    return is_numeric($this->row[$k]) ? (int) $this->row[$k] : (int) strtotime((string) $this->row[$k]);
                }
            }
            return 0;
        }
        if (isset($this->row[$name])) {
            return $this->row[$name];
        }
        // 评论列名别名 (mail→com_email, coid→com_id 等)
        if (isset(self::$commentAlias[$name]) && isset($this->row[self::$commentAlias[$name]])) {
            return $this->row[self::$commentAlias[$name]];
        }
        if (isset(self::$alias[$name]) && isset($this->row[self::$alias[$name]])) {
            return $this->row[self::$alias[$name]];
        }
        if ($name === 'date') {
            $time = isset($this->row['art_time']) ? $this->row['art_time'] : (isset($this->row['com_time']) ? $this->row['com_time'] : '');
            return $time === '' ? null : new \Typecho\Date($time);
        }
        if ($name === 'permalink') {
            return App::postUrl($this->row);
        }
        if ($name === 'author') {
            // 评论上下文: com_name 在 row 中直接返回字符串
            if (isset($this->row['com_name'])) {
                return $this->row['com_name'];
            }
            $author = isset($this->row['art_author']) ? $this->row['art_author'] : '';
            return new Author($author, App::$articleUrl);
        }
        if ($name === 'fields') {
            return new \ArticleFields($this->row);
        }
        // 评论特有属性 (主题常用 $comments->levels / authorId / ownerId / agent)
        if ($name === 'levels') {
            // 评论嵌套层级: com_pid>0 视为子评论(返回 1), 顶层返回 0
            return (intval(isset($this->row['com_pid']) ? $this->row['com_pid'] : 0) > 0) ? 1 : 0;
        }
        if ($name === 'authorId') {
            return intval(isset($this->row['com_uid']) ? $this->row['com_uid'] : 0);
        }
        if ($name === 'ownerId') {
            // 文章作者 uid: 本站无精确映射, 返回 0 (主题用于判断评论者是否=文章作者)
            return 0;
        }
        if ($name === 'agent') {
            // 本站不存储 UA, 返回空串 (主题用于解析浏览器/OS 图标)
            return '';
        }
        return null;
    }

    /**
     * 方法调用输出字段值 (支持 date('Y-m-d') / permalink() 等)
     */
    public function __call($name, $args)
    {
        if ($name === 'date') {
            $d = $this->__get('date');
            $fmt = isset($args[0]) ? (string) $args[0] : 'Y-m-d H:i:s';
            if ($d instanceof \Typecho\Date) {
                echo $d->format($fmt);
            } elseif ($d !== null && $d !== '') {
                // row['date'] 已被预置为时间字符串/时间戳时(如 RecentComments 设 $row['date'] = com_time),
                // 直接按时间格式化输出, 避免出现 "()" 空白前缀
                $ts = is_numeric($d) ? (int) $d : strtotime((string) $d);
                if ($ts === false || $ts < 0) {
                    $ts = time();
                }
                echo date($fmt, $ts);
            } else {
                echo '';
            }
            return $this;
        }
        if ($name === 'created' || $name === 'modified') {
            $ts = (int) $this->__get($name);
            echo date(isset($args[0]) ? (string) $args[0] : 'Y-m-d H:i:s', $ts ?: time());
            return $this;
        }
        if ($name === 'permalink') {
            echo $this->__get('permalink');
            return $this;
        }
        $value = $this->__get($name);
        if (is_scalar($value)) {
            // author(评论者/会员可控) 输出到 HTML 文本上下文, 必须转义防存储型 XSS
            if ($name === 'author') {
                echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            } else {
                echo (string) $value;
            }
        }
        return $this;
    }

    /**
     * 实例方法 widget(): 兼容 $this->widget('Widget_Metas_Category_List')
     */
    public function widget($widget, $parameter = null)
    {
        return \Typecho\Widget::widget($widget, $parameter);
    }

    /**
     * 获取参数
     */
    protected function param($name, $default = null)
    {
        return isset($this->parameter->$name) ? $this->parameter->$name : $default;
    }

    /**
     * 属性 isset 检测 (配合主题的 isset($this->xxx) 判定)
     */
    public function __isset($name)
    {
        if ($name === 'fields' || $name === 'parameter') {
            return true;
        }
        // 评论虚拟属性
        if (in_array($name, ['levels', 'authorId', 'ownerId', 'agent'], true)) {
            return true;
        }
        // 评论列名别名
        if (isset(self::$commentAlias[$name])) {
            return isset($this->row[self::$commentAlias[$name]]);
        }
        if (isset(self::$alias[$name])) {
            $real = self::$alias[$name];
            return isset($this->row[$real]);
        }
        return isset($this->row[$name]);
    }

    /** 取多列值 */
    public function toColumn($columns)
    {
        $result = [];
        foreach ($this->stack as $row) {
            $vals = [];
            foreach ((array) $columns as $col) {
                $alias = isset(self::$alias[$col]) ? self::$alias[$col] : $col;
                $vals[] = isset($row[$alias]) ? $row[$alias] : (isset($row[$col]) ? $row[$col] : null);
            }
            $result[] = count($vals) === 1 ? $vals[0] : $vals;
        }
        return $result;
    }

    /** 转数组 */
    public function toArray($columns = null)
    {
        if ($columns === null) {
            return $this->stack;
        }
        $result = [];
        foreach ($this->stack as $row) {
            $filtered = [];
            foreach ((array) $columns as $col) {
                $alias = isset(self::$alias[$col]) ? self::$alias[$col] : $col;
                $filtered[$col] = isset($row[$alias]) ? $row[$alias] : (isset($row[$col]) ? $row[$col] : null);
            }
            $result[] = $filtered;
        }
        return $result;
    }

    /**
     * Typecho: Widget_Abstract::filter($row) 返回"补全 Typecho 字段后的行"(数组)
     *
     * 主题 (如 dux functions.php) 以 $row = ...->filter($row); 再 $row['permalink'] 使用,
     * 必须返回数组: 若落到 __call() 返回 $this, 模板里被当数组下标访问就会致命。
     */
    public function filter($value)
    {
        if (!is_array($value)) {
            return [];
        }
        $row = $value;

        if (!isset($row['permalink'])) {
            $row['permalink'] = App::postUrl($row);
        }
        if (!isset($row['cid']) && isset($row['art_id'])) {
            $row['cid'] = $row['art_id'];
        }
        if (!isset($row['title']) && isset($row['art_title'])) {
            $row['title'] = $row['art_title'];
        }
        if (!isset($row['slug']) && isset($row['art_slug'])) {
            $row['slug'] = $row['art_slug'];
        }
        if (!isset($row['text']) && isset($row['art_content'])) {
            $row['text'] = $row['art_content'];
        }
        if (!isset($row['excerpt']) && isset($row['art_excerpt'])) {
            $row['excerpt'] = $row['art_excerpt'];
        }
        if (!isset($row['commentsNum']) && isset($row['art_comments'])) {
            $row['commentsNum'] = $row['art_comments'];
        }
        if (!isset($row['views']) && isset($row['art_views'])) {
            $row['views'] = $row['art_views'];
        }
        if (!isset($row['author']) && isset($row['art_author'])) {
            $row['author'] = $row['art_author'];
        }
        // 时间: date 为对象, created/modified 为 int 时间戳 (与 Typecho 原生一致)
        if (!isset($row['created'])) {
            $t = isset($row['art_time']) ? $row['art_time'] : '';
            $row['created'] = $t === '' ? 0 : (is_numeric($t) ? (int) $t : (int) strtotime((string) $t));
        }
        if (!isset($row['modified'])) {
            $t = isset($row['art_update']) ? $row['art_update'] : (isset($row['art_time']) ? $row['art_time'] : '');
            $row['modified'] = $t === '' ? 0 : (is_numeric($t) ? (int) $t : (int) strtotime((string) $t));
        }
        if (!isset($row['date'])) {
            $t = isset($row['art_time']) ? $row['art_time'] : '';
            $row['date'] = $t === '' ? null : new \Typecho\Date($t);
        }
        if (!isset($row['categories'])) {
            $categories = [];
            $catId = intval(isset($row['cat_id']) ? $row['cat_id'] : 0);
            if ($catId > 0) {
                $cat = $this->db->get_row("SELECT * FROM `lylme_article_cat` WHERE `cat_id` = " . $catId);
                if ($cat) {
                    $categories[] = [
                        'name'      => $cat['cat_name'],
                        'slug'      => $cat['cat_alias'],
                        'permalink' => App::categoryUrl($cat['cat_alias']),
                    ];
                }
            }
            $row['categories'] = $categories;
        }

        return $row;
    }

    /** 交替输出 */
    public function alt($prev, $next)
    {
        echo $this->sequence % 2 ? $next : $prev;
    }

    /** 条件交替 */
    public function altBy($condition, $prev, $next)
    {
        echo $condition ? $next : $prev;
    }

    /** 加载模板 */
    public function template($file)
    {
        $path = App::$themeDir . $file;
        if (is_file($path)) {
            include $path;
        }
    }

    /** 批量压入 */
    public function pushAll($values)
    {
        foreach ($values as $v) {
            $this->push($v);
        }
    }

    /** 插件句柄 (返回空对象) */
    public function pluginHandle($className = '')
    {
        return \Typecho\Widget\Helper\EmptyClass::getInstance();
    }

    /** 事件绑定 (空实现) */
    public function on($event, $callback = null)
    {
        return $this;
    }

    /** 带别名分配 */
    public static function allocWithAlias($alias, $params = null)
    {
        return new static($params);
    }

    /** 销毁别名 (空实现) */
    public static function destroy($alias)
    {
    }
}

/**
 * 安全组件兼容类
 * 主题多以 $this->security->getToken($data) 取 CSRF token,
 * 本站评论后端(article/comment.php)不校验 token, 故返回稳定无害串即可。
 */
class Security
{
    public function getToken($data = null)
    {
        return md5('lylme-compat-token|' . (string) $data);
    }

    public function protect($Contents, $fields = null)
    {
        return $Contents;
    }

    public function __call($name, $args)
    {
        return '';
    }
}

/**
 * 通知组件 (对应 Typecho 的 Widget_Notice, 原生用于后台)
 *
 * 部分主题/插件会引用该组件, 类不存在会直接致命。本站前台不需要真实通知,
 * 故提供安全空实现: 实例方法返回自身(支持链式)、静态方法返回新实例、转字符串为空。
 */
class Notice
{
    public static function alloc($parameter = null)
    {
        return new self();
    }

    public function set($value, $type = 'notice')
    {
        return $this;
    }

    public function get($name = null)
    {
        return [];
    }

    public function highlight($theId)
    {
        return $this;
    }

    public function __call($name, $args)
    {
        return $this;
    }

    public static function __callStatic($name, $args)
    {
        return new self();
    }

    public function __get($name)
    {
        return $this;
    }

    public function __toString()
    {
        return '';
    }
}

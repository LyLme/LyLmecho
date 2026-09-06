<?php
/**
 * Typecho 兼容层 - Typecho\Db 类
 * 提供 \Typecho\Db::get() 静态方法及表名映射
 */
namespace Typecho;

use Compat\App;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit('Access Denied');
}

/**
 * Typecho Db 兼容类
 */
class Db
{
    const SORT_ASC = 'ASC';
    const SORT_DESC = 'DESC';
    const JOIN_INNER = 'INNER';
    const JOIN_LEFT = 'LEFT';
    const JOIN_RIGHT = 'RIGHT';
    const JOIN_OUTER = 'OUTER';
    const LEFT_JOIN = 'LEFT';
    const RIGHT_JOIN = 'RIGHT';
    const INNER_JOIN = 'INNER';
    const FULL_JOIN = 'FULL';
    const OUTER_JOIN = 'OUTER';
    const READ = 'read';
    const WRITE = 'write';
    const SELECT = 'select';
    const UPDATE = 'update';
    const INSERT = 'insert';
    const DELETE = 'delete';

    /** @var \DB 真实数据库对象 */
    protected static $realDb = null;

    public function __construct()
    {
    }

    /** @var array Typecho 表名 -> lylme 实际表名 */
    protected static $tables = [
        'contents' => 'lylme_article',
        'options' => 'lylme_article_config',
        'comments' => 'lylme_article_comment',
        'metas' => 'lylme_article_cat',
        'relationships' => 'lylme_article_relationship',
        'fields' => 'lylme_article',  // Typecho fields 表重定向到文章表 (views 等字段映射到 art_* 列)
        'users' => 'lylme_member',     // Typecho 用户表重定向到会员表
        'links' => 'lylme_links',      // Typecho 友情链接表
    ];

    /** @var array Typecho 自定义字段名 -> lylme_article 列名 (供 table.fields 查询拦截) */
    public static $fieldColumns = [
        'views' => 'art_views',
    ];

    /** @var array options 表列名映射 (Typecho -> lylme) */
    protected static $optionColumns = [
        'name' => 'k',
        'value' => 'v',
        'user' => 'id',
    ];

    /** @var array contents 表(lylme_article)列名映射 (Typecho -> lylme) */
    public static $columnMap = [
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

    /** @var array 评论表(lylme_article_comment)列名映射 (Typecho -> lylme) */
    public static $commentColumns = [
        'coid' => 'com_id',
        'mail' => 'com_email',
        'url' => 'com_url',
        'created' => 'com_time',
        'author' => 'com_name',
        'text' => 'com_content',
        'cid' => 'art_id',
        'parent' => 'com_pid',
        'status' => 'com_status',
        'uid' => 'com_uid',
    ];

    /** @var array 评论 status 值翻译 (Typecho 字符串 -> lylme int) */
    public static $commentStatusMap = [
        'approved' => '1',
        'waiting' => '0',
        'spam' => '0',
    ];

    /** @var array users 表(lylme_member)列名映射 (Typecho -> lylme) */
    public static $userColumns = [
        'uid' => 'uid',
        'screenName' => 'nickname',
        'name' => 'username',
        'mail' => 'email',
        'group' => 'role',
    ];

    public static function setDb(\DB $db)
    {
        self::$realDb = $db;
    }

    public static function get()
    {
        if (!self::$realDb) {
            self::$realDb = App::$db;
        }
        return new self();
    }

    /**
     * 解析 table.xxx 表名
     */
    public static function table($name)
    {
        $name = (string) $name;
        if (strpos($name, 'table.') === 0) {
            $key = substr($name, 6);
            if (isset(self::$tables[$key])) {
                return '`' . self::$tables[$key] . '`';
            }
            // 未知插件表, 原样返回(查询时会失败并返回空)
            return '`' . $key . '`';
        }
        return strpos($name, '`') === 0 ? $name : '`' . $name . '`';
    }

    /**
     * 获取原始表名(不带反引号)
     */
    public static function rawTable($name)
    {
        $name = (string) $name;
        if (strpos($name, 'table.') === 0) {
            $key = substr($name, 6);
            return isset(self::$tables[$key]) ? self::$tables[$key] : $key;
        }
        return str_replace('`', '', $name);
    }

    /**
     * 是否 options 表
     */
    public static function isOptions($table)
    {
        return self::rawTable($table) === 'lylme_article_config';
    }

    /**
     * 解析列名 (处理 table.contents.created 及 options 表列映射)
     */
    public static function column($column, $isOptions = false)
    {
        $column = (string) $column;
        if (strpos($column, 'table.') === 0) {
            $parts = explode('.', $column, 2);
            if ($isOptions && isset(self::$optionColumns[$parts[1]])) {
                $parts[1] = self::$optionColumns[$parts[1]];
            } elseif (!$isOptions && isset(self::$columnMap[$parts[1]])) {
                $parts[1] = self::$columnMap[$parts[1]];
            }
            return self::table($parts[0]) . '.' . '`' . str_replace('`', '', $parts[1]) . '`';
        }
        // 别名.列形式 (Printer 等主题常用): c.cid -> c.`art_id`
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)$/', $column, $m)) {
            $col = $m[2];
            if ($isOptions && isset(self::$optionColumns[$col])) {
                $col = self::$optionColumns[$col];
            } elseif (!$isOptions && isset(self::$columnMap[$col])) {
                $col = self::$columnMap[$col];
            }
            return $m[1] . '.`' . str_replace('`', '', $col) . '`';
        }
        if ($isOptions && isset(self::$optionColumns[$column])) {
            return '`' . self::$optionColumns[$column] . '`';
        }
        if (!$isOptions && isset(self::$columnMap[$column])) {
            return '`' . self::$columnMap[$column] . '`';
        }
        if ($column === '*') {
            return '*';
        }
        return strpos($column, '`') === 0 ? $column : '`' . $column . '`';
    }

    /**
     * 将 options 表查询结果列名反向映射 (v -> value)
     */
    public static function mapOptionsRow($row)
    {
        if (is_array($row)) {
            $map = ['v' => 'value', 'k' => 'name'];
            foreach ($map as $from => $to) {
                if (isset($row[$from])) {
                    $row[$to] = $row[$from];
                }
            }
        }
        return $row;
    }

    /**
     * 将 contents 表查询结果列名反向映射 (art_id -> cid 等)
     */
    public static function mapContentsRow($row)
    {
        if (is_array($row)) {
            foreach (self::$columnMap as $to => $from) {
                if (isset($row[$from])) {
                    $row[$to] = $row[$from];
                }
            }
            // Typecho 语义: created/modified 为 int UNIX 时间戳, 而非 datetime 字符串
            foreach (['created', 'modified'] as $tk) {
                if (isset($row[$tk]) && $row[$tk] !== '' && $row[$tk] !== null) {
                    $row[$tk] = is_numeric($row[$tk]) ? (int) $row[$tk] : (int) strtotime((string) $row[$tk]);
                }
            }
        }
        return $row;
    }

    /**
     * 将评论表查询结果列名反向映射 (com_id -> coid, com_email -> mail 等)
     */
    public static function mapCommentsRow($row)
    {
        if (is_array($row)) {
            foreach (self::$commentColumns as $to => $from) {
                if (isset($row[$from])) {
                    $row[$to] = $row[$from];
                }
            }
        }
        return $row;
    }

    /**
     * 将用户表(lylme_member)查询结果列名反向映射 (nickname -> screenName, email -> mail 等)
     */
    public static function mapUsersRow($row)
    {
        if (is_array($row)) {
            foreach (self::$userColumns as $to => $from) {
                if (isset($row[$from])) {
                    $row[$to] = $row[$from];
                }
            }
        }
        return $row;
    }

    public function getPrefix()
    {
        return 'lylme_';
    }

    public function getAdapterName()
    {
        return 'Mysql';
    }

    public function getVersion()
    {
        try {
            $row = $this->getRealDb()->get_row("SELECT VERSION() as v");
            return $row ? $row['v'] : '5.7.0';
        } catch (\Exception $e) {
            return '5.7.0';
        }
    }

    public function sql()
    {
        return new DbQuery($this->getRealDb(), 'select');
    }

    public function truncate($table)
    {
        $table = self::table($table);
        try {
            $this->getRealDb()->query("TRUNCATE TABLE " . $table);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function addServer($server, $op = 'read')
    {
        // 兼容层: 单数据库, 忽略
    }

    public function set($key, $value)
    {
        // 兼容层: 无额外属性
    }

    public function getAdapter()
    {
        return new DbAdapter();
    }

    public function fetchRow($query)
    {
        return DbQuery::fromMixed($query, $this)->fetchRow();
    }

    public function fetchAll($query)
    {
        return DbQuery::fromMixed($query, $this)->fetchAll();
    }

    public function fetchObject($query)
    {
        return DbQuery::fromMixed($query, $this)->fetchObject();
    }

    public function query($query)
    {
        return DbQuery::fromMixed($query, $this)->query();
    }

    public function exec($query)
    {
        return DbQuery::fromMixed($query, $this)->exec();
    }

    public function select(...$columns)
    {
        // 支持 select() / select('title') / select('title','text','created','modified') 多列形式
        if (empty($columns)) {
            $columns = '*';
        } elseif (count($columns) === 1) {
            $columns = $columns[0];
        }
        return new DbQuery($this->getRealDb(), 'select', $columns);
    }

    public function update($table = null)
    {
        $q = new DbQuery($this->getRealDb(), 'update');
        if ($table !== null) {
            $q->update($table);
        }
        return $q;
    }

    public function insert($table = null)
    {
        $q = new DbQuery($this->getRealDb(), 'insert');
        if ($table !== null) {
            $q->insert($table);
        }
        return $q;
    }

    public function delete($table = null)
    {
        $q = new DbQuery($this->getRealDb(), 'delete');
        if ($table !== null) {
            $q->delete($table);
        }
        return $q;
    }

    public function getRealDb()
    {
        if (!self::$realDb) {
            self::$realDb = App::$db;
        }
        return self::$realDb;
    }

    /**
     * 转发未定义的原始 \DB 方法 (fetch/get_row/get_column/num_rows/escape/...),
     * 使 $this->db 同时支持 Typecho 流式 API 与项目原生 API。
     */
    public function __call($name, $args)
    {
        $real = $this->getRealDb();
        if ($real !== null && is_callable(array($real, $name))) {
            return call_user_func_array(array($real, $name), $args);
        }
        throw new \BadMethodCallException('Unknown method: ' . $name);
    }
}

/**
 * Db 适配器 (仅提供 quoteColumn)
 */
class DbAdapter
{
    public function quoteColumn($name)
    {
        if ($name === '*') {
            return '*';
        }
        return strpos($name, '`') === 0 ? $name : '`' . str_replace('`', '', $name) . '`';
    }

    public function quoteValue($value)
    {
        return \Compat\App::$db->escape($value);
    }
}

/**
 * 链式查询构造器
 */
class DbQuery
{
    /** @var \DB */
    protected $db;

    /** @var string select|update|insert|delete */
    protected $type = 'select';

    /** @var string|array 列 */
    protected $columns = '*';

    /** @var string 表 */
    protected $from = '';

    /** @var array where 条件(已处理) */
    protected $wheres = [];

    /** @var array order */
    protected $orders = [];

    /** @var array group */
    protected $groups = [];

    /** @var string having */
    protected $having = '';

    /** @var int|null limit */
    protected $limit = null;

    /** @var int|null offset */
    protected $offset = null;

    /** @var array join 子句 */
    protected $joins = [];

    /** @var array 插入/更新数据 */
    protected $rows = [];

    /** @var bool 是否 options 表 */
    protected $isOptions = false;

    /** @var bool 是否 fields 表 (table.fields → lylme_article 自定义字段查询拦截) */
    protected $isFields = false;

    /** @var bool 是否评论表 (table.comments → lylme_article_comment) */
    protected $isComments = false;

    /** @var bool 是否用户表 (table.users → lylme_member) */
    protected $isUsers = false;

    /** @var string|null 原始 SQL (直接传入时使用) */
    public $rawQuery = null;

    public function __construct(\DB $db, $type = 'select', $columns = '*')
    {
        $this->db = $db;
        $this->type = $type;
        $this->columns = $columns;
    }

    /**
     * 从 Db::fetchRow 等接收的混合参数创建 DbQuery
     */
    public static function fromMixed($query, Db $db)
    {
        if ($query instanceof self) {
            return $query;
        }
        $q = new self($db->getRealDb(), 'select');
        $q->rawQuery = $query;
        return $q;
    }

    public function select(...$columns)
    {
        $this->type = 'select';
        if (empty($columns)) {
            $columns = '*';
        } elseif (count($columns) === 1) {
            $columns = $columns[0];
        }
        $this->columns = $columns;
        return $this;
    }

    /**
     * 生成表 SQL 片段 (支持 '表名 AS 别名' 形式, Printer 等主题常用)
     */
    protected function tableSql($table)
    {
        $table = (string) $table;
        if (preg_match('/^(.+?)\s+AS\s+([a-zA-Z_][a-zA-Z0-9_]*)$/i', trim($table), $m)) {
            return Db::table($m[1]) . ' AS ' . $m[2];
        }
        return Db::table($table);
    }

    public function from($table)
    {
        $rawTable = Db::rawTable($table);
        $this->from = $this->tableSql($table);
        $this->isOptions = ($rawTable === 'lylme_article_config');
        $this->isFields = ($rawTable === 'lylme_article' && strpos((string) $table, 'table.fields') !== false);
        $this->isComments = ($rawTable === 'lylme_article_comment');
        $this->isUsers = ($rawTable === 'lylme_member');
        return $this;
    }

    public function update($table)
    {
        $this->type = 'update';
        $this->from = $this->tableSql($table);
        $rawTable = Db::rawTable($table);
        $this->isOptions = ($rawTable === 'lylme_article_config');
        $this->isFields = ($rawTable === 'lylme_article' && strpos((string) $table, 'table.fields') !== false);
        $this->isComments = ($rawTable === 'lylme_article_comment');
        return $this;
    }

    public function insert($table)
    {
        $this->type = 'insert';
        $this->from = $this->tableSql($table);
        $rawTable = Db::rawTable($table);
        $this->isOptions = ($rawTable === 'lylme_article_config');
        $this->isFields = ($rawTable === 'lylme_article' && strpos((string) $table, 'table.fields') !== false);
        $this->isComments = ($rawTable === 'lylme_article_comment');
        return $this;
    }

    public function delete($table)
    {
        $this->type = 'delete';
        $this->from = $this->tableSql($table);
        $rawTable = Db::rawTable($table);
        $this->isOptions = ($rawTable === 'lylme_article_config');
        $this->isComments = ($rawTable === 'lylme_article_comment');
        return $this;
    }

    public function where($condition, ...$params)
    {
        $this->wheres[] = ['AND', $this->buildCondition((string) $condition, $params)];
        return $this;
    }

    /** OR 连接的条件 (语义同 where, 组装时以 OR 与前一条连接) */
    public function orWhere($condition, ...$params)
    {
        $this->wheres[] = ['OR', $this->buildCondition((string) $condition, $params)];
        return $this;
    }

    /**
     * 单条条件加工: 表前缀/列名映射 → 占位符绑定 → Typecho 枚举值翻译
     */
    protected function buildCondition($condition, array $params)
    {
        // 替换 table.xxx. 前缀
        $condition = preg_replace('/table\.([a-z_]+)\./', '`$1`.', $condition);
        // 替换裸列名 (options 表)
        if ($this->isOptions) {
            $condition = preg_replace('/(^|[\s(,])name(?=\s*[=<>!]|$)/', '$1`k`', $condition);
            $condition = preg_replace('/(^|[\s(,])value(?=\s*[=<>!]|$)/', '$1`v`', $condition);
        } elseif ($this->isComments) {
            // 评论表列名映射 (coid→com_id, mail→com_email 等)
            $condition = preg_replace_callback('/(^|[\s,(])([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)/', function ($m) {
                $col = $m[3];
                if (isset(Db::$commentColumns[$col])) {
                    $col = Db::$commentColumns[$col];
                }
                return $m[1] . $m[2] . '.`' . str_replace('`', '', $col) . '`';
            }, $condition);
            $condition = preg_replace('/(^|[\s(,])`?([a-z_]+)`?(?=\s*[=<>!]|$)/', '$1`$2`', $condition);
            foreach (Db::$commentColumns as $from => $to) {
                $condition = str_replace('`' . $from . '`', '`' . $to . '`', $condition);
            }
        } elseif ($this->isUsers) {
            // 用户表列名映射 (screenName→nickname, mail→email 等)
            $condition = preg_replace_callback('/(^|[\s,(])([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)/', function ($m) {
                $col = $m[3];
                if (isset(Db::$userColumns[$col])) {
                    $col = Db::$userColumns[$col];
                }
                return $m[1] . $m[2] . '.`' . str_replace('`', '', $col) . '`';
            }, $condition);
            $condition = preg_replace('/(^|[\s(,])`?([a-z_]+)`?(?=\s*[=<>!]|$)/', '$1`$2`', $condition);
            foreach (Db::$userColumns as $from => $to) {
                $condition = str_replace('`' . $from . '`', '`' . $to . '`', $condition);
            }
        } elseif ($this->isFields) {
            // fields 表 WHERE 条件:
            //   UPDATE/DELETE/INSERT 重写路径: 剥离 name=? 条件 (name 在 lylme_article 无对应列, 泄漏会 SQL 报错),
            //     兼容占位符 ? 与字面量 '...' / "..."
            //   SELECT 路径: 保留 name 条件, 由 buildFieldsQuery() 提取字段名后改写 (故仅非 select 时剥离)
            if ($this->type !== 'select') {
                $condition = preg_replace("/`?name`?\s*=\s*(?:\?|'[^']*'|\"[^\"]*\")/i", '1 = 1', $condition);
            }
            $condition = preg_replace('/`?cid`?/', '`art_id`', $condition);
        } else {
            // 别名.列映射: c.created -> c.`art_time` (Printer 等主题常用)
            $condition = preg_replace_callback('/(^|[\s,(])([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)/', function ($m) {
                $col = $m[3];
                if (isset(Db::$columnMap[$col])) {
                    $col = Db::$columnMap[$col];
                }
                return $m[1] . $m[2] . '.`' . str_replace('`', '', $col) . '`';
            }, $condition);
            $condition = preg_replace('/(^|[\s(,])`?([a-z_]+)`?(?=\s*[=<>!]|$)/', '$1`$2`', $condition);
            foreach (Db::$columnMap as $from => $to) {
                $condition = str_replace('`' . $from . '`', '`' . $to . '`', $condition);
            }
        }
        // 按序替换 ? 占位符 (支持 where('a = ? OR b = ?', $x, $y) 与 IN ? 数组绑定)
        foreach ($params as $p) {
            $pos = strpos($condition, '?');
            if ($pos === false) {
                break;
            }
            if (is_array($p)) {
                $escaped = [];
                foreach ($p as $v) {
                    $escaped[] = "'" . $this->db->escape($v) . "'";
                }
                $replacement = implode(',', $escaped);
            } else {
                $replacement = "'" . $this->db->escape($p) . "'";
            }
            $condition = substr($condition, 0, $pos) . $replacement . substr($condition, $pos + 1);
        }
        // Typecho 语义伪列/枚举值翻译到本项目 schema:
        //   contents 表无 type 列(全部为文章), status 列以 int 存储 (1=发布)
        //   仅命中下列精确模式, 避免误伤业务数据
        if (!$this->isOptions && !$this->isComments && !$this->isUsers) {
            $condition = preg_replace("/`type`\s*=\s*'post'/i", '1 = 1', $condition);
            $condition = preg_replace("/`type`\s*=\s*'page'/i", '1 = 0', $condition);
            $condition = preg_replace("/`art_status`\s*=\s*'publish'/i", '`art_status` = 1', $condition);
            $condition = preg_replace("/`art_status`\s*=\s*'pending'/i", '`art_status` = 0', $condition);
            $condition = preg_replace("/`art_status`\s*=\s*'hidden'/i", '`art_status` = 2', $condition);
        }
        // 评论表 status 值翻译: 'approved' → 1, 'waiting' → 0
        if ($this->isComments) {
            foreach (Db::$commentStatusMap as $from => $to) {
                $condition = preg_replace("/`com_status`\s*=\s*'" . preg_quote($from, '/') . "'/i", '`com_status` = ' . $to, $condition);
            }
        }
        return $condition;
    }

    public function order($column, $dir = 'ASC')
    {
        $column = (string) $column;
        $column = preg_replace('/table\.([a-z_]+)\./', '`$1`.', $column);
        if ($this->isOptions) {
            $column = preg_replace('/`?name`?(?=$|[\s,])/', '`k`', $column);
            $column = preg_replace('/`?value`?(?=$|[\s,])/', '`v`', $column);
        } else {
            // 反引号化最后一个列名片段 (table 前缀部分已反引号)
            $column = preg_replace_callback('/^([a-zA-Z0-9_`\s.\-]*?)([a-z_]+)$/i', function ($m) {
                return $m[1] . '`' . $m[2] . '`';
            }, $column);
            // 按表选择列名映射: 评论表 / 用户表 / 文章表
            $map = $this->isComments ? Db::$commentColumns
                : ($this->isUsers ? Db::$userColumns : Db::$columnMap);
            foreach ($map as $from => $to) {
                $column = str_replace('`' . $from . '`', '`' . $to . '`', $column);
            }
        }
        $this->orders[] = $column . ' ' . $dir;
        return $this;
    }

    public function group($column)
    {
        $this->groups[] = $column;
        return $this;
    }

    public function having($condition)
    {
        $this->having = (string) $condition;
        return $this;
    }

    public function join($table, $condition, $type = 'INNER')
    {
        $tableSql = $this->tableSql($table);
        $condition = preg_replace('/table\.([a-z_]+)\./', '`$1`.', (string) $condition);
        $condition = preg_replace('/(^|[\s(,])`?([a-z_]+)`?(?=\s*[=<>!]|$)/', '$1`$2`', $condition);
        if (!$this->isOptions) {
            foreach (Db::$columnMap as $from => $to) {
                $condition = str_replace('`' . $from . '`', '`' . $to . '`', $condition);
            }
        }
        $this->joins[] = strtoupper($type) . ' JOIN ' . $tableSql . ' ON ' . $condition;
        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $this->limit = intval($limit);
        if ($offset !== null) {
            $this->offset = intval($offset);
        }
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = intval($offset);
        return $this;
    }

    public function page($page, $pageSize)
    {
        $page = max(1, intval($page));
        $this->offset = ($page - 1) * intval($pageSize);
        $this->limit = intval($pageSize);
        return $this;
    }

    public function rows($data)
    {
        if ($this->isOptions) {
            $map = ['name' => 'k', 'value' => 'v', 'user' => 'id'];
            foreach ($map as $from => $to) {
                if (isset($data[$from]) && !isset($data[$to])) {
                    $data[$to] = $data[$from];
                    unset($data[$from]);
                }
            }
        } elseif ($this->isFields) {
            // table.fields: 保留原始 Typecho 键 (str_value/name/type/cid),
            // 由 buildSet()/insert 分支按 fields 语义重写 (避免 cid→art_id 提前改名)
        } elseif (!$this->type || $this->type === 'update' || $this->type === 'insert') {
            foreach (Db::$columnMap as $from => $to) {
                if (isset($data[$from]) && !isset($data[$to])) {
                    $data[$to] = $data[$from];
                    unset($data[$from]);
                }
            }
        }
        $this->rows = $data;
        return $this;
    }

    /**
     * 生成 SQL
     */
    public function __toString()
    {
        if (!empty($this->rawQuery)) {
            return (string) $this->rawQuery;
        }

        // table.fields 查询重写:
        // Typecho 的 fields 表存储自定义字段, 本项目将 views 等直接存入 lylme_article.art_views
        // 将 SELECT str_value ... WHERE name='views' AND cid=? 重写为 SELECT art_views ... WHERE art_id=?
        if ($this->isFields && $this->type === 'select') {
            return $this->buildFieldsQuery();
        }

        $sql = '';
        if ($this->type === 'select') {
            $sql = 'SELECT ' . $this->buildColumns() . ' FROM ' . $this->from;
        } elseif ($this->type === 'update') {
            $sql = 'UPDATE ' . $this->from . ' SET ' . $this->buildSet();
        } elseif ($this->type === 'insert') {
            // table.fields INSERT 语义为"为已有文章设置字段值", lylme 中 views 等是文章表列,
            // 故重写为 UPDATE (而非向 lylme_article 插入新行, 否则会因 name/type/str_value 列不存在而失败)
            if ($this->isFields) {
                $targetCol = $this->fieldsTargetColumn();
                $artId = isset($this->rows['cid']) ? intval($this->rows['cid']) : 0;
                $value = isset($this->rows['str_value']) ? $this->rows['str_value']
                    : (isset($this->rows['value']) ? $this->rows['value'] : 0);
                return 'UPDATE `lylme_article` SET `' . $targetCol . '` = \''
                    . $this->db->escape($value) . '\' WHERE `art_id` = ' . $artId;
            }
            $keys = array_keys($this->rows);
            $vals = array_map([$this->db, 'escape'], array_values($this->rows));
            $sql = 'INSERT INTO ' . $this->from . ' (`' . implode('`,`', $keys) . '`) VALUES (\'' . implode("','", $vals) . '\')';
        } elseif ($this->type === 'delete') {
            $sql = 'DELETE FROM ' . $this->from;
        }

        foreach ($this->joins as $join) {
            $sql .= ' ' . $join;
        }
        if (!empty($this->wheres)) {
            $where = '';
            foreach ($this->wheres as $i => $w) {
                $where .= ($i > 0 ? ' ' . $w[0] . ' ' : '') . $w[1];
            }
            $sql .= ' WHERE ' . $where;
        }
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(',', $this->groups);
        }
        if ($this->having !== '') {
            $sql .= ' HAVING ' . $this->having;
        }
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(',', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        return $sql;
    }

    protected function buildColumns()
    {
        if (is_array($this->columns)) {
            $cols = [];
            foreach ($this->columns as $key => $val) {
                if (is_numeric($key)) {
                    $cols[] = $this->mapSelectColumn($val);
                } else {
                    // 别名: 'COUNT(cid)' => 'num'
                    $cols[] = $this->mapSelectColumn($val) . ' AS `' . str_replace('`', '', $key) . '`';
                }
            }
            return implode(', ', $cols);
        }
        return $this->columns === '*' ? '*' : $this->mapSelectColumn($this->columns);
    }

    /**
     * SELECT 列名映射: 根据当前表 (评论/用户/文章) 选择正确的列名映射
     */
    protected function mapSelectColumn($column)
    {
        $column = (string) $column;
        // COUNT(cid) 等聚合表达式: 仅替换内部列名
        if ($this->isComments || $this->isUsers) {
            $map = $this->isComments ? Db::$commentColumns : Db::$userColumns;
            $bare = preg_replace('/[^a-zA-Z_]/', '', $column);
            if (isset($map[$bare])) {
                $mapped = $map[$bare];
                // 保留聚合函数与别名包裹 (COUNT(cid) → COUNT(`com_id`))
                $quoted = '`' . $mapped . '`';
                if (strpos($column, $bare) !== false && !ctype_alnum(str_replace($bare, '', $column))) {
                    return str_replace($bare, $quoted, $column);
                }
                return $quoted;
            }
            // 未命中映射但含函数包裹 (COUNT(coid)): 递归替换内部列
            return preg_replace_callback('/([a-zA-Z_][a-zA-Z0-9_]*)/', function ($m) use ($map) {
                return isset($map[$m[1]]) ? '`' . $map[$m[1]] . '`' : $m[1];
            }, $column);
        }
        return Db::column($column, $this->isOptions);
    }

    /**
     * 构建 table.fields 重写后的 SQL
     * Typecho fields 表查询 → lylme_article 列直接查询
     * 例: SELECT str_value FROM table.fields WHERE cid=4 AND name='views'
     *    → SELECT `art_views` FROM `lylme_article` WHERE `art_id` = '4'
     */
    protected function buildFieldsQuery()
    {
        // 1. 从 WHERE 条件中提取字段名 (name = 'views')
        $fieldName = '';
        $remainingWheres = [];
        foreach ($this->wheres as $w) {
            $glue = $w[0];
            $cond = $w[1];
            if (preg_match('/`?name`?\s*=\s*[\'"](\w+)[\'"]/', $cond, $m)) {
                $fieldName = $m[1];
            } else {
                // 将 cid 映射为 art_id (select 路径可能已提前映射, 未命中亦无妨)
                $cond = preg_replace('/`?cid`?/', '`art_id`', $cond);
                $remainingWheres[] = [$glue, $cond];
            }
        }

        // 2. 确定 SELECT 列: str_value → 对应的 art_* 列
        $selectCol = '`art_views`'; // 默认
        if ($fieldName !== '' && isset(Db::$fieldColumns[$fieldName])) {
            $selectCol = '`' . Db::$fieldColumns[$fieldName] . '`';
        }

        // 处理 SELECT 列中的 str_value / cid 映射
        $cols = $this->columns;
        if (is_array($cols)) {
            $mappedCols = [];
            foreach ($cols as $key => $val) {
                if ($val === 'str_value') {
                    $val = $selectCol;
                } elseif ($val === 'cid') {
                    $val = '`art_id`';
                }
                if (is_numeric($key)) {
                    $mappedCols[] = $val;
                } else {
                    $mappedCols[$key] = $val;
                }
            }
            $cols = $mappedCols;
        } else {
            if ($cols === 'str_value' || $cols === '`str_value`') {
                $cols = $selectCol;
            }
        }

        // 3. 构建 SQL
        $sql = 'SELECT ' . (is_array($cols) ? implode(', ', array_map(function($v) { return strpos($v, '`') === 0 ? $v : '`' . $v . '`'; }, (array) $cols)) : $cols);
        $sql .= ' FROM `lylme_article`';
        if (!empty($remainingWheres)) {
            $where = '';
            foreach ($remainingWheres as $i => $w) {
                $where .= ($i > 0 ? ' ' . $w[0] . ' ' : '') . $w[1];
            }
            $sql .= ' WHERE ' . $where;
        }
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(',', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        return $sql;
    }

    /**
     * 将 fields 查询结果映射回 Typecho 语义
     * art_views → str_value, art_id → cid
     */
    protected function mapFieldsRow($row)
    {
        if (is_array($row)) {
            // 反向映射: art_* → Typecho fields 表列名
            foreach (Db::$fieldColumns as $fieldName => $artCol) {
                if (isset($row[$artCol])) {
                    $row['str_value'] = $row[$artCol];
                }
            }
            if (isset($row['art_id'])) {
                $row['cid'] = $row['art_id'];
            }
            // 同时保留原始列名, 供 mapContentsRow 处理
            $row = Db::mapContentsRow($row);
        }
        return $row;
    }

    protected function buildSet()
    {
        $parts = [];
        // table.fields UPDATE: str_value → art_* 列, 丢弃 name/type/cid 等 fields 表专有列
        if ($this->isFields) {
            $targetCol = $this->fieldsTargetColumn();
            foreach ($this->rows as $key => $val) {
                if ($key === 'str_value' || $key === 'value') {
                    $parts[] = '`' . $targetCol . '` = \'' . $this->db->escape($val) . '\'';
                } elseif ($key === 'cid') {
                    $parts[] = '`art_id` = \'' . $this->db->escape($val) . '\'';
                }
                // name/type 在 lylme_article 无对应列, 直接丢弃
            }
            return $parts ? implode(', ', $parts) : '`art_id` = `art_id`';
        }
        foreach ($this->rows as $key => $val) {
            $col = Db::column($key, $this->isOptions);
            $parts[] = $col . ' = \'' . $this->db->escape($val) . '\'';
        }
        return implode(', ', $parts);
    }

    /**
     * 从 WHERE 条件中提取 fields 字段名, 返回对应的 lylme_article 列名
     * 例: name='views' → art_views; 未命中时默认 art_views
     */
    protected function fieldsTargetColumn()
    {
        foreach ($this->wheres as $w) {
            if (preg_match('/`?name`?\s*=\s*[\'"](\w+)[\'"]/', $w[1], $m)) {
                if (isset(Db::$fieldColumns[$m[1]])) {
                    return Db::$fieldColumns[$m[1]];
                }
            }
        }
        // rows 中可能直接带 name 列
        foreach ($this->rows as $key => $val) {
            if ($key === 'name' && isset(Db::$fieldColumns[$val])) {
                return Db::$fieldColumns[$val];
            }
        }
        return 'art_views';
    }

    /**
     * 返回第一行 (关联数组)
     * 表或列不存在时返回空数组, 避免整页白屏
     */
    public function fetchRow()
    {
        $sql = $this->__toString();
        try {
            $row = $this->db->get_row($sql);
        } catch (\Exception $e) {
            return [];
        }
        if ($this->isFields && $row) {
            $row = $this->mapFieldsRow($row);
        } elseif ($this->isOptions && $row) {
            $row = Db::mapOptionsRow($row);
        } elseif ($this->isComments && $row) {
            $row = Db::mapCommentsRow($row);
        } elseif ($this->isUsers && $row) {
            $row = Db::mapUsersRow($row);
        } elseif (!$this->isOptions && !$this->isFields && $row) {
            $row = Db::mapContentsRow($row);
        }
        return $row ?: [];
    }

    /**
     * 返回所有行
     */
    public function fetchAll()
    {
        $sql = $this->__toString();
        try {
            $result = $this->db->query($sql);
        } catch (\Exception $e) {
            return [];
        }
        $rows = [];
        if ($result) {
            while ($row = $this->db->fetch($result)) {
                if ($this->isFields) {
                    $row = $this->mapFieldsRow($row);
                } elseif ($this->isOptions) {
                    $row = Db::mapOptionsRow($row);
                } elseif ($this->isComments) {
                    $row = Db::mapCommentsRow($row);
                } elseif ($this->isUsers) {
                    $row = Db::mapUsersRow($row);
                } else {
                    $row = Db::mapContentsRow($row);
                }
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * 返回第一行对象
     */
    public function fetchObject()
    {
        $row = $this->fetchRow();
        return (object) $row;
    }

    /**
     * 执行查询, 返回结果集 (失败返回 false)
     */
    public function query()
    {
        $sql = $this->__toString();
        try {
            return $this->db->query($sql);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 执行语句 (insert/update/delete), 返回受影响行或自增ID
     */
    public function exec()
    {
        $sql = $this->__toString();
        try {
            if ($this->type === 'insert') {
                return $this->db->insert($sql);
            }
            return $this->db->query($sql);
        } catch (\Exception $e) {
            return false;
        }
    }

    /** 获取绑定参数 */
    public function getParams()
    {
        return [];
    }

    /** 获取属性 */
    public function getAttribute($key)
    {
        return null;
    }

    /** 清除属性 */
    public function cleanAttribute($key)
    {
        return $this;
    }

    /** 设置默认值 */
    public static function setDefault($prefix, $adapter)
    {
        // 兼容层: 忽略
    }

    /** 值引号 */
    public function quoteValue($value)
    {
        return "'" . $this->db->escape($value) . "'";
    }

    /** 值引号(批量): 数组 → 'a','b' 形式, 用于 IN 展开 */
    public function quoteValues(array $values)
    {
        return implode(', ', array_map(function ($value) {
            return $this->quoteValue($value);
        }, $values));
    }

    /** 创建表达式对象 */
    public static function expression($value)
    {
        return new DbExpression($value);
    }

    /** 预编译: 参数已在 where() 就地绑定, 直接返回当前 SQL */
    public function prepare()
    {
        return $this->__toString();
    }
}

/** 数据库表达式 (不被转义) */
class DbExpression
{
    protected $value;
    public function __construct($value) { $this->value = (string) $value; }
    public function __toString() { return $this->value; }
}

/**
 * Typecho 嵌套命名空间别名类
 * Typecho 原始源码将 Db 相关类放在嵌套命名空间 Typecho\Db\ 下
 * (DbQuery/Adapter/Exception/SQLException), 而兼容层为兼容 PHP 8.2 将其实体
 * 置于扁平命名空间 Typecho\ (DbQuery/DbAdapter/DbExpression) + Typecho\Exception。
 * 此处补齐 Typecho\Db\* 别名, 供写 new \Typecho\Db\Query() / catch (\Typecho\Db\Exception $e) 的主题。
 */
namespace Typecho\Db;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit('Access Denied');
}

/** @see \Typecho\DbQuery */
class Query extends \Typecho\DbQuery {}

/** @see \Typecho\DbAdapter */
class Adapter extends \Typecho\DbAdapter {}

/** @see \Typecho\Exception */
class Exception extends \Typecho\Exception {}

/** @see \Typecho\Exception */
class SQLException extends \Typecho\Exception {}

<?php
/**
 * Typecho 兼容层 - Typecho 命名空间类
 * 提供 \Typecho\Widget 及 Helper\Form\Element 等主题常用类
 * 使用花括号命名空间语法避免 PHP 8.2 多 namespace 注册问题
 */

namespace Typecho {

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit('Access Denied');
}

use Compat\App;

/**
 * Typecho Widget 基类 (含静态 widget() 工厂)
 */
abstract class Widget
{
    /** @var array 当前行数据 */
    public $row = [];
    /** @var array 堆栈(所有行) */
    public $stack = [];
    /** @var int 行数 */
    public $length = 0;
    /** @var int 序列计数 */
    public $sequence = 0;
    /** @var \Compat\Options 全局配置 */
    public $options;
    /** @var \DB 数据库 */
    public $db;
    /** @var int 指针 */
    protected $pointer = -1;
    /** @var \stdClass 查询参数 */
    public $parameter;

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
        $this->execute();
    }

    public static function widget($widget, $parameter = null, $response = null, $options = null)
    {
        $map = [
            'Widget_Options' => '\\Compat\\Options',
            'Widget_User' => '\\Compat\\User',
            'Widget_Stat' => '\\Compat\\Stat',
            'Widget_Contents_Page_List' => '\\Compat\\Widgets\\PageRows',
            'Widget_Metas_Category_List' => '\\Compat\\Widgets\\CategoryList',
            'Widget_Metas_Category_Rows' => '\\Compat\\Widgets\\CategoryRows',
            'Widget_Metas_Tag_Cloud' => '\\Compat\\Widgets\\TagCloud',
            'Widget_Contents_Post_Recent' => '\\Compat\\Widgets\\RecentPosts',
            'Widget_Comments_Recent' => '\\Compat\\Widgets\\RecentComments',
            'Widget_Contents_Post_Date' => '\\Compat\\Widgets\\PostDate',
            'Widget_Contents_Page_Rows' => '\\Compat\\Widgets\\PageRows',
            'Widget_Archive' => '\\Compat\\Archive',
            'Widget_Comments_Archive' => '\\Compat\\CommentsWidget',
            'Widget_Users_Author' => '\\Compat\\Author',
            'Widget_Metas_Category_Related' => '\\Compat\\Widgets\\CategoryRows',
            'Widget_Metas_Tag_Related' => '\\Compat\\Widgets\\TagCloud',
            'Widget_Abstract_Contents' => '\\Compat\\Archive',
            'Widget_Abstract_Comments' => '\\Compat\\CommentsWidget',
            'Widget_Abstract_Metas' => '\\Compat\\Widgets\\CategoryRows',
        ];
        if (is_string($widget) && strpos($widget, '@') !== false) {
            $widget = substr($widget, 0, strpos($widget, '@'));
        }
        $parsed = [];
        if (is_string($parameter)) {
            parse_str($parameter, $parsed);
            $parameter = $parsed;
        }
        if ($widget === 'Widget_Options') {
            return App::$options;
        }
        if ($widget === 'Widget_Archive') {
            return new \Compat\Archive($parameter, $response);
        }
        if (isset($map[$widget]) && class_exists($map[$widget], false)) {
            $class = $map[$widget];
            return new $class($parameter);
        }
        $nsClass = '\\Widget\\' . str_replace('_', '\\', $widget);
        if (class_exists($nsClass, false)) {
            return new $nsClass($parameter);
        }
        if (class_exists('\\' . $widget, false)) {
            $class = '\\' . $widget;
            return new $class($parameter);
        }
        return new PlaceholderWidget($parameter);
    }

    protected function execute() {}
    public function alloc($parameter = null) { return new static($parameter); }
    public function to(&$var) { $var = $this; return $this; }
    public function push($value) { $this->stack[] = $value; $this->length++; return $value; }
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
    public function have() { return $this->length > 0 && $this->pointer + 1 < $this->length; }
    public function reset() { $this->pointer = -1; $this->sequence = 0; }
    public function __get($name) { return isset($this->row[$name]) ? $this->row[$name] : null; }
    public function __call($name, $args) { $value = isset($this->row[$name]) ? $this->row[$name] : ''; echo $value; return $this; }
    protected function param($name, $default = null) { return isset($this->parameter->$name) ? $this->parameter->$name : $default; }
    public function __set($name, $value) { $this->row[$name] = $value; }
    /** 魔术方法名大小写不敏感: __isSet 与 __isset 同义, 定义其一即可同时满足 isset() */
    public function __isSet($name) { return isset($this->row[$name]); }
    /** 遍历行解析模板占位符 {field} 并输出 */
    public function parse($format)
    {
        while ($this->next()) {
            $row = $this->row;
            $search = [];
            $replace = [];
            foreach ($row as $k => $v) {
                if (is_scalar($v)) {
                    $search[] = '{' . $k . '}';
                    $replace[] = (string) $v;
                }
            }
            echo str_replace($search, $replace, (string) $format);
        }
    }
    /** 加载并执行主题模板文件 */
    public function template($file)
    {
        $path = \Compat\App::$themeDir . $file;
        if (is_file($path)) {
            include $path;
        }
    }
    /** 批量压入多行 */
    public function pushAll($values)
    {
        foreach ($values as $v) {
            $this->push($v);
        }
        return $this;
    }
    /** 转数组: 无参返回全部堆栈, 传列名则取每行指定列 */
    public function toArray($columns = null)
    {
        if ($columns === null) {
            return $this->stack;
        }
        $result = [];
        foreach ($this->stack as $row) {
            $filtered = [];
            foreach ((array) $columns as $col) {
                $filtered[$col] = isset($row[$col]) ? $row[$col] : null;
            }
            $result[] = $filtered;
        }
        return $result;
    }
    /** 取多列值: 每行单列返回标量, 多列返回数组 */
    public function toColumn($columns)
    {
        $result = [];
        foreach ($this->stack as $row) {
            $vals = [];
            foreach ((array) $columns as $col) {
                $vals[] = isset($row[$col]) ? $row[$col] : null;
            }
            $result[] = count($vals) === 1 ? $vals[0] : $vals;
        }
        return $result;
    }
    /** 行奇偶交替输出 */
    public function alt($prev, $next) { echo $this->sequence % 2 ? $next : $prev; }
    /** 条件交替输出 */
    public function altBy($condition, $prev, $next) { echo $condition ? $next : $prev; }
    /** 插件钩子句柄 (空对象, 可安全承接链式调用) */
    public function pluginHandle($className = '') { return \Typecho\Widget\Helper\EmptyClass::getInstance(); }
    /** 事件绑定 (空实现) */
    public function on($event, $callback = null) { return $this; }
    /** 带别名分配 */
    public static function allocWithAlias($alias, $params = null) { return new static($params); }
    /** 销毁别名 (空实现) */
    public static function destroy($alias) {}
}

/** 未知 widget 占位对象 */
class PlaceholderWidget extends Widget
{
    public function __construct($parameter = null) { $this->parameter = new \stdClass(); $this->options = App::$options; $this->db = class_exists('\Typecho\Db') ? \Typecho\Db::get() : App::$db; }
    protected function execute() {}
    public function __get($name) {
        if (isset($this->row) && is_array($this->row) && array_key_exists($name, $this->row)) {
            return $this->row[$name];
        }
        return null;
    }
    public function __call($name, $args) { return null; }
}

/** Typecho 异常基类 */
class Exception extends \Exception
{
    public function __construct($message = '', $code = 0, \Exception $previous = null) { parent::__construct($message, $code, $previous); }
}

/** Typecho 通用工具类 */
class Common
{
    const VERSION = '1.3.0';

    public static function url($path, $base = null)
    {
        $path = (string) $path;
        if (preg_match('#^(https?:)?//#i', $path) || strpos($path, 'data:') === 0 || strpos($path, '#') === 0) { return $path; }
        if ($base === null) { return $path; }
        $base = (string) $base;
        while (preg_match('#(^|/)\.\./#', $path)) { $path = preg_replace('#(^|/)\.\./#', '/', $path, 1); }
        if ($path === '' || $path === '/') { return rtrim($base, '/') . '/'; }
        if (strpos($path, '/') === 0) { $parts = parse_url($base); $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : ''; $host = isset($parts['host']) ? $parts['host'] : ''; $port = isset($parts['port']) ? ':' . $parts['port'] : ''; return $scheme . $host . $port . $path; }
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
    public static function subStr($string, $start, $length = null, $suffix = '...') { $string = (string) $string; $result = mb_substr($string, $start, $length, 'UTF-8'); if ($suffix !== '' && mb_strlen($string, 'UTF-8') > ($start + $length)) { $result .= $suffix; } return $result; }
    public static function shuffleScriptVar($key, $data = null) { if (func_num_args() < 2) { return json_encode($key); } echo '<script type="text/javascript">' . PHP_EOL . 'var ' . $key . ' = ' . json_encode($data) . ';' . PHP_EOL . '</script>'; return ''; }
    public static function gravatarUrl($mail, $size = 40, $rating = 'X', $default = null, $isSecure = false) { $hash = md5(strtolower(trim((string) $mail))); $scheme = $isSecure ? 'https' : 'http'; $url = $scheme . '://secure.gravatar.com/avatar/' . $hash . '?s=' . intval($size) . '&r=' . $rating; if ($default) { $url .= '&d=' . urlencode($default); } return $url; }
    public static function markdown($text) { if (class_exists('\Compat\Markdown')) { return \Compat\Markdown::convert($text); } return $text; }
    public static function stripTags($html, $allowed = '') { return strip_tags((string) $html, $allowed); }
    public static function fixHtml($string) { return preg_replace('/\s+/', ' ', trim((string) $string)); }
    public static function slugName($name, $suffix = '') { $name = preg_replace('/[^a-zA-Z0-9\-_]/', '-', strtolower((string) $name)); $name = preg_replace('/-+/', '-', trim($name, '-')); return $name . $suffix; }
    public static function safeUrl($url) { $url = (string) $url; if (preg_match('/^(https?:)?\/\//i', $url)) { return $url; } return htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); }
    public static function removeXSS($val) { $val = (string) $val; $val = preg_replace('/<(script|iframe|object|embed|applet)[^>]*>.*<\/\1>/is', '', $val); $val = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $val); return $val; }
    public static function randString($length = 16) { $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; $str = ''; for ($i = 0; $i < $length; $i++) { $str .= $chars[mt_rand(0, strlen($chars) - 1)]; } return $str; }
    public static function hash($string) { return md5((string) $string); }
    public static function strLen($str) { return mb_strlen((string) $str, 'UTF-8'); }
    public static function strBy($str, $default = '') { $str = (string) $str; return $str === '' ? $default : $str; }
    public static function isAvailableClass($className) { return class_exists($className); }
    public static function nativeClassName($className) { $pos = strrpos(str_replace('\\', '/', (string) $className), '/'); return $pos !== false ? substr($className, $pos + 1) : $className; }
    public static function arrayFlatten($array) { $result = []; array_walk_recursive($array, function ($v) use (&$result) { $result[] = $v; }); return $result; }
    public static function splitByCount($count, $sizes) { $result = []; $offset = 0; foreach ($sizes as $size) { if ($offset >= $count) { break; } $result[] = min($size, $count - $offset); $offset += $size; } return $result; }
    public static function mimeContentType($fileName) { $ext = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION)); $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','svg'=>'image/svg+xml','webp'=>'image/webp','pdf'=>'application/pdf','zip'=>'application/zip','mp3'=>'audio/mpeg','mp4'=>'video/mp4','css'=>'text/css','js'=>'application/javascript','json'=>'application/json','xml'=>'text/xml','html'=>'text/html','txt'=>'text/plain']; return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream'; }
    public static function idnToUtf8($idn) { if (function_exists('idn_to_utf8')) { return idn_to_utf8((string) $idn); } return $idn; }
    public static function parseDate($format) { return new Date($format); }
    public static function rid() { return md5(uniqid(mt_rand(), true)); }
    public static function init() {}
    public static function error($message) { if (defined('DEBUG') && DEBUG) { error_log('[compat] Typecho\Common::error: ' . (string) $message); } return false; }
    public static function filterSearchQuery($query) { $query = (string) $query; return htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); }
    public static function buildUrl($type, $params = [], $prefix = null) { return \Typecho\Router::url($type, $params, $prefix); }
    public static function hashValidate($data, $hash) { return is_string($hash) && hash_equals($hash, self::hash($data)); }
    public static function timeToken($time = 0) { $time = intval($time ?: time()); return substr(md5(self::VERSION . $time), 0, 16); }
    public static function timeTokenValidate($token, $time = 0) { return is_string($token) && hash_equals($token, self::timeToken($time)); }
    public static function buildBackupBuffer($buffer) { return base64_encode(gzcompress((string) $buffer, 6)); }
    public static function extractBackupBuffer($buffer) { $decoded = @gzuncompress(base64_decode((string) $buffer)); return $decoded === false ? (string) $buffer : $decoded; }
    public static function checkSafeHost($host) { return is_string($host) && $host !== '' && (bool) preg_match('#^[a-zA-Z0-9._\-]+$#', $host); }
    public static function isAppEngine() { return false; }
    public static function mimeIconType($ext)
    {
        $map = ['jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image', 'svg' => 'image', 'bmp' => 'image', 'ico' => 'image',
            'mp3' => 'audio', 'wav' => 'audio', 'ogg' => 'audio', 'flac' => 'audio', 'aac' => 'audio',
            'mp4' => 'video', 'avi' => 'video', 'mkv' => 'video', 'mov' => 'video', 'wmv' => 'video', 'flv' => 'video', 'webm' => 'video',
            'pdf' => 'pdf', 'zip' => 'zip', 'rar' => 'zip', '7z' => 'zip', 'gz' => 'zip', 'tar' => 'zip', 'bz2' => 'zip',
            'doc' => 'word', 'docx' => 'word', 'xls' => 'excel', 'xlsx' => 'excel', 'csv' => 'excel', 'ppt' => 'ppt', 'pptx' => 'ppt',
            'txt' => 'text', 'md' => 'text', 'php' => 'code', 'js' => 'code', 'css' => 'code', 'html' => 'code', 'htm' => 'code', 'json' => 'code', 'xml' => 'code'];
        $ext = strtolower((string) $ext);
        return isset($map[$ext]) ? $map[$ext] : 'file';
    }
}

/** Typecho Cookie 类 */
class Cookie
{
    protected static $prefix = 'typecho_';
    protected static $path = '/';
    protected static $domain = null;
    protected static $secure = false;

    public static function setPrefix($prefix) { self::$prefix = $prefix; }
    public static function getPrefix() { return self::$prefix; }
    public static function setPath($path) { self::$path = $path; }
    public static function getPath() { return self::$path; }
    public static function setDomain($domain) { self::$domain = $domain; }
    public static function getDomain() { return self::$domain; }
    public static function getSecure() { return self::$secure; }
    public static function setOptions($options) { if (is_array($options)) { if (isset($options['path'])) { self::$path = $options['path']; } if (isset($options['domain'])) { self::$domain = $options['domain']; } if (isset($options['secure'])) { self::$secure = (bool) $options['secure']; } } }
    public static function set($name, $value, $expire = 0, $path = null, $domain = null, $secure = null)
    {
        if ($expire > 0) { $expire = time() + intval($expire); }
        if (headers_sent()) { return false; }
        $path = $path !== null ? $path : self::$path;
        $domain = $domain !== null ? $domain : self::$domain;
        $secure = $secure !== null ? $secure : self::$secure;
        // PHP 8.1+: setcookie() 的 $domain 参数不接受 null, 传 null 会报 deprecated。
        // Typecho 原生 Cookie::$domain 默认就是 null, 故在此统一转为空串。
        return setcookie(self::$prefix . $name, (string) $value, $expire, $path, (string) $domain, $secure);
    }
    public static function get($name, $default = null) { $key = self::$prefix . $name; return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default; }
    public static function delete($name, $path = null, $domain = null) { $path = $path !== null ? $path : self::$path; $domain = $domain !== null ? $domain : self::$domain; if (!headers_sent()) { setcookie(self::$prefix . $name, '', time() - 3600, $path, (string) $domain); } unset($_COOKIE[self::$prefix . $name]); }
    public static function has($name) { return isset($_COOKIE[self::$prefix . $name]); }
}

/** Typecho 路由工具 */
class Router
{
    protected static $routes = [];
    protected static $current = null;

    public static function setRoutes($routes) { self::$routes = $routes; }
    public static function get($name) { return isset(self::$routes[$name]) ? self::$routes[$name] : null; }
    public static function getCurrent() { return self::$current; }
    public static function match($path, $routes = null) { $routes = $routes ?: self::$routes; foreach ($routes as $name => $route) { if (isset($route['url']) && preg_match($route['url'], $path)) { self::$current = $name; return $route; } } return false; }
    public static function dispatch($path = null) { if ($path === null) { $path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/'; } return self::match($path); }
    public static function url($type, $row = [], $indexBase = null)
    {
        if (is_array($row)) {
            if (!empty($row['permalink'])) { return $row['permalink']; }
            $slug = isset($row['slug']) ? $row['slug'] : (isset($row['art_alias']) ? $row['art_alias'] : '');
            if ($slug) { $base = rtrim($indexBase !== null ? (string) $indexBase : \Compat\App::$articleUrl, '/'); return $base . '/' . urlencode($slug) . '.html'; }
        }
        return \Compat\App::$articleUrl;
    }
}

/** Typecho 配置对象 */
class Config implements \Iterator, \ArrayAccess
{
    protected $data = [];
    protected $iteratorPosition = 0;
    protected $iteratorKeys = [];

    public function __construct($data = []) { if (is_array($data)) { $this->data = $data; } elseif ($data instanceof self) { $this->data = $data->toArray(); } }
    public static function factory($config = []) { if ($config instanceof self) { return $config; } return new self($config); }
    public function get($name, $default = null) { return isset($this->data[$name]) ? $this->data[$name] : $default; }
    public function set($name, $value) { $this->data[$name] = $value; return $this; }
    public function setDefault($key, $value) { if (!isset($this->data[$key])) { $this->data[$key] = $value; } return $this; }
    public function isEmpty() { return empty($this->data); }
    public function __get($name) { return isset($this->data[$name]) ? $this->data[$name] : null; }
    public function __set($name, $value) { $this->data[$name] = $value; }
    public function __isset($name) { return isset($this->data[$name]); }
    public function __unset($name) { unset($this->data[$name]); }
    public function __toString() { return json_encode($this->data); }
    public function __call($key, $args) { $value = isset($this->data[$key]) ? $this->data[$key] : ''; echo $value; return $this; }
    public function toArray() { return $this->data; }
    #[\ReturnTypeWillChange]
    public function rewind() { $this->iteratorKeys = array_keys($this->data); $this->iteratorPosition = 0; }
    #[\ReturnTypeWillChange]
    public function current() { $key = $this->iteratorKeys[$this->iteratorPosition]; return $this->data[$key]; }
    #[\ReturnTypeWillChange]
    public function key() { return $this->iteratorKeys[$this->iteratorPosition]; }
    #[\ReturnTypeWillChange]
    public function next() { $this->iteratorPosition++; }
    #[\ReturnTypeWillChange]
    public function valid() { return isset($this->iteratorKeys[$this->iteratorPosition]); }
    #[\ReturnTypeWillChange]
    public function offsetExists($offset) { return isset($this->data[$offset]); }
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) { return isset($this->data[$offset]) ? $this->data[$offset] : null; }
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) { $this->data[$offset] = $value; }
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) { unset($this->data[$offset]); }
}

/** Typecho 日期对象 */
class Date
{
    public static $timezoneOffset = 0;
    public static $serverTimezoneOffset = 0;
    public $timeStamp;
    public $year;
    public $month;
    public $day;
    public $hour;
    public $minute;
    public $second;

    public static function setTimezoneOffset($offset) { self::$timezoneOffset = intval($offset); }
    public static function gmtTime() { return time() + self::$serverTimezoneOffset; }
    public static function time() { return time(); }
    public function __construct($time = 0, $zone = null)
    {
        if (!is_numeric($time)) { $time = strtotime((string) $time); if ($time === false || $time < 0) { $time = time(); } }
        $this->timeStamp = (int) $time;
        $this->year = (int) date('Y', $this->timeStamp);
        $this->month = (int) date('n', $this->timeStamp);
        $this->day = (int) date('j', $this->timeStamp);
        $this->hour = (int) date('G', $this->timeStamp);
        $this->minute = (int) date('i', $this->timeStamp);
        $this->second = (int) date('s', $this->timeStamp);
    }
    public function format($format) { return date($format, $this->timeStamp); }
    public function word() { $diff = time() - $this->timeStamp; if ($diff < 60) { return '刚刚'; } if ($diff < 3600) { return floor($diff / 60) . ' 分钟前'; } if ($diff < 86400) { return floor($diff / 3600) . ' 小时前'; } if ($diff < 2592000) { return floor($diff / 86400) . ' 天前'; } return $this->format('Y-m-d'); }
    public function __toString() { return $this->format('Y-m-d H:i:s'); }
}

/**
 * Typecho 插件管理器 (兼容层真实实现)
 *
 * 兼容 Typecho 插件钩子机制:
 *   - 插件 activate() 中通过 Typecho_Plugin::factory('Widget_Archive')->header = 'X::cb' 注册钩子
 *     也支持 factory('Widget_Archive')->header('X::cb') 调用式注册
 *   - 渲染点 (Compat\Archive::header/footer) 调用 Typecho_Plugin::export('Widget_Archive','header',$this) 触发
 */
class Plugin
{
    /** @var array 钩子注册表: component => method => [callbacks] */
    protected static $handles = [];

    /** @var string 当前绑定的组件名 (如 Widget_Archive) */
    protected $component = '';

    public function __construct($component = '')
    {
        $this->component = (string)$component;
    }

    /**
     * 工厂方法: 返回绑定到指定组件的钩子代理
     */
    public static function factory($component = '')
    {
        return new self($component);
    }

    /**
     * 魔术赋值: factory('X')->header = 'cb' 注册钩子
     */
    public function __set($name, $value)
    {
        self::$handles[$this->component][$name][] = $value;
    }

    /**
     * 魔术读取: 返回已注册的某方法回调列表
     */
    public function __get($name)
    {
        return isset(self::$handles[$this->component][$name]) ? self::$handles[$this->component][$name] : null;
    }

    /**
     * 魔术调用: factory('X')->header('cb') 注册钩子并返回自身(支持链式)
     */
    public function __call($name, $args)
    {
        foreach ($args as $cb) {
            self::$handles[$this->component][$name][] = $cb;
        }
        return $this;
    }

    /**
     * 触发组件方法钩子, 收集所有处理器输出(echo + 返回值)并返回拼接结果
     * @param string $component 组件名 (如 Widget_Archive)
     * @param string $method    方法名 (如 header/footer)
     * @param mixed ...$args    透传给处理器回调的参数(通常传入 $this 即 Widget 实例)
     */
    public static function export($component, $method, ...$args)
    {
        $result = '';
        if (!empty(self::$handles[$component][$method])) {
            foreach (self::$handles[$component][$method] as $callback) {
                if (!is_callable($callback)) {
                    continue;
                }
                ob_start();
                $ret = call_user_func_array($callback, $args);
                $result .= ob_get_clean();
                if ($ret !== null) {
                    $result .= (string)$ret;
                }
            }
        }
        return $result;
    }

    /**
     * 仅检查是否有某组件方法钩子注册 (供渲染点判断是否需要触发, 可选)
     */
    public static function exists($component, $method = null)
    {
        if ($method === null) {
            return !empty(self::$handles[$component]);
        }
        return !empty(self::$handles[$component][$method]);
    }

    /** 初始化 (空实现, 兼容调用) */
    public static function init() {}

    /** 启用插件 (转发 Compat\PluginManager, 持久化于 lylme_article_config) */
    public static function activate($name) { return \Compat\PluginManager::activate($name); }

    /** 停用插件 */
    public static function deactivate($name) { return \Compat\PluginManager::deactivate($name); }

    /** 解析插件文件顶部元信息 */
    public static function parseInfo($file) { return \Compat\PluginManager::parseInfo($file); }

    /** 插件门户 (空实现, 管理后台入口) */
    public static function portal() {}

    /** 版本依赖检查 (兼容层接受任意版本) */
    public static function checkDependence($version = '') { return true; }

    /** 旧式触发接口: 等价于 export() */
    public static function trigger($component, $method = null, ...$args)
    {
        return self::export($component, $method, ...$args);
    }

    /** 旧式调用接口: 等价于 export() */
    public static function call($component, $method = null, $args = [])
    {
        return self::export($component, $method, ...(is_array($args) ? $args : [$args]));
    }

    /** 回调过滤 (透传, 保持链式兼容) */
    public static function filter($value) { return $value; }
}

/** Typecho 请求对象 */
class Request
{
    protected static $instance;
    protected static $type = 'index';
    protected $params = [];

    protected function __construct() { $this->params = array_merge($_POST, $_GET); if (isset($this->params['id']) && !isset($this->params['cid'])) { $this->params['cid'] = $this->params['id']; } }
    public static function getInstance() { if (!(self::$instance instanceof self)) { self::$instance = new self(); } return self::$instance; }
    public static function setType($type) { self::$type = (string) $type; }
    public static function getType() { return self::$type; }
    public function get($key, $default = null) { return isset($this->params[$key]) ? $this->params[$key] : $default; }
    public function getArray() { return $this->params; }
    public function from($key) { return $this; }
    public function filter($type = null) { return $this; }
    public function getRequestUrl() { return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/'; }
    public function getRequestUri() { return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/'; }
    public function getPathInfo() { return isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ''; }
    public function getContentType() { return isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : ''; }
    public function getServer($key = null) { if ($key === null) { return $_SERVER; } return isset($_SERVER[$key]) ? $_SERVER[$key] : null; }
    public function getHeader($key) { $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key)); return isset($_SERVER[$key]) ? $_SERVER[$key] : null; }
    public function getAgent() { return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''; }
    public function getUrlPrefix() { $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'; return $scheme . '://' . $host; }
    public function isPost() { return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST'; }
    public function isGet() { return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'GET'; }
    public function isPut() { return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'PUT'; }
    public function isSecure() { return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; }
    public function isCli() { return php_sapi_name() === 'cli'; }
    public function isAjax() { return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'; }
    public function isJson() { return strpos($this->getContentType(), 'application/json') !== false; }
    public function getIp() { return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''; }
    public function getReferer() { return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''; }
    public function is($type)
    {
        $current = self::$type;
        $isSingle = isset($_GET['id']) || isset($_GET['slug']);
        switch ($type) {
            case 'post': case 'page': case 'single': return $current === 'post' || $isSingle;
            case 'index': case 'home': case 'front': return $current === 'index' && !$isSingle;
            case 'archive': return in_array($current, ['category', 'search']) || (!$isSingle && (isset($_GET['cat']) || isset($_GET['month']) || isset($_GET['s']) || isset($_GET['page'])));
            case 'category': return $current === 'category' || isset($_GET['cat']);
            case 'search': return $current === 'search' || isset($_GET['s']);
            default: return $current === $type;
        }
    }

    /** @var array 沙箱/代理数据栈 */
    protected $sandboxStack = [];

    /** 进入沙箱: 以指定数据叠加当前请求参数 (beginSandbox/endSandbox 成对使用) */
    public function beginSandbox(array $sandbox = [])
    {
        $this->sandboxStack[] = $this->params;
        $this->params = array_merge($this->params, $sandbox);
        return $this;
    }

    /** 退出沙箱: 恢复进入前的参数 */
    public function endSandbox()
    {
        if ($this->sandboxStack) {
            $this->params = array_pop($this->sandboxStack);
        }
        return $this;
    }

    /** 代理请求参数 (proxy/endProxy 成对使用) */
    public function proxy($params = null)
    {
        $this->sandboxStack[] = $this->params;
        if ($params === null) {
            $this->params = array_merge($_POST, $_GET);
        } elseif (is_array($params)) {
            $this->params = $params;
        }
        return $this;
    }

    /** 结束代理, 恢复原参数 */
    public function endProxy()
    {
        return $this->endSandbox();
    }

    /** 站点根地址 (协议://主机) */
    public function getRequestRoot()
    {
        return $this->getUrlPrefix();
    }

    /** 基于当前请求拼接绝对 URI */
    public function makeUriByRequest($requestUri = null)
    {
        if ($requestUri === null) {
            $requestUri = $this->getRequestUri();
        }
        return $this->getRequestRoot() . (string) $requestUri;
    }
}

/** Typecho 校验类 */
class Validate
{
    protected $rules = [];
    protected $messages = [];
    protected $break = true;

    public function addRule($name, $rule, $message, $break = null) { $this->rules[$name][] = ['rule' => $rule, 'message' => $message]; if ($break !== null) { $this->break = (bool) $break; } return $this; }
    public function setBreak($break) { $this->break = (bool) $break; return $this; }
    public function run($data) { $errors = []; foreach ($this->rules as $field => $rules) { $value = isset($data[$field]) ? $data[$field] : ''; foreach ($rules as $rule) { $method = $rule['rule']; $valid = true; if (method_exists($this, '_check' . ucfirst($method))) { $valid = call_user_func([$this, '_check' . ucfirst($method)], $value); } if (!$valid) { $errors[$field] = $rule['message']; if ($this->break) { break; } } } } return $errors; }
    public function confirm($field1, $field2, $message) { $this->rules[$field1][] = ['rule' => 'confirm', 'message' => $message, 'field2' => $field2]; return $this; }
    public static function email($value) { return (bool) filter_var($value, FILTER_VALIDATE_EMAIL); }
    public static function url($value) { return (bool) filter_var($value, FILTER_VALIDATE_URL); }
    public static function maxLength($value, $length) { return mb_strlen((string) $value, 'UTF-8') <= intval($length); }
    public static function minLength($value, $length) { return mb_strlen((string) $value, 'UTF-8') >= intval($length); }
    public static function alpha($value) { return (bool) preg_match('/^[a-zA-Z]+$/', (string) $value); }
    public static function alphaNumeric($value) { return (bool) preg_match('/^[a-zA-Z0-9]+$/', (string) $value); }
    public static function regexp($value, $pattern) { return (bool) preg_match($pattern, (string) $value); }
    public static function required($value) { return $value !== '' && $value !== null; }
    protected function _checkRequired($value) { return self::required($value); }
    protected function _checkEmail($value) { return self::email($value); }
    protected function _checkUrl($value) { return self::url($value); }
    protected function _checkAlpha($value) { return self::alpha($value); }
    protected function _checkAlphaNumeric($value) { return self::alphaNumeric($value); }
}

/** Typecho 响应类 */
class Response
{
    protected static $instance;
    protected $status = 200;
    protected $headers = [];
    protected $charset = 'UTF-8';
    protected $contentType = 'text/html';
    protected $autoSendHeaders = true;
    protected $responders = [];
    protected $sandboxBodies = [];

    public static function getInstance() { if (!(self::$instance instanceof self)) { self::$instance = new self(); } return self::$instance; }
    public function setStatus($status) { $this->status = intval($status); return $this; }
    public function setHeader($key, $value) { $this->headers[(string) $key] = (string) $value; return $this; }
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = null) { Cookie::set($name, $value, $expire, $path, $domain); return $this; }
    public function setContentType($type) { $this->contentType = (string) $type; return $this; }
    public function getCharset() { return $this->charset; }
    public function setCharset($charset) { $this->charset = (string) $charset; return $this; }
    /** 是否在 respond() 时自动发送头部 */
    public function enableAutoSendHeaders($enabled = true) { $this->autoSendHeaders = (bool) $enabled; return $this; }
    /** 发送已缓存的状态/头部 (受 headers_sent 保护) */
    public function sendHeaders()
    {
        if (!headers_sent()) {
            header('Content-Type: ' . $this->contentType . '; charset=' . $this->charset);
            foreach ($this->headers as $key => $value) {
                header($key . ': ' . $value);
            }
        }
    }
    /** 清空已设置的状态/头部/响应器, 恢复默认 */
    public function clean()
    {
        $this->status = 200;
        $this->headers = [];
        $this->contentType = 'text/html';
        $this->responders = [];
        return $this;
    }
    /** 注册输出响应器 (respond() 时逐个回调, 形如 function (Response $r) {}) */
    public function addResponder($responder) { $this->responders[] = $responder; return $this; }
    /** 进入输出沙箱: 后续 echo 内容进入缓冲区 */
    public function beginSandbox() { ob_start(); return $this; }
    /** 退出输出沙箱: 收集缓冲内容存入 sandboxBodies */
    public function endSandbox() { $this->sandboxBodies[] = ob_get_clean(); return $this; }
    /** 取沙箱收集的缓冲内容 */
    public function getSandboxBodies() { return $this->sandboxBodies; }
    public function respond($data = null)
    {
        foreach ($this->responders as $responder) {
            if (is_callable($responder)) {
                call_user_func($responder, $this);
            }
        }
        if ($this->autoSendHeaders) {
            $this->sendHeaders();
        }
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                echo json_encode($data);
            } else {
                echo $data;
            }
        }
    }
}

/** Typecho Feed 聚合类 (按 RSS1/RSS2/Atom 渲染) */
class Feed
{
    const RSS1 = 'RSS 1.0';
    const RSS2 = 'RSS 2.0';
    const ATOM1 = 'Atom 1.0';
    const ATOM03 = 'Atom 0.3';
    protected $type = self::RSS2;
    protected $title = '';
    protected $subTitle = '';
    protected $feedUrl = '';
    protected $baseUrl = '';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $items = [];

    public function __construct($type = self::RSS2) { $this->type = $type; }
    public function getType() { return $this->type; }
    public function setTitle($title) { $this->title = (string) $title; return $this; }
    public function setSubTitle($subTitle) { $this->subTitle = (string) $subTitle; return $this; }
    public function setFeedUrl($url) { $this->feedUrl = (string) $url; return $this; }
    public function getFeedUrl() { return $this->feedUrl; }
    public function setBaseUrl($url) { $this->baseUrl = (string) $url; return $this; }
    public function dateFormat($format) { $this->dateFormat = (string) $format; return $this; }
    public function addItem($item) { $this->items[] = $item; return $this; }
    public function getItems() { return $this->items; }
    /** XML 转义 */
    protected function esc($str) { return htmlspecialchars((string) $str, ENT_QUOTES | ENT_XML1, 'UTF-8'); }
    protected function itemLink($item) { return isset($item['link']) ? $item['link'] : (isset($item['permalink']) ? $item['permalink'] : $this->baseUrl); }
    protected function itemDate($item)
    {
        if (empty($item['date'])) {
            return 0;
        }
        return is_numeric($item['date']) ? intval($item['date']) : strtotime((string) $item['date']);
    }
    protected function itemDesc($item)
    {
        $content = isset($item['content']) ? $item['content']
            : (isset($item['excerpt']) ? $item['excerpt']
            : (isset($item['description']) ? $item['description'] : ''));
        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $content)));
    }
    public function __toString()
    {
        $title = $this->esc($this->title);
        $link = $this->esc($this->feedUrl !== '' ? $this->feedUrl : $this->baseUrl);
        $desc = $this->esc($this->subTitle);

        if ($this->type === self::RSS1) {
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n"
                . '<channel rdf:about="' . $link . '">' . "\n"
                . '<title>' . $title . '</title>' . "\n"
                . '<link>' . $link . '</link>' . "\n"
                . '<description>' . $desc . '</description>' . "\n"
                . '<items><rdf:Seq>';
            $itemXml = '';
            foreach ($this->items as $item) {
                $itemLink = $this->esc($this->itemLink($item));
                $out .= '<rdf:li resource="' . $itemLink . '"/>';
                $itemXml .= '<item rdf:about="' . $itemLink . '">' . "\n"
                    . '<title>' . $this->esc(isset($item['title']) ? $item['title'] : '') . '</title>' . "\n"
                    . '<link>' . $itemLink . '</link>' . "\n"
                    . '<description>' . $this->esc($this->itemDesc($item)) . '</description>' . "\n";
                $date = $this->itemDate($item);
                if ($date) {
                    $itemXml .= '<dc:date>' . date('c', $date) . '</dc:date>' . "\n";
                }
                $itemXml .= '</item>' . "\n";
            }
            return $out . '</rdf:Seq></items>' . "\n" . '</channel>' . "\n" . $itemXml . '</rdf:RDF>';
        }

        if ($this->type === self::ATOM1 || $this->type === self::ATOM03) {
            $ns = $this->type === self::ATOM1
                ? '<feed xmlns="http://www.w3.org/2005/Atom">'
                : '<feed xmlns="http://purl.org/atom/ns#" version="0.3">';
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $ns . "\n"
                . '<title>' . $title . '</title>' . "\n"
                . '<link href="' . $link . '"/>' . "\n"
                . '<id>' . $link . '</id>' . "\n"
                . '<subtitle>' . $desc . '</subtitle>' . "\n";
            foreach ($this->items as $item) {
                $itemLink = $this->esc($this->itemLink($item));
                $itemDate = $this->itemDate($item);
                $out .= '<entry>' . "\n"
                    . '<title>' . $this->esc(isset($item['title']) ? $item['title'] : '') . '</title>' . "\n"
                    . '<link href="' . $itemLink . '"/>' . "\n"
                    . '<id>' . $itemLink . '</id>' . "\n"
                    . ($itemDate ? '<updated>' . date('c', $itemDate) . '</updated>' . "\n" : '')
                    . '<summary>' . $this->esc($this->itemDesc($item)) . '</summary>' . "\n"
                    . '</entry>' . "\n";
            }
            return $out . '</feed>';
        }

        // 默认 RSS 2.0
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0"><channel>' . "\n"
            . '<title>' . $title . '</title>' . "\n"
            . '<link>' . $link . '</link>' . "\n"
            . '<description>' . $desc . '</description>' . "\n";
        foreach ($this->items as $item) {
            $itemLink = $this->esc($this->itemLink($item));
            $itemDate = $this->itemDate($item);
            $out .= '<item>' . "\n"
                . '<title>' . $this->esc(isset($item['title']) ? $item['title'] : '') . '</title>' . "\n"
                . '<link>' . $itemLink . '</link>' . "\n"
                . '<guid>' . $itemLink . '</guid>' . "\n"
                . ($itemDate ? '<pubDate>' . date('r', $itemDate) . '</pubDate>' . "\n" : '')
                . '<description>' . $this->esc($this->itemDesc($item)) . '</description>' . "\n"
                . '</item>' . "\n";
        }
        return $out . '</channel></rss>';
    }
}

/** Typecho 国际化类 (基于翻译表, 未命中返回原文) */
class I18n
{
    protected static $lang = 'zh_CN';
    protected static $instance;
    protected static $translations = [];

    public static function getInstance() { if (!(self::$instance instanceof self)) { self::$instance = new self(); } return self::$instance; }
    public static function setLang($lang) { self::$lang = $lang; }
    public static function getLang() { return self::$lang; }
    /** 载入翻译: 传数组直接合并, 传语言文件路径则 include (需返回数组) */
    public static function addLang($lang)
    {
        if (is_array($lang)) {
            self::$translations = array_merge(self::$translations, $lang);
        } elseif (is_string($lang) && is_file($lang)) {
            $loaded = @include $lang;
            if (is_array($loaded)) {
                self::$translations = array_merge(self::$translations, $loaded);
            }
        }
        return true;
    }
    public function translate($string) { return isset(self::$translations[$string]) ? self::$translations[$string] : $string; }
    public function e($string) { echo $this->translate($string); }
    public function n($single, $plural, $count) { return intval($count) == 1 ? $single : $plural; }
    public function ngettext($single, $plural, $count) { return $this->n($single, $plural, $count); }
    /** 友好日期词 ("x 天前", 超出范围回落为给定日期格式) */
    public function dateWord($time, $format = 'Y-m-d H:i:s')
    {
        $word = (new Date($time))->word();
        return $word;
    }
    public function setLocale($locale) {}
    public function getLocale() { return self::$lang; }
    public function isAvailable($locale) { return false; }
}

} // end namespace Typecho

namespace Typecho\Widget\Helper {

/** 全局 Helper */
class Helper
{
    public static function options() { return \Compat\App::$options; }
    public static function threadedCommentsScript() {}

    /** Typecho: Helper::security() 返回安全组件 (getToken / protect) */
    public static function security() { return new \Compat\Security(); }

    /** Typecho: Helper::widgetById($widget, $pkId) 按主键取组件 */
    public static function widgetById($widget, $pkId = null) { return \Typecho\Widget::widget($widget); }

    /** Typecho: Helper::widgetByName($widget, $name) 按名称取组件 */
    public static function widgetByName($widget, $name = null) { return \Typecho\Widget::widget($widget); }

    /**
     * 兜底: 主题调用本兼容层未实现的 Helper::xxx() 时不再致命。
     * EmptyClass 的 __call/__get 返回自身、__toString 返回空串, 可安全承接任意链式调用。
     */
    public static function __callStatic($name, $args)
    {
        if (defined('DEBUG') && DEBUG) {
            error_log('[compat] Helper::' . $name . '() 未实现, 已返回安全空对象');
        }
        return EmptyClass::getInstance();
    }
}

/** 布局类 (Typecho API: Typecho\Widget\Helper\Layout) */
class Layout
{
    protected $_tagName = 'div';
    protected $_attributes = [];
    protected $_items = [];
    protected $_parent = null;
    protected $_close = true;
    protected $_html = '';

    public function __construct($tagName = 'div', $attributes = []) { $this->_tagName = $tagName; $this->_attributes = $attributes; }
    public function setTagName($tagName) { $this->_tagName = $tagName; return $this; }
    public function getTagName() { return $this->_tagName; }
    public function setAttribute($name, $value) { $this->_attributes[$name] = $value; return $this; }
    public function removeAttribute($name) { unset($this->_attributes[$name]); return $this; }
    public function getAttribute($name) { return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null; }
    public function addItem(Layout $item) { $this->_items[] = $item; $item->setParent($this); return $this; }
    public function removeItem(Layout $item) { foreach ($this->_items as $k => $v) { if ($v === $item) { unset($this->_items[$k]); } } $this->_items = array_values($this->_items); return $this; }
    public function getItems() { return $this->_items; }
    public function setParent(Layout $parent) { $this->_parent = $parent; return $this; }
    public function getParent() { return $this->_parent; }
    public function appendTo(Layout $parent) { $parent->addItem($this); return $this; }
    public function setClose($close) { $this->_close = (bool) $close; return $this; }
    public function html($html = null) { if ($html !== null) { $this->_html = (string) $html; return $this; } return $this->_html; }
    public function start() { $attr = ''; foreach ($this->_attributes as $k => $v) { $attr .= ' ' . $k . '="' . htmlspecialchars((string) $v) . '"'; } echo '<' . $this->_tagName . $attr . '>'; }
    public function end() { if ($this->_close) { echo '</' . $this->_tagName . '>'; } }
    public function render() { $this->start(); echo $this->_html; foreach ($this->_items as $item) { $item->render(); } $this->end(); }
    public function __toString() { ob_start(); $this->render(); return ob_get_clean(); }
}

/** 空对象 (Typecho API: Typecho\Widget\Helper\EmptyClass) */
class EmptyClass
{
    protected static $instance;
    public static function getInstance() { if (!(self::$instance instanceof self)) { self::$instance = new self(); } return self::$instance; }
    public function __call($name, $args) { return $this; }
    public function __get($name) { return $this; }
    public function __toString() { return ''; }
}

/** 表单容器 */
class Form
{
    const POST_METHOD = 'post';
    const GET_METHOD = 'get';
    const STANDARD_ENCODE = 'application/x-www-form-urlencoded';
    const MULTIPART_ENCODE = 'multipart/form-data';
    const TEXT_ENCODE = 'text/plain';

    public $items = [];
    protected $action = '';
    protected $method = 'post';
    protected $encodeType = 'application/x-www-form-urlencoded';

    public function addInput($item) { $this->items[] = $item; return $this; }

    /**
     * Typecho 原生 Form 继承自 Layout, 因此具备 addItem();
     * 主题 (如 Printer) 用它往表单里插入自定义分组标题等 Layout 元素,
     * 缺失该方法会直接 "Call to undefined method Form::addItem()"。
     */
    public function addItem($item) { $this->items[] = $item; return $this; }

    public function getItems() { return $this->items; }
    public function getInput($name) { foreach ($this->items as $item) { if ($item->getName() === $name) { return $item; } } return null; }
    public function setAction($action) { $this->action = (string) $action; return $this; }
    public function setMethod($method) { $this->method = (string) $method; return $this; }
    public function setEncodeType($type) { $this->encodeType = (string) $type; return $this; }
    public function getValues() { $values = []; foreach ($this->items as $item) { $values[$item->getName()] = $item->getValue(); } return $values; }
    /** 合并请求参数 (校验/回填场景) */
    public function getAllRequest() { return array_merge($_GET, $_POST); }
    /** 返回表单元素列表 (仅 AbstractElement 实例) */
    public function getInputs()
    {
        $inputs = [];
        foreach ($this->items as $item) {
            if ($item instanceof \Typecho\Widget\Helper\Form\Element\AbstractElement) {
                $inputs[] = $item;
            }
        }
        return $inputs;
    }
    /** 返回元素 name => value 参数表 */
    public function getParams()
    {
        $params = [];
        foreach ($this->getInputs() as $input) {
            $params[$input->getName()] = $input->getValue();
        }
        return $params;
    }
    public function validate() { return []; }
    public function render() { echo '<form action="' . htmlspecialchars($this->action) . '" method="' . $this->method . '" enctype="' . $this->encodeType . '">'; foreach ($this->items as $item) { echo '<div class="typecho-option"><label class="typecho-label">' . htmlspecialchars($item->getLabel()) . '</label><input type="' . $item->type . '" name="' . htmlspecialchars($item->getName()) . '" value="' . htmlspecialchars((string) $item->getValue()) . '" />'; if ($item->getDescription()) { echo '<p class="description">' . htmlspecialchars($item->getDescription()) . '</p>'; } echo '</div>'; } echo '</form>'; }
}

/** 分页导航基类 */
class PageNavigator
{
    protected $_total;
    protected $_pageSize;
    protected $_currentPage;
    protected $_totalPage;
    protected $_pageTemplate;
    protected $_pageHolder = 'page';
    protected $_anchor;

    public function __construct($total, $currentPage, $pageSize, $pageTemplate, $anchor = null)
    {
        $this->_total = max(0, intval($total));
        $this->_pageSize = max(1, intval($pageSize));
        $this->_currentPage = max(1, intval($currentPage));
        $this->_pageTemplate = strval($pageTemplate);
        $this->_anchor = $anchor ? '#' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $anchor) : '';
        $this->_totalPage = max(1, ceil($this->_total / $this->_pageSize));
        if ($this->_currentPage > $this->_totalPage) { $this->_currentPage = $this->_totalPage; }
        $this->_pageTemplate = str_replace('{page}', '{' . $this->_pageHolder . '}', $this->_pageTemplate);
        $this->_pageTemplate = preg_replace('/\{\d+\}/', '{' . $this->_pageHolder . '}', $this->_pageTemplate);
    }
    public function getTotalPage() { return $this->_totalPage; }
    public function getCurrentPage() { return $this->_currentPage; }
    public function getPageLink($page) { return str_replace('{' . $this->_pageHolder . '}', (string) $page, $this->_pageTemplate); }
    public function setPageHolder($holder) { $this->_pageHolder = $holder; return $this; }
    public function setAnchor($anchor) { $this->_anchor = $anchor ? '#' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $anchor) : ''; return $this; }
    public function render($pager = '') {}
}

/** 盒状分页导航 */
class PageNavigatorBox extends PageNavigator
{
    public function render($pager = '')
    {
        if ($this->_totalPage <= 1) { return; }
        $anchor = $this->_anchor;
        echo '<ol class="page-navigator">';
        if ($this->_currentPage > 1) { echo '<li class="prev"><a href="' . $this->getPageLink($this->_currentPage - 1) . $anchor . '">&laquo;</a></li>'; }
        $start = max(1, $this->_currentPage - 3);
        $end = min($this->_totalPage, $this->_currentPage + 3);
        if ($start > 1) { echo '<li><a href="' . $this->getPageLink(1) . $anchor . '">1</a></li>'; if ($start > 2) { echo '<li><span>...</span></li>'; } }
        for ($i = $start; $i <= $end; $i++) { if ($i == $this->_currentPage) { echo '<li class="current"><a href="' . $this->getPageLink($i) . $anchor . '">' . $i . '</a></li>'; } else { echo '<li><a href="' . $this->getPageLink($i) . $anchor . '">' . $i . '</a></li>'; } }
        if ($end < $this->_totalPage) { if ($end < $this->_totalPage - 1) { echo '<li><span>...</span></li>'; } echo '<li><a href="' . $this->getPageLink($this->_totalPage) . $anchor . '">' . $this->_totalPage . '</a></li>'; }
        if ($this->_currentPage < $this->_totalPage) { echo '<li class="next"><a href="' . $this->getPageLink($this->_currentPage + 1) . $anchor . '">&raquo;</a></li>'; }
        echo '</ol>';
    }
}

/** 经典分页导航 */
class PageNavigatorClassic extends PageNavigator
{
    public function render($pager = '')
    {
        if ($this->_totalPage <= 1) { return; }
        $anchor = $this->_anchor;
        if ($this->_currentPage > 1) { echo '<a href="' . $this->getPageLink($this->_currentPage - 1) . $anchor . '" class="prev">&laquo; ' . _t('上一页') . '</a>'; }
        if ($this->_currentPage < $this->_totalPage) { echo '<a href="' . $this->getPageLink($this->_currentPage + 1) . $anchor . '" class="next">' . _t('下一页') . ' &raquo;</a>'; }
    }
}

} // end namespace Typecho\Widget\Helper

namespace Typecho\Widget\Helper\Form\Element {

/** 表单元素基类 */
abstract class AbstractElement
{
    public $name = '';
    public $options = null;
    public $value = null;
    public $label = '';
    public $description = '';
    public $type = 'text';
    public $rules = [];
    public $multiMode = false;
    public $container;
    public $message = '';
    public $multiline = false;

    /**
     * 输入框布局对象 (Typecho 原生属性)
     * 主题常用 $element->input->setAttribute('class', 'xxx') 给输入框加样式/属性,
     * 缺失该属性时会取到 null, 紧接着调用 setAttribute() 即致命。
     */
    public $input;

    public function __construct($name, $options = null, $value = null, $label = '', $description = '')
    {
        $this->name = $name;
        $this->options = $options;
        $this->value = $value;
        $this->label = $label;
        $this->description = $description;
        $this->input = new \Typecho\Widget\Helper\Layout('input');
    }
    public function addRule($rule, $message = '') { $this->rules[$rule] = $message; return $this; }
    public function multiMode() { $this->multiMode = true; return $this; }
    public function getName() { return $this->name; }
    public function getValue() { return $this->value; }
    public function getLabel() { return $this->label; }
    public function getDescription() { return $this->description; }
    public function getOptions() { return $this->options; }
    public function input() { return $this; }
    public function label($val = null) { if ($val !== null) { $this->label = $val; return $this; } return $this->label; }
    public function value($val = null) { if ($val !== null) { $this->value = $val; return $this; } return $this->value; }
    public function description($val = null) { if ($val !== null) { $this->description = $val; return $this; } return $this->description; }
    public function init() {}
    public function container($container = null)
    {
        if ($container !== null) {
            $this->container = ($container instanceof \Typecho\Widget\Helper\Layout)
                ? $container
                : new \Typecho\Widget\Helper\Layout((string) $container);
            return $this;
        }
        return $this->container;
    }
    public function message($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
            return $this;
        }
        return $this->message;
    }
    public function multiline($multiline = true) { $this->multiline = (bool) $multiline; return $this; }
    public function setInputsAttribute($name, $value)
    {
        if ($this->input instanceof \Typecho\Widget\Helper\Layout) {
            $this->input->setAttribute($name, $value);
        }
        return $this;
    }
    public function inputValue($value) { return $value; }
}

class Text extends AbstractElement { public $type = 'text'; }
class Textarea extends AbstractElement { public $type = 'textarea'; }
class Password extends AbstractElement { public $type = 'password'; }
class Checkbox extends AbstractElement { public $type = 'checkbox'; }
class Radio extends AbstractElement { public $type = 'radio'; }
class Select extends AbstractElement { public $type = 'select'; }
class Submit extends AbstractElement { public $type = 'submit'; }
class Hidden extends AbstractElement { public $type = 'hidden'; }
class Number extends AbstractElement { public $type = 'number'; }
class Url extends AbstractElement { public $type = 'url'; }
class Fake extends AbstractElement { public $type = 'fake'; }

} // end namespace Typecho\Widget\Helper\Form\Element

namespace Typecho\Widget\Helper\PageNavigator {

/** 盒状分页 (命名空间别名指向) */
class Box extends \Typecho\Widget\Helper\PageNavigatorBox {}
/** 经典分页 (命名空间别名指向) */
class Classic extends \Typecho\Widget\Helper\PageNavigatorClassic {}

} // end namespace Typecho\Widget\Helper\PageNavigator

<?php
/**
 * Typecho 兼容层 - 引导文件
 * 定义全局函数与命名空间别名, 使 Typecho 主题可以直接运行
 */
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit('Access Denied');
}

// 加载兼容类
require_once __DIR__ . '/App.php';
require_once __DIR__ . '/Options.php';
require_once __DIR__ . '/Member.php';
require_once __DIR__ . '/BaseWidget.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Author.php';
require_once __DIR__ . '/Stat.php';
require_once __DIR__ . '/Archive.php';
require_once __DIR__ . '/CommentsWidget.php';
require_once __DIR__ . '/SidebarWidgets.php';
// Markdown 渲染器 (文章内容 Markdown -> HTML)
require_once __DIR__ . '/Markdown.php';
// 自包含 SMTP 邮件客户端 (会员注册邮箱验证发信)
require_once __DIR__ . '/Mailer.php';
// Typecho 命名空间兼容类 (Widget/Common/Cookie/Router/Db 等)
require_once __DIR__ . '/TypechoCompat.php';
require_once __DIR__ . '/TypechoDb.php';
// 插件管理器 (扫描/启用/加载 Typecho 插件)
require_once __DIR__ . '/PluginManager.php';

// 全局旧式类名兼容 (Widget_Options 等), 供部分老主题使用
if (!class_exists('Widget_Options', false)) {
    class_alias('\Compat\Options', 'Widget_Options');
}
if (!class_exists('Widget_User', false)) {
    class_alias('\Compat\User', 'Widget_User');
}
if (!class_exists('Widget_Stat', false)) {
    class_alias('\Compat\Stat', 'Widget_Stat');
}
if (!class_exists('Widget_Contents_Page_List', false)) {
    class_alias('\Compat\Widgets\PageRows', 'Widget_Contents_Page_List');
}
// 通知组件: 部分主题/插件会引用 (Typecho 原生为后台组件), 缺失会致命, 这里给安全空实现
if (!class_exists('Widget_Notice', false)) {
    class_alias('\Compat\Notice', 'Widget_Notice');
}
if (!class_exists('\Widget\Notice', false)) {
    class_alias('\Compat\Notice', 'Widget\Notice');
}
if (!class_exists('Widget_Metas_Category_Rows', false)) {
    class_alias('\Compat\Widgets\CategoryRows', 'Widget_Metas_Category_Rows');
}
if (!class_exists('Widget_Contents_Post_Recent', false)) {
    class_alias('\Compat\Widgets\RecentPosts', 'Widget_Contents_Post_Recent');
}
if (!class_exists('Widget_Comments_Recent', false)) {
    class_alias('\Compat\Widgets\RecentComments', 'Widget_Comments_Recent');
}
if (!class_exists('Widget_Contents_Post_Date', false)) {
    class_alias('\Compat\Widgets\PostDate', 'Widget_Contents_Post_Date');
}
if (!class_exists('Widget_Abstract_Contents', false)) {
    class_alias('\Compat\Archive', 'Widget_Abstract_Contents');
}
// 旧式全局名 Widget_Archive (Typecho 老主题常用), 指向兼容 Archive, 避免类不存在致命错误
if (!class_exists('Widget_Archive', false)) {
    class_alias('\Compat\Archive', 'Widget_Archive');
}
if (!class_exists('Widget_Abstract_Comments', false)) {
    class_alias('\Compat\CommentsWidget', 'Widget_Abstract_Comments');
}
if (!class_exists('Widget_Abstract_Metas', false)) {
    class_alias('\Compat\Widgets\CategoryRows', 'Widget_Abstract_Metas');
}

// 命名空间别名: 让 \Widget\xxx 指向兼容类
if (!class_exists('\Widget\Archive', false)) {
    class_alias('\Compat\Archive', 'Widget\Archive');
}
if (!class_exists('\Widget\Contents\Post\Recent', false)) {
    class_alias('\Compat\Widgets\RecentPosts', 'Widget\Contents\Post\Recent');
}
if (!class_exists('\Widget\Contents\Page\Rows', false)) {
    class_alias('\Compat\Widgets\PageRows', 'Widget\Contents\Page\Rows');
}
if (!class_exists('\Widget\Comments\Recent', false)) {
    class_alias('\Compat\Widgets\RecentComments', 'Widget\Comments\Recent');
}
if (!class_exists('\Widget\Metas\Category\Rows', false)) {
    class_alias('\Compat\Widgets\CategoryRows', 'Widget\Metas\Category\Rows');
}
if (!class_exists('\Widget\Contents\Post\Date', false)) {
    class_alias('\Compat\Widgets\PostDate', 'Widget\Contents\Post\Date');
}

// === 补充命名空间别名 ===
if (!class_exists('\Widget\Comments\Archive', false)) {
    class_alias('\Compat\CommentsWidget', 'Widget\Comments\Archive');
}
if (!class_exists('\Widget\Users\Author', false)) {
    class_alias('\Compat\Author', 'Widget\Users\Author');
}
if (!class_exists('\Widget\Metas\Tag\Cloud', false)) {
    class_alias('\Compat\Widgets\TagCloud', 'Widget\Metas\Tag\Cloud');
}
if (!class_exists('\Widget\Options', false)) {
    class_alias('\Compat\Options', 'Widget\Options');
}
if (!class_exists('\Widget\User', false)) {
    class_alias('\Compat\User', 'Widget\User');
}
if (!class_exists('\Widget\Stat', false)) {
    class_alias('\Compat\Stat', 'Widget\Stat');
}
if (!class_exists('\Widget\Metas\Category\Related', false)) {
    class_alias('\Compat\Widgets\CategoryRows', 'Widget\Metas\Category\Related');
}
if (!class_exists('\Widget\Metas\Tag\Related', false)) {
    class_alias('\Compat\Widgets\TagCloud', 'Widget\Metas\Tag\Related');
}
if (!class_exists('\Widget\Metas\From', false)) {
    class_alias('\Compat\Widgets\CategoryRows', 'Widget\Metas\From');
}
if (!class_exists('\Widget\Contents\From', false)) {
    class_alias('\Compat\Archive', 'Widget\Contents\From');
}
if (!class_exists('\Widget\Contents\Related', false)) {
    class_alias('\Typecho\PlaceholderWidget', 'Widget\Contents\Related');
}
if (!class_exists('\Widget\Contents\Related\Author', false)) {
    class_alias('\Typecho\PlaceholderWidget', 'Widget\Contents\Related\Author');
}
if (!class_exists('\Widget\Contents\Attachment\Related', false)) {
    class_alias('\Typecho\PlaceholderWidget', 'Widget\Contents\Attachment\Related');
}
if (!class_exists('\Widget\Comments\Ping', false)) {
    class_alias('\Typecho\PlaceholderWidget', 'Widget\Comments\Ping');
}
if (!class_exists('\Widget\Upload', false)) {
    class_alias('\Typecho\PlaceholderWidget', 'Widget\Upload');
}

// Typecho 常量: 主题目录 URL 路径 (相对站点根), 供主题拼接静态资源路径
if (!defined('__TYPECHO_THEME_DIR__')) {
    define('__TYPECHO_THEME_DIR__', '/article/theme');
}

// Typecho 全局 Helper 别名: \Helper::options() 供 TypechoGlass 等主题使用
// 注意: Helper 类定义在 Typecho\Widget\Helper 命名空间下, 完整类名为 Typecho\Widget\Helper\Helper
if (!class_exists('\Helper', false) && class_exists('Typecho\Widget\Helper\Helper', false)) {
    class_alias('\Typecho\Widget\Helper\Helper', 'Helper');
}

// Typecho 旧式插件接口 (供主题 implements Typecho_Plugin_Interface 使用, 如 RoricalTheme 的 Titleshow_Plugin)
if (!interface_exists('Typecho_Plugin_Interface', false)) {
    interface Typecho_Plugin_Interface
    {
        public static function activate();
        public static function deactivate();
        public static function config(Typecho_Widget_Helper_Form $form);
        public static function personalConfig(Typecho_Widget_Helper_Form $form);
    }
}

// Typecho 旧式全局类名兼容 (Typecho_* 风格), 供 RoricalTheme 等主题使用
$typechoLegacyAliases = [
    'Typecho_Widget_Helper_PageNavigator' => 'Typecho\Widget\Helper\PageNavigator',
    'Typecho_Widget_Helper_Form'          => 'Typecho\Widget\Helper\Form',
    'Typecho_Common'                      => 'Typecho\Common',
    'Typecho_Cookie'                      => 'Typecho\Cookie',
    'Typecho_Db'                          => 'Typecho\Db',
    'Typecho_Request'                     => 'Typecho\Request',
    'Typecho_Router'                      => 'Typecho\Router',
    'Typecho_Date'                        => 'Typecho\Date',
    'Typecho_Config'                      => 'Typecho\Config',
    'Typecho_Widget'                      => 'Typecho\Widget',
    'Typecho_Plugin'                      => 'Typecho\Plugin',
    'Typecho_Widget_Helper_Form_Element_Text'     => 'Typecho\Widget\Helper\Form\Element\Text',
    'Typecho_Widget_Helper_Form_Element_Textarea' => 'Typecho\Widget\Helper\Form\Element\Textarea',
    'Typecho_Widget_Helper_Form_Element_Password' => 'Typecho\Widget\Helper\Form\Element\Password',
    'Typecho_Widget_Helper_Form_Element_Checkbox' => 'Typecho\Widget\Helper\Form\Element\Checkbox',
    'Typecho_Widget_Helper_Form_Element_Radio'    => 'Typecho\Widget\Helper\Form\Element\Radio',
    'Typecho_Widget_Helper_Form_Element_Select'   => 'Typecho\Widget\Helper\Form\Element\Select',
    'Typecho_Widget_Helper_Form_Element_Hidden'   => 'Typecho\Widget\Helper\Form\Element\Hidden',
    'Typecho_Widget_Helper_Form_Element_Submit'   => 'Typecho\Widget\Helper\Form\Element\Submit',
    'Typecho_Widget_Helper_Form_Element_Number'   => 'Typecho\Widget\Helper\Form\Element\Number',
    'Typecho_Widget_Helper_Form_Element_Url'      => 'Typecho\Widget\Helper\Form\Element\Url',
    'Typecho_Widget_Helper_Layout'                => 'Typecho\Widget\Helper\Layout',
    'Typecho_Widget_Helper_EmptyClass'            => 'Typecho\Widget\Helper\EmptyClass',
    // 注意: 不再预建 Typecho_Widget_Helper_PageNavigator_Box / _Classic 别名,
    // 因为 RoricalTheme 的 functions.php 会自行声明同名类 (extends 上面基类),
    // 预建别名会导致 "Cannot declare class ... already in use" 致命错误。
    // 且全站无任何主题以消费者身份引用这两个遗留名, 移除安全。
    'Typecho_Validate'                            => 'Typecho\Validate',
    'Typecho_Response'                            => 'Typecho\Response',
    'Typecho_Feed'                                => 'Typecho\Feed',
    'Typecho_I18n'                                => 'Typecho\I18n',
    'Typecho_Exception'                           => 'Typecho\Exception',
    'Typecho_Db_Query'                            => 'Typecho\DbQuery',
    'Typecho_Db_Exception'                        => 'Typecho\Exception',
    'Typecho_Db_Adapter'                          => 'Typecho\DbAdapter',
];
foreach ($typechoLegacyAliases as $typechoAliasName => $typechoAliasTarget) {
    if (!class_exists($typechoAliasName, false) && class_exists($typechoAliasTarget, false)) {
        class_alias($typechoAliasTarget, $typechoAliasName);
    }
}

/**
 * 翻译函数: 简化实现, 直接返回原文
 */
function _t($string)
{
    $args = func_get_args();
    array_shift($args);
    if (!empty($args)) {
        return vsprintf($string, $args);
    }
    return $string;
}

/**
 * 文章自定义字段兼容对象
 * 将 Typecho 主题常用的字段名映射到实际文章列,
 * 使 $this->fields->pic / ->cover / ->thumb 等能正确返回封面图。
 */
class ArticleFields extends \Typecho\Config
{
    /** @var array 文章行数据 */
    protected $row = [];

    /** @var array 字段名 → 文章列名映射 */
    protected static $map = [
        // 封面图
        'pic'         => 'art_cover',
        'cover'       => 'art_cover',
        'thumb'       => 'art_cover',
        'thumbnail'   => 'art_cover',
        'image'       => 'art_cover',
        'img'         => 'art_cover',
        'coverImage'  => 'art_cover',
        'postImage'   => 'art_cover',
        'articleImage'=> 'art_cover',
        // 摘要/副标题
        'subtitle'    => 'art_excerpt',
        'description' => 'art_excerpt',
        'desc'        => 'art_excerpt',
        'summary'     => 'art_excerpt',
        'excerpt'     => 'art_excerpt',
        // 关键词/标签
        'keywords'    => 'art_keywords',
        'tags'        => 'art_keywords',
        'tag'         => 'art_keywords',
        // 作者
        'authorName'  => 'art_author',
        'writer'      => 'art_author',
    ];

    public function __construct($row = [])
    {
        $this->row = is_array($row) ? $row : [];
        parent::__construct([]);
    }

    public function __get($name)
    {
        // 先查映射到文章列
        if (isset(self::$map[$name])) {
            $col = self::$map[$name];
            return isset($this->row[$col]) ? $this->row[$col] : null;
        }
        // 再查实际字段名 (art_xxx 直传)
        if (isset($this->row[$name])) {
            return $this->row[$name];
        }
        return parent::__get($name);
    }

    public function __isset($name)
    {
        if (isset(self::$map[$name])) {
            $col = self::$map[$name];
            return isset($this->row[$col]) && $this->row[$col] !== '' && $this->row[$col] !== null;
        }
        if (isset($this->row[$name])) {
            return $this->row[$name] !== '' && $this->row[$name] !== null;
        }
        return parent::__isset($name);
    }
}

/**
 * 输出翻译
 */
function _e($string)
{
    echo call_user_func_array('_t', func_get_args());
}

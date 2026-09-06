<?php
/**
 * Typecho 兼容层 - 站点配置对象
 * 支持 $options->title() 输出、$options->title 取值、$options->logoUrl 属性
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Options
{
    protected $data = [];

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * 获取原始值
     */
    public function get($name, $default = null)
    {
        return array_key_exists($name, $this->data) ? $this->data[$name] : $default;
    }

    /**
     * 动态 URL/配置映射 (Typecho 原版通过 ___method 动态生成)
     * @return mixed|null 未命中返回 null
     */
    protected function dynamic($name)
    {
        $map = [
            'loginUrl' => App::$articleUrl . '?member=login',
            'loginAction' => App::$articleUrl . '?member=login',
            'registerUrl' => App::$articleUrl . '?member=register',
            'registerAction' => App::$articleUrl . '?member=register',
            'profileUrl' => App::$articleUrl . '?member=center',
            'memberPostUrl' => App::$articleUrl . '?member=post',
            'memberModerateUrl' => App::$articleUrl . '?member=moderate',
            'logoutUrl' => App::$articleUrl . '?logout=1',
            'commentsFeedUrl' => App::$articleUrl . '?feed=comments',
            'commentsFeedRssUrl' => App::$articleUrl . '?feed=comments',
            'commentsFeedAtomUrl' => App::$articleUrl . '?feed=comments',
            'xmlRpcUrl' => App::$articleUrl,
            'serverTimezone' => date_default_timezone_get(),
            'gmtTime' => time(),
            'allowedAttachmentTypes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'pdf'],
            'commentsRequireLogin' => (intval(isset(App::$config['article_comment']) ? App::$config['article_comment'] : 1) === 2),
            'software' => 'Typecho',
            'version' => '1.3.0',
            'contentType' => 'text/html',
            // robes 等主题通过 $this->options->screenName 获取管理员显示名
            'screenName' => !empty(App::$config['article_name']) ? (string) App::$config['article_name'] : 'admin',
            // Typecho 原版 Options 常用属性占位
            'lang' => 'zh-CN',
        ];
        return array_key_exists($name, $map) ? $map[$name] : null;
    }

    /**
     * 属性访问取值
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        $dyn = $this->dynamic($name);
        if ($dyn !== null) {
            return $dyn;
        }
        return null;
    }

    /**
     * isset($options->name) 支持 (Typecho Config 语义, 供主题 ag_option 等判断)
     */
    public function __isset($name)
    {
        return isset($this->data[$name]) || $this->dynamic($name) !== null;
    }

    /**
     * 属性赋值 (主题常用: Helper::options()->commentsMaxNestingLevels = 999)
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 方法调用输出值 (与 Typecho 原版 Config::__call 一致: echo 后无返回值)
     * 注意: 不能 return $this, 否则 `echo ... ? ... : $this->options->xxx()` 会把对象传给 echo
     * 导致 "Object of class Compat\Options could not be converted to string"
     */
    public function __call($name, $args)
    {
        $value = $this->get($name, '');
        if ($value === '' || $value === null) {
            $dyn = $this->dynamic($name);
            if ($dyn !== null) {
                $value = $dyn;
            }
        }

        // 带路径参数的 URL 方法: themeUrl('style.css') / adminUrl('login.php')
        if (!empty($args) && in_array($name, array('themeUrl', 'adminUrl'), true)) {
            $value = rtrim((string) $value, '/') . '/' . ltrim((string) $args[0], '/');
        }

        if (is_scalar($value)) {
            echo (string) $value;
        }
    }

    /**
     * 直接 echo Options 对象时的兜底 (避免 "could not be converted to string" fatal)
     */
    public function __toString()
    {
        return '';
    }

    /**
     * 输出所有配置(调试用)
     */
    public function dump()
    {
        print_r($this->data);
    }

    /**
     * 读取插件配置 (Typecho 语义: Helper::options()->plugin('Name'))
     * 返回 Typecho\Config 对象, 支持 $cfg->foo 取值
     */
    public function plugin($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$name);
        $data = [];
        $file = ROOT . 'article/config/plugins/' . $name . '.json';
        if (is_file($file)) {
            $dec = json_decode((string)file_get_contents($file), true);
            if (is_array($dec)) {
                $data = $dec;
            }
        }
        return new \Typecho\Config($data);
    }
}

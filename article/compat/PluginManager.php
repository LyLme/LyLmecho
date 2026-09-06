<?php
/**
 * Typecho 兼容层 - 插件管理器
 *
 * 负责扫描 article/plugins 下的 Typecho 插件、维护启用状态与配置、并在前端请求时
 * 加载已启用插件 (调用其 activate() 完成钩子注册)。
 *
 * 插件约定:
 *   - 每个插件占一个目录: article/plugins/{PluginName}/Plugin.php
 *   - Plugin.php 定义类 {PluginName}_Plugin (旧式) 或实现 Typecho_Plugin_Interface 的类
 *   - 类包含静态方法 activate()/deactivate()/config($form)/personalConfig($form)
 *   - 插件元信息取自文件顶部注释: @name @version @author @description @homepage
 *
 * 启用状态持久化: lylme_article_config 表 key=article_plugins (JSON 数组: ["Demo", ...])
 * 插件配置持久化: article/config/plugins/{PluginName}.json
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit('Access Denied');
}

class PluginManager
{
    /** 插件目录 */
    const PLUGIN_DIR = 'article/plugins/';

    /** 启用状态配置键 */
    const CONFIG_KEY = 'article_plugins';

    /** 插件配置子目录 */
    const PLUGIN_CONFIG_DIR = 'article/config/plugins/';

    /** 扫描所有插件, 返回带元信息的数组 */
    public static function scan()
    {
        $dir = ROOT . self::PLUGIN_DIR;
        $plugins = [];
        if (!is_dir($dir)) {
            return $plugins;
        }
        foreach (glob($dir . '*', GLOB_ONLYDIR) as $pluginDir) {
            $name = basename($pluginDir);
            $pluginFile = $pluginDir . '/Plugin.php';
            if (!is_file($pluginFile)) {
                continue;
            }
            $info = self::parseInfo($pluginFile);
            $info['name'] = $info['name'] !== '' ? $info['name'] : $name;
            $info['dir'] = $name;
            $info['file'] = $pluginFile;
            $info['activated'] = self::isActivated($name);
            $info['hasConfig'] = self::hasConfigMethod($name);
            $plugins[$name] = $info;
        }
        ksort($plugins);
        return $plugins;
    }

    /** 解析插件文件顶部注释元信息 */
    public static function parseInfo($file)
    {
        $default = ['name' => '', 'version' => '', 'description' => '', 'author' => '', 'homepage' => ''];
        $content = @file_get_contents($file);
        if ($content === false) {
            return $default;
        }
        // 取首个文档注释块 ( /** ... */ ) 的元信息
        if (!preg_match('#/\*\*(.*?)\*/#s', $content, $m)) {
            return $default;
        }
        $doc = $m[1];
        $map = [
            '@name'        => 'name',
            '@version'     => 'version',
            '@author'      => 'author',
            '@description' => 'description',
            '@homepage'    => 'homepage',
        ];
        foreach ($map as $tag => $key) {
            if (preg_match('/' . preg_quote($tag, '/') . '\s+([^\n\r*]+)/', $doc, $mm)) {
                $default[$key] = trim($mm[1]);
            }
        }
        return $default;
    }

    /** 获取插件类名 (优先 {Dir}_Plugin, 否则首个实现 Typecho_Plugin_Interface 的类) */
    public static function className($name)
    {
        $name = self::sanitize($name);
        $file = ROOT . self::PLUGIN_DIR . $name . '/Plugin.php';
        if (!is_file($file)) {
            return null;
        }
        $legacy = $name . '_Plugin';
        if (!class_exists($legacy, false)) {
            // 仅加载一次, 避免重复定义
            include_once $file;
        }
        if (class_exists($legacy, false)) {
            return $legacy;
        }
        // 兜底: 首个实现插件接口的类
        foreach (get_declared_classes() as $cls) {
            if (in_array('Typecho_Plugin_Interface', class_implements($cls), true)) {
                return $cls;
            }
        }
        return null;
    }

    /** 读取启用列表 */
    public static function getActivated()
    {
        global $DB;
        $list = [];
        if (isset($DB) && $DB instanceof \DB) {
            $row = $DB->get_row("SELECT `v` FROM `lylme_article_config` WHERE `k` = '" . self::CONFIG_KEY . "' LIMIT 1");
            if ($row && isset($row['v'])) {
                $dec = json_decode((string)$row['v'], true);
                if (is_array($dec)) {
                    $list = $dec;
                }
            }
        }
        return $list;
    }

    /** 是否启用 */
    public static function isActivated($name)
    {
        return in_array(self::sanitize($name), self::getActivated(), true);
    }

    /** 启用插件 (写入配置) */
    public static function activate($name)
    {
        $name = self::sanitize($name);
        if ($name === '') {
            return false;
        }
        $list = self::getActivated();
        if (!in_array($name, $list, true)) {
            $list[] = $name;
            self::saveActivated($list);
        }
        return true;
    }

    /** 禁用插件 */
    public static function deactivate($name)
    {
        $name = self::sanitize($name);
        $list = self::getActivated();
        $list = array_values(array_diff($list, [$name]));
        self::saveActivated($list);
        return true;
    }

    /** 持久化启用列表 */
    protected static function saveActivated(array $list)
    {
        global $DB;
        if (!isset($DB) || !($DB instanceof \DB)) {
            return false;
        }
        $v = $DB->escape(json_encode(array_values($list), JSON_UNESCAPED_UNICODE));
        $DB->query("INSERT INTO `lylme_article_config` (`k`, `v`) VALUES ('" . self::CONFIG_KEY . "', '$v') ON DUPLICATE KEY UPDATE `v` = '$v'");
        return true;
    }

    /** 前端启动时加载所有已启用插件的 activate() (注册钩子) */
    public static function loadActivated()
    {
        foreach (self::getActivated() as $name) {
            $cls = self::className($name);
            if ($cls === null || !class_exists($cls, false)) {
                continue;
            }
            if (!is_callable([$cls, 'activate'])) {
                continue;
            }
            // activate() 可能 echo 安装信息, 丢弃输出避免污染页面
            ob_start();
            try {
                call_user_func([$cls, 'activate']);
            } catch (\Exception $e) {
                // 单个插件出错不影响其他插件与页面
            }
            ob_end_clean();
        }
    }

    /** 读取插件配置 (返回数组) */
    public static function getConfig($name)
    {
        $name = self::sanitize($name);
        $file = ROOT . self::PLUGIN_CONFIG_DIR . $name . '.json';
        if (is_file($file)) {
            $dec = json_decode((string)file_get_contents($file), true);
            if (is_array($dec)) {
                return $dec;
            }
        }
        return [];
    }

    /** 保存插件配置 */
    public static function saveConfig($name, array $data)
    {
        $name = self::sanitize($name);
        $dir = ROOT . self::PLUGIN_CONFIG_DIR;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return false;
            }
        }
        $file = $dir . $name . '.json';
        return @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    /** 插件是否声明了 config() 配置表单 */
    public static function hasConfigMethod($name)
    {
        $cls = self::className($name);
        return $cls !== null && is_callable([$cls, 'config']);
    }

    /** 构建插件配置表单 (调用其 config($form)) 并返回表单元素数组 */
    public static function buildConfigForm($name)
    {
        $name = self::sanitize($name);
        $cls = self::className($name);
        if ($cls === null) {
            return null;
        }
        $form = new \Typecho\Widget\Helper\Form();
        if (is_callable([$cls, 'config'])) {
            call_user_func([$cls, 'config'], $form);
        }
        return $form;
    }

    /** 名称白名单过滤, 防止路径穿越 */
    public static function sanitize($name)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$name);
    }
}

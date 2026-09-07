<?php
/**
 * 文章模块 - 公共初始化
 * 加载系统核心并初始化 Typecho 兼容层
 */

// 引入系统公共文件(会加载 $DB / $conf / ROOT 常量)
require_once dirname(__FILE__) . '/../include/common.php';

// 启动 Session(兼容 User 组件)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 定义 Typecho 兼容层根目录常量
if (!defined('__TYPECHO_ROOT_DIR__')) {
    define('__TYPECHO_ROOT_DIR__', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}

// 加载兼容层
require_once __DIR__ . '/compat/bootstrap.php';

// 读取文章模块独立配置, 合并进 $conf
$articleConfig = [];
try {
    $configResult = $DB->query("SELECT * FROM `lylme_article_config`");
    if ($configResult) {
        while ($configRow = $DB->fetch($configResult)) {
            $articleConfig[$configRow['k']] = $configRow['v'];
        }
    }
} catch (Exception $e) {
    // 表不存在则忽略
}

$conf = array_merge($conf, $articleConfig);

// 文章模块开关: 关闭后文章模块不再对外提供服务
if (intval(isset($conf['article_status']) ? $conf['article_status'] : 1) !== 1) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>功能暂未开放</title>
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{
        min-height:100vh;
        display:flex;align-items:center;justify-content:center;
        font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Hiragino Sans GB","Microsoft YaHei",sans-serif;
        background:linear-gradient(135deg,#eef2f7 0%,#e6ecf5 100%);
        padding:24px;
    }
    .card{
        background:#fff;
        border-radius:16px;
        box-shadow:0 12px 40px rgba(60,80,120,.12);
        padding:56px 48px 44px;
        max-width:480px;width:100%;
        text-align:center;
        animation:fadeIn .5s ease;
    }
    .icon{
        width:88px;height:88px;margin:0 auto 24px;
        border-radius:50%;
        background:linear-gradient(135deg,#f5e9e9 0%,#fbeaea 100%);
        display:flex;align-items:center;justify-content:center;
        animation:float 2.6s ease-in-out infinite;
    }
    .icon svg{width:44px;height:44px}
    h1{font-size:22px;font-weight:600;color:#333;margin-bottom:10px}
    p{font-size:14px;line-height:1.8;color:#888;margin-bottom:30px}
    a.btn{
        display:inline-block;
        padding:11px 34px;
        border-radius:999px;
        background:linear-gradient(135deg,#4f7cf7 0%,#3b62e0 100%);
        color:#fff;font-size:14px;text-decoration:none;
        transition:transform .2s,box-shadow .2s;
        box-shadow:0 6px 18px rgba(59,98,224,.28);
    }
    a.btn:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(59,98,224,.36)}
    .footer{margin-top:28px;font-size:12px;color:#c0c6d0}
    @keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
</style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#d76a6a" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3l10 18H2L12 3z"/>
                <line x1="12" y1="10" x2="12" y2="14"/>
                <circle cx="12" cy="17.2" r="0.4" fill="#d76a6a" stroke="none"/>
            </svg>
        </div>
        <h1>功能暂未开放</h1>
        <p>文章模块目前处于关闭状态，站长暂未启用该功能。<br>您可以返回首页浏览其他内容。</p>
        <a class="btn" href="/">返回首页</a>
        <div class="footer">LyLme_Spage</div>
    </div>
</body>
</html>
HTML;
    exit;
}

// 初始化兼容层应用容器
\Compat\App::init($DB, $conf);

// 初始化 Typecho\Db 兼容单例
\Typecho\Db::setDb($DB);

// 加载已启用的 Typecho 插件 (调用其 activate() 完成钩子注册)
\Compat\PluginManager::loadActivated();

// 全局: 独立页面正文输出 (dux 等主题的 page.php 调用 parseContent($this))
if (!function_exists('parseContent')) {
    function parseContent($archive)
    {
        echo $archive->content();
    }
}

// 确保导航链接详情页所需的评论类型/计数字段存在(一次性, 可重复执行, 无权限时静默忽略)
if (!function_exists('article_ensure_site_schema')) {
    function article_ensure_site_schema()
    {
        global $DB;
        if (!is_object($DB) || !method_exists($DB, 'query')) {
            return;
        }
        try {
            $col = $DB->get_row("SHOW COLUMNS FROM `lylme_article_comment` LIKE 'com_type'");
            if (empty($col)) {
                $DB->query(
                    "ALTER TABLE `lylme_article_comment` ADD COLUMN `com_type` TINYINT(1) NOT NULL DEFAULT 0 "
                    . "COMMENT '评论类型:0=文章,1=链接' AFTER `art_id`"
                );
            }
            $col2 = $DB->get_row("SHOW COLUMNS FROM `lylme_links` LIKE 'comments'");
            if (empty($col2)) {
                $DB->query(
                    "ALTER TABLE `lylme_links` ADD COLUMN `comments` INT(11) NOT NULL DEFAULT 0 "
                    . "COMMENT '评论数' AFTER `link_keywords`"
                );
            }
            $col3 = $DB->get_row("SHOW COLUMNS FROM `lylme_links` LIKE 'link_content'");
            if (empty($col3)) {
                $DB->query(
                    "ALTER TABLE `lylme_links` ADD COLUMN `link_content` MEDIUMTEXT "
                    . "COMMENT '链接详情长文(Markdown, 渲染在详情页正文下方)' AFTER `link_pwd`"
                );
            }
        } catch (\Exception $e) {
            // 无 ALTER 权限时由站长手动执行迁移 SQL, 不影响既有功能
        }
    }
}
article_ensure_site_schema();

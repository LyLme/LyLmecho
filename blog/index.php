<?php
/**
 * LyLmecho /blog 快捷入口
 *
 * 仅提供入口跳转: 访问 /blog(及其子路径) 一律 301 重定向到博客模块 /article/ 对应地址。
 * 站点正式地址、canonical、RSS、sitemap 等仍以 /article 为准, 此目录不承载任何业务逻辑。
 *
 * 说明:
 *  - 301 会把 /blog 的收录权重合并到 /article (推荐); 若只把它当临时入口不想影响缓存, 将下面
 *    http_response_code(301) 改为 302 即可。
 *  - 若同时希望 nginx 下 /blog/任意路径 也能跳转 (默认深层路径会被当静态文件 404),
 *    在站点 server 块中补充:
 *        location /blog/ { rewrite ^ /blog/index.php last; }
 *    Apache 可用等价 RewriteRule 将不存在的 URI 交给本脚本。
 */

// 保留当前协议 / 主机 (含子目录部署: blog 与 article 同级, 自动回退到其父级)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : 'localhost';

$self = rtrim(str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/blog/index.php')), '/');
$root = $self === '' ? '' : substr($self, 0, (int) strrpos($self, '/')); // /blog 的上一级 = 站点部署根

$uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$path = (string) parse_url($uri, PHP_URL_PATH);

// 剥离本目录前缀, 得到 /blog 之后的子路径 (无子路径则为空)
$rest = '';
if ($path !== '' && $self !== '' && stripos($path, $self) === 0) {
    $rest = ltrim(substr($path, strlen($self)), '/');
}

$target = rtrim($root, '/') . '/article/' . $rest;
if ($target === '/article/') {
    $target = '/article';
}

$qs = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') ? $_SERVER['QUERY_STRING'] : '';
$target = $scheme . '://' . $host . $target . ($qs !== '' ? '?' . $qs : '');

http_response_code(301);
header('Location: ' . $target);
header('Cache-Control: no-store');
exit;

<?php
/**
 * 文章模块 - 会员 登录 / 注册 / 退出 / 邮箱验证 / 个人中心
 * 由 index.php 在 ?member=xxx 或 ?logout 时引入 (约定同 comment.php / feed.php)
 * 依赖已加载的 $DB / $conf / Compat\App / Compat\Member
 */
if (!defined('ARTICLE_INDEX')) {
    exit;
}

use Compat\App;
use Compat\Member;

$siteRoot = rtrim(substr(App::$articleUrl, 0, -8), '/');
$captchaUrl = $siteRoot . '/include/validatecode.php';
$action = isset($_GET['member']) ? strtolower(trim((string) $_GET['member'])) : '';
$isPost = isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';

// ---------- 工具函数 ----------

if (!function_exists('member_e')) {
    function member_e($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('member_home')) {
    function member_home()
    {
        return App::$articleUrl;
    }
}

if (!function_exists('member_safe_redirect')) {
    /**
     * 安全跳转: 仅允许跳回本站文章域 / 站内相对路径, 防开放重定向。
     * 非法目标回落到文章首页。
     */
    function member_safe_redirect($target, $fallback = null)
    {
        if ($fallback === null) {
            $fallback = member_home();
        }
        $target = trim((string) $target);
        if ($target === '') {
            header('Location: ' . $fallback);
            exit;
        }
        $siteRoot = rtrim(substr(App::$articleUrl, 0, -8), '/');
        $articleHost = parse_url(App::$articleUrl, PHP_URL_HOST);
        // 站内相对路径
        if ($target[0] === '/' && (strlen($target) < 2 || ($target[1] !== '/' && $target[1] !== '\\'))) {
            header('Location: ' . $siteRoot . $target);
            exit;
        }
        if (strpos($target, 'index.php') === 0 || strpos($target, '?') === 0) {
            header('Location: ' . App::$articleUrl . ltrim($target, '?'));
            exit;
        }
        // 绝对 URL: 仅接受本站 host 且路径在站点范围内
        if (preg_match('~^https?://~i', $target)) {
            $h = parse_url($target, PHP_URL_HOST);
            if ($h && $articleHost && strcasecmp($h, $articleHost) === 0) {
                header('Location: ' . $target);
                exit;
            }
        }
        header('Location: ' . $fallback);
        exit;
    }
}

if (!function_exists('member_current_redirect')) {
    /** 从 GET/POST 读取 redirect 目标 */
    function member_current_redirect()
    {
        foreach ([$_GET['redirect'] ?? null, $_POST['redirect'] ?? null] as $r) {
            if ($r !== null && trim((string) $r) !== '') {
                return (string) $r;
            }
        }
        return '';
    }
}

if (!function_exists('member_captcha_ok')) {
    function member_captcha_ok($code)
    {
        // 开发调试(DEBUG)下跳过验证码, 便于端到端测试; 生产(DEBUG=false)强制校验
        if (defined('DEBUG') && DEBUG === true) {
            return true;
        }
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $expected = isset($_SESSION['authcode']) ? (string) $_SESSION['authcode'] : '';
        // 用后即焚, 防止重放
        unset($_SESSION['authcode']);
        return $expected !== '' && is_string($code) && hash_equals($expected, strtolower(trim($code)));
    }
}

if (!function_exists('member_throttle')) {
    /**
     * 简易失败限流(按会话): 5 次失败锁定 60 秒。
     * @return array [locked(bool), remain(int 秒)]
     */
    function member_throttle($op = 'check')
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        // 计数器 key 与行为(op)解耦: check/fail/reset 必须操作同一组 session 变量,
        // 否则锁定永不触发、成功登录后计数也不清零。
        $key = 'lylme_member_fail';
        $lockKey = $key . '_lock';
        $fails = isset($_SESSION[$key]) ? (int) $_SESSION[$key] : 0;
        $lockUntil = isset($_SESSION[$lockKey]) ? (int) $_SESSION[$lockKey] : 0;

        if ($op === 'check') {
            if ($lockUntil > time()) {
                return [true, $lockUntil - time()];
            }
            return [false, 0];
        }
        if ($op === 'fail') {
            $fails++;
            $_SESSION[$key] = $fails;
            if ($fails >= 5) {
                $_SESSION[$lockKey] = time() + 60;
                $_SESSION[$key] = 0;
            }
            return [false, 0];
        }
        if ($op === 'reset') {
            unset($_SESSION[$key], $_SESSION[$lockKey]);
            return [false, 0];
        }
        return [false, 0];
    }
}

if (!function_exists('member_palette')) {
    /**
     * 由主色 hex 派生一组色值(与主题 lylme_palette 同算法), 供会员页 CSS 使用.
     */
    function member_palette($hex)
    {
        $hex = trim((string) $hex);
        if (!preg_match('/^#([0-9a-f]{6})$/i', $hex)) {
            $hex = '#4f7cf7';
        }
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        $rgb = $r . ', ' . $g . ', ' . $b;
        // RGB -> HSL
        $rn = $r / 255; $gn = $g / 255; $bn = $b / 255;
        $mx = max($rn, $gn, $bn); $mn = min($rn, $gn, $bn);
        $l = ($mx + $mn) / 2;
        if ($mx === $mn) { $h = $s = 0; }
        else {
            $d = $mx - $mn;
            $s = $l > 0.5 ? $d / (2 - $mx - $mn) : $d / ($mx + $mn);
            if ($mx === $rn) { $h = (($gn - $bn) / $d + ($gn < $bn ? 6 : 0)) / 6; }
            elseif ($mx === $gn) { $h = (($bn - $rn) / $d + 2) / 6; }
            else { $h = (($rn - $gn) / $d + 4) / 6; }
        }
        $h *= 360;
        // HSL -> hex helper
        $hsl2hex = function($hh, $ss, $ll) {
            $hh = fmod($hh, 360); if ($hh < 0) $hh += 360;
            $c = (1 - abs(2 * $ll - 1)) * $ss;
            $x = $c * (1 - abs(fmod($hh / 60, 2) - 1));
            $m = $ll - $c / 2;
            if ($hh < 60) { $rp = $c; $gp = $x; $bp = 0; }
            elseif ($hh < 120) { $rp = $x; $gp = $c; $bp = 0; }
            elseif ($hh < 180) { $rp = 0; $gp = $c; $bp = $x; }
            elseif ($hh < 240) { $rp = 0; $gp = $x; $bp = $c; }
            elseif ($hh < 300) { $rp = $x; $gp = 0; $bp = $c; }
            else { $rp = $c; $gp = 0; $bp = $x; }
            return sprintf('#%02x%02x%02x', round(($rp + $m) * 255), round(($gp + $m) * 255), round(($bp + $m) * 255));
        };
        $accent = $hsl2hex(($h + 32) % 360, min(1, $s * 1.04), max(0.32, min(0.72, $l * 0.96)));
        $deep = $hsl2hex($h, min(1, $s * 1.1), max(0.18, $l * 0.74));
        return ['base' => strtolower($hex), 'rgb' => $rgb, 'accent' => $accent, 'deep' => $deep];
    }
}

if (!function_exists('member_simple_css')) {
    /**
     * 简单会员页(登录/注册/验证)的现代 CSS.
     * 玻璃拟态卡片 + 动态背景 + 明暗双模, 与 lylme 主题设计语言一致.
     */
    function member_simple_css()
    {
        return <<<'CSS'
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Microsoft YaHei",sans-serif;
background:#f4f6fb;color:#23262f;padding:24px;position:relative;overflow-x:hidden}
body::before,body::after{content:"";position:fixed;border-radius:50%;pointer-events:none;z-index:0;
width:420px;height:420px;filter:blur(80px);opacity:.5}
body::before{background:rgba(79,124,247,.3);top:-120px;right:-80px;animation:mDrift 28s ease-in-out infinite}
body::after{background:rgba(123,92,240,.22);bottom:-100px;left:-60px;animation:mDrift 34s ease-in-out infinite reverse}
@keyframes mDrift{50%{transform:translate(50px,-30px)}}
.card{position:relative;z-index:1;background:rgba(255,255,255,.82);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
border:1px solid rgba(255,255,255,.5);border-radius:22px;box-shadow:0 8px 32px rgba(20,24,45,.08),0 1.5px 4px rgba(20,24,45,.04);
padding:40px 36px;max-width:420px;width:100%;animation:mUp .5s ease}
@keyframes mUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
h1{font-size:22px;margin:0 0 24px;text-align:center;font-weight:700;color:#1b1e28;letter-spacing:-.01em}
.field{margin-bottom:16px}
.field label{display:block;font-size:13px;color:#5b6472;margin-bottom:6px;font-weight:500}
.field input{width:100%;padding:10px 14px;border:1px solid rgba(24,28,45,.1);border-radius:10px;font-size:14px;
background:rgba(255,255,255,.7);color:inherit;transition:border-color .2s,box-shadow .2s}
.field input:focus{outline:none;border-color:rgba(79,124,247,.5);box-shadow:0 0 0 3px rgba(79,124,247,.12);background:#fff}
.cap{display:flex;gap:10px;align-items:center}
.cap img{border-radius:8px;border:1px solid rgba(24,28,45,.08);cursor:pointer;height:38px}
button{width:100%;padding:12px;border:none;border-radius:10px;
background:linear-gradient(135deg,#4f7cf7,#7b5cf0);color:#fff;font-size:15px;font-weight:600;
cursor:pointer;margin-top:8px;transition:transform .15s,box-shadow .2s}
button:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(79,124,247,.35)}
button:active{transform:translateY(0)}
.flash{background:rgba(192,57,43,.08);color:#c0392b;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:18px;
border:1px solid rgba(192,57,43,.12)}
.ok{background:rgba(46,125,67,.08)!important;color:#2e7d43!important;border-color:rgba(46,125,67,.12)!important}
.links{margin-top:22px;text-align:center;font-size:13px}
.links a{color:#4f7cf7;text-decoration:none;font-weight:500}
.links a:hover{text-decoration:underline}
.home{display:flex;align-items:center;gap:4px;text-align:center;margin-bottom:20px;font-size:13px;color:#6f7889;text-decoration:none;transition:color .2s}
.home:hover{color:#4f7cf7}
.tip{font-size:12px;color:#6f7889;margin-top:10px;line-height:1.6}
.switch{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#5b6472;margin-top:4px}
@media(prefers-color-scheme:dark){
body{background:#12141f;color:#dfe3f0}
body::before{background:rgba(79,124,247,.12);opacity:.3}
body::after{background:rgba(123,92,240,.1);opacity:.2}
.card{background:rgba(27,30,45,.82);border-color:rgba(255,255,255,.08);
box-shadow:0 8px 32px rgba(0,0,0,.3),0 1.5px 4px rgba(0,0,0,.2)}
h1{color:#f2f4fb}
.field label{color:#aab1c7}
.field input{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);color:#dfe3f0}
.field input:focus{background:rgba(255,255,255,.1);border-color:rgba(79,124,247,.5);box-shadow:0 0 0 3px rgba(79,124,247,.2)}
.flash{background:rgba(192,57,43,.15);border-color:rgba(192,57,43,.2)}
.ok{background:rgba(46,125,67,.15)!important;border-color:rgba(46,125,67,.2)!important}
.links a{color:#7ba0ff}
.home{color:#79839f}
.home:hover{color:#7ba0ff}
.tip{color:#79839f}
.switch{color:#aab1c7}
button{background:linear-gradient(135deg,#5d8aff,#8b6cf0)}
button:hover{box-shadow:0 6px 20px rgba(93,138,255,.3)}
}
CSS;
    }
}

if (!function_exists('member_dashboard_css')) {
    /**
     * 会员仪表盘(概览/文章/审核/资料/评论)的现代 CSS.
     * 个人资料卡 + 统计卡 + 现代导航 + 明暗双模.
     */
    function member_dashboard_css()
    {
        return <<<'CSS'
*{box-sizing:border-box}
body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Microsoft YaHei",sans-serif;
background:linear-gradient(135deg,#f8f9ff 0%,#f4f6fb 100%);color:#23262f;min-height:100vh}
a{text-decoration:none;color:inherit}
.wrap{max-width:1000px;margin:0 auto;padding:32px 24px}
/* ---- 顶栏 ---- */
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;gap:12px;flex-wrap:wrap}
.top .who{font-size:14px;color:#5b6472;display:flex;align-items:center;gap:8px}
.role{display:inline-block;font-size:11px;font-weight:600;color:#4f7cf7;background:rgba(79,124,247,.08);
border:1px solid rgba(79,124,247,.2);border-radius:var(--r-full);padding:2px 10px}
.home{font-size:13px;color:#6f7889;display:flex;align-items:center;gap:4px;transition:color .2s}
.home:hover{color:#4f7cf7}
/* ---- 导航标签 ---- */
.nav{display:flex;gap:2px;flex-wrap:wrap;margin-bottom:28px;background:rgba(24,28,45,.04);
padding:4px;border-radius:14px}
.nav a{padding:10px 20px;font-size:14px;font-weight:500;color:#5b6472;border-radius:10px;transition:all .2s}
.nav a:hover{color:#23262f;background:rgba(255,255,255,.6)}
.nav a.on{color:#4f7cf7;background:#fff;box-shadow:0 2px 8px rgba(20,24,45,.06),0 0 0 1px rgba(79,124,247,.12)}
/* ---- 卡片 ---- */
.card{background:#fff;border:1px solid rgba(24,28,45,.06);border-radius:20px;
box-shadow:0 2px 8px rgba(20,24,45,.04),0 8px 32px rgba(20,24,45,.06);
padding:32px;margin-bottom:24px}
h1{font-size:20px;margin:0 0 24px;font-weight:700;color:#1b1e28;letter-spacing:-.01em}
/* ---- 个人资料头 ---- */
.profile-head{position:relative;background:#fff;border:1px solid rgba(24,28,45,.06);
border-radius:20px;box-shadow:0 2px 8px rgba(20,24,45,.04),0 8px 32px rgba(20,24,45,.06);margin-bottom:24px}
.profile-banner{height:90px;background:linear-gradient(135deg,#4f7cf7 0%,#7b5cf0 100%);position:relative;overflow:hidden;border-radius:20px 20px 0 0}
.profile-banner::after{content:"";position:absolute;inset:0;
background:radial-gradient(circle at 80% 20%,rgba(255,255,255,.25),transparent 60%)}
.profile-body{display:flex;align-items:center;gap:24px;padding:0 32px 28px;margin-top:-42px;position:relative;flex-wrap:wrap;background:#fff}
.profile-avatar{width:84px;height:84px;border-radius:50%;background:linear-gradient(135deg,#4f7cf7,#7b5cf0);
border:4px solid #fff;display:flex;align-items:center;justify-content:center;
font-size:32px;font-weight:700;color:#fff;box-shadow:0 6px 20px rgba(79,124,247,.35);flex-shrink:0}
.profile-info{flex:1;min-width:0}
.profile-name{font-size:22px;font-weight:700;color:#1b1e28;margin:0 0 6px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.profile-meta{font-size:13px;color:#6f7889;display:flex;gap:20px;flex-wrap:wrap;margin-top:8px}
.profile-meta span{display:flex;align-items:center;gap:6px}
/* ---- 统计卡 ---- */
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:8px}
.stat{position:relative;background:#fff;border:1px solid rgba(24,28,45,.06);border-radius:16px;
padding:24px;text-align:left;overflow:hidden;transition:all .25s}
.stat:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(20,24,45,.1)}
.stat::before{content:"";position:absolute;top:0;left:0;right:0;height:4px;
background:linear-gradient(90deg,#4f7cf7,#7b5cf0)}
.stat b{display:block;font-size:32px;font-weight:700;color:#1b1e28;margin-bottom:6px}
.stat span{font-size:13px;color:#6f7889;font-weight:500}
/* ---- 快捷操作 ---- */
.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
.btn{display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:500;padding:10px 20px;
border-radius:12px;border:1px solid rgba(24,28,45,.1);background:#fff;color:#23262f;
cursor:pointer;transition:all .2s}
.btn:hover{border-color:rgba(79,124,247,.3);color:#4f7cf7;transform:translateY(-1px);
box-shadow:0 4px 12px rgba(20,24,45,.08)}
.btn.pri{background:linear-gradient(135deg,#4f7cf7,#7b5cf0);color:#fff;border:none;
box-shadow:0 4px 12px rgba(79,124,247,.25)}
.btn.pri:hover{box-shadow:0 6px 20px rgba(79,124,247,.4)}
.btn.danger{color:#c0392b;border-color:rgba(192,57,43,.2)}
.btn.danger:hover{background:rgba(192,57,43,.06)}
/* ---- 表格 ---- */
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{text-align:left;padding:14px 12px;border-bottom:1px solid rgba(24,28,45,.06);vertical-align:middle}
th{color:#6f7889;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em}
tr:hover td{background:rgba(24,28,45,.02)}
.badge{display:inline-block;font-size:12px;font-weight:500;padding:4px 12px;border-radius:var(--r-full)}
.badge.pub{background:rgba(46,125,67,.08);color:#2e7d43}
.badge.pend{background:rgba(178,106,0,.08);color:#b26a00}
.badge.draft{background:rgba(103,112,127,.08);color:#67707f}
/* ---- 表单 ---- */
.field{margin-bottom:18px}
.field label{display:block;font-size:13px;color:#5b6472;margin-bottom:8px;font-weight:500}
.field input,.field textarea,.field select{width:100%;padding:11px 14px;border:1px solid rgba(24,28,45,.1);
border-radius:10px;font-size:14px;font-family:inherit;background:#fff;color:inherit;
transition:border-color .2s,box-shadow .2s}
.field input:focus,.field textarea:focus,.field select:focus{outline:none;
border-color:rgba(79,124,247,.5);box-shadow:0 0 0 3px rgba(79,124,247,.12)}
.field textarea{min-height:220px;resize:vertical}
.row2{display:flex;gap:16px;flex-wrap:wrap}.row2>.field{flex:1;min-width:220px}
/* ---- Flash ---- */
.flash{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:18px;
background:rgba(192,57,43,.08);color:#c0392b;border:1px solid rgba(192,57,43,.12)}
.flash.ok{background:rgba(46,125,67,.08);color:#2e7d43;border-color:rgba(46,125,67,.12)}
/* ---- 杂项 ---- */
.tip{font-size:12px;color:#6f7889;margin-top:10px;line-height:1.6}
.muted{color:#9aa4b2}
.pager{margin-top:18px;display:flex;gap:6px;justify-content:center;font-size:13px}
.pager a{color:#4f7cf7;padding:6px 12px;border-radius:8px;transition:background .2s}
.pager a:hover{background:rgba(79,124,247,.08)}
.content-preview{border:1px solid rgba(24,28,45,.06);border-radius:14px;padding:24px;
margin-top:14px;background:rgba(24,28,45,.015);line-height:1.8;word-break:break-word}
.content-preview img{max-width:100%}
:root{--r-full:999px}
/* ---- Vditor 编辑器 ---- */
.vditor-wrap{margin-top:8px}
.vditor-wrap .vditor{border:1px solid rgba(24,28,45,.1);border-radius:12px;overflow:hidden}
.vditor-wrap .vditor-toolbar{background:#fafbfc;border-bottom:1px solid rgba(24,28,45,.08)}
.vditor-wrap .vditor-content{background:#fff;min-height:400px}
/* ---- 暗色模式 ---- */
@media(prefers-color-scheme:dark){
body{background:linear-gradient(135deg,#0e1019 0%,#12141f 100%);color:#dfe3f0}
.top .who{color:#aab1c7}
.role{color:#7ba0ff;background:rgba(79,124,247,.15);border-color:rgba(79,124,247,.25)}
.home{color:#79839f}.home:hover{color:#7ba0ff}
.nav{background:rgba(255,255,255,.04)}
.nav a{color:#aab1c7}.nav a:hover{color:#f2f4fb;background:rgba(255,255,255,.06)}
.nav a.on{color:#7ba0ff;background:rgba(27,30,45,.9);box-shadow:0 0 0 1px rgba(79,124,247,.2)}
.card,.profile-head{background:#1b1e2d;border-color:rgba(255,255,255,.08)}
h1{color:#f2f4fb}
.stat{background:#1b1e2d;border-color:rgba(255,255,255,.08)}
.stat b{color:#f2f4fb}
.stat span{color:#79839f}
.btn{background:#1b1e2d;border-color:rgba(255,255,255,.1);color:#dfe3f0}
.btn:hover{border-color:rgba(79,124,247,.3);color:#7ba0ff}
.btn.pri{background:linear-gradient(135deg,#5d8aff,#8b6cf0)}
th{color:#79839f}
th,td{border-color:rgba(255,255,255,.06)}
tr:hover td{background:rgba(255,255,255,.02)}
.field input,.field textarea,.field select{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);color:#dfe3f0}
.field label{color:#aab1c7}
.flash{background:rgba(192,57,43,.15);border-color:rgba(192,57,43,.2)}
.flash.ok{background:rgba(46,125,67,.15);border-color:rgba(46,125,67,.2)}
.tip{color:#79839f}.muted{color:#6b7494}
.pager a{color:#7ba0ff}.pager a:hover{background:rgba(79,124,247,.15)}
.content-preview{background:rgba(255,255,255,.03);border-color:rgba(255,255,255,.06)}
.profile-avatar{border-color:#1b1e2d}
.profile-body{background:#1b1e2d}
.profile-name{color:#f2f4fb}
.profile-meta{color:#79839f}
.badge.pub{background:rgba(46,125,67,.15)}
.badge.pend{background:rgba(178,106,0,.15)}
.badge.draft{background:rgba(103,112,127,.15)}
.vditor-wrap .vditor{border-color:rgba(255,255,255,.1)}
.vditor-wrap .vditor-toolbar{background:#1b1e2d;border-color:rgba(255,255,255,.08)}
.vditor-wrap .vditor-content{background:#1b1e2d;color:#dfe3f0}
}
CSS;
    }
}

if (!function_exists('member_render')) {
    /**
     * 渲染独立会员页(不依赖主题), 统一卡片风格。
     * @param string $title 页标题
     * @param string $body  正文 HTML
     * @param string $flash 提示(可选)
     * @param array  $links 底部链接 [文本 => URL]
     */
    function member_render($title, $body, $flash = '', $links = [])
    {
        $home = member_e(member_home());
        $t = member_e($title);
        $flashHtml = $flash !== ''
            ? '<div class="flash">' . member_e($flash) . '</div>'
            : '';
        $linkHtml = '';
        if (!empty($links)) {
            $items = [];
            foreach ($links as $text => $url) {
                $items[] = '<a href="' . member_e($url) . '">' . member_e($text) . '</a>';
            }
            $linkHtml = '<div class="links">' . implode(' &nbsp;|&nbsp; ', $items) . '</div>';
        }
        header('Content-Type: text/html; charset=utf-8');
        $css = member_simple_css();
        echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $t . '</title><style>' . $css . '</style></head><body><div class="card">';
        echo '<a class="home" href="' . $home . '">&laquo; 返回文章</a>';
        echo '<h1>' . $t . '</h1>';
        echo $flashHtml;
        echo $body;
        echo $linkHtml;
        echo '</div></body></html>';
    }
}

if (!function_exists('member_page')) {
    /**
     * 会员中心仪表盘外壳(宽版): 顶部导航 + 卡片 + flash。
     * 仅用于已登录会员页面(概览/我的文章/审核台/资料/我的评论)。
     * @param string $title  页标题
     * @param string $body   正文 HTML(调用方自行转义)
     * @param string $active 高亮导航 key
     * @param string $flash  提示(可选)
     * @param bool   $ok     flash 是否为成功态(绿色)
     */
    function member_page($title, $body, $active = '', $flash = '', $ok = false)
    {
        $member = Member::current();
        $home = member_e(member_home());
        $base = App::$articleUrl;
        $t = member_e($title);
        $canSubmit = Member::can('submit', $member);
        $canModerate = Member::can('moderate', $member);
        $navMap = [
            'center'   => ['概览', $base . '?member=center'],
            'post'     => ['我的文章', $base . '?member=post'],
            'moderate' => ['审核台', $base . '?member=moderate'],
            'profile'  => ['资料', $base . '?member=profile'],
            'comments' => ['我的评论', $base . '?member=comments'],
        ];
        $navHtml = '';
        foreach ($navMap as $k => $item) {
            if ($k === 'post' && !$canSubmit) {
                continue;
            }
            if ($k === 'moderate' && !$canModerate) {
                continue;
            }
            $cls = ($k === $active) ? ' class="on"' : '';
            $navHtml .= '<a href="' . member_e($item[1]) . '"' . $cls . '>' . member_e($item[0]) . '</a>';
        }
        $roleMap = ['subscriber' => '订阅者', 'contributor' => '投稿者', 'editor' => '编辑'];
        $role = $member ? (string) $member['role'] : '';
        $roleName = isset($roleMap[$role]) ? $roleMap[$role] : $role;
        $isAdminView = !$member && Member::isAdminLoggedIn();
        if ($isAdminView) {
            $roleName = '管理员';
        }
        $who = $member
            ? member_e($member['nickname'] !== '' ? $member['nickname'] : $member['username'])
              . '<span class="role">' . member_e($roleName) . '</span>'
            : ($isAdminView ? '站点管理员<span class="role">管理员</span>' : '');
        $flashHtml = $flash !== ''
            ? '<div class="flash' . ($ok ? ' ok' : '') . '">' . member_e($flash) . '</div>'
            : '';
        header('Content-Type: text/html; charset=utf-8');
        $css = member_dashboard_css();
        // 紧凑资料条: 所有仪表盘页面顶部统一显示
        $profileBar = '';
        if ($member) {
            $pName = $member['nickname'] !== '' ? $member['nickname'] : $member['username'];
            $pInitial = mb_substr($pName, 0, 1, 'UTF-8');
            $pEmail = isset($member['email']) ? (string) $member['email'] : '';
            $pReg = isset($member['reg_time']) ? (string) $member['reg_time'] : '';
            $profileBar = '<div class="profile-head">'
                . '<div class="profile-banner"></div>'
                . '<div class="profile-body">'
                . '<div class="profile-avatar">' . member_e($pInitial) . '</div>'
                . '<div class="profile-info">'
                . '<p class="profile-name">' . member_e($pName)
                . '<span class="role">' . member_e($roleName) . '</span></p>'
                . '<div class="profile-meta">';
            if ($pEmail !== '') {
                $profileBar .= '<span>' . member_e($pEmail) . '</span>';
            }
            if ($pReg !== '') {
                $profileBar .= '<span>加入于 ' . member_e(substr($pReg, 0, 10)) . '</span>';
            }
            $profileBar .= '</div></div></div></div>';
        } elseif ($isAdminView) {
            // 管理员也显示资料卡, 避免空白感
            $profileBar = '<div class="profile-head">'
                . '<div class="profile-banner" style="background:linear-gradient(135deg,#1b1e2d,#2d3561)"></div>'
                . '<div class="profile-body">'
                . '<div class="profile-avatar" style="background:linear-gradient(135deg,#1b1e2d,#2d3561)">&#9733;</div>'
                . '<div class="profile-info">'
                . '<p class="profile-name">站点管理员<span class="role" style="color:#ffd66b;background:rgba(255,214,107,.1);border-color:rgba(255,214,107,.25)">管理员</span></p>'
                . '<div class="profile-meta"><span>拥有全部管理权限</span></div>'
                . '</div></div></div></div>';
        }
        echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $t . '</title><style>' . $css . '</style>'
            . '<link rel="stylesheet" href="/assets/admin/vditor/index.css">'
            . '</head><body><div class="wrap">';
        echo '<div class="top"><a class="home" href="' . $home . '">&larr; 返回文章</a><div class="who">' . $who . ' &nbsp;<a class="home" href="' . member_e($base . '?logout=1') . '">退出</a></div></div>';
        echo '<nav class="nav">' . $navHtml . '</nav>';
        echo $profileBar;
        echo '<div class="card"><h1>' . $t . '</h1>' . $flashHtml . $body . '</div>';
        echo '</div>';
        // Vditor 编辑器 (仅在 post/write 页面加载)
        if ($active === 'post') {
            echo '<script src="/assets/admin/vditor/index.min.js"></script>';
            echo '<script>';
            echo '(function(){';
            echo 'var VDITOR_CDN="/assets/admin/vditor/";';
            echo 'var TOOLBAR=["emoji","headings","bold","italic","strike","link","|","list","ordered-list","check","outdent","indent","|","quote","line","code","inline-code","insert-before","insert-after","|","upload","table","|","undo","redo","|","fullscreen","edit-mode","preview","export"];';
            echo 'var ta=document.getElementById("art_content");';
            echo 'if(ta&&document.getElementById("vditor")){';
            echo 'var editor=new Vditor("vditor",{';
            echo 'cdn:VDITOR_CDN,lang:"zh_CN",value:ta.value,mode:"ir",theme:"classic",width:"100%",height:560,minHeight:360,';
            echo 'placeholder:"请输入文章内容，支持 Markdown 语法与 HTML 标签…",cache:{enable:false},tab:"\\t",counter:{enable:true,type:"text"},toolbar:TOOLBAR,toolbarConfig:{pin:true},';
            echo 'preview:{delay:300,theme:"classic",hljs:{lineNumber:true,style:"github"},math:{engine:"KaTeX"},markdown:{toc:true,mark:true,footnote:true,autoSpace:true,isOpen:true},actions:["desktop","tablet","mobile","both","outline","beautify"]},';
            echo 'hljs:{lineNumber:true,style:"github"},emoji:{enable:true},';
            echo 'upload:{url:"/include/file.php?compress=1",fieldName:"file",max:5*1024*1024,accept:"image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp",filename:function(n){return n.replace(/[\\\\/:*?"<>|]/g,"").replace(/\\s+/g,"_")},format:function(files,rt){var r;try{r=JSON.parse(rt)}catch(e){r=null}var ok=!!r&&(r.code===200||r.code==="200")&&!!r.url;return JSON.stringify({msg:(r&&r.msg)||"上传失败",code:ok?0:1,data:{errFiles:ok?[]:[files[0].name],succMap:ok?{[files[0].name]:r.url}:{}}})}},';
            echo 'after:function(){if(editor&&typeof editor.getValue==="function")ta.value=editor.getValue()},';
            echo 'input:function(v){ta.value=v}';
            echo '});';
            echo '}';
            echo '})();';
            echo '</script>';
        }
        echo '</body></html>';
    }
}

// ---------- 动作分发 ----------

// 退出登录
if (isset($_GET['logout']) || $action === 'logout') {
    $back = member_current_redirect() ?: member_home();
    Member::logout();
    // 若同时为导航后台管理员, 清理 admin_token cookie (整站登出)
    if (Member::isAdminLoggedIn() && !headers_sent()) {
        setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/']);
        setcookie('admin_token', '', time() - 3600, '/');
    }
    member_safe_redirect($back, member_home());
}

// 邮箱验证
if ($action === 'verify') {
    $token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
    $flash = '验证链接无效';
    if ($token !== '' && $DB instanceof \DB) {
        $esc = $DB->escape($token);
        $row = $DB->get_row("SELECT `uid`,`status` FROM `lylme_member` WHERE `verify_token` = '{$esc}' AND `verify_token` <> '' LIMIT 1");
        if ($row) {
            $uid = intval($row['uid']);
            $DB->query("UPDATE `lylme_member` SET `status` = 1, `verify_token` = '' WHERE `uid` = {$uid}");
            member_render('邮箱验证', '<p style="text-align:center">邮箱验证成功，现在可以登录了。</p>', '', ['前往登录' => App::$articleUrl . '?member=login']);
            exit;
        }
    }
    member_render('邮箱验证', '<p style="text-align:center">链接已失效或不存在。</p>', $flash, ['返回登录' => App::$articleUrl . '?member=login']);
    exit;
}

// 会员中心 - 概览仪表盘
if ($action === 'center') {
    $member = Member::current();
    if (!$member) {
        if (Member::isAdminLoggedIn()) {
            member_page('会员中心', '<p>您是站点管理员，文章与评论请在<a href="' . member_e((string) App::$options->get('adminUrl', $siteRoot . '/admin/')) . '">导航后台</a>管理。</p>', 'center');
            exit;
        }
        member_safe_redirect('', App::$articleUrl . '?member=login');
        exit;
    }
    $uid = intval($member['uid']);
    $pub = intval($DB->count("SELECT COUNT(*) FROM `lylme_article` WHERE `art_author_uid` = {$uid} AND `art_status` = 1"));
    $pend = intval($DB->count("SELECT COUNT(*) FROM `lylme_article` WHERE `art_author_uid` = {$uid} AND `art_status` = 2"));
    $draft = intval($DB->count("SELECT COUNT(*) FROM `lylme_article` WHERE `art_author_uid` = {$uid} AND `art_status` = 0"));
    $myComments = intval($DB->count("SELECT COUNT(*) FROM `lylme_article_comment` WHERE `com_uid` = {$uid}"));

    $notice = isset($_SESSION['member_notice']) ? (string) $_SESSION['member_notice'] : '';
    $noticeOk = isset($_SESSION['member_notice_ok']) ? intval($_SESSION['member_notice_ok']) : 0;
    unset($_SESSION['member_notice'], $_SESSION['member_notice_ok']);

    $stat = function ($n, $label) {
        return '<div class="stat"><b>' . intval($n) . '</b><span>' . member_e($label) . '</span></div>';
    };
    $body = '<div class="grid">'
        . $stat($pub, '已发布') . $stat($pend, '待审核') . $stat($draft, '草稿') . $stat($myComments, '我的评论')
        . '</div>';

    $body .= '<div class="actions">';
    if (Member::can('submit', $member)) {
        $body .= '<a href="' . member_e(App::$articleUrl . '?member=post') . '" class="btn pri">&#9998; 投稿 / 我的文章</a>';
    }
    if (Member::can('moderate', $member)) {
        $pp = intval($DB->count("SELECT COUNT(*) FROM `lylme_article` WHERE `art_status` = 2"));
        $pc = intval($DB->count("SELECT COUNT(*) FROM `lylme_article_comment` WHERE `com_status` = 0"));
        $body .= '<a href="' . member_e(App::$articleUrl . '?member=moderate') . '" class="btn">&#9878; 审核台';
        if ($pp + $pc > 0) {
            $body .= ' <span class="badge pend">' . ($pp + $pc) . ' 待处理</span>';
        }
        $body .= '</a>';
    }
    $body .= '<a href="' . member_e(App::$articleUrl . '?member=profile') . '" class="btn">&#9881; 编辑资料</a>';
    $body .= '<a href="' . member_e(App::$articleUrl . '?member=comments') . '" class="btn">&#9993; 我的评论</a>';
    $body .= '</div>';
    if (!Member::can('submit', $member)) {
        $body .= '<p class="tip">当前为订阅者角色，仅可浏览与评论。如需投稿请联系管理员将账号提升为投稿者/编辑。</p>';
    }
    member_page('概览', $body, 'center', $notice, $noticeOk === 1);
    exit;
}

// 会员中心 - 资料设置
if ($action === 'profile') {
    $member = Member::current();
    if (!$member) {
        member_safe_redirect('', App::$articleUrl . '?member=login');
        exit;
    }
    $flash = '';
    $flashOk = false;
    if ($isPost) {
        if (!Member::checkCsrf(isset($_POST['_csrf']) ? $_POST['_csrf'] : '')) {
            $flash = '会话已过期，请重试';
        } else {
            Member::resetErrors();
            $fields = [];
            if (isset($_POST['nickname'])) {
                $fields['nickname'] = $_POST['nickname'];
            }
            if (isset($_POST['email'])) {
                $fields['email'] = $_POST['email'];
            }
            if (isset($_POST['url'])) {
                $fields['url'] = $_POST['url'];
            }
            if (isset($_POST['password']) && trim((string) $_POST['password']) !== '') {
                // 改密需验证旧密码
                if (!Member::verifyPassword(isset($_POST['old_password']) ? $_POST['old_password'] : '', $member['password'])) {
                    Member::addError('原密码不正确');
                } else {
                    $fields['password'] = $_POST['password'];
                }
            }
            if (!empty($fields)) {
                if (Member::updateProfile($member['uid'], $fields) === false) {
                    $flash = implode('；', Member::errors());
                } else {
                    member_safe_redirect('', App::$articleUrl . '?member=profile&saved=1');
                    exit;
                }
            }
        }
    }
    if (isset($_GET['saved']) && intval($_GET['saved']) === 1) {
        $flash = '资料已保存';
        $flashOk = true;
    }
    $csrf = member_e(Member::csrfToken());
    $body = '<form method="post" action="' . member_e(App::$articleUrl . '?member=profile') . '">'
        . '<input type="hidden" name="_csrf" value="' . $csrf . '">'
        . '<div class="field"><label>用户名</label><input type="text" value="' . member_e($member['username']) . '" disabled></div>'
        . '<div class="field"><label>昵称</label><input type="text" name="nickname" value="' . member_e($member['nickname']) . '"></div>'
        . '<div class="field"><label>邮箱</label><input type="email" name="email" value="' . member_e($member['email']) . '"></div>'
        . '<div class="field"><label>个人网站</label><input type="url" name="url" value="' . member_e($member['url']) . '"></div>'
        . '<div class="row2">'
        . '<div class="field"><label>原密码（修改密码时填写）</label><input type="password" name="old_password" autocomplete="off"></div>'
        . '<div class="field"><label>新密码（留空则不修改）</label><input type="password" name="password" autocomplete="new-password"></div>'
        . '</div>'
        . '<div class="actions"><button type="submit" class="btn pri">保存资料</button></div>'
        . '</form>';
    member_page('资料设置', $body, 'profile', $flash, $flashOk);
    exit;
}

// 会员中心 - 我的评论
if ($action === 'comments') {
    $member = Member::current();
    if (!$member) {
        member_safe_redirect('', App::$articleUrl . '?member=login');
        exit;
    }
    $uid = intval($member['uid']);
    $size = 15;
    $page = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
    $off = ($page - 1) * $size;
    $total = intval($DB->count("SELECT COUNT(*) FROM `lylme_article_comment` WHERE `com_uid` = {$uid}"));
    $pages = (int) ceil($total / $size);
    $rowsHtml = '';
    $result = $DB->query(
        "SELECT c.`com_id`,c.`art_id`,c.`com_content`,c.`com_status`,c.`com_time`,a.`art_title` "
        . "FROM `lylme_article_comment` c LEFT JOIN `lylme_article` a ON c.`art_id` = a.`art_id` "
        . "WHERE c.`com_uid` = {$uid} ORDER BY c.`com_time` DESC LIMIT {$off}, {$size}"
    );
    $n = 0;
    while ($result && ($r = $DB->fetch($result))) {
        $n++;
        $badge = intval($r['com_status']) === 1
            ? '<span class="badge pub">已通过</span>'
            : '<span class="badge pend">待审核</span>';
        $rowsHtml .= '<tr>'
            . '<td>' . intval($r['com_id']) . '</td>'
            . '<td>' . nl2br(member_e(mb_substr((string) $r['com_content'], 0, 200, 'UTF-8')))
            . '<br><small class="muted">《' . member_e((string) $r['art_title']) . '》 · ' . member_e((string) $r['com_time']) . '</small></td>'
            . '<td>' . $badge . '</td>'
            . '<td><a href="' . member_e(App::$articleUrl . '?id=' . intval($r['art_id']) . '#comments') . '" class="btn">查看</a></td>'
            . '</tr>';
    }
    if ($n === 0) {
        $rowsHtml = '<tr><td colspan="4" class="muted" style="text-align:center;padding:24px">你还没有发表过评论。</td></tr>';
    }
    $pager = '';
    if ($pages > 1) {
        for ($i = 1; $i <= $pages; $i++) {
            $pager .= $i === $page
                ? '<span class="btn">' . $i . '</span>'
                : '<a href="' . member_e(App::$articleUrl . '?member=comments&page=' . $i) . '">' . $i . '</a>';
        }
        $pager = '<div class="pager">' . $pager . '</div>';
    }
    $table = '<p class="tip">共 ' . $total . ' 条评论</p><table><thead><tr><th>ID</th><th>内容</th><th>状态</th><th>操作</th></tr></thead><tbody>'
        . $rowsHtml . '</tbody></table>' . $pager;
    member_page('我的评论', $table, 'comments');
    exit;
}

// 注册
if ($action === 'register') {
    if (!Member::registrationOpen()) {
        member_render('注册', '<p style="text-align:center">站点当前未开放自助注册，请联系管理员开通账号。</p>', '', ['返回登录' => App::$articleUrl . '?member=login']);
        exit;
    }
    $flash = '';
    if ($isPost) {
        if (!Member::checkCsrf(isset($_POST['_csrf']) ? $_POST['_csrf'] : '')) {
            $flash = '会话已过期，请重新提交';
        } elseif (!member_captcha_ok(isset($_POST['captcha']) ? $_POST['captcha'] : '')) {
            $flash = '验证码错误';
        } else {
            [$locked] = member_throttle('check');
            if ($locked) {
                $flash = '操作过于频繁，请稍后再试';
            } else {
                $uid = Member::create(
                    isset($_POST['username']) ? $_POST['username'] : '',
                    isset($_POST['email']) ? $_POST['email'] : '',
                    isset($_POST['password']) ? $_POST['password'] : '',
                    isset($_POST['nickname']) ? $_POST['nickname'] : ''
                );
                if ($uid === false) {
                    $flash = implode('；', Member::errors());
                    member_throttle('fail');
                } else {
                    if (Member::requireVerify()) {
                        $m = Member::findByUid($uid);
                        $vlink = App::$articleUrl . '?member=verify&token=' . rawurlencode((string) $m['verify_token']);
                        $sent = Member::mailConfigured() && Member::sendVerifyEmail(
                            isset($m['email']) ? $m['email'] : '',
                            $vlink,
                            isset($m['username']) ? $m['username'] : ''
                        );
                        if ($sent) {
                            member_render('注册成功', '<p>账号已创建，验证邮件已发送至 <b>' . member_e(isset($m['email']) ? $m['email'] : '') . '</b>，请查收并点击邮件中的链接完成验证后方可登录。</p>', '请完成邮箱验证', ['前往登录' => App::$articleUrl . '?member=login']);
                            exit;
                        }
                        // 未配置 SMTP 或发送失败: 降级为直接展示验证链接(与接入邮件前行为一致)
                        $note = Member::mailConfigured()
                            ? '验证邮件发送失败（' . member_e(Member::mailError()) . '），请手动使用以下链接完成验证：'
                            : '尚未配置邮件发送服务，验证链接如下（正式环境将通过邮件发送）：';
                        member_render('注册成功', '<p>账号已创建，需完成邮箱验证后方可登录。</p>'
                            . '<p class="tip">' . $note . '</p>'
                            . '<p><a href="' . member_e($vlink) . '">' . member_e($vlink) . '</a></p>', '请完成邮箱验证', ['前往登录' => App::$articleUrl . '?member=login']);
                        exit;
                    }
                    $member = Member::findByUid($uid);
                    Member::login($member, false);
                    member_safe_redirect(member_current_redirect(), member_home());
                }
            }
        }
    }
    $csrf = member_e(Member::csrfToken());
    $body = '<form method="post" action="' . member_e(App::$articleUrl . '?member=register') . '">'
        . '<input type="hidden" name="_csrf" value="' . $csrf . '">'
        . '<div class="field"><label>用户名 *</label><input type="text" name="username" required></div>'
        . '<div class="field"><label>邮箱 *</label><input type="email" name="email" required></div>'
        . '<div class="field"><label>昵称（可选）</label><input type="text" name="nickname"></div>'
        . '<div class="field"><label>密码 *</label><input type="password" name="password" required minlength="6" autocomplete="new-password"></div>'
        . '<div class="field cap"><div style="flex:1"><label>验证码 *</label><input type="text" name="captcha" required autocomplete="off"></div>'
        . '<img src="' . member_e($captchaUrl) . '" alt="验证码" title="点击刷新" onclick="this.src=\'' . member_e($captchaUrl) . '?t=\'+Date.now()"></div>'
        . '<button type="submit">注册</button></form>';
    member_render('注册', $body, $flash, ['已有账号？登录' => App::$articleUrl . '?member=login', '返回首页' => member_home()]);
    exit;
}

// 登录 (默认动作)
if ($action === 'login' || $action === '') {
    $flash = '';
    if ($isPost) {
        [$locked, $remain] = member_throttle('check');
        if ($locked) {
            $flash = '失败次数过多，请 ' . $remain . ' 秒后再试';
        } elseif (!Member::checkCsrf(isset($_POST['_csrf']) ? $_POST['_csrf'] : '')) {
            $flash = '会话已过期，请重新提交';
        } elseif (!member_captcha_ok(isset($_POST['captcha']) ? $_POST['captcha'] : '')) {
            $flash = '验证码错误';
        } else {
            $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
            $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
            $remember = !empty($_POST['remember']);
            $member = Member::attempt($username, $password);
            if ($member) {
                member_throttle('reset');
                Member::login($member, $remember);
                member_safe_redirect(member_current_redirect(), member_home());
            }
            member_throttle('fail');
            $flash = '用户名或密码错误';
        }
    }
    $csrf = member_e(Member::csrfToken());
    $redirectVal = member_e(member_current_redirect());
    $body = '<form method="post" action="' . member_e(App::$articleUrl . '?member=login') . '">'
        . '<input type="hidden" name="_csrf" value="' . $csrf . '">'
        . '<input type="hidden" name="redirect" value="' . $redirectVal . '">'
        . '<div class="field"><label>用户名</label><input type="text" name="username" required autofocus></div>'
        . '<div class="field"><label>密码</label><input type="password" name="password" required autocomplete="current-password"></div>'
        . '<div class="field cap"><div style="flex:1"><label>验证码</label><input type="text" name="captcha" required autocomplete="off"></div>'
        . '<img src="' . member_e($captchaUrl) . '" alt="验证码" title="点击刷新" onclick="this.src=\'' . member_e($captchaUrl) . '?t=\'+Date.now()"></div>'
        . '<label class="switch"><input type="checkbox" name="remember" value="1"> 记住我</label>'
        . '<button type="submit">登录</button></form>';
    $links = ['返回首页' => member_home()];
    if (Member::registrationOpen()) {
        $links['注册账号'] = App::$articleUrl . '?member=register';
    }
    member_render('登录', $body, $flash, $links);
    exit;
}

// 投稿 / 我的文章 / 新建编辑 / 删除 / 预览
if (in_array($action, ['post', 'write', 'delete', 'preview'], true)) {
    require __DIR__ . '/member_post.php';
    exit;
}

// 编辑审核台 / 审核文章 / 审核评论
if (in_array($action, ['moderate', 'audit_post', 'audit_comment'], true)) {
    require __DIR__ . '/member_moderate.php';
    exit;
}

// 未知动作
member_safe_redirect('', member_home());

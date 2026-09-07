<?php
/**
 * 访问管理 - 博客主题精简版(兼容层)
 *
 * 由 article/index.php?route=pwd 注入到博客页面渲染。
 * - 自带作用域样式(.lylme-pwd-compat), 卡片压缩、去全屏背景;
 * - action 使用绝对路径 /include/go.php (博客上下文相对路径会失效);
 * - 复用 include/common.php 提供的 session_start_safe()/csrf_field()/$DB。
 */

if (!isset($DB)) {
    require_once __DIR__ . '/../include/common.php';
}
session_start_safe();

$pwd_enabled = $DB->num_rows($DB->query("SELECT * FROM `lylme_pwd`")) != 0;
// 是否已登录
$is_logged_in = isset($_SESSION['pass']) && $_SESSION['pass'] == 1;
?>
<div class="lylme-pwd-compat">
    <style>
    .lylme-pwd-compat { max-width: 375px; margin: 24px auto; text-align: center; }
    .lylme-pwd-compat * { box-sizing: border-box; }
    .lylme-pwd-compat h1, .lylme-pwd-compat h2 { margin: 0 0 16px; }
    .lylme-pwd-compat p { margin: 0 0 16px; opacity: .75; }
    .lylme-pwd-compat .lylme-form-item input { width: 100%; height: 46px; padding: 0 16px; border: 1px solid rgba(128, 128, 128, .25); border-radius: 8px; font-size: 1em; outline: none; }
    .lylme-pwd-compat .lylme-form-item input:focus { border-color: rgba(128, 128, 128, .5); }
    .lylme-pwd-compat .lylme-btn { width: 100%; height: 46px; margin-top: 10px; border: 1px solid rgba(128, 128, 128, .35); border-radius: 8px; background: transparent; color: inherit; font-size: 1.1em; cursor: pointer; }
    .lylme-pwd-compat .lylme-home { display: inline-block; margin-top: 14px; color: inherit; opacity: .5; text-decoration: none; }
    </style>
    <?php if ($pwd_enabled): ?>
        <?php if (!$is_logged_in): ?>
            <h1>访问管理</h1>
            <p>请输入密码登录</p>
            <form name="form" action="/include/go.php" method="POST">
                <?php echo csrf_field(); ?>
                <div class="lylme-form-item">
                    <input type="password" autocomplete="new-password" name="pass" required="required" value="" placeholder="密码" autocomplete="off">
                </div>
                <input type="submit" class="lylme-btn" title="登录" value="登录">
            </form>
        <?php else: ?>
            <h1>访问管理</h1>
            <form name="form" action="/include/go.php" method="POST">
                <?php echo csrf_field(); ?>
                <p>欢迎回来，您已登录！<br><br>用户组:
                    <?php foreach ($_SESSION['list'] as $list) { echo (' [' . htmlspecialchars($list, ENT_QUOTES, 'UTF-8') . '] '); } ?>
                </p>
                <input type="hidden" autocomplete="new-password" name="exit" required="required" value="exit">
                <input type="submit" class="lylme-btn" title="注销登录" value="注销登录">
            </form>
        <?php endif; ?>
    <?php else: ?>
        <h2>当前站点未启用链接加密</h2>
    <?php endif; ?>
</div>

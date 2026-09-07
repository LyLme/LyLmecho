<?php
/**
 * 申请收录 - 博客主题精简版(兼容层)
 *
 * 由 article/index.php?route=apply 注入到博客页面渲染。
 * - 自带作用域样式(.lylme-apply-compat), 不撑全屏, 不依赖 bootstrap主题;
 * - 字段 name/id 与 apply/index.php 完全一致, 复用 /apply/apply.js;
 * - 提交/获取接口走 /apply/index.php (apply.js 内 url:"index.php" 在伪静态
 *   /apply 下由浏览器解析为 /apply/index.php, 故无需改动 apply/ 目录)。
 */

if (!isset($site) || !$site) {
    require_once __DIR__ . '/../include/site.php';
}
?>
<div class="lylme-apply-compat">
    <style>
    .lylme-apply-compat { max-width: 560px; margin: 24px auto; font-size: 14px; }
    .lylme-apply-compat * { box-sizing: border-box; }
    .lylme-apply-compat h2 { margin: 0 0 16px; font-size: 20px; text-align: center; }
    .lylme-apply-compat .apply_gg { margin: 0 0 16px; line-height: 1.8; opacity: .7; }
    .lylme-apply-compat .lylme-form-group { margin-bottom: 14px; }
    .lylme-apply-compat label { display: block; margin-bottom: 6px; font-weight: 600; }
    .lylme-apply-compat .lylme-input-row { display: flex; flex-wrap: nowrap; align-items: center; }
    .lylme-apply-compat .lylme-form-control { width: 100%; height: 38px; padding: 0 12px; border: 1px solid rgba(128, 128, 128, .25); border-radius: 6px; font-size: 14px; }
    .lylme-apply-compat .lylme-input-row .lylme-form-control { flex: 1 1 auto; min-width: 0; border-radius: 6px 0 0 6px; border-right: none; }
    .lylme-apply-compat .lylme-input-row .lylme-btn,
    .lylme-apply-compat .lylme-input-row .lylme-captcha { flex: 0 0 auto; height: 38px; border: 1px solid rgba(128, 128, 128, .25); border-left: none; border-radius: 0 6px 6px 0; background: transparent; color: inherit; cursor: pointer; }
    .lylme-apply-compat .lylme-input-row .lylme-btn { padding: 0 14px; white-space: nowrap; }
    .lylme-apply-compat .lylme-input-row .lylme-captcha { display: block; }
    .lylme-apply-compat select.lylme-form-control { padding: 0 8px; }
    .lylme-apply-compat .lylme-help { display: block; margin-top: 6px; font-size: 12px; opacity: .6; }
    .lylme-apply-compat .lylme-btn.lylme-submit { width: 100%; height: 42px; border: 1px solid rgba(128, 128, 128, .35); border-radius: 6px; background: transparent; color: inherit; font-size: 15px; cursor: pointer; }
    .lylme-apply-compat .lylme-home { display: inline-block; margin-top: 8px; color: inherit; opacity: .5; text-decoration: none; }
    .lylme-apply-compat .lylme-home:hover { opacity: .8; }
    .lylme-apply-compat .lylme-review { display: none; margin: 10px auto; width: 100px; height: 100px; }
    #loading { position: fixed; inset: 0; z-index: 9999; display: none; background: rgba(255, 255, 255, .6); }
    #loading img { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
    </style>
    <?php if (isset($conf['apply']) && $conf['apply'] == 2): ?>
        <h2>网站已关闭收录</h2>
        <div class="apply_gg"><?php echo isset($conf['apply_gg']) ? $conf['apply_gg'] : ''; ?></div>
        <center><p><a href="/" class="lylme-home">返回首页</a></p></center>
    <?php else: ?>
        <h2>申请收录</h2>
        <div class="apply_gg"><?php echo isset($conf['apply_gg']) ? $conf['apply_gg'] : ''; ?></div>
        <div class="lylme-form-group">
            <label>*URL链接地址:</label>
            <div class="lylme-input-row">
                <input type="text" class="lylme-form-control" name="url" placeholder="完整链接或域名" value="" onchange="gurl()" required>
                <button class="lylme-btn" onclick="get_url()" type="button">自动获取</button>
            </div>
        </div>
        <div class="lylme-form-group">
            <label>* 选择分组:</label>
            <select title="分组" class="lylme-form-control" name="group_id" required>
                <option value="">请选择</option>
                <?php
                $applygroup = $site->getGroups();
                while ($grouplist = $DB->fetch($applygroup)) {
                ?>
                    <option value="<?php echo $grouplist['group_id']; ?>"><?php echo $grouplist['group_name']; ?></option>
                <?php
                }
                ?>
            </select>
        </div>
        <div class="lylme-form-group">
            <label>* 网站名称:</label>
            <input type="text" class="lylme-form-control" id="title" name="name" value="" required placeholder="网站名称">
            <span class="lylme-help">填写网站名称</span>
        </div>
        <div class="lylme-form-group">
            <label>网站图标:</label>
            <div class="lylme-input-row">
                <input type="text" id="icon" class="lylme-form-control" name="icon" placeholder="填写图标的URL地址">
                <input type="file" id="file" onchange="uploadimg()" accept="image/png, image/jpeg,image/gif,image/x-icon" style="display:none" />
                <button class="lylme-btn" id="uploadImage" onclick="$('#file').click();" type="button">选择</button>
            </div>
            <img id="review" src="" class="lylme-review" />
            <span class="lylme-help">填写图标的<code>URL</code>地址，如：<code>http://www.xxx.com/logo.png</code><br>部分网站无法自动获取，请手动填写</span>
        </div>
        <div class="lylme-form-group">
            <label>* 验证码:</label>
            <div class="lylme-input-row">
                <input type="text" name="authcode" class="lylme-form-control" placeholder="验证码" required>
                <img id="captcha_img" class="lylme-captcha" title="验证码" src="/include/validatecode.php" onclick="recode()" />
            </div>
        </div>
        <div class="lylme-form-group">
            <button class="lylme-btn lylme-submit" onclick="submit()">提交</button>
        </div>
    <?php endif; ?>
</div>
<div id="loading"><img src="/assets/admin/loading.gif" /></div>

<?php
$title = '文章基础设置';
include './head.php';

// 读取文章配置
$configs = array();
$cq = $DB->query("SELECT * FROM `lylme_article_config`");
while ($c = $DB->fetch($cq)) $configs[$c['k']] = $c['v'];

$urlStyle  = isset($configs['article_url_style']) ? $configs['article_url_style'] : 'default';
$urlCustom = isset($configs['article_url_custom']) ? $configs['article_url_custom'] : 'article/post{id}.html';

// 伪静态重写规则 (通用规则集, 覆盖所有风格)
$nginxRules = <<<'EOF'
rewrite ^/site-(\d+)\.html$ /site/index.php?id=$1 last;
rewrite ^/sitemap.xml$ /site/sitemap.php last;

location /article/ {
    try_files $uri $uri/ /article/index.php?rewrite=$uri&$args;
}
EOF;

$apacheRules = <<<'EOF'
RewriteRule ^site-(\d+)\.html$ /site/index.php?id=$1
RewriteRule ^sitemap.xml$ /site/sitemap.php

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^article/(.*)$ /article/index.php?rewrite=$1 [L,QSA]
EOF;

$urlStyles = array(
    'default' => '不启用伪静态 (article/index.php?id=1) (默认)',
    'post_id' => 'article/post1.html  (文章ID风格)',
    'id'      => 'article/1.html  (纯ID风格)',
    'slug'    => 'article/hello-world.html  (别名风格)',
    'custom'  => '自定义模板 (支持 {id} {slug} {year} {month} {day} {title})',
);
?>
<main class="lyear-layout-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h4>文章基础设置</h4>

            <ul class="nav nav-tabs" id="cfgTab" role="tablist">
              <li class="nav-item" role="presentation">
                <a class="nav-link active" id="tab-basic-tab" data-bs-toggle="tab" href="#tab-basic" role="tab" aria-controls="tab-basic" aria-selected="true"><i class="mdi mdi-cog"></i> 基本设置</a>
              </li>
              <li class="nav-item" role="presentation">
                <a class="nav-link" id="tab-member-tab" data-bs-toggle="tab" href="#tab-member" role="tab" aria-controls="tab-member" aria-selected="false"><i class="mdi mdi-account-group"></i> 会员设置</a>
              </li>
              <li class="nav-item" role="presentation">
                <a class="nav-link" id="tab-mail-tab" data-bs-toggle="tab" href="#tab-mail" role="tab" aria-controls="tab-mail" aria-selected="false"><i class="mdi mdi-email-outline"></i> 邮件服务</a>
              </li>
              <li class="nav-item" role="presentation">
                <a class="nav-link" id="tab-link-tab" data-bs-toggle="tab" href="#tab-link" role="tab" aria-controls="tab-link" aria-selected="false"><i class="mdi mdi-link"></i> 链接设置</a>
              </li>
            </ul>

            <div class="tab-content p-t-15">
            <form id="saveConfigForm" action="./ajax_article.php?submit=save_config" method="POST">
            <style>
            .tab-content > form > .tab-pane { display: none; }
            .tab-content > form > .tab-pane.show.active { display: block; }
            </style>

              <!-- ========== 基本设置 ========== -->
              <div class="tab-pane fade show active" id="tab-basic" role="tabpanel" aria-labelledby="tab-basic-tab">

              <div class="form-group">
                <label for="article_web_title">网站标题:</label>
                <input type="text" class="form-control" id="article_web_title" name="article_web_title" placeholder="博客" value="<?php echo htmlspecialchars(isset($configs['article_web_title']) ? $configs['article_web_title'] : '博客', ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="form-group">
                <label for="article_web_description">副标题:</label>
                <input type="text" class="form-control" id="article_web_description" name="article_web_description" placeholder="LyLme Spage Blog" value="<?php echo htmlspecialchars(isset($configs['article_web_description']) ? $configs['article_web_description'] : 'LyLme Spage Blog', ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="form-group">
                <label for="article_web_keywords">关键词:</label>
                <input type="text" class="form-control" id="article_web_keywords" name="article_web_keywords" placeholder="lylme,lylme_spage" value="<?php echo htmlspecialchars(isset($configs['article_web_keywords']) ? $configs['article_web_keywords'] : 'lylme,lylme_spage', ENT_QUOTES, 'UTF-8'); ?>">
                <small class="help-block">多个关键词用英文逗号分隔</small>
              </div>

              <div class="form-group">
                <label>文章模块开关:</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="article_status" value="1"<?php echo (isset($configs['article_status']) && $configs['article_status'] == '1') ? ' checked' : ''; ?>> 开启</label>
                  <label class="radio-inline"><input type="radio" name="article_status" value="0"<?php echo (isset($configs['article_status']) && $configs['article_status'] == '0') ? ' checked' : ''; ?>> 关闭</label>
                </div>
                <small class="help-block">关闭后文章模块将不再对外提供服务</small>
              </div>

              <div class="form-group">
                <label for="article_perpage">每页文章数:</label>
                <input type="number" class="form-control" id="article_perpage" name="article_perpage" min="1" max="100" value="<?php echo isset($configs['article_perpage']) ? intval($configs['article_perpage']) : 10; ?>">
              </div>

              <div class="form-group">
                <label>评论开关:</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="article_comment" value="1"<?php echo (isset($configs['article_comment']) && $configs['article_comment'] == '1') ? ' checked' : ''; ?>> 免登录评论</label>
                  <label class="radio-inline"><input type="radio" name="article_comment" value="2"<?php echo (isset($configs['article_comment']) && $configs['article_comment'] == '2') ? ' checked' : ''; ?>> 仅登录可评论</label>
                  <label class="radio-inline"><input type="radio" name="article_comment" value="0"<?php echo (isset($configs['article_comment']) && $configs['article_comment'] == '0') ? ' checked' : ''; ?>> 关闭评论</label>
                </div>
                <small class="help-block">免登录=游客也可直接评论；仅登录=需登录会员/管理员后才能评论；关闭=隐藏评论表单并禁止提交</small>
              </div>

              <div class="form-group">
                <label>评论审核:</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="article_audit" value="1"<?php echo (isset($configs['article_audit']) && $configs['article_audit'] == '1') ? ' checked' : ''; ?>> 开启</label>
                  <label class="radio-inline"><input type="radio" name="article_audit" value="0"<?php echo (isset($configs['article_audit']) && $configs['article_audit'] == '0') ? ' checked' : ''; ?>> 关闭</label>
                </div>
                <small class="help-block">开启后新评论需在后台审核后才显示</small>
              </div>

              <div class="form-group">
                <label for="article_name">管理员名称:</label>
                <input type="text" class="form-control" id="article_name" name="article_name" placeholder="管理员" value="<?php echo htmlspecialchars(isset($configs['article_name']) ? $configs['article_name'] : '管理员', ENT_QUOTES, 'UTF-8'); ?>">
                <small class="help-block">后台回复评论时对外显示的昵称，避免泄露真实后台账号</small>
              </div>

              </div><!-- /tab-basic -->

              <!-- ========== 会员设置 ========== -->
              <div class="tab-pane fade" id="tab-member" role="tabpanel" aria-labelledby="tab-member-tab">

              <div class="form-group">
                <small class="help-block">以下开关仅作用于文章前台会员体系，与导航后台管理员账号无关</small>
              </div>

              <div class="form-group">
                <label>自助注册:</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="article_member_status" value="1"<?php echo (isset($configs['article_member_status']) && $configs['article_member_status'] == '1') ? ' checked' : ''; ?>> 开放</label>
                  <label class="radio-inline"><input type="radio" name="article_member_status" value="0"<?php echo (!isset($configs['article_member_status']) || $configs['article_member_status'] != '1') ? ' checked' : ''; ?>> 关闭</label>
                </div>
                <small class="help-block">关闭后前台不显示注册入口，会员仅能在"会员管理"中手动添加</small>
              </div>

              <div class="form-group">
                <label>注册邮箱验证:</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="article_member_verify" value="1"<?php echo (isset($configs['article_member_verify']) && $configs['article_member_verify'] == '1') ? ' checked' : ''; ?>> 需验证</label>
                  <label class="radio-inline"><input type="radio" name="article_member_verify" value="0"<?php echo (!isset($configs['article_member_verify']) || $configs['article_member_verify'] != '1') ? ' checked' : ''; ?>> 免验证</label>
                </div>
                <small class="help-block">开启后新注册需点击邮件验证链接方可登录（需正确配置 SMTP；未配置时验证链接会直接显示在前台供手动发送）</small>
              </div>

              <div class="form-group">
                <label>新注册默认角色:</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="article_member_role" value="subscriber"<?php echo (!isset($configs['article_member_role']) || $configs['article_member_role'] != 'contributor') ? ' checked' : ''; ?>> 订阅者(subscriber)</label>
                  <label class="radio-inline"><input type="radio" name="article_member_role" value="contributor"<?php echo (isset($configs['article_member_role']) && $configs['article_member_role'] == 'contributor') ? ' checked' : ''; ?>> 投稿者(contributor)</label>
                </div>
                <small class="help-block">仅决定前台会员身份等级；管理员权限不入库、不由此开关产生。"编辑"角色不会出现在自助注册，需到"会员管理"手动指定</small>
              </div>

              <div class="form-group">
                <label>前台投稿:</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="article_post_status" value="1"<?php echo (isset($configs['article_post_status']) && $configs['article_post_status'] == '1') ? ' checked' : ''; ?>> 开放</label>
                  <label class="radio-inline"><input type="radio" name="article_post_status" value="0"<?php echo (!isset($configs['article_post_status']) || $configs['article_post_status'] != '1') ? ' checked' : ''; ?>> 关闭</label>
                </div>
                <small class="help-block">开启后，投稿者/编辑会员可在会员中心前台投稿；投稿者提交需编辑/管理员审核，编辑可直接发布</small>
              </div>

              </div><!-- /tab-member -->

              <!-- ========== 邮件服务 ========== -->
              <div class="tab-pane fade" id="tab-mail" role="tabpanel" aria-labelledby="tab-mail-tab">

              <div class="form-group">
                <small class="help-block">用于前台会员注册"邮箱验证"发信。未启用或未配置时，验证链接会直接显示在前台供手动发送。修改后先"保存设置"再点"发送测试邮件"。</small>
              </div>

              <div class="form-group">
                <label>邮件发送:</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="article_mail_status" value="1"<?php echo (isset($configs['article_mail_status']) && $configs['article_mail_status'] == '1') ? ' checked' : ''; ?>> 启用 SMTP</label>
                  <label class="radio-inline"><input type="radio" name="article_mail_status" value="0"<?php echo (!isset($configs['article_mail_status']) || $configs['article_mail_status'] != '1') ? ' checked' : ''; ?>> 关闭</label>
                </div>
              </div>

              <div class="form-group">
                <label for="article_mail_host">SMTP 服务器:</label>
                <input type="text" class="form-control" id="article_mail_host" name="article_mail_host" placeholder="如 smtp.qq.com" value="<?php echo htmlspecialchars(isset($configs['article_mail_host']) ? $configs['article_mail_host'] : '', ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="form-group">
                <label for="article_mail_port">端口:</label>
                <input type="number" class="form-control" id="article_mail_port" name="article_mail_port" min="1" max="65535" placeholder="465" value="<?php echo htmlspecialchars(isset($configs['article_mail_port']) ? $configs['article_mail_port'] : '465', ENT_QUOTES, 'UTF-8'); ?>">
                <small class="help-block">SSL 常用 465；STARTTLS 常用 587；无加密常用 25</small>
              </div>

              <div class="form-group">
                <label for="article_mail_secure">加密方式:</label>
                <select class="form-control" id="article_mail_secure" name="article_mail_secure">
                  <?php $secure = isset($configs['article_mail_secure']) ? $configs['article_mail_secure'] : 'ssl'; ?>
                  <option value="ssl"<?php echo ($secure === 'ssl') ? ' selected' : ''; ?>>SSL (隐式, 465)</option>
                  <option value="tls"<?php echo ($secure === 'tls') ? ' selected' : ''; ?>>STARTTLS (587)</option>
                  <option value="none"<?php echo ($secure === 'none') ? ' selected' : ''; ?>>无加密 (25)</option>
                </select>
              </div>

              <div class="form-group">
                <label for="article_mail_user">SMTP 用户名:</label>
                <input type="text" class="form-control" id="article_mail_user" name="article_mail_user" autocomplete="off" placeholder="发信账号（多为邮箱地址）" value="<?php echo htmlspecialchars(isset($configs['article_mail_user']) ? $configs['article_mail_user'] : '', ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="form-group">
                <label for="article_mail_pass">SMTP 密码 / 授权码:</label>
                <input type="password" class="form-control" id="article_mail_pass" name="article_mail_pass" autocomplete="new-password" placeholder="（留空则不修改已保存的密码）" value="">
                <small class="help-block">QQ/163 等邮箱通常需使用"授权码"而非登录密码</small>
              </div>

              <div class="form-group">
                <label for="article_mail_from">发件邮箱:</label>
                <input type="text" class="form-control" id="article_mail_from" name="article_mail_from" placeholder="your@mail.com" value="<?php echo htmlspecialchars(isset($configs['article_mail_from']) ? $configs['article_mail_from'] : '', ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="form-group">
                <label for="article_mail_from_name">发件显示名:</label>
                <input type="text" class="form-control" id="article_mail_from_name" name="article_mail_from_name" placeholder="留空则使用管理员名称" value="<?php echo htmlspecialchars(isset($configs['article_mail_from_name']) ? $configs['article_mail_from_name'] : '', ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="form-group">
                <label>发送测试邮件:</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="mailTestTo" placeholder="收件邮箱">
                  <span class="input-group-btn"><button type="button" class="btn btn-default" id="mailTestBtn">发送测试邮件</button></span>
                </div>
                <small class="help-block" id="mailTestResult"></small>
              </div>

              </div><!-- /tab-mail -->

              <!-- ========== 链接设置 ========== -->
              <div class="tab-pane fade" id="tab-link" role="tabpanel" aria-labelledby="tab-link-tab">

              <div class="form-group">
                <label>文章链接格式:</label>
                <div>
                  <?php foreach ($urlStyles as $val => $label): ?>
                    <label class="radio-inline">
                      <input type="radio" name="article_url_style" value="<?php echo $val; ?>"<?php echo ($urlStyle === $val) ? ' checked' : ''; ?>>
                      <?php echo $label; ?>
                    </label>
                    <br>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="form-group" id="urlCustomGroup" style="display:<?php echo $urlStyle === 'custom' ? 'block' : 'none'; ?>;">
                <label for="article_url_custom">自定义 URL 模板:</label>
                <input type="text" class="form-control" id="article_url_custom" name="article_url_custom" placeholder="article/post{id}.html" value="<?php echo htmlspecialchars($urlCustom, ENT_QUOTES, 'UTF-8'); ?>">
                <small class="help-block">
                  支持占位符: <code>{id}</code> 文章ID, <code>{slug}</code> 文章别名, <code>{title}</code> 文章标题(URL编码), <code>{year}</code> <code>{month}</code> <code>{day}</code> 发布时间年月日<br>
                  示例: <code>article/post{id}.html</code> &nbsp; <code>article/{slug}.html</code> &nbsp; <code>article/{year}/{month}/{id}.html</code> &nbsp; <code>{id}.html</code>
                </small>
              </div>

              <div class="form-group">
                <label>链接效果预览:</label>
                <pre class="alert alert-info mb-1" id="urlPreview" style="margin-bottom:8px;">article/post1.html</pre>
              </div>

              <div class="form-group" id="rewriteHint" style="display:<?php echo $urlStyle === 'default' ? 'none' : 'block'; ?>;">
                <label>伪静态重写规则 (服务器配置)</label>
                <p class="help-block">选择非"不启用伪静态"风格后，需要将对应规则加入 Web 服务器配置，规则见下方；<code>nginx.htaccess</code> 与 <code>.htaccess</code> 文件已内置。</p>

                <ul class="nav nav-tabs" role="tablist">
                  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabNginx">Nginx</a></li>
                  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabApache">Apache</a></li>
                </ul>
                <div class="tab-content">
                  <div class="tab-pane fade show active" id="tabNginx">
                    <pre class="alert alert-warning mt-1" id="nginxRules" style="white-space:pre-wrap;"><?php echo htmlspecialchars($nginxRules, ENT_QUOTES, 'UTF-8'); ?></pre>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-copy="#nginxRules">复制 Nginx 规则</button>
                  </div>
                  <div class="tab-pane fade" id="tabApache">
                    <pre class="alert alert-warning mt-1" id="apacheRules" style="white-space:pre-wrap;"><?php echo htmlspecialchars($apacheRules, ENT_QUOTES, 'UTF-8'); ?></pre>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-copy="#apacheRules">复制 Apache 规则</button>
                  </div>
                </div>
              </div>

              </div><!-- /tab-link -->

            </form>

            <div class="form-group" style="margin-top:16px">
              <input type="submit" class="btn btn-primary btn-block" value="保存设置" form="saveConfigForm">
            </div>

            </div><!-- /tab-content -->
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include './footer.php'; ?>

<script type="text/javascript">
  // 自定义 Tab 切换（Bootstrap Tab 组件在 tab-pane 非 tab-content 直接子元素时会出错）
  (function() {
    var tabNav = document.getElementById('cfgTab');
    if (!tabNav) return;
    var tabs = tabNav.querySelectorAll('.nav-link');
    var panes = document.querySelectorAll('#saveConfigForm > .tab-pane');
    tabs.forEach(function(tab) {
      tab.addEventListener('click', function(e) {
        e.preventDefault();
        // 移除所有 tab 和 pane 的激活状态
        tabs.forEach(function(t) { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
        panes.forEach(function(p) { p.classList.remove('active', 'show'); });
        // 激活当前 tab
        this.classList.add('active');
        this.setAttribute('aria-selected', 'true');
        var target = document.querySelector(this.getAttribute('href'));
        if (target) { target.classList.add('active', 'show'); }
      });
    });
  })();

  // 链接效果预览
  var urlCustomGroup = document.getElementById('urlCustomGroup');
  var urlPreview = document.getElementById('urlPreview');
  var rewriteHint = document.getElementById('rewriteHint');

  function previewUrl(style) {
    var id = 1, slug = 'hello-world', title = 'Hello%20World', year = '2026', month = '05', day = '01';
    var custom = document.getElementById('article_url_custom').value.trim();
    if (style === 'custom' && custom === '') custom = 'article/post{id}.html';

    var url = 'article/index.php?id=' + id;
    switch (style) {
      case 'post_id': url = 'article/post' + id + '.html'; break;
      case 'id': url = 'article/' + id + '.html'; break;
      case 'slug': url = 'article/' + slug + '.html'; break;
      case 'custom':
        url = custom
          .replace(/\{id\}/g, id)
          .replace(/\{slug\}/g, slug)
          .replace(/\{title\}/g, title)
          .replace(/\{year\}/g, year)
          .replace(/\{month\}/g, month)
          .replace(/\{day\}/g, day);
        break;
    }
    urlPreview.textContent = '文章: ' + url + '    分类: ' + (style === 'default' ? 'article/index.php?cat=demo' : 'article/category-demo.html');
  }

  // 风格切换
  var radios = document.querySelectorAll('input[name="article_url_style"]');
  for (var i = 0; i < radios.length; i++) {
    radios[i].addEventListener('change', function () {
      var style = this.value;
      urlCustomGroup.style.display = (style === 'custom') ? 'block' : 'none';
      rewriteHint.style.display = (style === 'default') ? 'none' : 'block';
      previewUrl(style);
    });
  }

  // 自定义模板实时预览
  document.getElementById('article_url_custom').addEventListener('input', function () {
    if (document.querySelector('input[name="article_url_style"]:checked').value === 'custom') {
      previewUrl('custom');
    }
  });

  // 复制规则
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var pre = document.querySelector(btn.getAttribute('data-copy'));
      var text = pre.textContent;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () { alert('已复制'); });
      } else {
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        alert('已复制');
      }
    });
  });

  // 保存设置 AJAX 提交（阻止默认跳转，弹窗显示服务端返回）
  (function () {
    var form = document.getElementById('saveConfigForm');
    if (!form) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.action, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
          try {
            var res = JSON.parse(xhr.responseText);
            alert(res.msg);
            if (res.code === 200) location.reload();
          } catch (e) {
            alert('保存失败，请重试');
          }
        }
      };
      xhr.send(new FormData(form));
    });
  })();

  previewUrl('<?php echo $urlStyle; ?>');

  // 发送测试邮件（使用已保存的 SMTP 配置）
  (function () {
    var btn = document.getElementById('mailTestBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var to = document.getElementById('mailTestTo').value.trim();
      var result = document.getElementById('mailTestResult');
      if (to === '') { result.textContent = '请先填写收件邮箱'; return; }
      btn.disabled = true;
      result.textContent = '发送中…';
      var xhr = new XMLHttpRequest();
      xhr.open('POST', './ajax_article.php?submit=test_mail', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          btn.disabled = false;
          if (xhr.status === 200) {
            try { result.textContent = JSON.parse(xhr.responseText).msg; }
            catch (e) { result.textContent = '响应解析失败'; }
          } else {
            result.textContent = '请求失败';
          }
        }
      };
      xhr.send('to=' + encodeURIComponent(to));
    });
  })();
</script>

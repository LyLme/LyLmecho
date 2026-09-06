<?php
$title = '文章插件管理';
include './head.php';

// 读取文章配置
$configs = array();
$cq = $DB->query("SELECT * FROM `lylme_article_config`");
while ($c = $DB->fetch($cq)) $configs[$c['k']] = $c['v'];

// 初始化 Typecho 兼容层 (仅需类定义与 PluginManager)
if (!defined('__TYPECHO_ROOT_DIR__')) {
    define('__TYPECHO_ROOT_DIR__', dirname(__DIR__) . '/article/');
}
require_once dirname(__DIR__) . '/article/compat/bootstrap.php';

// 扫描插件目录
$plugins = \Compat\PluginManager::scan();

// ============================================================
// 插件配置子页 (?set=plugin&plugin=Name)
// 调用插件的 config($form) 渲染配置表单, 保存至 article/config/plugins/{plugin}.json
// ============================================================
if (isset($_GET['set']) && $_GET['set'] === 'plugin') {
    $currentPlugin = \Compat\PluginManager::sanitize(isset($_GET['plugin']) ? $_GET['plugin'] : '');
    if ($currentPlugin === '' || !isset($plugins[$currentPlugin])) {
        echo '<div class="alert alert-danger">插件不存在</div>';
        include './footer.php';
        exit;
    }

    $form = \Compat\PluginManager::buildConfigForm($currentPlugin);
    $items = $form ? $form->getItems() : array();
    $savedConfig = \Compat\PluginManager::getConfig($currentPlugin);

    /**
     * 将单个表单元素渲染为 HTML (与主题设置共用逻辑)
     */
    function lylme_render_plugin_element($element, $saved = null)
    {
        $name  = $element->getName();
        $label = $element->getLabel();
        $desc  = $element->getDescription();
        $type  = $element->type;
        $value = ($saved !== null && $saved !== '' && $saved !== array()) ? $saved : $element->getValue();
        $html  = '';

        switch ($type) {
            case 'text':
            case 'password':
                $inputType = ($type === 'password') ? 'password' : 'text';
                $html = '<input type="' . $inputType . '" class="form-control" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
                    . '" value="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
                break;

            case 'textarea':
                $html = '<textarea class="form-control" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" rows="4">'
                    . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</textarea>';
                break;

            case 'checkbox':
            case 'radio':
                $inputName    = $name . ($element->multiMode ? '[]' : '');
                $checkedList  = is_array($value) ? $value : array($value);
                $html = '<div>';
                foreach ((array)$element->getOptions() as $key => $optLabel) {
                    $checked = in_array($key, $checkedList) ? ' checked' : '';
                    $html .= '<label class="checkbox-inline" style="margin-right:15px;font-weight:normal;">'
                        . '<input type="' . $type . '" name="' . htmlspecialchars($inputName, ENT_QUOTES, 'UTF-8')
                        . '" value="' . htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') . '"' . $checked . '> '
                        . htmlspecialchars((string)$optLabel, ENT_QUOTES, 'UTF-8') . '</label>';
                }
                $html .= '</div>';
                break;

            case 'select':
                $html = '<select class="form-control" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
                foreach ((array)$element->getOptions() as $key => $optLabel) {
                    $selected = ((string)$value === (string)$key) ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>'
                        . htmlspecialchars((string)$optLabel, ENT_QUOTES, 'UTF-8') . '</option>';
                }
                $html .= '</select>';
                break;

            case 'hidden':
                $html = '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="'
                    . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
                return $html;

            case 'submit':
                return '<div class="form-group"><button type="submit" class="btn btn-primary">'
                    . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</button></div>';

            default:
                $html = '<input type="text" class="form-control" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="'
                    . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
        }

        return '<div class="form-group">'
            . '<label class="control-label">' . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</label>'
            . $html
            . ($desc !== '' ? '<small class="help-block">' . htmlspecialchars((string)$desc, ENT_QUOTES, 'UTF-8') . '</small>' : '')
            . '</div>';
    }
    ?>
    <main class="lyear-layout-content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-body">
                <h4>插件设置：<?php echo htmlspecialchars($currentPlugin); ?></h4>
                <div class="alert alert-info">
                  <div class="alert-stat">
                    <div>当前插件：<b><?php echo htmlspecialchars($currentPlugin); ?></b>，配置保存文件：<code>article/config/plugins/<?php echo htmlspecialchars($currentPlugin); ?>.json</code></div>
                    <a href="./article_plugin.php" class="btn btn-default btn-sm">返回插件列表</a>
                  </div>
                </div>

                <?php if (empty($items)): ?>
                  <div class="alert alert-warning">该插件未定义可自定义的配置项（config() 方法为空）。</div>
                <?php else: ?>
                  <form id="pluginConfigForm" action="./ajax_article.php?submit=save_plugin_config" method="POST">
                    <input type="hidden" name="plugin" value="<?php echo htmlspecialchars($currentPlugin, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php
                    foreach ($items as $item) {
                        $key = $item->getName();
                        $saved = array_key_exists($key, $savedConfig) ? $savedConfig[$key] : null;
                        echo lylme_render_plugin_element($item, $saved);
                    }
                    ?>
                    <div class="form-group">
                      <button type="submit" class="btn btn-primary">保存设置</button>
                      <a href="./article_plugin.php" class="btn btn-default">返回</a>
                    </div>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include './footer.php'; ?>
    <script type="text/javascript">
      (function () {
        var form = document.getElementById('pluginConfigForm');
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
              } catch (e) { alert('保存失败，请重试'); }
            }
          };
          xhr.send(new FormData(form));
        });
      })();
    </script>
    <?php exit; ?>
<?php } ?>

<main class="lyear-layout-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h4>文章插件管理</h4>
            <div class="alert alert-info">
              <div class="alert-stat">
                <div><i class="mdi mdi-bell-ring-outline mdi-alert-icon"></i>当前共： <b><?php echo count($plugins); ?></b> 个插件</div>
                <a href="./article_theme.php" class="btn btn-default btn-sm">主题设置</a>
              </div>
              <div class="mt-2">插件目录：<code>article/plugins/</code>，启用状态保存在 <code>lylme_article_config.article_plugins</code></div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th>插件</th>
                    <th>版本</th>
                    <th>作者</th>
                    <th>描述</th>
                    <th>状态</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($plugins)): ?>
                    <tr><td colspan="6" class="text-center">未检测到插件，请将 Typecho 插件放入 article/plugins/{插件名}/Plugin.php</td></tr>
                  <?php else: ?>
                    <?php foreach ($plugins as $p): ?>
                      <tr>
                        <td><b><?php echo htmlspecialchars($p['name']); ?></b><br><small class="text-muted"><?php echo htmlspecialchars($p['dir']); ?></small></td>
                        <td><?php echo htmlspecialchars($p['version']); ?></td>
                        <td><?php echo htmlspecialchars($p['author']); ?></td>
                        <td><?php echo htmlspecialchars($p['description']); ?></td>
                        <td>
                          <?php if ($p['activated']): ?>
                            <span class="label label-success">已启用</span>
                          <?php else: ?>
                            <span class="label label-default">未启用</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($p['activated']): ?>
                            <button class="btn btn-warning btn-xs btn-plugin" data-action="deactivate" data-plugin="<?php echo htmlspecialchars($p['dir'], ENT_QUOTES, 'UTF-8'); ?>">禁用</button>
                          <?php else: ?>
                            <button class="btn btn-success btn-xs btn-plugin" data-action="activate" data-plugin="<?php echo htmlspecialchars($p['dir'], ENT_QUOTES, 'UTF-8'); ?>">启用</button>
                          <?php endif; ?>
                          <?php if ($p['hasConfig']): ?>
                            <a class="btn btn-info btn-xs" href="./article_plugin.php?set=plugin&plugin=<?php echo htmlspecialchars($p['dir'], ENT_QUOTES, 'UTF-8'); ?>">设置</a>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include './footer.php'; ?>

<script type="text/javascript">
  // 启用 / 禁用插件 (AJAX 提交到 ajax_article.php)
  (function () {
    var btns = document.querySelectorAll('.btn-plugin');
    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var action = btn.getAttribute('data-action');
        var plugin = btn.getAttribute('data-plugin');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', './ajax_article.php?submit=' + action + '_plugin', true);
        var fd = new FormData();
        fd.append('plugin', plugin);
        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            try {
              var res = JSON.parse(xhr.responseText);
              alert(res.msg);
              if (res.code === 200) location.reload();
            } catch (e) { alert('操作失败，请重试'); }
          }
        };
        xhr.send(fd);
      });
    });
  })();
</script>

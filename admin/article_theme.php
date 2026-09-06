<?php
$title = '文章主题设置';
include './head.php';

// 读取文章配置
$configs = array();
$cq = $DB->query("SELECT * FROM `lylme_article_config`");
while ($c = $DB->fetch($cq)) $configs[$c['k']] = $c['v'];


// 扫描 article/theme 目录下的主题 (仅目录, 排除 error.php 等文件)
$themeDir  = realpath(__DIR__ . '/../article/theme');
$themes    = array();
if ($themeDir !== false && is_dir($themeDir)) {
    foreach (glob($themeDir . '/*', GLOB_ONLYDIR) as $dir) {
        $themes[] = basename($dir);
    }
}
sort($themes);
if (empty($themes)) $themes = array('typecho');


// ============================================================
// 主题自定义设置 (?set=theme)
// 渲染 article/theme/{theme}/functions.php 中的 themeConfig($form)
// 配置保存文件: article/config/theme/{theme}.json (保存功能后续实现)
// ============================================================
if (isset($_GET['set']) && $_GET['set'] === 'theme') {

    // 当前主题名
    $currentTheme = isset($configs['article_theme']) ? trim($configs['article_theme']) : 'typecho';
    $currentTheme = preg_replace('/[^a-zA-Z0-9_-]/', '', $currentTheme);
    if ($currentTheme === '') $currentTheme = 'typecho';

    // 初始化 Typecho 兼容层 (仅需类定义与 _t/_e, 无需 App::init)
    if (!defined('__TYPECHO_ROOT_DIR__')) {
        define('__TYPECHO_ROOT_DIR__', dirname(__DIR__) . '/article/');
    }
    require_once dirname(__DIR__) . '/article/compat/bootstrap.php';

    // 主题 functions.php 与已保存配置
    $themeFunctions = dirname(__DIR__) . '/article/theme/' . $currentTheme . '/functions.php';
    $savedConfig = array();
    $configJson = dirname(__DIR__) . '/article/config/theme/' . $currentTheme . '.json';
    if (is_file($configJson)) {
        $json = json_decode((string)file_get_contents($configJson), true);
        if (is_array($json)) $savedConfig = $json;
    }

    // 调用主题的 themeConfig 构建配置表单
    $form = new \Typecho\Widget\Helper\Form();
    if (is_file($themeFunctions)) {
        require_once $themeFunctions;
        if (function_exists('themeConfig')) {
            themeConfig($form);
        }
    }
    $items = $form->getItems();

    /**
     * 将单个表单元素渲染为 HTML
     * @param \Typecho\Widget\Helper\Form\Element\AbstractElement $element
     * @param mixed $saved 已保存的配置值
     */
    function lylme_render_theme_element($element, $saved = null)
    {
        $name  = $element->getName();
        $label = $element->getLabel();
        $desc  = $element->getDescription();
        $type  = $element->type;
        $value = ($saved !== null && $saved !== '') ? $saved : $element->getValue();
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
                <h4>主题自定义设置</h4>

                <div class="alert alert-info">
                  <div class="alert-stat">
                    <div><i class="mdi mdi-bell-ring-outline mdi-alert-icon"></i>当前主题：<b><?php echo htmlspecialchars($currentTheme); ?></b>，配置保存文件：<code>article/config/theme/<?php echo htmlspecialchars($currentTheme); ?>.json</code></div>
                    <a href="./article_theme.php" class="btn btn-default btn-sm">返回主题列表</a>
                  </div>
                </div>

                <?php if (empty($items)): ?>
                  <div class="alert alert-warning">当前主题未定义可自定义的配置项（functions.php 中缺少 themeConfig 函数）。</div>
                <?php else: ?>
                  <form id="themeConfigForm" action="./ajax_article.php?submit=save_theme_config" method="POST">
                    <input type="hidden" name="theme" value="<?php echo htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php
                    foreach ($items as $item) {
                        // 主题可通过 $form->addItem() 插入非表单元素 (如 Printer 的分组标题 Layout),
                        // 这类对象没有 getName(), 直接输出其 HTML, 否则调用 getName() 会致命
                        if (!is_object($item) || !method_exists($item, 'getName')) {
                            if (is_object($item) && method_exists($item, 'render')) {
                                $item->render();
                            }
                            continue;
                        }
                        $key = $item->getName();
                        $saved = array_key_exists($key, $savedConfig) ? $savedConfig[$key] : null;
                        echo lylme_render_theme_element($item, $saved);
                    }
                    ?>
                    <div class="form-group">
                      <button type="submit" class="btn btn-primary">保存设置</button>
                      <a href="./article_theme.php" class="btn btn-default">返回</a>
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
      // 保存主题自定义设置 (AJAX 提交到 ajax_article.php?submit=save_theme_config)
      (function () {
        var form = document.getElementById('themeConfigForm');
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
    </script>
    <?php exit; ?>
<?php } ?>


<main class="lyear-layout-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h4>文章主题设置</h4>

            <div class="alert alert-info">
                        <div class="alert-stat">
                            <div><i class="mdi mdi-bell-ring-outline mdi-alert-icon"></i>当前共： <b><?php echo count($themes); ?></b> 个主题</div>
                            <a href="./article_theme.php?set=theme" class="btn btn-primary btn-sm">主题自定义设置</a>
                        </div>
                        <div class="mt-2">文章首页地址：<code><?php echo siteurl(); ?>/article</code> <a href="<?php echo siteurl(); ?>/article" target="_blank">访问</a></div>
                  </div>

            <form id="saveConfigForm" action="./ajax_article.php?submit=save_config" method="POST">

              <div class="form-group">
                <label for="article_theme">文章主题:</label>
                <select class="form-control" id="article_theme" name="article_theme">
                  <?php $currentTheme = isset($configs['article_theme']) ? $configs['article_theme'] : 'typecho'; ?>
                  <?php foreach ($themes as $theme): ?>
                  <option value="<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($theme == $currentTheme) ? ' selected' : ''; ?>><?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="help-block">主题目录位于 article/theme 下</small>
              </div>

              <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="保存设置">
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include './footer.php'; ?>

<script type="text/javascript">
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
</script>
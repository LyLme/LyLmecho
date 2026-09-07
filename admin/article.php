<?php
$title = '文章管理';
include './head.php';

// 预取分类列表（新增/编辑/列表共用）
$catlists = array();
$cq = $DB->query("SELECT * FROM `lylme_article_cat` ORDER BY `cat_order` ASC");
while ($c = $DB->fetch($cq)) $catlists[] = $c;

$set = isset($_GET['set']) ? $_GET['set'] : null;
?>
<main class="lyear-layout-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">

<?php if ($set == 'add'): ?>
            <h4>新增文章</h4>
            <form id="addArticleForm" action="./ajax_article.php?submit=add_article" method="POST">
              <div class="form-group">
                <label for="add_title">*文章标题:</label>
                <input type="text" class="form-control" id="add_title" name="art_title" placeholder="文章标题" required>
              </div>

              <div class="form-group">
                <label for="add_cat">*所属分类:</label>
                <select class="form-control" id="add_cat" name="cat_id">
                  <option value="0">未分类</option>
                  <?php foreach ($catlists as $catlist): ?>
                    <option value="<?php echo $catlist['cat_id']; ?>"><?php echo $catlist['cat_id']; ?> - <?php echo $catlist['cat_name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="add_slug">文章别名:</label>
                <input type="text" class="form-control" id="add_slug" name="art_slug" placeholder="URL友好别名，可留空">
                <small class="help-block">用于 URL 友好的别名，可留空</small>
              </div>

              <div class="form-group">
                <label for="add_author">作者:</label>
                <input type="text" class="form-control" id="add_author" name="art_author" value="管理员" placeholder="作者">
              </div>

              <div class="form-group">
                <label for="add_cover">封面图地址:</label>
                <input type="text" class="form-control" id="add_cover" name="art_cover" placeholder="封面图URL，可留空">
              </div>

              <div class="form-group">
                <label for="add_excerpt">文章摘要:</label>
                <textarea rows="2" class="form-control" id="add_excerpt" name="art_excerpt" placeholder="文章摘要，可留空"></textarea>
              </div>

              <div class="form-group">
                <label for="add_keywords">SEO关键词:</label>
                <input type="text" class="form-control" id="add_keywords" name="art_keywords" placeholder="多个关键词用逗号分隔">
              </div>

              <div class="form-group">
                <label for="add_description">SEO描述:</label>
                <input type="text" class="form-control" id="add_description" name="art_description" placeholder="SEO描述，可留空">
              </div>

              <div class="form-group">
                <label for="add_content">*文章内容:</label>
                <textarea rows="12" class="form-control" id="add_content" name="art_content" placeholder="支持HTML内容" required style="display:none"></textarea>
                <div id="vditorAdd"></div>
              </div>

              <div class="form-group" style="display: none;">
                <label for="add_time">发布时间:</label>
                <input type="datetime-local" class="form-control" id="add_time" name="art_time">
                <small class="help-block">留空则使用当前时间</small>
              </div>

              <div class="form-group">
                <label>是否置顶:</label>
                <label class="radio-inline"><input type="radio" name="art_top" value="1"> 是</label>
                <label class="radio-inline"><input type="radio" name="art_top" value="0" checked> 否</label>
              </div>

              <div class="form-group">
                <label>文章状态:</label>
                <label class="radio-inline"><input type="radio" name="art_status" value="1" checked> 发布</label>
                <label class="radio-inline"><input type="radio" name="art_status" value="0"> 草稿</label>
              </div>

              <div class="form-group">
                <label>是否允许评论:</label>
                <label class="radio-inline"><input type="radio" name="art_allow_comment" value="1" checked> 允许</label>
                <label class="radio-inline"><input type="radio" name="art_allow_comment" value="0"> 不允许</label>
              </div>

              <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="添加">
              </div>
            </form>
            <br><a href="./article.php"><<返回</a>

<?php elseif ($set == 'edit'): ?>
            <?php
            $id = intval($_GET['id']);
            $row2 = $DB->query("select * from lylme_article where art_id='$id' limit 1");
            $row = $DB->fetch($row2);
            if (!$row) exit('该条记录不存在！');
            $edit_time = str_replace(' ', 'T', $row['art_time']);
            ?>
            <h4>修改文章信息</h4>
            <form id="editArticleForm" action="./ajax_article.php?submit=edit_article&id=<?php echo $id; ?>" method="POST">
              <div class="form-group">
                <label for="edit_title">*文章标题:</label>
                <input type="text" class="form-control" id="edit_title" name="art_title" value="<?php echo htmlspecialchars($row['art_title']); ?>" required>
              </div>

              <div class="form-group">
                <label for="edit_cat">*所属分类:</label>
                <select class="form-control" id="edit_cat" name="cat_id">
                  <option value="0"<?php echo $row['cat_id'] == 0 ? ' selected' : ''; ?>>未分类</option>
                  <?php foreach ($catlists as $catlist): ?>
                    <option value="<?php echo $catlist['cat_id']; ?>"<?php echo $row['cat_id'] == $catlist['cat_id'] ? ' selected' : ''; ?>><?php echo $catlist['cat_id']; ?> - <?php echo $catlist['cat_name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="edit_slug">文章别名:</label>
                <input type="text" class="form-control" id="edit_slug" name="art_slug" value="<?php echo htmlspecialchars($row['art_slug']); ?>">
              </div>

              <div class="form-group">
                <label for="edit_author">作者:</label>
                <input type="text" class="form-control" id="edit_author" name="art_author" value="<?php echo htmlspecialchars($row['art_author']); ?>">
              </div>

              <div class="form-group">
                <label for="edit_cover">封面图地址:</label>
                <input type="text" class="form-control" id="edit_cover" name="art_cover" value="<?php echo htmlspecialchars($row['art_cover']); ?>">
              </div>

              <div class="form-group">
                <label for="edit_excerpt">文章摘要:</label>
                <textarea rows="2" class="form-control" id="edit_excerpt" name="art_excerpt"><?php echo htmlspecialchars($row['art_excerpt']); ?></textarea>
              </div>

              <div class="form-group">
                <label for="edit_keywords">SEO关键词:</label>
                <input type="text" class="form-control" id="edit_keywords" name="art_keywords" value="<?php echo htmlspecialchars($row['art_keywords']); ?>">
              </div>

              <div class="form-group">
                <label for="edit_description">SEO描述:</label>
                <input type="text" class="form-control" id="edit_description" name="art_description" value="<?php echo htmlspecialchars($row['art_description']); ?>">
              </div>

              <div class="form-group">
                <label for="edit_content">*文章内容:</label>
                <textarea id="edit_content" name="art_content" class="form-control" rows="150" style="display:none" required><?php echo htmlspecialchars($row['art_content']); ?></textarea>
                <div id="vditor"></div>
              </div>

              <div class="form-group">
                <label for="edit_time">发布时间:</label>
                <input type="datetime-local" class="form-control" id="edit_time" name="art_time" value="<?php echo $edit_time; ?>">
              </div>

              <div class="form-group">
                <label>是否置顶:</label>
                <label class="radio-inline"><input type="radio" name="art_top" value="1"<?php echo $row['art_top'] == 1 ? ' checked' : ''; ?>> 是</label>
                <label class="radio-inline"><input type="radio" name="art_top" value="0"<?php echo $row['art_top'] == 0 ? ' checked' : ''; ?>> 否</label>
              </div>

              <div class="form-group">
                <label>文章状态:</label>
                <label class="radio-inline"><input type="radio" name="art_status" value="1"<?php echo $row['art_status'] == 1 ? ' checked' : ''; ?>> 发布</label>
                <label class="radio-inline"><input type="radio" name="art_status" value="0"<?php echo $row['art_status'] == 0 ? ' checked' : ''; ?>> 草稿</label>
              </div>

              <div class="form-group">
                <label>是否允许评论:</label>
                <label class="radio-inline"><input type="radio" name="art_allow_comment" value="1"<?php echo $row['art_allow_comment'] == 1 ? ' checked' : ''; ?>> 允许</label>
                <label class="radio-inline"><input type="radio" name="art_allow_comment" value="0"<?php echo $row['art_allow_comment'] == 0 ? ' checked' : ''; ?>> 不允许</label>
              </div>

              <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="修改">
              </div>
            </form>
            <br><a href="./article.php"><<返回</a>

<?php elseif ($set == 'page'): ?>
            <h4>独立页面管理</h4>
            <div id="toolbar" class="toolbar-btn-action mb-2">
              <a href="./article.php?set=page_add" class="btn btn-primary btn-label">
                <label><i class="mdi mdi-plus" aria-hidden="true"></i></label>新增页面</a>
              <button id="btn_on_page" type="button" class="btn btn-success btn-label" onclick="on_page()">
                <label><i class="mdi mdi-check" aria-hidden="true"></i></label>发布</button>
              <button id="btn_off_page" type="button" class="btn btn-warning btn-label" onclick="off_page()">
                <label><i class="mdi mdi-block-helper" aria-hidden="true"></i></label>转草稿</button>
              <button id="btn_delete_page" type="button" class="btn btn-danger btn-label" onclick="del_page()">
                <label><i class="mdi mdi-window-close" aria-hidden="true"></i></label>删除</button>
              <a href="./article.php" class="btn btn-default btn-label">
                <label><i class="mdi mdi-arrow-left" aria-hidden="true"></i></label>返回文章</a>
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="pagelisttbody">
                <thead>
                  <tr>
                    <th><input type="checkbox" class="checkbox-parent" id="check_all_page" onclick="check_all_page()"></th>
                    <th>ID</th>
                    <th>标题</th>
                    <th>别名</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $prs = $DB->query("SELECT * FROM `lylme_article_page` ORDER BY `page_order` ASC, `page_id` ASC");
                  while ($p = $DB->fetch($prs)) {
                  ?>
                    <tr>
                      <td><input type="checkbox" name="page-check" value="<?php echo $p['page_id']; ?>"></td>
                      <td><?php echo $p['page_id']; ?></td>
                      <td><?php echo htmlspecialchars($p['page_title']); ?></td>
                      <td><?php echo htmlspecialchars($p['page_slug']); ?></td>
                      <td><?php echo $p['page_order']; ?></td>
                      <td><?php if ($p['page_status'] == '0'): ?><font color="red">草稿</font><?php else: ?><font color="green">发布</font><?php endif; ?></td>
                      <td>
                        <a href="./article.php?set=page_edit&id=<?php echo $p['page_id']; ?>" class="btn btn-info btn-xs">编辑</a>&nbsp;
                        <a href="/article/index.php?page=<?php echo urlencode($p['page_slug']); ?>" target="_blank" class="btn btn-default btn-xs">预览</a>&nbsp;
                        <button class="btn btn-danger btn-xs" onclick="del_page('<?php echo $p['page_id']; ?>')">删除</button>
                      </td>
                    </tr>
                  <?php
                  }
                  ?>
                </tbody>
              </table>
            </div>

<?php elseif ($set == 'page_add'): ?>
            <h4>新增独立页面</h4>
            <form id="addPageForm" action="./ajax_article.php?submit=add_page" method="POST">
              <div class="form-group">
                <label for="add_page_title">*页面标题:</label>
                <input type="text" class="form-control" id="add_page_title" name="page_title" placeholder="页面标题" required>
              </div>
              <div class="form-group">
                <label for="add_page_slug">URL别名:</label>
                <input type="text" class="form-control" id="add_page_slug" name="page_slug" placeholder="如 about，留空则使用标题">
                <small class="help-block">用于页面链接 ?page=别名 (伪静态为 /page/别名.html)</small>
              </div>
              <div class="form-group">
                <label for="add_page_order">排序:</label>
                <input type="number" class="form-control" id="add_page_order" name="page_order" value="10">
              </div>
              <div class="form-group">
                <label for="add_page_excerpt">页面摘要:</label>
                <textarea rows="2" class="form-control" id="add_page_excerpt" name="page_excerpt" placeholder="页面摘要，可留空"></textarea>
              </div>
              <div class="form-group">
                <label for="add_content">*页面内容:</label>
                <textarea rows="12" class="form-control" id="add_content" name="page_content" placeholder="支持HTML内容" required style="display:none"></textarea>
                <div id="vditorAdd"></div>
              </div>
              <div class="form-group">
                <label>状态:</label>
                <label class="radio-inline"><input type="radio" name="page_status" value="1" checked> 发布</label>
                <label class="radio-inline"><input type="radio" name="page_status" value="0"> 草稿</label>
              </div>
              <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="添加">
              </div>
            </form>
            <br><a href="./article.php?set=page"><<返回</a>

<?php elseif ($set == 'page_edit'): ?>
            <?php
            $pid = intval($_GET['id']);
            $prow = $DB->get_row("SELECT * FROM `lylme_article_page` WHERE `page_id` = '{$pid}' LIMIT 1");
            if (!$prow) exit('该页面不存在！');
            ?>
            <h4>编辑独立页面</h4>
            <form id="editPageForm" action="./ajax_article.php?submit=edit_page&id=<?php echo $pid; ?>" method="POST">
              <div class="form-group">
                <label for="edit_page_title">*页面标题:</label>
                <input type="text" class="form-control" id="edit_page_title" name="page_title" value="<?php echo htmlspecialchars($prow['page_title']); ?>" required>
              </div>
              <div class="form-group">
                <label for="edit_page_slug">URL别名:</label>
                <input type="text" class="form-control" id="edit_page_slug" name="page_slug" value="<?php echo htmlspecialchars($prow['page_slug']); ?>">
              </div>
              <div class="form-group">
                <label for="edit_page_order">排序:</label>
                <input type="number" class="form-control" id="edit_page_order" name="page_order" value="<?php echo $prow['page_order']; ?>">
              </div>
              <div class="form-group">
                <label for="edit_page_excerpt">页面摘要:</label>
                <textarea rows="2" class="form-control" id="edit_page_excerpt" name="page_excerpt"><?php echo htmlspecialchars($prow['page_excerpt']); ?></textarea>
              </div>
              <div class="form-group">
                <label for="edit_content">*页面内容:</label>
                <textarea rows="12" class="form-control" id="edit_content" name="page_content" required style="display:none"><?php echo htmlspecialchars($prow['page_content']); ?></textarea>
                <div id="vditor"></div>
              </div>
              <div class="form-group">
                <label>状态:</label>
                <label class="radio-inline"><input type="radio" name="page_status" value="1"<?php echo $prow['page_status'] == 1 ? ' checked' : ''; ?>> 发布</label>
                <label class="radio-inline"><input type="radio" name="page_status" value="0"<?php echo $prow['page_status'] == 0 ? ' checked' : ''; ?>> 草稿</label>
              </div>
              <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="修改">
              </div>
            </form>
            <br><a href="./article.php?set=page"><<返回</a>

<?php else: ?>
            <div id="toolbar" class="toolbar-btn-action mb-2">
              <a href="./article.php?set=page" class="btn btn-info btn-label">
                <label><i class="mdi mdi-file-document" aria-hidden="true"></i></label>独立页面</a>
              <a href="./article.php?set=add" class="btn btn-primary btn-label">
                <label><i class="mdi mdi-plus" aria-hidden="true"></i></label>新增文章</a>
              <button id="btn_on" type="button" class="btn btn-success btn-label" onclick="on_article()">
                <label><i class="mdi mdi-check" aria-hidden="true"></i></label>发布</button>
              <button id="btn_off" type="button" class="btn btn-warning btn-label" onclick="off_article()">
                <label><i class="mdi mdi-block-helper" aria-hidden="true"></i></label>转草稿 </button>
              <button id="btn_top" type="button" class="btn btn-cyan btn-label" onclick="top_article()">
                <label><i class="mdi mdi-pin" aria-hidden="true"></i></label>置顶</button>
              <button id="btn_delete" type="button" class="btn btn-danger btn-label" onclick="del_article()">
                <label><i class="mdi mdi-window-close" aria-hidden="true"></i></label>删除</button>
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="classlisttbody">
                <thead>
                  <tr>
                    <th><input type="checkbox" class="checkbox-parent" id="check_all" onclick="check_all()"></th>
                    <th>ID</th>
                    <th>标题</th>
                    <th>分类</th>
                    <th>作者</th>
                    <th>发布时间</th>
                    <th>浏览/评论</th>
                    <th>状态</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $rs = $DB->query("SELECT a.*, c.cat_name FROM `lylme_article` a LEFT JOIN `lylme_article_cat` c ON a.cat_id=c.cat_id ORDER BY a.art_time DESC");
                  while ($res = $DB->fetch($rs)) {
                  ?>
                    <tr>
                      <td><input type="checkbox" name="article-check" value="<?php echo $res['art_id']; ?>"></td>
                      <td><?php echo $res['art_id']; ?></td>
                      <td>
                        <?php echo $res['art_title']; ?>
                        <?php if ($res['art_top'] == 1): ?><span class="label label-warning">置顶</span><?php endif; ?>
                        <?php if (!empty($res['art_slug'])): ?><br><small><a href="./article.php?set=edit&id=<?php echo $res['art_id']; ?>">别名: <?php echo htmlspecialchars($res['art_slug']); ?></a></small><?php endif; ?>
                      </td>
                      <td><?php echo $res['cat_name'] ? $res['cat_name'] : '未分类'; ?></td>
                      <td><?php echo $res['art_author']; ?></td>
                      <td><?php echo $res['art_time']; ?></td>
                      <td><?php echo $res['art_views']; ?> / <?php echo $res['art_comments']; ?></td>
                      <td>
                        <?php if ($res['art_status'] == "0"): ?><font color="red">草稿</font><?php elseif ($res['art_status'] == "2"): ?><font color="orange">待审核</font><?php else: ?><font color="green">发布</font><?php endif; ?>
                      </td>
                      <td>
                        <a href="./article.php?set=edit&id=<?php echo $res['art_id']; ?>" class="btn btn-info btn-xs">编辑</a>&nbsp;
                        <a href="/article/?id=<?php echo $res['art_id']; ?>" target="_blank" class="btn btn-default btn-xs">预览</a>&nbsp;
                        <button class="btn btn-danger btn-xs" onclick="del_article('<?php echo $res['art_id']; ?>')">删除</button>
                      </td>
                    </tr>
                  <?php
                  }
                  ?>
                </tbody>
              </table>
            </div>
<?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include './footer.php'; ?>
<link rel="stylesheet" href="/assets/admin/vditor/index.css">
<style>
/* Vditor 编辑区内边距为 JS 运行期内联生成, 用 !important 覆盖(仅编辑区、仅 PC 端) */
@media (min-width: 768px) {
  pre.vditor-reset[contenteditable="true"] { padding-left: 15px !important; padding-right: 15px !important; }
}
</style>
<script src="/assets/admin/vditor/index.min.js"></script>
<script>
(function () {
  // 静态资源统一走本地 /assets/admin/vditor/dist/，语言包、Lute、代码高亮、KaTeX、Emoji 等均不再外联
  var VDITOR_CDN = '/assets/admin/vditor/';

  var TOOLBAR = [
    'emoji', 'headings', 'bold', 'italic', 'strike', 'link', '|',
    'list', 'ordered-list', 'check', 'outdent', 'indent', '|',
    'quote', 'line', 'code', 'inline-code', 'insert-before', 'insert-after', '|',
    'upload', 'table', '|',
    'undo', 'redo', '|',
    'fullscreen', 'edit-mode', 'preview', 'export'
  ];

  function createEditor(elId, ta) {
    var editor = new Vditor(elId, {
      cdn: VDITOR_CDN,
      lang: 'zh_CN',
      value: ta.value,
      mode: 'ir',                 // ir 即时渲染 / sv 分屏 / wysiwyg 所见即所得
      theme: 'classic',
      width: '100%',
      height: 560,
      minHeight: 360,
      placeholder: '请输入文章内容，支持 Markdown 语法与 HTML 标签…',
      cache: { enable: false },
      tab: '\t',
      counter: { enable: true, type: 'text' },
      toolbar: TOOLBAR,
      toolbarConfig: { pin: true },
      preview: {
        delay: 300,
        theme: 'classic',
        hljs: { lineNumber: true, style: 'github' },
        math: { engine: 'KaTeX' },
        markdown: { toc: true, mark: true, footnote: true, autoSpace: true, isOpen: true },
        actions: ['desktop', 'tablet', 'mobile', 'both', 'outline', 'beautify']
      },
      hljs: { lineNumber: true, style: 'github' },
      emoji: { enable: true },
      upload: {
        url: '/include/file.php?compress=1', // compress=1 等比压缩不裁剪，适合文章配图
        fieldName: 'file',
        max: 5 * 1024 * 1024,
        accept: 'image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp',
        filename: function (name) {
          return name.replace(/[\\/:*?"<>|]/g, '').replace(/\s+/g, '_');
        },
        // 适配本站 /include/file.php 返回的 {code:'200',msg,url} 格式
        format: function (files, responseText) {
          var res;
          try { res = JSON.parse(responseText); } catch (e) { res = null; }
          var ok = !!res && (res.code === 200 || res.code === '200') && !!res.url;
          return JSON.stringify({
            msg: (res && res.msg) || '上传失败',
            code: ok ? 0 : 1,
            data: { errFiles: ok ? [] : [files[0].name], succMap: ok ? { [files[0].name]: res.url } : {} }
          });
        }
      },
      after: function () {
        // 注意：此处 this 不指向编辑器实例，须通过闭包引用 editor
        if (editor && typeof editor.getValue === 'function') ta.value = editor.getValue();
      },
      input: function (value) { ta.value = value; }
    });
    return editor;
  }

  if (document.getElementById('edit_content')) {
    createEditor('vditor', document.getElementById('edit_content'));
  }
  if (document.getElementById('add_content')) {
    createEditor('vditorAdd', document.getElementById('add_content'));
  }
})();
</script>
<script type="text/javascript">
  // 新增/编辑表单 AJAX 提交（阻止默认跳转，弹窗显示服务端返回）
  function bindFormAjax(formId) {
    var form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.action, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var res = JSON.parse(xhr.responseText);
          alert(res.msg);
          if (res.code === 200) {
            location.href = './article.php';
          }
        }
      };
      xhr.send(new FormData(form));
    });
  }
  bindFormAjax('addArticleForm');
  bindFormAjax('editArticleForm');
</script>
<script type="text/javascript">
  // 全选/取消全选
  function check_all() {
    var checked = $('#check_all').prop('checked');
    $('input[name="article-check"]').prop('checked', checked);
  }
  // 批量发布
  function on_article() {
    var ids = [];
    $('input[name="article-check"]:checked').each(function () { ids.push(this.value); });
    if (ids.length == 0) { alert('请先选择要发布的文章'); return; }
    if (confirm('确定发布所选文章吗？')) {
      $.get('ajax_article.php?submit=on_article&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  // 批量转草稿
  function off_article() {
    var ids = [];
    $('input[name="article-check"]:checked').each(function () { ids.push(this.value); });
    if (ids.length == 0) { alert('请先选择要转为草稿的文章'); return; }
    if (confirm('确定将所选文章转为草稿吗？')) {
      $.get('ajax_article.php?submit=off_article&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  // 批量置顶
  function top_article() {
    var ids = [];
    $('input[name="article-check"]:checked').each(function () { ids.push(this.value); });
    if (ids.length == 0) { alert('请先选择要置顶的文章'); return; }
    if (confirm('确定置顶所选文章吗？')) {
      $.get('ajax_article.php?submit=top_article&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  // 删除（支持批量）
  function del_article(id) {
    var ids = [];
    if (id) {
      ids.push(id);
    } else {
      $('input[name="article-check"]:checked').each(function () { ids.push(this.value); });
      if (ids.length == 0) { alert('请先选择要删除的文章'); return; }
    }
    if (confirm('确定删除所选文章吗？删除后不可恢复！')) {
      $.get('ajax_article.php?submit=del_article&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }

  // ===== 独立页面操作 =====
  function check_all_page() {
    var all = document.getElementById('check_all_page');
    document.querySelectorAll('input[name=page-check]').forEach(function (e) { e.checked = all.checked; });
  }
  function getPageIds() {
    var ids = [];
    document.querySelectorAll('input[name=page-check]:checked').forEach(function (e) { ids.push(e.value); });
    return ids;
  }
  function del_page(id) {
    var ids = id ? [String(id)] : getPageIds();
    if (ids.length == 0) { alert('请先选择要删除的页面'); return; }
    if (confirm('确定删除所选页面吗？删除后不可恢复！')) {
      $.get('ajax_article.php?submit=del_page&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  function on_page() {
    var ids = getPageIds();
    if (ids.length == 0) { alert('请先选择要发布的页面'); return; }
    if (confirm('确定发布所选页面吗？')) {
      $.get('ajax_article.php?submit=on_page&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  function off_page() {
    var ids = getPageIds();
    if (ids.length == 0) { alert('请先选择要转为草稿的页面'); return; }
    if (confirm('确定将所选页面转为草稿吗？')) {
      $.get('ajax_article.php?submit=off_page&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
</script>

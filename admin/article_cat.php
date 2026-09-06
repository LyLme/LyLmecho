<?php
$title = '文章分类';
include './head.php';

$set = isset($_GET['set']) ? $_GET['set'] : null;
?>
<main class="lyear-layout-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">

<?php if ($set == 'add'): ?>
            <h4>新增分类</h4>
            <form id="addCatForm" action="./ajax_article.php?submit=add_cat" method="POST">
              <div class="form-group">
                <label for="add_cat_name">*分类名称:</label>
                <input type="text" class="form-control" id="add_cat_name" name="cat_name" placeholder="分类名称" required>
              </div>

              <div class="form-group">
                <label for="add_cat_alias">分类别名:</label>
                <input type="text" class="form-control" id="add_cat_alias" name="cat_alias" placeholder="URL友好别名，如 notice">
              </div>

              <div class="form-group">
                <label for="add_cat_desc">分类描述:</label>
                <input type="text" class="form-control" id="add_cat_desc" name="cat_desc" placeholder="分类描述">
              </div>

              <div class="form-group">
                <label for="add_cat_order">排序:</label>
                <input type="number" class="form-control" id="add_cat_order" name="cat_order" value="10">
                <small class="help-block">数字越小越靠前</small>
              </div>

              <div class="form-group">
                <label>状态:</label>
                <label class="radio-inline"><input type="radio" name="cat_status" value="1" checked> 启用</label>
                <label class="radio-inline"><input type="radio" name="cat_status" value="0"> 禁用</label>
              </div>

              <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="添加">
              </div>
            </form>
            <br><a href="./article_cat.php"><<返回</a>

<?php elseif ($set == 'edit'): ?>
            <?php
            $id = intval($_GET['id']);
            $row2 = $DB->query("select * from lylme_article_cat where cat_id='$id' limit 1");
            $row = $DB->fetch($row2);
            if (!$row) exit('该条记录不存在！');
            ?>
            <h4>修改分类信息</h4>
            <form id="editCatForm" action="./ajax_article.php?submit=edit_cat&id=<?php echo $id; ?>" method="POST">
              <div class="form-group">
                <label for="edit_cat_name">*分类名称:</label>
                <input type="text" class="form-control" id="edit_cat_name" name="cat_name" value="<?php echo htmlspecialchars($row['cat_name']); ?>" required>
              </div>

              <div class="form-group">
                <label for="edit_cat_alias">分类别名:</label>
                <input type="text" class="form-control" id="edit_cat_alias" name="cat_alias" value="<?php echo htmlspecialchars($row['cat_alias']); ?>">
              </div>

              <div class="form-group">
                <label for="edit_cat_desc">分类描述:</label>
                <input type="text" class="form-control" id="edit_cat_desc" name="cat_desc" value="<?php echo htmlspecialchars($row['cat_desc']); ?>">
              </div>

              <div class="form-group">
                <label for="edit_cat_order">排序:</label>
                <input type="number" class="form-control" id="edit_cat_order" name="cat_order" value="<?php echo $row['cat_order']; ?>">
              </div>

              <div class="form-group">
                <label>状态:</label>
                <label class="radio-inline"><input type="radio" name="cat_status" value="1"<?php echo $row['cat_status'] == 1 ? ' checked' : ''; ?>> 启用</label>
                <label class="radio-inline"><input type="radio" name="cat_status" value="0"<?php echo $row['cat_status'] == 0 ? ' checked' : ''; ?>> 禁用</label>
              </div>

              <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="修改">
              </div>
            </form>
            <br><a href="./article_cat.php"><<返回</a>

<?php else: ?>
            <div id="toolbar" class="toolbar-btn-action mb-2">
              <a href="./article_cat.php?set=add" class="btn btn-primary btn-label">
                <label><i class="mdi mdi-plus" aria-hidden="true"></i></label>新增分类</a>
              <button id="btn_on" type="button" class="btn btn-success btn-label" onclick="on_cat()">
                <label><i class="mdi mdi-check" aria-hidden="true"></i></label>启用</button>
              <button id="btn_off" type="button" class="btn btn-warning btn-label" onclick="off_cat()">
                <label><i class="mdi mdi-block-helper" aria-hidden="true"></i></label>禁用 </button>
              <button id="btn_delete" type="button" class="btn btn-danger btn-label" onclick="del_cat()">
                <label><i class="mdi mdi-window-close" aria-hidden="true"></i></label>删除</button>
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="classlisttbody">
                <thead>
                  <tr>
                    <th><input type="checkbox" class="checkbox-parent" id="check_all" onclick="check_all()"></th>
                    <th>ID</th>
                    <th>分类名称</th>
                    <th>别名</th>
                    <th>描述</th>
                    <th>排序</th>
                    <th>文章数</th>
                    <th>状态</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $rs = $DB->query("SELECT c.*, (SELECT COUNT(*) FROM `lylme_article` a WHERE a.cat_id=c.cat_id) AS art_count FROM `lylme_article_cat` c ORDER BY c.cat_order ASC");
                  while ($res = $DB->fetch($rs)) {
                  ?>
                    <tr>
                      <td><input type="checkbox" name="cat-check" value="<?php echo $res['cat_id']; ?>"></td>
                      <td><?php echo $res['cat_id']; ?></td>
                      <td><?php echo $res['cat_name']; ?></td>
                      <td><?php echo htmlspecialchars($res['cat_alias']); ?></td>
                      <td><?php echo htmlspecialchars($res['cat_desc']); ?></td>
                      <td><?php echo $res['cat_order']; ?></td>
                      <td><?php echo $res['art_count']; ?></td>
                      <td>
                        <?php if ($res['cat_status'] == "0"): ?><font color="red">禁用</font><?php else: ?><font color="green">启用</font><?php endif; ?>
                      </td>
                      <td>
                        <a href="./article_cat.php?set=edit&id=<?php echo $res['cat_id']; ?>" class="btn btn-info btn-xs">编辑</a>&nbsp;
                        <button class="btn btn-danger btn-xs" onclick="del_cat('<?php echo $res['cat_id']; ?>')">删除</button>
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

<script type="text/javascript">
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
            location.href = './article_cat.php';
          }
        }
      };
      xhr.send(new FormData(form));
    });
  }
  bindFormAjax('addCatForm');
  bindFormAjax('editCatForm');
</script>
<script type="text/javascript">
  function check_all() {
    var checked = $('#check_all').prop('checked');
    $('input[name="cat-check"]').prop('checked', checked);
  }
  function on_cat() {
    var ids = [];
    $('input[name="cat-check"]:checked').each(function () { ids.push(this.value); });
    if (ids.length == 0) { alert('请先选择要启用的分类'); return; }
    if (confirm('确定启用所选分类吗？')) {
      $.get('ajax_article.php?submit=on_cat&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  function off_cat() {
    var ids = [];
    $('input[name="cat-check"]:checked').each(function () { ids.push(this.value); });
    if (ids.length == 0) { alert('请先选择要禁用的分类'); return; }
    if (confirm('确定禁用所选分类吗？')) {
      $.get('ajax_article.php?submit=off_cat&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  function del_cat(id) {
    var ids = [];
    if (id) {
      ids.push(id);
    } else {
      $('input[name="cat-check"]:checked').each(function () { ids.push(this.value); });
      if (ids.length == 0) { alert('请先选择要删除的分类'); return; }
    }
    if (confirm('确定删除所选分类吗？该分类下文章将变为未分类！')) {
      $.get('ajax_article.php?submit=del_cat&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
</script>

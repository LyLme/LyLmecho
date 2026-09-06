<?php
$title = '会员管理';
include './head.php';

// 统计各状态数量
$total = intval($DB->count("SELECT COUNT(*) FROM `lylme_member`"));
$enabled = intval($DB->count("SELECT COUNT(*) FROM `lylme_member` WHERE `status` = 1"));

// 关键字搜索
$kw = isset($_GET['kw']) ? trim((string) $_GET['kw']) : '';
$where = '';
if ($kw !== '') {
    $kwEsc = $DB->escape($kw);
    $where = " WHERE `username` LIKE '%{$kwEsc}%' OR `nickname` LIKE '%{$kwEsc}%' OR `email` LIKE '%{$kwEsc}%'";
}
?>
<main class="lyear-layout-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                共 <b><?php echo $total; ?></b> 名会员，已激活 <b class="text-success"><?php echo $enabled; ?></b> 名
              </div>
              <div class="d-flex gap-2">
                <form class="form-inline" method="get" style="display:inline-flex;gap:6px;">
                  <input type="text" class="form-control form-control-sm" name="kw" placeholder="用户名/昵称/邮箱" value="<?php echo htmlspecialchars($kw, ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="submit" class="btn btn-sm btn-secondary">搜索</button>
                  <?php if ($kw !== ''): ?><a href="./article_member.php" class="btn btn-sm btn-outline-secondary">清空</a><?php endif; ?>
                </form>
                <button type="button" class="btn btn-primary btn-label" onclick="open_add()">
                  <label><i class="mdi mdi-account-plus" aria-hidden="true"></i></label>添加会员</button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="memberlist">
                <thead>
                  <tr>
                    <th>UID</th>
                    <th>用户名</th>
                    <th>昵称</th>
                    <th>邮箱</th>
                    <th>角色</th>
                    <th>状态</th>
                    <th>注册时间</th>
                    <th>最后登录</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $rs = $DB->query("SELECT * FROM `lylme_member`{$where} ORDER BY `uid` DESC");
                  if (!$rs) {
                  ?>
                    <tr><td colspan="9" class="text-center text-muted">数据表暂不存在或读取失败，请先运行数据库升级</td></tr>
                  <?php
                  } else {
                    $rows = 0;
                    while ($res = $DB->fetch($rs)) {
                      $rows++;
                      $uid = intval($res['uid']);
                      $status = intval($res['status']);
                      $role = in_array($res['role'], array('contributor', 'editor'), true) ? $res['role'] : 'subscriber';
                  ?>
                    <tr>
                      <td><?php echo $uid; ?></td>
                      <td><?php echo htmlspecialchars($res['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($res['nickname'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($res['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <select class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="set_role(<?php echo $uid; ?>, this.value)">
                          <option value="subscriber"<?php echo $role === 'subscriber' ? ' selected' : ''; ?>>订阅者</option>
                          <option value="contributor"<?php echo $role === 'contributor' ? ' selected' : ''; ?>>投稿者</option>
                          <option value="editor"<?php echo $role === 'editor' ? ' selected' : ''; ?>>编辑</option>
                        </select>
                      </td>
                      <td>
                        <?php if ($status === 1): ?>
                          <font color="green">正常</font>
                        <?php elseif ($res['verify_token'] !== ''): ?>
                          <font color="orange">待验证</font>
                        <?php else: ?>
                          <font color="red">已禁用</font>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars((string) $res['reg_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo $res['last_login'] ? htmlspecialchars((string) $res['last_login'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                      <td>
                        <?php if ($status === 1): ?>
                          <button class="btn btn-warning btn-xs" onclick="set_status(<?php echo $uid; ?>, 0)">禁用</button>
                        <?php else: ?>
                          <button class="btn btn-success btn-xs" onclick="set_status(<?php echo $uid; ?>, 1)">启用</button>
                        <?php endif; ?>
                        <button class="btn btn-info btn-xs" onclick="reset_pwd(<?php echo $uid; ?>)">重置密码</button>
                        <button class="btn btn-danger btn-xs" onclick="del_member(<?php echo $uid; ?>)">删除</button>
                      </td>
                    </tr>
                  <?php
                    }
                    if ($rows === 0) {
                  ?>
                    <tr><td colspan="9" class="text-center text-muted">暂无会员<?php echo $kw !== '' ? '（无匹配结果）' : ''; ?></td></tr>
                  <?php
                    }
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- 添加会员弹窗 -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">添加会员</h5>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="关闭"></button>
      </div>
      <div class="modal-body">
        <form id="addForm">
          <div class="form-group">
            <label for="add_username">用户名 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="add_username" name="username" placeholder="3-30 位字母/数字/下划线/中文" required>
          </div>
          <div class="form-group">
            <label for="add_email">邮箱 <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="add_email" name="email" required>
          </div>
          <div class="form-group">
            <label for="add_password">密码 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="add_password" name="password" placeholder="6-64 位" required>
          </div>
          <div class="form-group">
            <label for="add_nickname">昵称</label>
            <input type="text" class="form-control" id="add_nickname" name="nickname" placeholder="留空则与用户名相同">
          </div>
          <div class="form-group">
            <label>角色</label>
            <div>
              <label class="radio-inline"><input type="radio" name="role" value="subscriber" checked> 订阅者</label>
              <label class="radio-inline"><input type="radio" name="role" value="contributor"> 投稿者</label>
              <label class="radio-inline"><input type="radio" name="role" value="editor"> 编辑</label>
            </div>
          </div>
          <div class="form-group">
            <label>状态</label>
            <div>
              <label class="radio-inline"><input type="radio" name="status" value="1" checked> 正常(可直接登录)</label>
              <label class="radio-inline"><input type="radio" name="status" value="0"> 禁用</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
        <button type="button" class="btn btn-primary" onclick="submit_add()">保存</button>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>

<script type="text/javascript">
  function set_status(uid, status) {
    if (!confirm(status == 1 ? '确定启用该会员吗？' : '确定禁用该会员吗？禁用后将无法登录。')) return;
    $.post('ajax_article.php?submit=set_member_status', { uid: uid, status: status }, function (data) {
      alert(data.msg);
      if (data.code === 200) location.reload();
    }, 'json');
  }
  function set_role(uid, role) {
    $.post('ajax_article.php?submit=set_member_role', { uid: uid, role: role }, function (data) {
      alert(data.msg);
      if (data.code === 200) location.reload();
    }, 'json');
  }
  function reset_pwd(uid) {
    var pwd = prompt('请输入该会员的新密码（6-64 位）：');
    if (pwd === null) return;
    pwd = $.trim(pwd);
    if (pwd.length < 6 || pwd.length > 64) { alert('密码长度需为 6-64 位'); return; }
    $.post('ajax_article.php?submit=reset_member_pwd', { uid: uid, password: pwd }, function (data) {
      alert(data.msg);
    }, 'json');
  }
  function del_member(uid) {
    if (!confirm('确定删除该会员吗？该操作不可恢复！')) return;
    $.post('ajax_article.php?submit=del_member', { uid: uid }, function (data) {
      alert(data.msg);
      if (data.code === 200) location.reload();
    }, 'json');
  }
  var addModalInst = null;
  function open_add() {
    document.getElementById('addForm').reset();
    if (!addModalInst) {
      addModalInst = bootstrap.Modal.getOrCreateInstance(document.getElementById('addModal'));
    }
    addModalInst.show();
  }
  function submit_add() {
    var form = document.getElementById('addForm');
    var data = {
      username: $.trim(form.username.value),
      email: $.trim(form.email.value),
      password: form.password.value,
      nickname: $.trim(form.nickname.value),
      role: form.role.value,
      status: form.status.value
    };
    if (data.username === '' || data.email === '' || data.password === '') { alert('用户名、邮箱、密码为必填项'); return; }
    $.post('ajax_article.php?submit=add_member', data, function (res) {
      alert(res.msg);
      if (res.code === 200) { addModalInst.hide(); location.reload(); }
    }, 'json');
  }
</script>

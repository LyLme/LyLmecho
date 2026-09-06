<?php
$title = '文章评论';
include './head.php';
$admin_user = isset($conf['admin_user']) ? $conf['admin_user'] : 'admin';
// 读取管理员显示名称配置，兜底为"管理员"
$admin_name = trim((string)$DB->get_column("SELECT `v` FROM `lylme_article_config` WHERE `k` = 'article_name'"));
if ($admin_name === '') { $admin_name = '管理员'; }
?>
<main class="lyear-layout-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div id="toolbar" class="toolbar-btn-action mb-2">
              <button id="btn_on" type="button" class="btn btn-success btn-label" onclick="on_comment()">
                <label><i class="mdi mdi-check" aria-hidden="true"></i></label>审核通过</button>
              <button id="btn_off" type="button" class="btn btn-warning btn-label" onclick="off_comment()">
                <label><i class="mdi mdi-block-helper" aria-hidden="true"></i></label>取消审核 </button>
              <button id="btn_delete" type="button" class="btn btn-danger btn-label" onclick="del_comment()">
                <label><i class="mdi mdi-window-close" aria-hidden="true"></i></label>删除</button>
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="classlisttbody">
                <thead>
                  <tr>
                    <th><input type="checkbox" class="checkbox-parent" id="check_all" onclick="check_all()"></th>
                    <th>ID</th>
                    <th>文章</th>
                    <th>昵称</th>
                    <th>邮箱</th>
                    <th>IP</th>
                    <th>内容</th>
                    <th>时间</th>
                    <th>状态</th>
                    <th>回复</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // 只显示父评论（com_pid=0），并统计各父评论的回复数
                  $rs = $DB->query("SELECT cm.*, a.art_title, (SELECT COUNT(*) FROM `lylme_article_comment` c2 WHERE c2.com_pid = cm.com_id) AS reply_count FROM `lylme_article_comment` cm LEFT JOIN `lylme_article` a ON cm.art_id=a.art_id WHERE cm.com_pid = 0 ORDER BY cm.com_time DESC");
                  // 收集所有回复记录，按父评论分组，用于弹窗时间轴
                  $threadMap = array();
                  $replyRs = $DB->query("SELECT * FROM `lylme_article_comment` WHERE `com_pid` > 0 ORDER BY `com_time` ASC, `com_id` ASC");
                  while ($r = $DB->fetch($replyRs)) {
                    $threadMap[$r['com_pid']][] = $r;
                  }
                  $threadData = array();
                  while ($res = $DB->fetch($rs)) {
                    // BFS 递归收集完整对话线程（父评论 + 直接回复 + 回复的回复……）
                    $thread = array($res);
                    $queue = array($res['com_id']);
                    $seen = array($res['com_id'] => true);
                    while ($queue) {
                      $cur = array_shift($queue);
                      if (!empty($threadMap[$cur])) {
                        foreach ($threadMap[$cur] as $child) {
                          if (isset($seen[$child['com_id']])) { continue; }
                          $seen[$child['com_id']] = true;
                          $thread[] = $child;
                          $queue[] = $child['com_id'];
                        }
                      }
                    }
                    // 组装时间轴数据：昵称统一处理（管理员显示配置名），内容已转义
                    $items = array();
                    foreach ($thread as $t) {
                      $isAdmin = (($t['com_name'] == $admin_name) || ($t['com_name'] == $admin_user));
                      $items[] = array(
                        'com_id'   => intval($t['com_id']),
                        'name'     => htmlspecialchars($isAdmin ? $admin_name : $t['com_name'], ENT_QUOTES, 'UTF-8'),
                        'is_admin' => $isAdmin,
                        'content'  => htmlspecialchars($t['com_content'], ENT_QUOTES, 'UTF-8'),
                        'time'     => $t['com_time'],
                      );
                    }
                    $threadData[$res['com_id']] = $items;
                    $content = $res['com_content'];
                    if (function_exists('mb_strlen') && mb_strlen($content, 'UTF-8') > 60) {
                      $content = mb_substr($content, 0, 57, 'UTF-8') . '...';
                    } elseif (strlen($content) > 90) {
                      $content = substr($content, 0, 87) . '...';
                    }
                  ?>
                    <tr>
                      <td><input type="checkbox" name="comment-check" value="<?php echo $res['com_id']; ?>"></td>
                      <td><?php echo $res['com_id']; ?></td>
                      <td>
                        <?php if (!empty($res['art_title'])): ?>
                          <a href="/article/?id=<?php echo $res['art_id']; ?>" target="_blank"><?php echo htmlspecialchars($res['art_title']); ?></a>
                        <?php else: ?>
                          <font color="red">文章已删除</font>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars((($res['com_name'] == $admin_name) || ($res['com_name'] == $admin_user)) ? $admin_name : $res['com_name']); ?></td>
                      <td><?php echo htmlspecialchars($res['com_email']); ?></td>
                      <td><?php echo $res['com_ip']; ?></td>
                      <td title="<?php echo htmlspecialchars($res['com_content']); ?>"><?php echo htmlspecialchars($content); ?></td>
                      <td><?php echo $res['com_time']; ?></td>
                      <td>
                        <?php if ($res['com_status'] == "0"): ?><font color="red">未审核</font>
                        <?php elseif ($res['reply_count'] > 0): ?><font color="blue">已回复</font>
                        <?php else: ?><font color="green">已审核</font><?php endif; ?>
                      </td>
                      <td>
                        <button class="btn btn-info btn-xs" onclick="open_reply('<?php echo $res['com_id']; ?>')"><?php echo $res['reply_count'] > 0 ? '查看回复 (' . $res['reply_count'] . ')' : '回复'; ?></button>
                      </td>
                      <td>
                        <?php if ($res['com_status'] == 0): ?>
                          <button class="btn btn-success btn-xs" onclick="on_comment('<?php echo $res['com_id']; ?>')">通过</button>
                        <?php else: ?>
                          <button class="btn btn-warning btn-xs" onclick="off_comment('<?php echo $res['com_id']; ?>')">取消审核</button>
                        <?php endif; ?>
                        <button class="btn btn-danger btn-xs" onclick="del_comment('<?php echo $res['com_id']; ?>')">删除</button>
                      </td>
                    </tr>
                  <?php
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

<!-- 评论时间轴弹窗 -->
<style type="text/css">
  .timeline{position:relative;margin:0;padding:0;list-style:none}
  .timeline::before{content:'';position:absolute;left:15px;top:4px;bottom:4px;width:2px;background:#e9ecef}
  .timeline-item{position:relative;padding-left:42px;margin-bottom:16px}
  .timeline-item:last-child{margin-bottom:0}
  .timeline-marker{position:absolute;left:10px;top:6px;width:12px;height:12px;border-radius:50%;background:#adb5bd;border:2px solid #fff;box-shadow:0 0 0 2px #adb5bd}
  .timeline-item.timeline-primary .timeline-marker{background:#007bff;box-shadow:0 0 0 2px #007bff}
  .timeline-card{background:#f8f9fa;border:1px solid #e9ecef;border-radius:6px;padding:10px 12px}
  .timeline-item.timeline-primary .timeline-card{border-color:#b8daff;background:#f0f7ff}
  .timeline-head{font-size:13px;margin-bottom:4px}
  .timeline-head .tl-name{font-weight:600;color:#007bff}
  .timeline-head .tl-time{float:right;color:#868e96}
  .timeline-content{font-size:14px;color:#343a40;word-break:break-all;white-space:pre-wrap}
</style>
<div class="modal fade" id="replyModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:900px" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">评论时间轴</h5>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="关闭"></button>
      </div>
      <div class="modal-body">
        <div class="timeline" id="reply_timeline"></div>
      </div>
      <div class="modal-footer d-block text-end">
        <div class="input-group w-100">
          <textarea class="form-control" id="reply_content" rows="2" placeholder="输入回复内容，回复将追加到该评论下方"></textarea>
        </div>
        <div class="mt-2 text-end">
          <button type="button" class="btn btn-secondary me-1" data-bs-dismiss="modal">取消</button>
          <button type="button" class="btn btn-primary" onclick="submit_reply()">回复</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>

<script type="text/javascript">
  // 评论时间轴数据：com_id => 该评论及其全部回复（按时间升序），昵称/内容已在 PHP 端转义
  var replyThreadData = <?php echo json_encode($threadData, JSON_UNESCAPED_UNICODE); ?>;
  function check_all() {
    var checked = $('#check_all').prop('checked');
    $('input[name="comment-check"]').prop('checked', checked);
  }
  function on_comment(id) {
    var ids = [];
    if (id) {
      ids.push(id);
    } else {
      $('input[name="comment-check"]:checked').each(function () { ids.push(this.value); });
      if (ids.length == 0) { alert('请先选择要审核通过的评论'); return; }
    }
    if (confirm('确定审核通过所选评论吗？')) {
      $.get('ajax_article.php?submit=on_comment&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  function off_comment(id) {
    var ids = [];
    if (id) {
      ids.push(id);
    } else {
      $('input[name="comment-check"]:checked').each(function () { ids.push(this.value); });
      if (ids.length == 0) { alert('请先选择要取消审核的评论'); return; }
    }
    if (confirm('确定取消审核所选评论吗？')) {
      $.get('ajax_article.php?submit=off_comment&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
  var replyTarget = null;
  function open_reply(id) {
    replyTarget = { id: id };
    var thread = replyThreadData[id] || [];
    var html = '';
    for (var i = 0; i < thread.length; i++) {
      var item = thread[i];
      html += '<div class="timeline-item' + (i === 0 ? ' timeline-primary' : '') + '">'
        + '<div class="timeline-marker"></div>'
        + '<div class="timeline-card">'
        + '<div class="timeline-head"><span class="tl-name">' + item.name + '</span>'
        + (item.is_admin ? ' <span class="badge text-bg-info">管理员</span>' : '')
        + '<span class="tl-time">' + item.time + '</span></div>'
        + '<div class="timeline-content">' + item.content + '</div>'
        + '</div></div>';
    }
    if (thread.length === 0) {
      html = '<div class="text-center text-muted py-3">暂无回复</div>';
    }
    $('#reply_timeline').html(html);
    $('#reply_content').val('');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('replyModal')).show();
  }
  function submit_reply() {
    var content = $.trim($('#reply_content').val());
    if (content == '') { alert('请输入回复内容'); return; }
    $.post('ajax_article.php?submit=reply_comment', { com_id: replyTarget.id, reply_content: content }, function (data) {
      alert(data.msg);
      if (data.code === 200) location.reload();
    }, 'json');
  }
  function del_comment(id) {
    var ids = [];
    if (id) {
      ids.push(id);
    } else {
      $('input[name="comment-check"]:checked').each(function () { ids.push(this.value); });
      if (ids.length == 0) { alert('请先选择要删除的评论'); return; }
    }
    if (confirm('确定删除所选评论吗？删除后不可恢复！')) {
      $.get('ajax_article.php?submit=del_comment&id=' + ids.join(','), function (data) { alert(data.msg); if (data.code === 200) location.reload(); }, 'json');
    }
  }
</script>

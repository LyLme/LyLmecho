<?php
/* 
 * @Description: 关于页面
 * @Author: LyLme admin@lylme.com
 * @Date: 2024-01-23 12:25:35
 * @LastEditors: LyLme admin@lylme.com
 * @LastEditTime: 2026-03-22 18:10:13
 * @FilePath: /lylme_spage/admin/about.php
 * @Copyright (c) 2024 by LyLme, All Rights Reserved. 
 */
$title = '关于页面设置';
include './head.php';
$set = isset($_GET['set'])?$_GET['set']:"";
if ($set== 'conf_submit') {
    $about = $_POST['about'];
    saveSetting('about_content', $about);
    echo '<script>$.alert({title:"成功",content:"修改成功！",buttons:{confirm:{text:"确定",btnClass:"btn-primary",action:function(){window.location.href="./about.php";}}}});</script>';
    exit();
}
if ($set == 'default') {

    saveSetting('about_content', "# 关于本站\r\n\r\n本站是一个**轻量上网起始页 + 个人博客**：打开浏览器就能直达常用网站，顺手也能翻翻站长精选链接、写的测评、公告和折腾笔记。\r\n\r\n不花哨、不打扰，把它设成主页就够了。\r\n\r\n## 你能在这里做什么\r\n\r\n**① 一页直达常用网站**\r\n\r\n首页按分组整理了常用站点（工具、开发、设计、影音、学习等），点一下就走。链接直接指向目标地址，**不做任何二次中转**。\r\n\r\n**② 输入即搜**\r\n\r\n在顶部搜索框输入关键词回车即可。没被收录的站点，也可以直接在这里搜到。\r\n\r\n**③ 翻翻文章**\r\n\r\n站内文章板块记录了一些实用测评、站点公告和使用说明，有新内容会第一时间出现在这里。\r\n\r\n**④ 推荐你觉得好用的站**\r\n\r\n发现冷门又优质的网站？通过「申请收录」提交，通过后会归到对应分组里。\r\n\r\n## 收藏本站或设为浏览器主页\r\n\r\n- **收藏本站：**按`Ctrl`+`D`组合键可快速收藏本站\r\n- **设置主页：**浏览器打开设置→起始页面→打开特定网页或一组网页，填入本站地址\r\n- **Tip：**本站也对手机端进行了适配，手机浏览器也能用哦！\r\n\r\n## 关于隐私\r\n\r\n- 本站提供网址导航与跳转，链接默认直接指向目标地址，**也不因此产生跳转记录。**\r\n- 本站**不收集**你的点击、访问、搜索记录等隐私信息。\r\n- 但通过本站进入的外部站点，其数据收集与处理方式由对方决定。涉及账号、支付、个人信息时，建议先了解对方的相关说明。\r\n\r\n## 有问题、想提建议？\r\n\r\n- 推荐网站 / 链接失效 / 分类放错 → 站内「申请收录」或留言\r\n- 使用建议、体验吐槽 → 直接留言就行\r\n- 提交时加个【收录】【失效】【建议】【友链】前缀，并尽量写清复现步骤，处理速度会快很多\r\n\r\n## 版权与开源\r\n\r\n站内原创文章内容版权归本站所有，转载请注明出处。\r\n\r\n本站基于开源程序 **LyLmecho** 搭建（其上游为六零导航页 LyLme Spage），二者分别遵循 GPL-2.0 与 Apache-2.0 开源许可，在此致谢。");
    echo '<script>$.alert({title:"成功",content:"恢复默认成功！",buttons:{confirm:{text:"确定",btnClass:"btn-primary",action:function(){window.location.href="./about.php";}}}});</script>';
    exit();
}
?>
<main class="lyear-layout-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4>修改收录设置</h4>
                        <div class="panel-body">
                            <form action="./about.php?set=conf_submit" method="POST">
                                <div class="form-group" id="about">
                                    <label class="d-block w-100" for="web_yan_status">关于页面地址</label>
                                    <p><code><?php echo siteurl() ?>/about</code></p>
                                    <a class="btn btn-primary" href="<?php echo siteurl() ?>/about" target="_blank">访问关于页面</a>
                                    <a class="btn btn-danger" href="./about.php?set=default" onclick="var h=this.href;event.preventDefault();$.confirm({title:'警告',content:'确定将关于页面内容恢复默认？<br>注意：该操作不可逆',type:'red',buttons:{confirm:{text:'确定恢复',btnClass:'btn-danger',action:function(){window.location.href=h;}},cancel:{text:'取消'}}});return false;">恢复默认内容</a>
                                </div>
                                <div class="form-group">
                                    <label for="about_content">关于页内容</label>
                                    <textarea id="about_content" name="about" class="form-control" rows="20" style="display:none" required><?php echo htmlspecialchars($conf['about_content']); ?></textarea>
                                    <div id="vditorAbout"></div>
                                    <small class="help-block">显示在关于页面的内容，支持 <code>Markdown</code> 语法（与文章/页面编辑器一致）</small>
                                </div>
                                <div class="form-about">
                                    <input type="submit" class="btn btn-primary d-block w-100" value="保存">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include './footer.php';
?>
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
  // 静态资源统一走本地 /assets/admin/vditor/dist/, 语言包、Lute、代码高亮、Emoji 等均不再外联
  var VDITOR_CDN = '/assets/admin/vditor/';

  var TOOLBAR = [
    'emoji', 'headings', 'bold', 'italic', 'strike', 'link', '|',
    'list', 'ordered-list', 'check', 'outdent', 'indent', '|',
    'quote', 'line', 'code', 'inline-code', '|',
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
      height: 460,
      minHeight: 300,
      placeholder: '请输入关于页面内容，支持 Markdown 语法…',
      cache: { enable: false },
      tab: '\t',
      counter: { enable: true, type: 'text' },
      toolbar: TOOLBAR,
      toolbarConfig: { pin: true },
      preview: {
        delay: 300,
        theme: 'classic',
        hljs: { lineNumber: true, style: 'github' },
        markdown: { toc: true, mark: true, footnote: true, autoSpace: true, isOpen: true }
      },
      hljs: { lineNumber: true, style: 'github' },
      emoji: { enable: true },
      after: function () {
        // 注意：此处 this 不指向编辑器实例，须通过闭包引用 editor
        if (editor && typeof editor.getValue === 'function') ta.value = editor.getValue();
      },
      input: function (value) { ta.value = value; }
    });
    return editor;
  }

  if (document.getElementById('about_content')) {
    createEditor('vditorAbout', document.getElementById('about_content'));
  }
})();
</script>

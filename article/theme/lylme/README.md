# LyLme Modern 主题

六零导航页`文章站点`的现代风格前端主题。Bootstrap 5 + Material Design Icons + `style.min.css` 设计基座，再以 `style.css` 覆写为圆角 / 玻璃拟态 / 微交互的现代阅读皮肤。

主题文件位于 `article/theme/lylme/`，由后台「网站后台 ->  文章管理-> 文章主题 -> 主题自定义设置」渲染配置并保存至 `article/config/lylme.json`，由 `Compat\App` 注入 Options 后在模板中通过 `$this->options->{name}` 读取。

---

## 特性

- **现代视觉**：圆角卡片、玻璃拟态顶栏、渐变氛围背景、入场淡动效。
- **明暗双色**：内置浅色 / 深色 / 跟随系统三档，顶栏一键切换并记忆（与后台共用 `localStorage.theme` 键，刷新前无闪烁）。
- **主色驱动**：后台填写一个主色（如 `#4f7cf7`），由 HSL 色轮自动推导渐变副色、深色调、软色底与描边，真正驱动全局强调色。
- **模块开关**：Hero 大标题、封面缩略图、文章目录、热门内容、最新评论、分类、标签云、站点统计均可独立开关。
- **文章增强**：自动目录（TOC，滚动高亮 + 折叠记忆）、阅读进度条、预计阅读时长、上一篇/下一篇、相关文章、代码块复制、分享、打印。
- **SEO / 社交**：动态 `title` / `description` / `keywords`、规范链接 `canonical`、Open Graph 与 Twitter Card、RSS 订阅。
- **无障碍**：跳转到正文链接、语义化 `role` / `aria`、尊重 `prefers-reduced-motion` 减少动效。
- **响应式**：桌面双栏（8 + 4），移动端单栏；侧栏可收起并记忆状态。

---

## 目录结构

```
article/theme/lylme/
├── READMEmd            # 本文档
├── index.php           # 首页 / 分类 / 搜索 / 月份归档 列表
├── post.php            # 单篇文章
├── 404.php             # 404 页面
├── header.php          # 文档头 + 左侧导航 + 顶栏
├── sidebar.php         # 右侧栏小工具区
├── footer.php          # 页脚 + 脚本收尾
├── comments.php        # 评论区
├── functions.php       # 主题配置表单 + 全部辅助函数
├── style.css           # 主题现代皮肤样式
└── main.js             # 交互脚本（明暗切换 / TOC / 进度条 / 复制 / 分享 等）
```

---

## 安装与启用

1. 将主题目录 `lylme/` 放置在 `article/theme/` 下。
2. 登录后台，进入「文章主题 -> 主题管理」，启用 **LyLme**。
3. 进入「主题自定义设置」按需填写配置并保存。
4. 前台访问文章站点即可看到效果。

> 主题依赖后台基座静态资源（`assets/admin/css|js` 下的 Bootstrap、MDI、jQuery、`main.min.js` 等），请确保后台资源目录完整。

---

## 主题自定义设置

在后台「文章主题 -> 主题自定义设置」表单中可配置以下项：

| 配置项 | 说明 | 默认值 |
| --- | --- | --- |
| `lylmeLogo`（站点 LOGO 地址） | 建议正方形 PNG/SVG；留空回退全站「网站基本设置」LOGO，两者皆空时显示羽毛图标 | 空 |
| `lylmeLogoMatte`（LOGO 托底） | `white` 白色圆角托底 / `flat` 不加底（透明 logo 直接用） | `white` |
| `lylmeCover`（侧栏顶部封面图） | 左侧导航顶部背景图，建议较宽横图；留空使用渐变占位 | 空 |
| `lylmePrimary`（主题主色） | 形如 `#4f7cf7`，驱动按钮 / 徽标 / 高亮条 / 渐变强调色 | `#4f7cf7` |
| `lylmeScheme`（默认配色方案） | `auto` 跟随系统 / `default` 浅色 / `dark` 深色 | `auto` |
| `lylmeBlocks`（模块开关） | 见下方模块清单，可多选 | 全部开启 |
| `lylmeHotLimit`（热门内容数量） | 侧栏「热门内容」显示文章数 | `6` |
| `lylmeFooter`（页脚文案） | 支持简单 HTML；留空使用默认「© 年份 站点名 · Powered By LyLme Spage」 | 空 |
| `lylmeIcp`（备案号） | 例如「京ICP备xxxxxxxx号」，页脚以链接形式展示 | 空 |

### 模块开关（`lylmeBlocks`）

- `ShowHero` —— 首页顶部大标题（Hero，含统计与快速分类）
- `ShowCover` —— 列表文章封面缩略图（无封面时自动生成首字渐变占位）
- `ShowToc` —— 文章页目录（TOC）
- `ShowHotPosts` —— 侧栏热门内容（按浏览量）
- `ShowRecentComs` —— 侧栏最新评论
- `ShowCategories` —— 侧栏 / 导航分类
- `ShowTags` —— 侧栏标签云
- `ShowStats` —— 侧栏站点统计（文章 / 分类 / 评论）

---

## 交互能力（main.js）

1. 明暗配色切换（与后台共用 `localStorage.theme`，平滑过渡）
2. 轻提示 Toast
3. 复制文本（含 `execCommand` 降级）
4. 评论回复表单迁移（`TypechoComment.reply` / `cancelReply`，兼容层未定义时在此实现）
5. 文章目录 TOC（h2–h4，自动编号、折叠记忆、滚动高亮）
6. 阅读进度条
7. 返回顶部
8. 代码块复制按钮
9. 分享（复制链接）/ 打印
10. 侧栏开合记忆
11. 入视淡上动画（无 IntersectionObserver 或减少动效时自动跳过）
12. 顶栏滚动玻璃态
13. Hero 统计数字滚动
14. 侧栏子菜单手风琴开合

所有交互均尊重 `prefers-reduced-motion`，并在缺少相关 API 时优雅降级。

---

## 辅助函数（functions.php）

模板中可直接调用的助手函数（均带 `lylme_` 前缀，避免与核心冲突）：

- `lylme_opt($archive, $key, $default)` —— 读取主题配置（带默认值）
- `lylme_logo($archive)` —— 站点 LOGO 地址（归一化为根绝对路径，兼容子目录 / 反代）
- `lylme_site_base($archive)` / `lylme_asset($archive, $rel)` —— 站点根路径 / 根静态资源 URL
- `lylme_origin()` / `lylme_self_url()` / `lylme_abs_url()` —— 来源、当前绝对 URL、相对路径绝对化（用于 canonical / og:*）
- `lylme_og_image($archive)` —— 分享缩略图（封面优先，回退 LOGO）
- `lylme_asset_ver()` —— 静态资源版本号（改 `style.css` / `main.js` 后抬升以清缓存，当前 `20260905o`）
- `lylme_e($s)` —— HTML 转义简写
- `lylme_block_on($archive, $key)` —— 单模块开关查询
- `lylme_excerpt($archive, $len)` —— 文章摘要（优先 `art_excerpt`，回退正文首段，清洗 HTML）
- `lylme_cover_of($archive)` —— 封面 URL；`lylme_img_dims()` 取本地图尺寸防布局抖动
- `lylme_is_top($archive)` —— 是否置顶
- `lylme_time_ago($str)` —— 相对时间（刚刚 / x 分钟前 / … / 绝对日期）
- `lylme_archive_kind($archive)` —— 当前页语义（index / category / search / single / 404）
- `lylme_month_of($archive)` —— 月份归档友好标题（如「2026 年 9 月」）
- `lylme_hot_posts($limit)` / `lylme_tag_cloud($limit)` —— 热门文章 / 标签云
- `lylme_related_posts($row, $limit)` —— 同分类相关文章（无结果回退近期）
- `lylme_site_stats()` —— 文章 / 分类 / 评论计数
- `lylme_read_stat($archive)` —— 正文字数与预计阅读时长
- `lylme_search_url($keyword)` / `lylme_keywords_of($archive, $limit)` —— 统一搜索落地 URL / 当前行标签
- `lylme_palette($hex)` —— 由主色推导整套色板
- `lylme_hex_to_rgb()` / `lylme_rgb_to_hsl()` / `lylme_hsl_to_hex()` —— 颜色转换工具

---

## 二次开发

- **改样式**：编辑 `style.css`；修改后请同步抬高 `functions.php` 中 `lylme_asset_ver()` 的返回值，避免浏览器缓存旧文件。
- **改交互**：编辑 `main.js`，同样需抬高资源版本号。
- **改配置项**：在 `functions.php` 的 `themeConfig($form)` 中新增表单元素；保存值经 `article/config/lylme.json` 由 `Compat\App` 注入 Options，模板用 `$this->options->{name}` 读取。
- **新增布局**：复制对应模板（`index.php` / `post.php` 等）改造，保持 `header.php` 开 `col-lg-8`、`sidebar.php` 收 `col-lg-4`、`footer.php` 收尾的调用约定。
- **主色与色板**：仅需在后台修改「主题主色」即可联动全站强调色；如需自定义渐变逻辑，调整 `lylme_palette()`。

---

## 兼容与部署说明

- 子目录部署、反向代理（识别 `HTTP_X_FORWARDED_PROTO`）均已兼容，URL 与 LOGO 自动归一化为根绝对路径。
- 解析 `art_cover` / `art_keywords` / `art_excerpt` / `art_views` / `art_top` / `art_slug` / `art_author` 等字段；表名基于 `lylme_article` / `lylme_article_cat` / `lylme_article_comment`。
- 评论依赖 `Compat\CommentsWidget` 生成结构，`main.js` 负责回复表单迁移。
- 依赖数据库查询处均做了静默降级（捕获异常返回空），单篇页与列表页在数据库不可用时仍可渲染。

---

## 版本

- 主题：`1.0`
- 交互脚本：`1.0 / 2026-09-05`
- 作者：LyLme
- 主页：https://github.com/LyLme/

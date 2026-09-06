# LyLmecho

<p align="center">
  <a href="./LICENSE"><img src="https://img.shields.io/badge/license-GPL--2.0-orange" alt="License"></a>
  <img src="https://img.shields.io/badge/PHP-%3E%3D7.0-purple" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-%3E%3D5.6-orange" alt="MySQL">
  <img src="https://img.shields.io/badge/基于-LyLme%20Spage-4f7cf7" alt="基于 LyLme Spage">
</p>

> **LyLmecho** 是导航页 **六零导航页 / LyLme Spage** 的**一个独立全新版本（独立仓库）**。它在完整保留六零导航页(LyLme Spage)能力的基础上，**主要新增了一套完整的「文章 / 博客」模块**，并为文章模块实现了 **Typecho 主题兼容层**，使 Typecho 生态的主题无需修改即可直接运行——导航与博客二合一，简约而不简单。

---

## 功能特性

### 源自六零导航页（导航能力，完整保留）

- 多搜索引擎切换，后台可自定义增删与排序
- 分组导航：分组排序、分组/链接加密访问
- 在线收录申请：验证码 + 限流防护，后台审核
- 详情页模式：直接跳转 / 详情页两种运行模式，自动采集站点信息
- Bing 每日壁纸、随机一言、响应式多模板切换
- 完善的 WAF / CSRF / SSRF / 限流 / 文件校验等安全防护
- 后台链接、分组、搜索、主题、菜单、收录、账号安全等全套管理

### 新增：导航 × 博客 联动主题（默认主题 `template/lylmeblog/`）

- 安装默认主题即为 `lylmeblog`：与博客模块默认主题共用同一套皮肤（Lightyear 基座 + LyLme Modern），站点首页（`/`）与博客页（`/article/`）视觉完全同源，博客不再是"另一个站点"
- 首页原生内嵌博客联动区块：最新文章 / 热门文章 / 文章分类 / 博客信息卡，并配套博客直达与 RSS 入口
- Hero 搜索区提供「搜索引擎 / 站内检索 / 博客文章」三种模式，支持在已收录链接中即时过滤
- 博客端侧栏 / 页脚同步提供「返回导航首页」互链，形成导航 ⇄ 博客双向闭环

### 新增：文章 / 博客模块（`article/`）

| 能力 | 说明 |
| --- | --- |
| **文章发布** | 标题、分类、别名（slug）、封面、摘要、正文、SEO、发布时间、置顶、草稿/发布/待审三态 |
| **编辑器** | Vditor 富文本编辑器编写，保存 Markdown 源码，前台自动渲染（内置自包含 `Markdown` 渲染器） |
| **独立页面** | 支持「关于本站」等单页，前台 `?page=slug` / 伪静态 `/page/{slug}.html`，参与导航菜单 |
| **评论系统** | 三态开关（关闭 / 免登录 / 仅登录）、评论审核、嵌套回复、管理员回复、计数同步、提交限流与防刷 |
| **订阅输出** | 内置 RSS 2.0 / Atom / 评论订阅三种 feed 端点 |
| **URL 链接风格** | 5 种风格（default / post_id / id / slug / custom 自定义模板），前后台实时预览，伪静态规则一键复制 |
| **前台会员体系** | 自助注册、邮箱验证（内置自包含 SMTP 发信）、登录/个人中心、记住登录、暴破限流 |
| **投稿工作流** | `subscriber` / `contributor` / `editor` 三级角色，前台投稿 → 待审 → 编辑审核发布，全程权限隔离 |
| **主题系统** | `article/theme/` 多主题切换 + 自定义配置表单 |
| **Typecho 插件** | 兼容 Typecho 插件钩子体系，后台可启用/禁用/配置插件 |

### 核心亮点：Typecho 主题兼容层（`article/compat/`）

文章模块并非自建一套模板接口，而是实现了一个 **Typecho 兼容层**（约 16 个文件，覆盖 `Widget/Archive/Options/Db/Helper/Request/Form` 等 Typecho 主题常用 API），因此：

- 绝大多数 **Typecho 主题可直接放入 `article/theme/` 并后台一键切换**，无需改动
- 兼容 `index.php / post.php / page.php / comments.php / sidebar.php / functions.php` 等主题文件约定
- 内置 `lylme` 默认主题，开箱即用
- 详细实现与覆盖度说明见 [Typecho 兼容层对照与 API 清单](article/Typecho兼容层对照与API清单.md)

## 环境要求

| 组件 | 要求 |
| --- | --- |
| PHP | 导航内核 >= 5.6；**文章模块 / 兼容层要求 >= 7.0**（推荐 7.4 / 8.x） |
| MySQL | >= 5.6（推荐 5.7+） |
| Web 服务器 | Apache / Nginx |

**PHP 扩展**：mysqli、pdo_mysql、gd、curl、mbstring、xml、zip

## 快速开始

### 全新安装

1. 将项目源码上传至网站根目录
2. 访问 `http://域名/install`，按提示配置数据库完成安装
3. 后台地址：`http://域名/admin`，默认账号密码：`admin` / `123456`
4. 进入后台「文章 → 模块配置」，开启「文章模块开关」，即可开始写文章

### 从既有六零导航页升级

已部署 LyLme Spage 的站点，接入文章模块只需两步：

1. 用 `install/data/install_struct.sql` 中新增的建表语句，补齐文章模块所需的 6 张数据表
2. 将程序文件升级到本分支（`include/version.php` 版本号抬升后，访问任意页面会自动触发 `include/updbase.php` 增量升级）

### 伪静态

- `.htaccess`（Apache）与 `nginx.htaccess`（Nginx）均已内置文章模块的伪静态规则
- 在后台「文章 → 模块配置 → 链接设置」选择 URL 风格后，Apache 开启 Rewrite 即可；Nginx 请参照 `nginx.htaccess` 中的注释把规则加入站点配置

## 目录结构

在六零导航页（LyLme Spage）既有结构之上，本仓库（独立版本）**主要新增/改动**如下：

```
LyLmecho/
├── index.php                 # 前台导航入口（上游）
├── admin/                    # 后台管理
│   ├── article.php           #   文章 / 独立页面管理（Vditor 编辑器）
│   ├── article_cat.php       #   文章分类管理
│   ├── article_comment.php   #   评论管理（审核 / 回复时间轴）
│   ├── article_config.php    #   文章模块配置（基本/会员/邮件/链接 四个 Tab）
│   ├── article_theme.php     #   文章主题管理
│   ├── article_plugin.php    #   Typecho 插件管理
│   ├── article_member.php    #   前台会员管理
│   ├── ajax_article.php      #   文章模块统一 AJAX 接口
│   └── head.php              #   后台公共头部（侧边栏已挂「文章」菜单）
├── article/                  # ★ 文章 / 博客模块
│   ├── index.php             #   前台入口 + 路由分发
│   ├── common.php            #   公共初始化（加载核心 + 兼容层）
│   ├── comment.php           #   评论提交处理
│   ├── feed.php              #   RSS 2.0 / Atom / 评论订阅
│   ├── member.php            #   前台会员：登录 / 注册 / 个人中心
│   ├── member_post.php       #   前台投稿（含草稿 / 预览）
│   ├── member_moderate.php   #   编辑审核工作台（投稿 / 评论）
│   ├── compat/               #   Typecho 兼容层（16 个文件）
│   │   ├── bootstrap.php     #     兼容层加载入口（别名 / 常量 / 全局函数）
│   │   ├── App.php           #     应用容器：主题目录、URL 重写、链接生成
│   │   ├── Archive.php       #     Widget_Archive 主组件（主题中的 $this）
│   │   ├── TypechoCompat.php #     Typecho 命名空间主兼容类
│   │   ├── TypechoDb.php     #     Typecho 数据库兼容单例
│   │   ├── Markdown.php      #     自包含 Markdown → HTML 渲染器
│   │   ├── Mailer.php        #     自包含 SMTP 客户端
│   │   ├── Member.php        #     会员核心类
│   │   └── ...               #     Options / BaseWidget / User / Author /
│   │                         #     Stat / CommentsWidget / SidebarWidgets /
│   │                         #     PluginManager
│   ├── theme/                #   文章主题（内置 lylme；放置任意 Typecho 主题可后台切换）
│   ├── plugins/              #   Typecho 插件目录
│   └── config/               #   主题 / 插件配置持久化（JSON）
├── install/data/install_struct.sql  # 安装结构（含文章模块 6 张表）
├── include/
│   ├── version.php           # 版本号（v2.7.1）
│   └── updbase.php           # 数据库升级脚本（含文章模块增量升级）
├── .htaccess                 # Apache 伪静态（含文章模块规则）
├── nginx.htaccess            # Nginx 伪静态（含文章模块规则）
└── template/                 # 导航主题（上游，default / ltab / liquidglass 等）
```

## 文章模块路由

| 路由 | 说明 |
| --- | --- |
| `article/` 或 `?s=` | 文章首页 / 搜索 |
| `?id=xx` / `?slug=xx` | 单篇文章（浏览量 +1，会话去重） |
| `?cat=xx` | 分类列表（支持别名或 ID） |
| `?month=YYYY-MM` | 按月归档 |
| `?page=N` | 分页 |
| `?page=slug` | 独立页面（slug 为非数字，避免与分页冲突） |
| `?comment=xx`（POST） | 评论提交 |
| `?feed=rss\|atom\|comments\|rdf` | 订阅输出 |
| `?member=...` | 登录 / 注册 / 个人中心 / 投稿 / 审核 |

> 文章地址受「链接风格」影响：启用伪静态后可呈现 `post1.html`、`1.html`、`hello-world.html` 等形态。

## 文章模块数据表

| 数据表 | 说明 |
| --- | --- |
| `lylme_article` | 文章（正文、分类、作者、封面、浏览量、评论数、置顶、状态、SEO 等） |
| `lylme_article_cat` | 文章分类 |
| `lylme_article_comment` | 评论（嵌套、审核、访客信息、绑定会员 UID） |
| `lylme_article_page` | 独立页面 |
| `lylme_article_config` | 模块配置（键值对：开关 / 主题 / 评论 / 会员 / SMTP / URL 风格等） |
| `lylme_article_user` | 前台会员（bcrypt 密码、角色、状态、邮箱验证、可撤销 token） |

## 相关文档

| 文档 | 说明 |
| --- | --- |
| [文章模块开发进度](article/开发进度.md) | 模块开发历程、决策记录与验证结果 |
| [Typecho 兼容层对照与 API 清单](article/Typecho兼容层对照与API清单.md) | 兼容层 API 对照、覆盖度与缺口、降级说明 |

## 与主仓库（lylme_spage）的关系

- **主仓库**：[六零导航页 LyLme Spage](https://github.com/LyLme/lylme_spage)仅包含导航页功能，**不含 Typecho / 文章模块代码**
- **本仓库（LyLmecho）**：**独立的全新仓库**，相当于 LyLme Spage 的**另一个版本**（导航 + 博客增强线），在共用导航内核的基础上，新增文章 / 博客模块、前台会员体系与 Typecho 兼容层，独立维护、独立发布
- 两仓库相互独立、互不影响：主仓库代码不涉及 Typecho；本仓库因集成 Typecho 代码而整体采用 GPL-2.0（见下方 License）
- 导航功能效果可参考官方演示站 <https://hao.lylme.com>；文章模块以本仓库部署为准

## License

LyLmecho 是一个独立的全新仓库，区别于主仓库 [lylme_spage](https://github.com/LyLme/lylme_spage)（后者不含 Typecho 相关代码）。由于本仓库的文章模块**集成了基于 Typecho 实现的 Typecho 兼容层**，而 Typecho 采用 [GPL-2.0](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html) 协议，故 **LyLmecho 整体以 GNU General Public License v2.0 对外分发**，完整许可文本见 [LICENSE](LICENSE)。主仓库及其中源码的许可不因本仓库而改变。

**第三方代码致谢**：

| 内容 | 代码范围 | 许可 |
| --- | --- | --- |
| [六零导航页 LyLme Spage](https://github.com/LyLme/lylme_spage) | 导航内核、后台框架、导航主题（同源） | [Apache License 2.0](https://www.apache.org/licenses/LICENSE-2.0) |
| [Typecho](https://github.com/typecho/typecho) | 文章模块 Typecho 兼容层（`article/compat/`）及相关移植实现 | [GPL-2.0](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html) |
| Bootstrap / jQuery / Font Awesome / Vditor 等 | 前端依赖库 | 各自的开源许可 |

---

Copyright &copy; LyLmecho Contributors. 同源的 [LyLme Spage](https://github.com/LyLme/lylme_spage)（Apache-2.0）与 [Typecho](https://typecho.org)（GPL-2.0）版权归其各自作者所有。

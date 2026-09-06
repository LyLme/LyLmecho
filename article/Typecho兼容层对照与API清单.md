# Typecho 兼容层对照与 API 清单

> 更新时间：2026-09-06
> 目录：`article/compat/`（共 16 个文件，含新增 `PluginManager.php`）
> 作用：使 Typecho 主题无需修改即可在六零导航页文章模块上运行

---

# 第一部分：Typecho 原始 API 清单（参考基线）

> 来源：Typecho v1.3.0。仅作对照基线，兼容层不追求 1:1 复刻。

## 一、Typecho 核心类 (`Typecho\*`)

### 1.1 `Typecho\Common`
`url` / `init` / `error` / `isAvailableClass` / `arrayFlatten` / `nativeClassName` / `splitByCount` / `fixHtml` / `stripTags` / `filterSearchQuery` / `slugName` / `safeUrl` / `buildUrl` / `removeXSS` / `subStr` / `strBy` / `strLen` / `hashValidate` / `hash` / `randString` / `timeToken` / `timeTokenValidate` / `gravatarUrl` / `shuffleScriptVar` / `buildBackupBuffer` / `extractBackupBuffer` / `checkSafeHost` / `isAppEngine` / `mimeContentType` / `mimeIconType` / `idnToUtf8`；常量 `VERSION='1.3.0'`；全局函数 `_t`/`_e`/`_n`。

### 1.2 `Typecho\Widget`
静态：`widget`/`alloc`/`allocWithAlias`/`destroy`；实例：`execute`/`on`/`to`/`template`/`parse`/`toColumn`/`toArray`/`next`/`push`/`pushAll`/`alt`/`altBy`/`have`/`pluginHandle`；魔术 `__get`/`__set`/`__call`/`__isSet`；属性 `$parameter`/`$stack`/`$row`/`$length`/`$sequence`。

### 1.3 `Typecho\Db`
常量：`READ`/`WRITE`/`SORT_ASC`/`SORT_DESC`/`INNER_JOIN`/`OUTER_JOIN`/`LEFT_JOIN`/`RIGHT_JOIN`/`SELECT`/`UPDATE`/`INSERT`/`DELETE`；方法：`__construct`/`select`/`update`/`delete`/`insert`/`truncate`/`query`/`fetchAll`/`fetchRow`/`fetchObject`/`sql`/`getAdapter`/`getPrefix`/`addServer`/`getVersion`/`set`/`get`。

### 1.4 `Typecho\Db\Query`（注意：命名空间为 `Typecho\Db\Query`）
`setDefault`/`getParams`/`getAttribute`/`cleanAttribute`/`join`/`where`/`quoteValues`/`quoteValue`/`orWhere`/`limit`/`offset`/`page`/`rows`/`expression`/`order`/`group`/`having`/`select`/`from`/`update`/`delete`/`insert`/`prepare`/`__toString`。

### 1.5 `Typecho\Config`（实现 Iterator + ArrayAccess）
`factory`/`setDefault`/`isEmpty`/`rewind`/`current`/`next`/`key`/`valid`/`__get`/`__set`/`__call`/`__isSet`/`__toString`/`toArray`/`offsetExists`/`offsetGet`/`offsetSet`/`offsetUnset`。

### 1.6 `Typecho\Cookie`
`getPrefix`/`setPrefix`/`getPath`/`getDomain`/`getSecure`/`setOptions`/`get`/`set`/`delete`（均为静态）。

### 1.7 `Typecho\Date`
静态 `$timezoneOffset`/`$serverTimezoneOffset`/`$serverTimeStamp`；实例 `$timeStamp`/`$year`/`$month`/`$day`；`__construct`/`setTimezoneOffset`/`format`/`word`/`gmtTime`/`time`。

### 1.8 `Typecho\Router`
静态 `$current`；`match`/`dispatch`/`url`/`setRoutes`/`get`。

### 1.9 `Typecho\Plugin`
`init`/`factory`/`activate`/`deactivate`/`export`/`parseInfo`/`portal`/`checkDependence`/`exists`/`trigger`/`call`/`filter`/`__get`/`__set`。

### 1.10 `Typecho\Request`
单例 `getInstance`；`beginSandbox`/`endSandbox`/`proxy`/`endProxy`/`get`/`__get`/`__isset`/`getArray`/`from`/`filter`/`getRequestRoot`/`getRequestUrl`/`makeUriByRequest`/`getPathInfo`/`getContentType`/`getServer`/`getIp`/`getHeader`/`getAgent`/`getReferer`/`isSecure`/`isCli`/`isGet`/`isPost`/`isPut`/`isAjax`/`isJson`/`is`/`getRequestUri`/`getUrlPrefix`。

### 1.11 `Typecho\Response`
单例 `getInstance`；`beginSandbox`/`endSandbox`/`enableAutoSendHeaders`/`clean`/`sendHeaders`/`respond`/`setStatus`/`setHeader`/`setCookie`/`setContentType`/`getCharset`/`setCharset`/`addResponder`。

### 1.12 `Typecho\Feed`（常量 RSS1/RSS2/ATOM1/ATOM03）
`__construct`/`getType`/`setTitle`/`setSubTitle`/`setFeedUrl`/`getFeedUrl`/`setBaseUrl`/`addItem`/`dateFormat`/`__toString`。

### 1.13 `Typecho\Validate`
静态：`minLength`/`enum`/`maxLength`/`email`/`url`/`alpha`/`alphaNumeric`/`alphaDash`/`xssCheck`/`isFloat`/`isInteger`/`regexp`；实例：`addRule`/`setBreak`/`run`/`confirm`/`required`。

### 1.14 `Typecho\I18n`
`translate`/`ngettext`/`dateWord`/`addLang`/`getLang`/`setLang`（静态）。

### 1.15 `Typecho\Exception`
`__construct`（`message`,`code`）。

## 二、Widget 辅助类 (`Typecho\Widget\Helper\*`)

- `Form`：`setAction`/`setMethod`/`setEncodeType`/`addInput`/`getInput`/`getAllRequest`/`getValues`/`getInputs`/`validate`/`getParams`/`render`；常量 `POST_METHOD`/`GET_METHOD`/`STANDARD_ENCODE`/`MULTIPART_ENCODE`/`TEXT_ENCODE`。
- `Form\Element`（抽象）：`init`/`label`/`container`/`input`(抽象)/`value`/`description`/`message`/`multiline`/`multiMode`/`addRule`/`setInputsAttribute`/`inputValue`(抽象)/`filterValue`。
- `PageNavigator`（抽象）：`__construct(total,currentPage,pageSize,pageTemplate)`/`setPageHolder`/`setAnchor`/`render`(抽象)；子类 `Box`(`render`)/`Classic`(`render`/`prev`/`next`)。
- `Layout`：`setAttribute`/`removeItem`/`getItems`/`getTagName`/`setTagName`/`removeAttribute`/`getAttribute`/`setClose`/`getParent`/`setParent`/`appendTo`/`addItem`/`__get`/`__set`/`render`/`start`/`html`/`end`。
- `EmptyClass`：单例 `getInstance`；`__call`/`__get` 返回 `$this`。

## 三、Widget 组件类（`Widget\*`）

- `Widget\Archive`（核心主题类 `$this`，40+ 方法）：`addArchiveTitle`/`getArchiveTitle`/`setArchiveTitle`/`getArchiveSlug`/`setArchiveSlug`/`getArchiveType`/`setArchiveType`/`getArchiveUrl`/`setArchiveUrl`/`getArchiveDescription`/`setArchiveDescription`/`getArchiveKeywords`/`setArchiveKeywords`/`getArchiveFeedAtomUrl`/`setArchiveFeedAtomUrl`/`getArchiveFeedRssUrl`/`setArchiveFeedRssUrl`/`getArchiveFeedUrl`/`setArchiveFeedUrl`/`getFeed`/`setFeed`/`getCountSql`/`setCountSql`/`getCurrentPage`/`getTotalPage`/`getTotal`/`setTotal`/`getThemeFile`/`setThemeFile`/`getThemeDir`/`setThemeDir`/`execute`/`select`/`content`/`pageNav`/`pageLink`/`comments`/`pings`/`attachments`/`theNext`/`thePrev`/`theLink`/`related`/`header`/`footer`/`remember`/`archiveTitle`/`keywords`/`need`/`render`/`is`/`query` + 私有 Handle（`indexHandle`/`singleHandle`/`categoryHandle`/`tagHandle`/`authorHandle`/`dateHandle`/`searchHandle`/`error404Handle` 等）。
- `Widget\Options`：`execute`/`themeFile`/`siteUrl`/`index`/`themeUrl`/`pluginUrl`/`pluginDir`/`adminUrl`/`adminStaticUrl`/`commentsHTMLTagAllowed`/`plugin`/`personalPlugin` + 大量动态属性（`___method` 魔术）。
- `Widget\User`：`execute`/`hasLogin`/`logout`/`login`/`commitLogin`/`simpleLogin`/`pass`。
- `Widget\Stat`：22 个动态属性（`___method`）：`publishedPostsNum`/`waitingPostsNum`/`draftPostsNum`/`myPublishedPostsNum`/`myWaitingPostsNum`/`myDraftPostsNum`/`currentPublishedPostsNum`/`currentWaitingPostsNum`/`currentDraftPostsNum`/`publishedPagesNum`/`draftPagesNum`/`publishedCommentsNum`/`waitingCommentsNum`/`spamCommentsNum`/`myPublishedCommentsNum`/`myWaitingCommentsNum`/`mySpamCommentsNum`/`currentCommentsNum`/`currentPublishedCommentsNum`/`currentWaitingCommentsNum`/`currentSpamCommentsNum`/`categoriesNum`/`tagsNum`。
- `Widget\Comments\Archive`：`num`/`execute`/`push`/`pageNav`/`listComments`/`threadedCommentsCallback`/`levelsAlt`/`alt`/`reply`/`threadedComments`/`cancelReply` + 魔术 `___children`/`___isTopLevel`/`___commentPage`/`___parentContent`。
- `Widget\Comments\Ping`：`num`/`execute`/`listPings`/`___parentContent`。
- `Widget\Comments\Recent`：`execute`。
- `Widget\Users\Author`：`execute`。
- `Widget\Metas\Category\Rows`：`execute`/`listCategories`（TreeViewTrait，`levels`/`children`）。
- `Widget\Metas\Category\Related`：`execute`（TreeTrait）。
- `Widget\Metas\Tag\Cloud`：`execute`/`split`。
- `Widget\Metas\Tag\Related`：`execute`。
- `Widget\Metas\From`：`execute`（TreeTrait）。
- `Widget\Contents\From`：`execute`（TreeTrait）。
- `Widget\Contents\Related`：`execute`。
- `Widget\Contents\Related\Author`：`execute`。
- `Widget\Contents\Post\Recent`：`execute`。
- `Widget\Contents\Post\Date`：`execute`。
- `Widget\Contents\Page\Rows`：`execute`/`listPages`（TreeViewTrait）。
- `Widget\Contents\Attachment\Related`：`execute`。
- `Widget\Upload`：`deleteHandle`/`attachmentHandle`/`attachmentDataHandle`/`uploadHandle`/`modifyHandle`/`checkFileType`/`action`/`upload`/`modify`。

## 四、Trait / 接口 / 全局函数 / 主题回调

- Trait：`Widget\Base\TreeViewTrait`（`listRows`/`treeViewRowsCallback`/`treeViewRows`）、`Widget\Base\TreeTrait`（`getRows`/`getAllParents`/`getAllParentsSlug`/`getAllChildIds`）。
- 接口：`Router\ParamsDelegateInterface`/`Base\QueryInterface`/`Base\RowFilterInterface`/`Base\PrimaryKeyInterface`/`Base\ParamsDelegateInterface`/`ActionInterface`。
- 全局函数：`_t`/`_e`/`_n`。
- 主题回调：`themeInit`/`treeViewCategoriesCallback`/`treeViewPagesCallback`/`threadedComments`/`singlePing`。

---

# 第二部分：兼容层实现清单

## 一、文件总览

| 文件 | 说明 |
| --- | --- |
| `bootstrap.php` | 加载入口：注册所有别名、全局函数、常量 |
| `App.php` | 应用容器：站点 URL、主题路径、URL 重写、链接生成 |
| `Options.php` | 站点配置对象 |
| `BaseWidget.php` | Widget 基类：行迭代、字段访问、列名映射 |
| `Archive.php` | 归档主组件（主题模板中的 `$this`） |
| `Author.php` | 作者对象 |
| `User.php` | 用户组件（登录态检测） |
| `Stat.php` | 统计组件 |
| `CommentsWidget.php` | 评论组件（树形评论、Gravatar、回复） |
| `SidebarWidgets.php` | 侧边栏 Widget 集合（7 个子类） |
| `Markdown.php` | Markdown → HTML 渲染器 |
| `Mailer.php` | 自包含 SMTP 邮件客户端 |
| `Member.php` | 会员核心类（注册/认证/校验） |
| `PluginManager.php` | 插件管理器：扫描 `article/plugins`、启用/停用与配置持久化、前端加载激活插件 |
| `TypechoCompat.php` | Typecho 命名空间类集合（Widget/Common/Cookie/Router/Date/Config/Request/Plugin 等） |
| `TypechoDb.php` | 数据库兼容（Db/DbAdapter/DbQuery 链式查询） |

## 二、`bootstrap.php` 注册表

- 旧式 `Widget_*` 别名：`Widget_Options`/`Widget_User`/`Widget_Stat`/`Widget_Archive`/`Widget_Contents_Page_List`/`Widget_Notice`（安全空实现）/`Widget_Metas_Category_Rows`/`Widget_Contents_Post_Recent`/`Widget_Comments_Recent`/`Widget_Contents_Post_Date`/`Widget_Abstract_Contents`/`Widget_Abstract_Comments`/`Widget_Abstract_Metas`。
- 命名空间 `\Widget\*` 别名：`\Widget\Archive`/`\Widget\Contents\Post\Recent`/`\Widget\Contents\Page\Rows`/`\Widget\Comments\Recent`/`\Widget\Comments\Archive`/`\Widget\Users\Author`/`\Widget\Metas\Category\Rows`/`\Widget\Metas\Category\Related`/`\Widget\Metas\Tag\Cloud`/`\Widget\Metas\Tag\Related`/`\Widget\Metas\From`/`\Widget\Contents\From`/`\Widget\Contents\Related`/`\Widget\Contents\Related\Author`/`\Widget\Contents\Attachment\Related`/`\Widget\Comments\Ping`/`\Widget\Upload`（后 4 个指向 `PlaceholderWidget`）。
- `Typecho_*` 旧式别名：`Typecho_Widget_Helper_PageNavigator`/`Typecho_Widget_Helper_Form`/`Typecho_Common`/`Typecho_Cookie`/`Typecho_Db`/`Typecho_Request`/`Typecho_Router`/`Typecho_Date`/`Typecho_Config`/`Typecho_Widget`/`Typecho_Plugin`/`Typecho_Widget_Helper_Form_Element_*`（Text/Textarea/Password/Checkbox/Radio/Select/Hidden/Submit/Number/Url）/`Typecho_Widget_Helper_Layout`/`Typecho_Widget_Helper_EmptyClass`/`Typecho_Validate`/`Typecho_Response`/`Typecho_Feed`/`Typecho_I18n`/`Typecho_Exception`/`Typecho_Db_Query`→`Typecho\DbQuery`/`Typecho_Db_Exception`→`Typecho\Exception`/`Typecho_Db_Adapter`→`Typecho\DbAdapter`。
- 其他：`\Helper` → `Typecho\Widget\Helper\Helper`；`Typecho_Plugin_Interface` 接口；`__TYPECHO_THEME_DIR__='/article/theme'`；`_t()`/`_e()` 函数；`ArticleFields`（自定义字段对象）。

## 三、列名映射（Typecho → lylme）

| Typecho 列 | lylme 列 |
| --- | --- |
| `cid` | `art_id` |
| `title` | `art_title` |
| `slug` | `art_slug` |
| `created` | `art_time` |
| `modified` | `art_update` |
| `text` | `art_content` |
| `excerpt` | `art_excerpt` |
| `status` | `art_status` |
| `commentsNum` | `art_comments` |
| `views` | `art_views` |
| `authorId` | `art_author` |
| `category` | `cat_id` |

评论表额外：`coid→com_id`、`mail→com_email`、`url→com_url`、`author→com_name`、`created→com_time`、`parent→com_pid`、`status→com_status`、`uid→com_uid`。用户表：`screenName→nickname`、`name→username`、`mail→email`、`group→role`。

## 四、表名映射

| Typecho 表 | lylme 表 |
| --- | --- |
| `contents` | `lylme_article` |
| `options` | `lylme_article_config` |
| `comments` | `lylme_article_comment` |
| `metas` | `lylme_article_cat` |
| `relationships` | `lylme_article_relationship` |
| `fields` | `lylme_article`（重定向，仅 `views` 等列） |
| `users` | `lylme_article_user` |
| `links` | `lylme_links` |

# 第三部分：覆盖度与缺口深度检查（2026-09-06）

> 经逐文件比对 `article/typecho/` 原始源码与 `article/compat/` 实际实现得出。
> 图例：✅ 已实现 ｜ 🟡 降级/占位 ｜ ❌ 缺失 ｜ 优先级 P0(必现 fatal) / P1(常用主题易触发) / P2(罕见) / P3(系统内部，主题基本用不到)。

## 3.1 命名空间类名（嵌套 `Typecho\Db\*`）

Db 系兼容层以「实体置扁平 + 嵌套命名空间别名」双层结构提供，原始嵌套类均可解析：

| 原始类 | 兼容层现状 | 说明 |
| --- | --- | --- |
| `Typecho\Db` | ✅ `Typecho\Db`（实体） | 真实实现 |
| `Typecho\Db\Query` | ✅ `class Query extends \Typecho\DbQuery` | 嵌套别名 |
| `Typecho\Db\Adapter` | ✅ `class Adapter extends \Typecho\DbAdapter` | 嵌套别名 |
| `Typecho\Db\Exception` | ✅ `class Exception extends \Typecho\Exception` | 嵌套别名 |
| `Typecho\Db\SQLException` | ✅ `class SQLException extends \Typecho\Exception` | 嵌套别名 |

`new \Typecho\Db\Query($db)`、`catch (\Typecho\Db\Exception $e)`、`implements \Typecho\Db\Adapter` 等嵌套写法均可正常解析；旧式 `Typecho_Db_*` 全局别名指向扁平实体，行为一致。

## 3.2 全局 `DB` 类与 `use Typecho\Db` 命名冲突（已知限制，P0 级潜在 fatal）

- lylme 自身在全局命名空间定义了 `class DB`（`include/db.class.php`）。
- PHP 类名**大小写不敏感**，`DB` 与 `Db` 视为同名。
- 因此**任何主题/插件在全局命名空间文件里写 `use Typecho\Db;` 或 `use Typecho\Db as Db;` 都会触发**
  `Cannot use Typecho\Db as Db because the name is already in use` 致命错误。
- 已验证主题均用全限定 `\Typecho\Db::get()` 或自带命名空间，故未暴露；但这是一种**兼容性缺口**，应在文档明确标注，并建议主题侧改用全限定写法。
- 兼容层无法在 `use` 语句层面规避（`class_alias('Typecho\Db','Db')` 自身也会撞全局 `DB`）。
- **主题侧规避**：全局命名空间文件内勿写 `use Typecho\Db`，改用全限定 `\Typecho\Db::…`，或让主题自带命名空间（已验证主题均无此问题）。

## 3.3 `Typecho\Common` 方法覆盖

✅ 全部覆盖：`url`/`subStr`/`shuffleScriptVar`/`gravatarUrl`/`markdown`/`stripTags`/`fixHtml`/`slugName`/`safeUrl`/`removeXSS`/`randString`/`hash`/`strLen`/`strBy`/`isAvailableClass`/`nativeClassName`/`arrayFlatten`/`splitByCount`/`mimeContentType`/`idnToUtf8`/`parseDate`/`rid`/`init`/`error`/`filterSearchQuery`/`buildUrl`/`hashValidate`/`timeToken`/`timeTokenValidate`/`buildBackupBuffer`/`extractBackupBuffer`/`checkSafeHost`/`isAppEngine`/`mimeIconType`/`VERSION`。

> 说明：`buildUrl` 委托 `Typecho\Router::url`；`hashValidate` 用 `hash_equals` 比较 `Common::hash` 值；`timeToken`/`timeTokenValidate` 以 `md5(VERSION+时间戳)` 前 16 位自校验；`buildBackupBuffer`/`extractBackupBuffer` 基于 `gzcompress`+`base64`（解压失败原样返回）；`isAppEngine` 返回 false（非 SAE）；`mimeIconType` 内置常用扩展名→图标关键字映射，未命中返回 `file`。

## 3.4 `Typecho\Widget` 方法覆盖

✅ `Typecho\Widget` 基类已实现：`widget`(工厂)/`alloc`/`allocWithAlias`/`destroy`/`to`/`push`/`pushAll`/`next`/`have`/`reset`/`parse`/`template`/`toColumn`/`toArray`/`alt`/`altBy`/`pluginHandle`/`on`/`__get`/`__set`/`__call`/`__isSet`/`param`/`execute`/`__construct`。

> 说明：`__isSet` 即魔术 `__isset`（PHP 方法名大小写不敏感），`isset($widget->x)` 与显式 `$widget->__isSet('x')` 均可；`template()` 加载 `Compat\App::$themeDir` 下的模板文件；`parse()` 以 `{field}` 占位逐行替换输出；`toColumn()`/`toArray()` 支持按列提取（兼容 `Compat\BaseWidget` 语义）。

## 3.5 `Typecho\Db` / `Typecho\DbQuery` 方法缺口

✅ `Db`：常量 `SORT_ASC/DESC/JOIN_*/LEFT_JOIN/RIGHT_JOIN/INNER_JOIN/FULL_JOIN/OUTER_JOIN/READ/WRITE/SELECT/UPDATE/INSERT/DELETE`；`setDb`/`get`/`table`/`rawTable`/`isOptions`/`column`/`mapOptionsRow`/`mapContentsRow`/`getPrefix`/`getAdapterName`/`getVersion`/`sql`/`truncate`/`addServer`/`set`/`getAdapter`/`select`/`update`/`insert`/`delete`/`fetchRow`/`fetchAll`/`fetchObject`/`query`/`exec`。
🟡 `sql()` 返回空 Query（符合预期）。

✅ `DbQuery`：`fromMixed`/`select`/`from`/`update`/`insert`/`delete`/`where`/`order`/`group`/`having`/`join`/`limit`/`offset`/`page`/`rows`/`__toString`/`fetchRow`/`fetchAll`/`fetchObject`/`query`/`exec`/`getParams`/`getAttribute`/`cleanAttribute`/`setDefault`/`quoteValue`/`expression`。

✅ `DbQuery` 补充方法：`quoteValues`（数组→`'a','b'`）/`orWhere`（OR 连接，与 `where` 同款列映射与占位绑定）/`prepare`（参数已就地绑定，返回当前 SQL）。

> 说明：
> - `where`/`orWhere` 均支持 `?` 占位、IN 数组绑定、表前缀/列名映射与 Typecho 枚举值翻译；条件以 AND/OR 胶水按序拼接。
> - `Db::query()` 为单参数签名 `query($query)`（多余实参由 PHP 自动忽略），统一经全局 `DB` 执行，不区分 READ/WRITE 连接。
> - `fields` 表语义：UPDATE/DELETE/INSERT 重写路径剥离 `name=?` 条件（`name` 在 `lylme_article` 无对应列，保留会触发 SQL 错误）；SELECT 路径保留 `name` 由 `buildFieldsQuery()` 提取映射列（`views→art_views` 等）。

## 3.6 `Typecho\Cookie` / `Typecho\Date` / `Typecho\Router` / `Typecho\Config`

- `Cookie`：✅ 全覆盖（含 `has()`），`set`/`delete` 已做 `headers_sent()` 安全降级。
- `Date`：✅ 全覆盖（`$timezoneOffset`/`$serverTimezoneOffset`/`$timeStamp`/`$year`/`$month`/`$day` 及 `setTimezoneOffset`/`format`/`word`/`gmtTime`/`time`/`__toString`）。
- `Router`：🟡 `match`/`dispatch`/`url`/`setRoutes`/`get`/`getCurrent` 已覆盖，但 `url()` 签名与原始（`$type,$params,$prefix`）不同（兼容层取 `$row,$indexBase`），且路由表未接入真实文章路由（仅占位）。P2。
- `Config`：✅ 完整实现 `Iterator`+`ArrayAccess` 及全部魔术方法。

## 3.7 `Typecho\Plugin` / `Typecho\Request` / `Typecho\Response` / `Typecho\Feed` / `Typecho\I18n` / `Typecho\Validate`

- `Plugin`：✅ 真实钩子系统：`factory($component)` 返回钩子代理（`->header = 'cb'` 或 `->header('cb')` 注册回调），`export($component,$method,...)` 触发并拼接各回调输出，`exists()` 判断组件/方法钩子是否注册；另提供 `init`/`portal`/`checkDependence`/`trigger`/`call`/`filter`/`activate`/`deactivate`/`parseInfo`。插件扫描、启用/停用与配置由 `Compat\PluginManager` 承担（`scan`/`activate`/`deactivate`/`loadActivated`/`buildConfigForm`，状态持久化于 `lylme_article_config`）；`activate`/`deactivate`/`parseInfo` 委托 `Compat\PluginManager`，`trigger`/`call` 等价于 `export`，后台 `admin/article_plugin.php` 对接。
- `Request`：✅ 全覆盖：`getInstance`/`get`/`getArray`/`from`/`filter`/`getRequestRoot`/`getRequestUrl`/`getRequestUri`/`getPathInfo`/`getContentType`/`getServer`/`getHeader`/`getAgent`/`getUrlPrefix`/`makeUriByRequest`/`getIp`/`getReferer`/`isPost/Get/Put/Secure/Cli/Ajax/Json`/`is`/`setType`/`getType`/`beginSandbox`/`endSandbox`/`proxy`/`endProxy`。
- `Response`：✅ 全覆盖：`getInstance`/`setStatus`/`setHeader`/`setCookie`/`setContentType`/`getCharset`/`setCharset`/`respond`/`beginSandbox`/`endSandbox`/`enableAutoSendHeaders`/`sendHeaders`/`clean`/`addResponder`。
- `Feed`：✅ 按 RSS1/RSS2/Atom 1.0/Atom 0.3 渲染：`__construct`/`getType`/`setTitle`/`setSubTitle`/`setFeedUrl`/`getFeedUrl`/`setBaseUrl`/`dateFormat`/`addItem`/`getItems`/`__toString`（内部 `esc`/`itemLink`/`itemDate`/`itemDesc` 处理）；站点 RSS 主出口仍为 `article/feed.php`。
- `I18n`：✅ `translate`（查静态翻译表，未命中返回原文）/`e`/`n`/`ngettext`/`dateWord`/`addLang`/`setLocale`/`getLocale`/`isAvailable` + 静态 `getLang`/`setLang`；`addLang` 支持数组或语言文件路径。
- `Validate`：✅ 静态校验 + 实例 `addRule`/`setBreak`/`run`/`confirm`/`required` 已覆盖（主题表单校验可用）。

## 3.8 `Typecho\Widget\Helper\*` 缺口

- `Form`：✅ 覆盖 `setAction`/`setMethod`/`setEncodeType`/`addInput`/`addItem`/`getItems`/`getInput`/`getValues`/`getAllRequest`/`getInputs`/`getParams`/`validate`/`render` + 5 常量。
- `Form\Element\AbstractElement`：✅ 覆盖 `getName`/`getValue`/`getLabel`/`getDescription`/`getOptions`/`addRule`/`init`/`label`/`value`/`description`/`container`/`message`/`multiline`/`multiMode`/`input`/`inputValue`/`setInputsAttribute`；`$input` 为 `Layout` 实例，支持 `$element->input->setAttribute(...)`。
- `PageNavigator` 系列：✅ `PageNavigator` 基类 + `Box`/`Classic` 子类均实现（含 `setPageHolder`/`setAnchor`/`getPageLink`/`getTotalPage`/`getCurrentPage`/`render`）。
- `Layout`：✅ 全覆盖。
- `EmptyClass`：✅ 全覆盖。

## 3.9 `Widget\Base\*` 与完整 `Widget\Archive` 方法集（结构性降级）

兼容层**未逐类移植** `Widget\Base\Contents`/`Comments`/`Users`/`Metas`/`Options` 及 `Widget\Archive` 的 40+ 原始方法，而是以 `Compat\Archive`（主题 `$this`）对外提供**聚焦子集**。原始 `Widget\Archive`/`Widget\Options`/`Widget\User`/`Widget\Stat`/`Widget\Comments\Archive` 中**大量方法未单独实现**（如 `getArchiveTitle/Slug/Type/Url/Description/Keywords/FeedUrl` 系列、`getCountSql/setCountSql`、`getThemeFile/setThemeFile/setThemeDir`、`indexHandle`/`singleHandle`/`categoryHandle`/`tagHandle`/`authorHandle`/`dateHandle`/`searchHandle`/`error404Handle`、`pings`/`attachments`/`related`/`pageLink`/`theLink`、`Widget\Options::themeFile`/`plugin`/`commentsHTMLTagAllowed`、`Widget\User::login`/`logout`/`commitLogin`/`simpleLogin` 等）。

> **这是兼容层的设计取舍（非缺陷）**：只实现主题模板实际调用到的 API；未实现的方法对当前 8 个主题均不触发。新主题若调用上述未实现方法，会得到 `PlaceholderWidget` 或 `__call` 兜底，可能静默失效或报错，需按需补齐。

## 3.10 其他缺口

- `Typecho\Widget\Helper\PageNavigator\Box`/`Classic` 已存在，但 `bootstrap.php` 的 `Typecho_*` 旧式别名**未预建** `Typecho_Widget_Helper_PageNavigator_Box`/`_Classic`（设计决定：RoricalTheme 自行声明，预建会冲突）。
- `Widget\Base\TreeViewTrait`/`TreeTrait` 未单独实现（`CategoryList`/`PageRows` 内部自行处理树形）。
- 接口 `QueryInterface`/`RowFilterInterface`/`PrimaryKeyInterface`/`ParamsDelegateInterface`/`ActionInterface` 未声明（P3，仅类型提示用）。
- 主题回调 `treeViewCategoriesCallback`/`treeViewPagesCallback`/`singlePing` 由 `BaseWidget`/`CommentsWidget` 的 `listCategories`/`listComments` 内部支持，未单独暴露全局函数（P3）。

---

# 第四部分：数据模型差异与降级说明

| Typecho 概念 | lylme 实现 | 降级说明 |
| --- | --- | --- |
| 独立页面（page） | 无 | PageRows 返回空 |
| 标签表（metas type=tag） | 无 | TagCloud 返回空；Archive.tags 从 art_keywords 逗号拆分 |
| 自定义字段（fields） | 无 | Archive.fields 返回空 Config；`table.fields` 查询重定向到 lylme_article |
| 用户系统 | 仅会员/管理员登录态 | User 判断会员 session/cookie 或管理员 token |
| 插件系统 | 无 | Plugin 最小实现，factory 返回空壳 |
| RSS/Atom | 由 article/feed.php 实现 | Feed 类最小实现 |
| 评论分页 | 暂不实现 | CommentsWidget.pageNav 空实现 |
| relationships 表 | 无（表名映射存在但表可能不存在） | 查询 try/catch 兜底 |
| users 表 | lylme_article_user | 作者信息查询静默返回空 |
| attachments | 无 | attachments() 降级 |
| author 归档 | 无路由 | is('author') 永不命中 |
| date 归档 | ?month= 映射为 index | is('date') 永不命中 |

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


DROP TABLE IF EXISTS `lylme_apply`;
CREATE TABLE `lylme_apply` (
  `apply_id` int(11) NOT NULL COMMENT '收录ID',
  `apply_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '收录链接名称',
  `apply_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '收录链接地址',
  `apply_group` int(11) NOT NULL COMMENT '申请收录分组',
  `apply_icon` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '收录链接图标',
  `apply_desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '收录链接描述',
  `apply_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收录提交时间',
  `apply_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '收录状态(0待审核,1通过，2拒绝)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收录申请';

DROP TABLE IF EXISTS `lylme_article`;
CREATE TABLE `lylme_article` (
  `art_id` int(11) NOT NULL COMMENT '文章ID',
  `cat_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类ID',
  `art_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文章标题',
  `art_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'URL别名',
  `art_author` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '管理员' COMMENT '作者',
  `art_author_uid` int(11) NOT NULL DEFAULT '0' COMMENT '投稿会员UID(0为管理员/系统)',
  `art_content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文章内容',
  `art_excerpt` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文章摘要',
  `art_cover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面图URL',
  `art_keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `art_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `art_views` int(11) NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `art_likes` int(11) NOT NULL DEFAULT '0' COMMENT '点赞数',
  `art_comments` int(11) NOT NULL DEFAULT '0' COMMENT '评论数',
  `art_top` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否置顶(0否1是)',
  `art_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0草稿1发布2待审核)',
  `art_allow_comment` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否允许评论(0否1是)',
  `art_time` datetime NOT NULL COMMENT '发布时间',
  `art_update` datetime NOT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章表';

INSERT INTO `lylme_article` (`art_id`, `cat_id`, `art_title`, `art_slug`, `art_author`, `art_author_uid`, `art_content`, `art_excerpt`, `art_cover`, `art_keywords`, `art_description`, `art_views`, `art_likes`, `art_comments`, `art_top`, `art_status`, `art_allow_comment`, `art_time`, `art_update`) VALUES
(1, 1, '欢迎来到本站', 'hello', '管理员', 0, '**欢迎来到本站！很高兴在这里见到你。**\r\n\r\n本站是一个以网址收藏与分享为核心的**开源导航与博客二合一站点**：首页提供简洁高效的分类导航与搜索，同时用文章记录值得一看的发现、工具测评与上网技巧。本站基于开源项目 **LyLmecho** 搭建，而 LyLmecho 正是 **LyLme Spage（六零导航页）** 的独立增强版本——导航与写作，一站搞定。\r\n\r\n## 本站能做什么\r\n\r\n- **分类导航**：首页按分类整理常用网站，从效率工具到学习资源，分类浏览即可一键直达；\r\n- **快速搜索**：想找什么，在搜索框输入关键词即可快速定位目标；\r\n- **文章分享**：不定期更新收录心得、工具测评、上网技巧与站务公告；\r\n- **发现好站**：有私藏好站？欢迎通过站内提交渠道推荐，让更多人看\r\n\r\n### 什么是 LyLme Spage（六零导航页）\r\n\r\n**LyLme Spage（六零导航页）** 是一款开源的网址导航程序，由 LyLme 开发团队（上云六零科技工作室）开发维护，以 Apache License 2.0 协议开源。它坚持\"简洁高效、无广告\"的理念，把互联网上最具价值的链接沉淀为可分类、可搜索的导航首页，被广泛用于搭建个人起始页与网址导航站。\r\n\r\n### 什么是 LyLmecho\r\n\r\n**LyLmecho** 是 LyLme Spage 的**独立全新版本（独立仓库）**，相当于六零导航页的\"导航 + 博客\"增强线。它在完整保留 LyLme Spage 导航能力的基础上，新增了一套完整的**文章 / 博客模块**与 **Typecho 兼容层**，因此：\r\n\r\n- 既能整理收藏导航首页，也能发布文章、独立页面并支持评论互动；\r\n- 可直接运行 Typecho 生态的博客主题，无需二次开发即可换肤；\r\n- 整体以 GNU General Public License v2.0（GPL-2.0）开源发布。\r\n\r\n一句话总结：**LyLme Spage 负责\"链接\"，LyLmecho 能写\"文章\"**——本站的文章与博客部分，正是运行在 LyLmecho 之上。\r\n\r\n## 常见问题（FAQ）\r\n\r\n**1. 这是一个什么类型的网站？**\r\n\r\n本站属于**网站导航 + 博客站点**：核心是持续沉淀的高价值网址导航，外延是围绕上网效率与工具分享的原创文章，导航与博客数据同源、互相呼应。\r\n\r\n**2. 收藏的网址是人工整理的吗？**\r\n\r\n是。本站所有链接均由人工筛选、分类与维护，参考标准为：内容优质、更新稳定、无恶意弹窗与诱导下载、对多数访客有实用价值。被收录不代表背书，网站内容与运营情况随时可能变化，访问时请自行甄别。\r\n\r\n**3. 导航站为什么还要开博客？**\r\n\r\n基于 LyLmecho，导航与博客天然同源同库：公告、收录说明与工具测评可以长期沉淀，比一次性展示更适合承载\"为什么收录、如何使用\"这类深度内容，也方便访客留言交流。\r\n\r\n**4. 我的网站不希望被收录怎么办？**\r\n\r\n直接通过站内留言或文章评论区联系我们，说明站点域名即可，核实后会第一时间处理，并从收录中移除。\r\n\r\n## 声明与联系\r\n\r\n本站收录的网址均为人工收集整理，版权归原网站所有；本站不存储、不转发任何第三方网站内容。\r\n\r\n如发现收录信息有误、链接失效，或您的网站不希望被收录，欢迎**通过站内留言或文章评论区联系我们**。\r\n\r\n## 写在最后\r\n\r\n导航只是渡船，不是彼岸；收藏夹越满，越容易落满灰尘。愿你的收藏夹少一些\"从未开始\"的遗憾，多一些\"刚刚完成\"的踏实。\r\n\r\n祝你上网愉快！\r\n', '欢迎来到本站！很高兴在这里见到你。', '', '', '', 1, 0, 0, 1, 1, 1, '2026-09-06 16:09:52', '2026-09-07 14:00:31');

DROP TABLE IF EXISTS `lylme_article_cat`;
CREATE TABLE `lylme_article_cat` (
  `cat_id` int(11) NOT NULL COMMENT '分类ID',
  `cat_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `cat_alias` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类别名',
  `cat_desc` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类描述',
  `cat_order` int(4) NOT NULL DEFAULT '10' COMMENT '排序',
  `cat_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0禁用1启用)',
  `cat_time` datetime NOT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章分类表';

INSERT INTO `lylme_article_cat` (`cat_id`, `cat_name`, `cat_alias`, `cat_desc`, `cat_order`, `cat_status`, `cat_time`) VALUES
(1, '默认分类', 'default', '系统默认文章分类', 1, 1, '2026-09-06 07:58:34');

DROP TABLE IF EXISTS `lylme_article_comment`;
CREATE TABLE `lylme_article_comment` (
  `com_id` int(11) NOT NULL COMMENT '评论ID',
  `art_id` int(11) NOT NULL COMMENT '文章ID',
  `com_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '评论类型:0=文章,1=链接',
  `com_pid` int(11) NOT NULL DEFAULT '0' COMMENT '父评论ID(0为顶级)',
  `com_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `com_email` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `com_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '网站链接',
  `com_ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `com_agent` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `com_content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '评论内容',
  `com_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态(0待审核1已通过)',
  `com_uid` int(11) NOT NULL DEFAULT '0' COMMENT '发表评论的会员ID(0为游客)',
  `com_time` datetime NOT NULL COMMENT '评论时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章评论表';

DROP TABLE IF EXISTS `lylme_article_config`;
CREATE TABLE `lylme_article_config` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `k` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置项',
  `v` text COLLATE utf8mb4_unicode_ci COMMENT '配置值'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章模块配置';

INSERT INTO `lylme_article_config` (`id`, `k`, `v`) VALUES
(1, 'article_status', '1'),
(2, 'article_perpage', '10'),
(3, 'article_comment', '1'),
(4, 'article_audit', '1'),
(5, 'article_name', '管理员'),
(6, 'article_theme', 'lylmeblog'),
(7, 'article_web_title', 'LyLmecho'),
(8, 'article_web_keywords', 'lylme,lylme_spage,lylmecho,上云六零,六零'),
(9, 'article_web_description', '文字链接，皆有回响'),
(10, 'article_url_style', 'default'),
(11, 'article_url_custom', 'article/post{id}.html'),
(12, 'article_member_status', '0'),
(13, 'article_member_verify', '0'),
(14, 'article_member_role', 'subscriber'),
(15, 'article_mail_status', '0'),
(16, 'article_mail_host', ''),
(17, 'article_mail_port', '465'),
(18, 'article_mail_secure', 'ssl'),
(19, 'article_mail_user', ''),
(20, 'article_mail_pass', ''),
(21, 'article_mail_from', ''),
(22, 'article_mail_from_name', ''),
(23, 'article_post_status', '0');

DROP TABLE IF EXISTS `lylme_article_page`;
CREATE TABLE `lylme_article_page` (
  `page_id` int(11) NOT NULL COMMENT '页面ID',
  `page_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '页面标题',
  `page_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'URL别名',
  `page_content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '页面内容',
  `page_excerpt` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '页面摘要',
  `page_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0草稿1发布)',
  `page_order` int(11) NOT NULL DEFAULT '10' COMMENT '排序',
  `page_time` datetime NOT NULL COMMENT '创建时间',
  `page_update` datetime NOT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章独立页面表';

DROP TABLE IF EXISTS `lylme_article_user`;
CREATE TABLE `lylme_article_user` (
  `uid` int(11) NOT NULL COMMENT '会员ID',
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码哈希(password_hash)',
  `nickname` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '显示昵称',
  `email` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '个人网站',
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'subscriber' COMMENT '角色:subscriber/contributor/editor',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0待邮箱验证/禁用,1正常)',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像URL',
  `verify_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱验证令牌',
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '记住我令牌哈希(可撤销)',
  `token_exp` int(11) NOT NULL DEFAULT '0' COMMENT '令牌过期时间戳',
  `reg_ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '注册IP',
  `reg_time` datetime NOT NULL COMMENT '注册时间',
  `last_login` datetime DEFAULT NULL COMMENT '最后登录时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章前台会员表';

DROP TABLE IF EXISTS `lylme_config`;
CREATE TABLE `lylme_config` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `k` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置项',
  `v` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `description` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '配置描述'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站配置' ROW_FORMAT=COMPACT;

INSERT INTO `lylme_config` (`id`, `k`, `v`, `description`) VALUES
(1, 'admin_pwd', '1ef987f238b7a80eaf3689cfc42aad2d', '管理员密码'),
(2, 'admin_user', 'admin', '管理员账号'),
(3, 'apply', '0', '收录申请'),
(4, 'apply_gg', '<b>收录说明：</b><br>1. 禁止提交违规违法站点<br>2. 页面整洁，无多个弹窗广告和恶意跳转<br>3. 非盈利性网站，网站正常访问<br>4. 添加本站友链或网站已ICP备案优先收录<br>', '收录公告'),
(5, 'background', './assets/img/background.jpg', '背景图片'),
(6, 'title', '上网导航 - LyLme Spage', '网站名称'),
(7, 'cdnpublic', '', 'CDN地址'),
(8, 'copyright', 'Copyright ©2022-2026 <a href=\"/\">LyLmecho</a>.  All Rights Reserved.', '底部版权'),
(9, 'description', '文字链接，皆有回响', '网站描述'),
(10, 'home-title', '<h2 class=\"title\">上网，从这里开始！</h2>', '首页标题(该字段已废弃)'),
(11, 'icp', '', 'ICP备案号'),
(12, 'keywords', '六零导航页,百度搜索,哔哩哔哩搜索,知乎搜索,六零导航,LyLme Spage,六零,LyLme,网站导航,上网导航,LyLmecho', '网站关键词'),
(13, 'logo', './assets/img/logo.png', '网站LOGO'),
(14, 'md5pass', '1', '启用md5加密密码'),
(15, 'template', 'lylmeblog', '网站主题'),
(16, 'wztj', '', '自定义footer'),
(17, 'tq', 'false', '天气显示开关(部分主题支持)'),
(18, 'version', 'v5.0.0', '数据库版本号'),
(19, 'wap_background', '', '手机背景图片'),
(20, 'wxplus', '', '微信推送密钥'),
(21, 'wxplustime', '20:00', '微信推送时间'),
(22, 'yan', 'true', '随机一言开关'),
(23, 'about', '1', '新版关于页面'),
(24, 'about_content', '# 关于本站\r\n\r\n本站是一个**轻量上网起始页 + 个人博客**：打开浏览器就能直达常用网站，顺手也能翻翻站长精选链接、写的测评、公告和折腾笔记。\r\n\r\n不花哨、不打扰，把它设成主页就够了。\r\n\r\n## 你能在这里做什么\r\n\r\n**① 一页直达常用网站**\r\n\r\n首页按分组整理了常用站点（工具、开发、设计、影音、学习等），点一下就走。链接直接指向目标地址，**不做任何二次中转**。\r\n\r\n**② 输入即搜**\r\n\r\n在顶部搜索框输入关键词回车即可。没被收录的站点，也可以直接在这里搜到。\r\n\r\n**③ 翻翻文章**\r\n\r\n站内文章板块记录了一些实用测评、站点公告和使用说明，有新内容会第一时间出现在这里。\r\n\r\n**④ 推荐你觉得好用的站**\r\n\r\n发现冷门又优质的网站？通过「申请收录」提交，通过后会归到对应分组里。\r\n\r\n## 收藏本站或设为浏览器主页\r\n\r\n- **收藏本站：**按`Ctrl`+`D`组合键可快速收藏本站\r\n- **设置主页：**浏览器打开设置→起始页面→打开特定网页或一组网页，填入本站地址\r\n- **Tip：**本站也对手机端进行了适配，手机浏览器也能用哦！\r\n\r\n## 关于隐私\r\n\r\n- 本站提供网址导航与跳转，链接默认直接指向目标地址，**也不因此产生跳转记录。**\r\n- 本站**不收集**你的点击、访问、搜索记录等隐私信息。\r\n- 但通过本站进入的外部站点，其数据收集与处理方式由对方决定。涉及账号、支付、个人信息时，建议先了解对方的相关说明。\r\n\r\n## 有问题、想提建议？\r\n\r\n- 推荐网站 / 链接失效 / 分类放错 → 站内「申请收录」或留言\r\n- 使用建议、体验吐槽 → 直接留言就行\r\n- 提交时加个【收录】【失效】【建议】【友链】前缀，并尽量写清复现步骤，处理速度会快很多\r\n\r\n## 版权与开源\r\n\r\n站内原创文章内容版权归本站所有，转载请注明出处。\r\n\r\n本站基于开源程序 **LyLmecho** 搭建（其上游为六零导航页 LyLme Spage），二者分别遵循 GPL-2.0 与 Apache-2.0 开源许可，在此致谢。', '关于页面');

DROP TABLE IF EXISTS `lylme_groups`;
CREATE TABLE `lylme_groups` (
  `group_id` int(11) NOT NULL COMMENT '分组ID',
  `group_name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组名称',
  `group_icon` text COLLATE utf8mb4_unicode_ci COMMENT '分组图标',
  `group_order` int(4) NOT NULL DEFAULT '5' COMMENT '分组排序',
  `group_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '分组状态',
  `group_pwd` int(11) NOT NULL DEFAULT '0' COMMENT '加密组ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

INSERT INTO `lylme_groups` (`group_id`, `group_name`, `group_icon`, `group_order`, `group_status`, `group_pwd`) VALUES
(1, '常用导航', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-changyong\">\r\n</use></svg>', 1, 1, 0),
(2, '人工智能', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-ai\">\r\n</use></svg>', 2, 1, 0),
(3, '资讯社区', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zixun\">\r\n</use></svg>', 3, 1, 0),
(4, '设计办公', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-bangong\">\r\n</use></svg>', 4, 1, 0),
(5, '影音娱乐', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-yingyin\">\r\n</use></svg>', 5, 1, 0),
(6, '在线工具', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-gongju\">\r\n</use></svg>', 6, 1, 0),
(7, '通信存储', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-cunchu\">\r\n</use></svg>', 7, 1, 0),
(8, '学习平台', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-xuexi\">\r\n</use></svg>', 8, 1, 0),
(9, '生活服务', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-shenghuo\">\r\n</use></svg>', 9, 1, 0),
(10, '开发相关', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kaifa\">\r\n</use></svg>', 10, 1, 0),
(11, '网站公告', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-gonggao\">\r\n</use></svg>', 11, 1, 0);

DROP TABLE IF EXISTS `lylme_links`;
CREATE TABLE `lylme_links` (
  `id` int(11) NOT NULL COMMENT '链接ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '链接标题',
  `group_id` int(11) NOT NULL DEFAULT '1' COMMENT '分组名称',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '链接地址',
  `icon` text COLLATE utf8mb4_unicode_ci COMMENT '链接图标',
  `link_desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '链接描述',
  `link_keywords` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '链接关键词',
  `comments` int(11) NOT NULL DEFAULT '0' COMMENT '评论数',
  `link_order` int(4) DEFAULT '10' COMMENT '链接排序',
  `link_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '链接状态',
  `link_pwd` int(11) DEFAULT '0' COMMENT '加密组ID',
  `link_content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '链接详情长文'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

INSERT INTO `lylme_links` (`id`, `name`, `group_id`, `url`, `icon`, `link_desc`, `link_keywords`, `comments`, `link_order`, `link_status`, `link_pwd`, `link_content`) VALUES
(1, '豆包', 2, 'https://www.doubao.com/chat', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-doubao\"></use></svg>', '字节跳动AI助手，支持对话、写作与编程', '豆包,AI对话,AI聊天,AI写作,AI图片生成', 0, 0, 1, 0, NULL),
(2, 'DeepSeek', 2, 'https://chat.deepseek.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-deepseek\"></use></svg>', '深度求索AI助手，擅长代码与长文本对话', 'DeepSeek,深度求索,AI助手,代码助手,大语言模型', 0, 1, 1, 0, NULL),
(3, '腾讯元宝', 2, 'https://yuanbao.tencent.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tengxunyuanbao\"></use></svg>', '腾讯AI助手，接入混元大模型，支持搜索问答', '腾讯元宝,混元大模型,AI助手,AI搜索,AI对话', 0, 2, 1, 0, NULL),
(4, '阿里千问', 2, 'https://www.tongyi.com/qianwen', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tongyiqianwen\"></use></svg>', '阿里官方AI助手，支持搜索、PPT与生图', '千问,Qwen,AI助手,AI办公,AI生图', 0, 3, 1, 0, NULL),
(5, 'Kimi', 2, 'https://www.kimi.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kimi\"></use></svg>', '月之暗面AI助手，擅长长文本与深度研究', 'Kimi,月之暗面,Moonshot,AI助手,深度研究', 0, 4, 1, 0, NULL),
(6, '百度文心', 2, 'https://chat.baidu.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-wenxinyiyan\"></use></svg>', '百度文心大模型助手，一站式智能问答创作', '百度文心,文心大模型,AI助手,AI搜索,AI创作', 0, 5, 1, 0, NULL),
(7, '讯飞星火', 2, 'https://xinghuo.xfyun.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-xunfeixinghuo\"></use></svg>', '科大讯飞认知大模型，支持问答推理编程', '讯飞星火,星火大模型,AI问答,AI创作,科大讯飞', 0, 6, 1, 0, NULL),
(8, 'AiPPT', 2, 'https://www.aippt.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-AIppt\"></use></svg>', 'AI一键生成专业PPT，海量模板自动排版', 'AiPPT,AI生成PPT,PPT模板,智能排版', 0, 7, 1, 0, NULL),
(9, '蚂蚁阿福', 2, 'https://chat.antafu.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-afu\"></use></svg>', '支付宝AI健康助手，提供医疗问答与解读', '蚂蚁阿福,AI健康助手,疾病咨询,报告解读,循证医学', 0, 8, 1, 0, NULL),
(10, '即梦AI', 2, 'https://jimeng.jianying.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-jimeng\"></use></svg>', '字节AI创作平台，支持绘画与视频生成', '即梦AI,AI绘画,文生视频,AIGC,图片生成', 0, 9, 1, 0, NULL),
(11, '小云雀AI', 2, 'https://xyq.jianying.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-ai\"></use></svg>', '字节AI故事与短剧创作平台', '小云雀AI,短剧创作,AI故事,字节跳动', 0, 10, 1, 0, NULL),
(12, 'WorkBuddy', 2, 'https://www.workbuddy.cn/app', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-workbuddy\"></use></svg>', 'AI智能助手，高效完成工作与创作任务', 'WorkBuddy,AI助手,智能办公,AI编程', 0, 11, 1, 0, NULL),
(13, '秘塔AI', 2, 'https://metaso.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-mitaxiezuomao\"></use></svg>', '无广告AI搜索引擎，直达结果', '秘塔AI,AI搜索,智能搜索', 0, 12, 1, 0, NULL),
(14, '人民网', 3, 'http://www.people.com.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-renminwang\"></use></svg>', '人民日报旗下国家重点新闻网站', '人民网,人民日报,新闻,时政', 0, 0, 1, 0, NULL),
(15, '凤凰网', 3, 'http://www.ifeng.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-fenghuangwang\"></use></svg>', '领先综合门户，提供全方位新闻资讯', '凤凰网,新闻资讯,综合门户,凤凰卫视', 0, 1, 1, 0, NULL),
(16, '环球网', 3, 'https://www.huanqiu.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-huanqiuwanglogo\"></use></svg>', '人民日报社旗下国际新闻门户', '环球网,国际新闻,新闻资讯', 0, 2, 1, 0, NULL),
(17, '知乎', 3, 'https://www.zhihu.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhihu\"></use></svg>', '中文互联网高质量问答社区', '知乎,问答社区,知识分享,原创内容', 0, 3, 1, 0, NULL),
(18, '新浪微博', 3, 'https://weibo.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-weibo\"></use></svg>', '随时随地分享新鲜事的社交平台', '微博,新浪微博,社交媒体,热点资讯', 0, 4, 1, 0, NULL),
(19, '腾讯新闻', 3, 'https://news.qq.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tengxunxinwen\"></use></svg>', '腾讯旗下综合资讯平台，海量新闻频道', '腾讯新闻,新闻资讯,财经,科技,体育', 0, 5, 1, 0, NULL),
(20, '今日头条', 3, 'https://www.toutiao.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-jinritoutiao\"></use></svg>', '基于数据推荐的聚合资讯平台', '今日头条,头条,新闻资讯,个性化推荐', 0, 6, 1, 0, NULL),
(21, '百度贴吧', 3, 'https://tieba.baidu.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-baidutieba\"></use></svg>', '以兴趣主题聚合的中文社区', '百度贴吧,贴吧,兴趣社区,中文论坛', 0, 7, 1, 0, NULL),
(22, '天涯社区', 3, 'https://tianya.net', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tianyashequ\"></use></svg>', '老牌中文网络社区论坛', '天涯社区,论坛,天涯论坛', 0, 8, 1, 0, NULL),
(23, '36氪', 3, 'https://www.36kr.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-a-36ke\"></use></svg>', '科技创新创业资讯媒体', '36氪,科技资讯,创投,创业', 0, 9, 1, 0, NULL),
(24, '汽车之家', 3, 'https://www.autohome.com.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-qichezhijia\"></use></svg>', '专业汽车资讯与选车服务平台', '汽车之家,汽车报价,汽车评测,选车', 0, 10, 1, 0, NULL),
(25, '懂车帝', 3, 'https://www.dongchedi.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-dongchedi\"></use></svg>', '个性化推荐的汽车资讯平台', '懂车帝,汽车资讯,汽车报价,选车买车', 0, 11, 1, 0, NULL),
(26, '中关村在线', 3, 'http://www.zol.com.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhongguancunzaixian\"></use></svg>', '专业IT数码产品资讯与报价门户', '中关村在线,IT资讯,数码,硬件报价,评测', 0, 12, 1, 0, NULL),
(27, '东方财富网', 3, 'https://www.eastmoney.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-dongfangcaifuwang\"></use></svg>', '专业财经门户，提供行情与金融资讯', '东方财富,股票行情,财经资讯,基金,证券', 0, 13, 1, 0, NULL),
(28, '国家地理网', 3, 'http://www.dili360.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-guojiadilibiaozhi\"></use></svg>', '权威地理资讯与深度旅游平台', '中国国家地理,地理资讯,旅游,摄影', 0, 14, 1, 0, NULL),
(29, '酷安测评', 3, 'https://www.coolapk.com/officialEvaluation', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kuan\"></use></svg>', '酷安官方数码产品评测栏目', '酷安,数码评测,产品测评', 0, 15, 1, 0, NULL),
(30, 'office模板', 4, 'https://www.officeplus.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-Office\"></use></svg>', '微软官方PPT模板与素材下载站', 'officeplus,PPT模板,微软,模板下载', 0, 0, 1, 0, NULL),
(31, 'PDF在线工具', 4, 'https://www.ilovepdf.com/zh-cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-ilovepdf\"></use></svg>', '免费在线PDF转换、合并与压缩工具', 'iLovePDF,PDF转换,PDF合并,PDF压缩', 0, 1, 1, 0, NULL),
(32, '优品PPT', 4, 'https://www.ypppt.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-ppt\"></use></svg>', '免费高质量PPT模板下载网站', '优品PPT,PPT模板,免费下载,幻灯片模板', 0, 2, 1, 0, NULL),
(33, '钉钉会议', 4, 'https://www.dingtalk.com/meeting', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-dingding\"></use></svg>', '钉钉企业级视频会议与协作系统', '钉钉会议,视频会议,在线会议,远程协作', 0, 3, 1, 0, NULL),
(34, '腾讯会议', 4, 'https://meeting.tencent.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tengxunhuiyi\"></use></svg>', '高清流畅的一站式云视频会议', '腾讯会议,视频会议,在线会议,会议软件', 0, 4, 1, 0, NULL),
(35, '花瓣网', 4, 'https://huaban.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-huabanwang\"></use></svg>', '优质图片素材收集与分享社区', '花瓣网,图片素材,设计灵感,采集', 0, 5, 1, 0, NULL),
(36, '稿定设计', 4, 'https://www.gaoding.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-gaoding\"></use></svg>', '在线平面设计与图片编辑平台', '稿定设计,在线设计,平面设计,图片编辑', 0, 6, 1, 0, NULL),
(37, '图怪兽', 4, 'https://818ps.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tuguaishou\"></use></svg>', '在线图片编辑与海量模板设计', '图怪兽,在线作图,图片编辑,设计模板', 0, 7, 1, 0, NULL),
(38, '站酷', 4, 'https://www.zcool.com.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhanku123\"></use></svg>', '中国设计师互动交流平台', '站酷,ZCOOL,设计师,平面设计,插画', 0, 8, 1, 0, NULL),
(39, '千图网', 4, 'https://www.58pic.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-qiantuwang\"></use></svg>', '正版商用图片素材与模板下载', '千图网,图片素材,设计模板,矢量图', 0, 9, 1, 0, NULL),
(40, '知犀思维导图', 4, 'https://www.zhixi.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhixi\"></use></svg>', '免费思维导图与流程图工具', '知犀,思维导图,流程图,脑图模板', 0, 10, 1, 0, NULL),
(41, '免费商用字体', 4, 'https://www.fonts.net.cn/commercial-free/fonts-zh-1.html', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zititianxia\"></use></svg>', '海量中英文字体免费下载预览', '字体下载,中文字体,免费字体,商用字体', 0, 11, 1, 0, NULL),
(42, '抖音网页版', 5, 'https://www.douyin.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-douyin\"></use></svg>', '抖音官方网页版，在线刷短视频', '抖音,短视频,抖音网页版,直播', 0, 0, 1, 0, NULL),
(43, '快手网页版', 5, 'https://www.kuaishou.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kuaishou\"></use></svg>', '快手官网，短视频创作与分享平台', '快手,短视频,快手官网,直播', 0, 1, 1, 0, NULL),
(44, '哔哩哔哩', 5, 'https://www.bilibili.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-bilibilixds\"></use></svg>', '国内知名弹幕视频网站，ACG聚集地', '哔哩哔哩,B站,弹幕视频,动漫,ACG', 0, 2, 1, 0, NULL),
(45, '腾讯视频', 5, 'https://v.qq.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tencent\"></use></svg>', '腾讯旗下正版高清视频平台', '腾讯视频,在线视频,电视剧,电影,综艺', 0, 3, 1, 0, NULL),
(46, '爱奇艺', 5, 'https://www.iqiyi.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-aiqiyi\"></use></svg>', '正版高清视频，剧集综艺动漫齐全', '爱奇艺,在线视频,电影,电视剧,综艺', 0, 4, 1, 0, NULL),
(47, '优酷视频', 5, 'https://www.youku.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-youku\"></use></svg>', '阿里旗下正版在线视频平台', '优酷,在线视频,电视剧,电影,综艺', 0, 5, 1, 0, NULL),
(48, '汽水音乐', 5, 'https://www.qishui.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-qishuiyinle\"></use></svg>', '抖音官方出品的个性化音乐App', '汽水音乐,音乐播放,个性化推荐,抖音', 0, 6, 1, 0, NULL),
(49, 'QQ音乐', 5, 'https://y.qq.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-QQmusic\"></use></svg>', '腾讯旗下正版音乐在线试听平台', 'QQ音乐,在线音乐,无损音乐,音乐下载', 0, 7, 1, 0, NULL),
(50, '网易云音乐', 5, 'https://music.163.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-wangyiyunyinle\"></use></svg>', '专注发现与分享的音乐社区', '网易云音乐,在线音乐,歌单,音乐推荐', 0, 8, 1, 0, NULL),
(51, '酷狗音乐', 5, 'https://www.kugou.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kugou\"></use></svg>', '正版在线音乐试听与下载平台', '酷狗音乐,在线音乐,听歌,音乐下载', 0, 9, 1, 0, NULL),
(52, '酷我音乐', 5, 'https://www.kuwo.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kuwoyinle\"></use></svg>', '无损音质正版音乐在线试听', '酷我音乐,无损音乐,在线试听,音乐播放', 0, 10, 1, 0, NULL),
(53, 'AcFun', 5, 'https://www.acfun.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-CN_acfuntv\"></use></svg>', '国内首家弹幕视频网站', 'AcFun,A站,弹幕视频,动漫,鬼畜', 0, 11, 1, 0, NULL),
(54, '虎牙直播', 5, 'https://www.huya.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-a-huya_huaban1\"></use></svg>', '以游戏直播为主的互动直播平台', '虎牙直播,游戏直播,电竞赛事,手游直播', 0, 12, 1, 0, NULL),
(55, '斗鱼直播', 5, 'https://www.douyu.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-douyu\"></use></svg>', '高清流畅的游戏直播平台', '斗鱼,游戏直播,电竞赛事,直播平台', 0, 13, 1, 0, NULL),
(56, 'CCTV官网', 5, 'http://tv.cctv.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-cctv\"></use></svg>', '央视节目官方平台，直播点播齐全', 'CCTV,央视,央视直播,节目点播', 0, 14, 1, 0, NULL),
(57, '腾讯动漫', 5, 'https://ac.qq.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tengxundongman\"></use></svg>', '海量正版国漫日漫在线阅读', '腾讯动漫,在线漫画,正版漫画,国漫', 0, 15, 1, 0, NULL),
(58, 'QQ游戏', 5, 'https://qqgame.qq.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-QQyouxi\"></use></svg>', '腾讯休闲游戏大厅，棋牌小游戏', 'QQ游戏,游戏大厅,棋牌,小游戏', 0, 16, 1, 0, NULL),
(59, 'Steam商店', 5, 'https://store.steampowered.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-Steam\"></use></svg>', '全球知名PC游戏数字发行平台', 'Steam,游戏平台,PC游戏,数字发行', 0, 17, 1, 0, NULL),
(60, '微信文件传输助手', 6, 'https://filehelper.weixin.qq.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-wenjianchuanshuzhushou\"></use></svg>', '微信文件传输助手网页版', '微信,文件传输,网页版,传文件', 0, 0, 1, 0, NULL),
(61, '有道翻译', 6, 'https://fanyi.youdao.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-youdao\"></use></svg>', '支持多语种的在线翻译与文档翻译', '有道翻译,在线翻译,文档翻译,AI翻译', 0, 1, 1, 0, NULL),
(62, '百度翻译', 6, 'https://fanyi.baidu.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-baidufanyi\"></use></svg>', '百度AI大模型翻译平台，支持203种语言', '百度翻译,在线翻译,AI翻译,文档翻译', 0, 2, 1, 0, NULL),
(63, 'iconfont', 6, 'https://www.iconfont.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-iconfont\"></use></svg>', '阿里旗下矢量图标库，设计师必备', 'iconfont,矢量图标,图标库,阿里巴巴', 0, 3, 1, 0, NULL),
(64, '站长工具', 6, 'https://tool.chinaz.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhanchanggongju\"></use></svg>', '网站SEO数据与综合查询工具', '站长工具,SEO查询,权重查询,网站检测', 0, 4, 1, 0, NULL),
(65, '爱站网', 6, 'https://www.aizhan.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-aizhanwang\"></use></svg>', '网站权重与关键词排名查询平台', '爱站网,站长工具,权重查询,排名查询', 0, 5, 1, 0, NULL),
(66, 'MSDN原版镜像', 6, 'https://msdn.itellyou.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-MSDNitellyou\"></use></svg>', '微软原版系统镜像下载站', 'MSDN,原版镜像,系统下载,微软', 0, 6, 1, 0, NULL),
(67, 'JSON工具', 6, 'https://www.json.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-json\"></use></svg>', '在线JSON解析、格式化与校验', 'JSON,JSON解析,格式化,在线工具', 0, 7, 1, 0, NULL),
(68, '彼岸壁纸', 6, 'https://www.netbian.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-bizhi\"></use></svg>', '免费高清桌面壁纸下载网站', '彼岸壁纸,壁纸下载,高清壁纸,桌面壁纸', 0, 8, 1, 0, NULL),
(69, '配色卡', 6, 'https://peiseka.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-peise\"></use></svg>', '经典时尚的配色方案参考网站', '配色卡,配色方案,色彩搭配', 0, 9, 1, 0, NULL),
(70, 'Linux命令大全', 6, 'https://linux.lylme.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-linux\"></use></svg>', '专业Linux命令搜索与速查手册', 'Linux,Linux命令,命令大全,速查手册', 0, 10, 1, 0, NULL),
(71, '电脑维护快捷键', 6, 'https://doc.lylme.com/table/hotkey.html', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kuaijiejianshezhi\"></use></svg>', '电脑维护常用快捷键速查表', '快捷键,电脑维护,速查表', 0, 11, 1, 0, NULL),
(72, 'Markdown编辑器', 6, 'https://www.lylme.com/html/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-markdown\"></use></svg>', '开源在线Markdown编辑器', 'Markdown,在线编辑器,Editor.md', 0, 12, 1, 0, NULL),
(73, '在线文本比对', 6, 'https://tool.lu/diff/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-wenbenduibi\"></use></svg>', '在线文本与代码差异比对工具', '文本比对,代码比对,diff,在线工具', 0, 13, 1, 0, NULL),
(74, '腾讯软件中心', 6, 'https://pc.qq.com/category/c0.html', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tencent\"></use></svg>', '正版免费软件安全下载中心', '腾讯软件中心,软件下载,免费软件', 0, 14, 1, 0, NULL),
(75, '123云盘', 7, 'https://www.123pan.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-a-123\"></use></svg>', '不限速大容量免费云存储网盘', '123云盘,免费网盘,云存储,不限速', 0, 0, 1, 0, NULL),
(76, '阿里云盘', 7, 'https://www.aliyundrive.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-aliyunpan\"></use></svg>', '阿里旗下快速安全的个人网盘', '阿里云盘,网盘,云存储,文件分享', 0, 1, 1, 0, NULL),
(77, '夸克网盘', 7, 'https://pan.quark.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kuakewangpan\"></use></svg>', '阿里旗下智能云存储网盘', '夸克网盘,云存储,文件分享,在线解压', 0, 2, 1, 0, NULL),
(78, '蓝奏云', 7, 'https://www.lanzou.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-a-lanzouyuncunchu\"></use></svg>', '简单快捷的文件分享网盘', '蓝奏云,网盘,云存储,文件分享', 0, 3, 1, 0, NULL),
(79, '百度网盘', 7, 'https://pan.baidu.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-baiduwangpan\"></use></svg>', '百度旗下大容量云存储网盘', '百度网盘,云存储,文件分享,网盘下载', 0, 4, 1, 0, NULL),
(80, 'QQ邮箱', 7, 'https://mail.qq.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-QQyouxiang\"></use></svg>', '腾讯免费电子邮箱服务', 'QQ邮箱,免费邮箱,电子邮件,超大附件', 0, 5, 1, 0, NULL),
(81, '163邮箱', 7, 'https://mail.163.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-youxiang\"></use></svg>', '网易免费邮箱，专业电子邮局', '163邮箱,网易邮箱,免费邮箱,电子邮件', 0, 6, 1, 0, NULL),
(82, '126邮箱', 7, 'https://mail.126.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-youxiang1\"></use></svg>', '网易126免费邮箱服务', '126邮箱,网易邮箱,免费邮箱,电子邮件', 0, 7, 1, 0, NULL),
(83, 'Outlook', 7, 'https://outlook.live.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-Outlook\"></use></svg>', '微软免费电子邮箱服务', 'Outlook,微软邮箱,免费邮箱,电子邮件', 0, 8, 1, 0, NULL),
(84, '谷歌邮箱', 7, 'https://mail.google.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-Gmail\"></use></svg>', '谷歌Gmail免费电子邮箱', 'Gmail,谷歌邮箱,免费邮箱,电子邮件', 0, 9, 1, 0, NULL),
(85, '腾讯文档', 7, 'https://docs.qq.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tengxunwendang\"></use></svg>', '支持多人协作的在线文档平台', '腾讯文档,在线文档,多人协作,在线办公', 0, 10, 1, 0, NULL),
(86, '金山文档', 7, 'https://www.kdocs.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-jinshanwendang\"></use></svg>', '金山办公旗下在线协作文档', '金山文档,在线文档,协同办公,WPS', 0, 11, 1, 0, NULL),
(87, '飞书文档', 7, 'https://docs.feishu.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-feishu\"></use></svg>', '新一代高效在线协作文档工具', '飞书文档,在线文档,协同办公,思维笔记', 0, 12, 1, 0, NULL),
(88, '语雀', 7, 'https://www.yuque.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-yuque\"></use></svg>', '阿里旗下的知识库与笔记工具', '语雀,知识库,在线文档,笔记', 0, 13, 1, 0, NULL),
(89, '石墨文档', 7, 'https://shimo.im/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-bangong\"></use></svg>', '多人在线协作云Office办公软件', '石墨文档,在线文档,协同办公,云Office', 0, 14, 1, 0, NULL),
(90, '有道云笔记', 7, 'https://note.youdao.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-youdao\"></use></svg>', '多端同步的在线笔记与记录工具', '有道云笔记,云笔记,笔记,多端同步', 0, 15, 1, 0, NULL),
(91, '学习强国', 8, 'https://www.xuexi.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-xuexiqiangguo\"></use></svg>', '中共中央宣传部学习平台', '学习强国,学习平台,时政学习', 0, 0, 1, 0, NULL),
(92, '国家博物馆', 8, 'https://www.chnmuseum.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-bowuguan\"></use></svg>', '中国国家博物馆官方网站', '国家博物馆,展览预约,藏品,参观导览', 0, 1, 1, 0, NULL),
(93, '国家图书馆', 8, 'http://nlc.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tushuguan\"></use></svg>', '中国国家图书馆官方网站', '国家图书馆,图书借阅,数字资源,馆藏检索', 0, 2, 1, 0, NULL),
(94, '中国法律服务网', 8, 'https://www.12348.gov.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhongguofalvfaguiku\"></use></svg>', '司法部公共法律服务平台', '法律咨询,法律服务,法律援助,12348', 0, 3, 1, 0, NULL),
(95, '终身教育平台', 8, 'https://le.ouchn.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-leouchn\"></use></svg>', '国家开放大学终身学习平台', '终身教育,在线学习,国家开放大学', 0, 4, 1, 0, NULL),
(96, '国家博物馆数字展厅', 8, 'https://www.chnmuseum.cn/Portals/0/web/vr/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-shuzizhanting\"></use></svg>', '国家博物馆线上VR虚拟展厅', '国博,数字展厅,VR展览,虚拟展厅', 0, 5, 1, 0, NULL),
(97, '中国数字科技馆', 8, 'https://www.cdstm.cn/knowledge/kpwk/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhongguoshuzikejiguan\"></use></svg>', '国家级公益性科普服务平台', '数字科技馆,科普,科技知识,科普平台', 0, 6, 1, 0, NULL),
(98, '科普中国网', 8, 'https://www.kepuchina.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kepuzhongguo\"></use></svg>', '中国科协主办权威科普平台', '科普中国,科学普及,科普资讯,科协', 0, 7, 1, 0, NULL),
(99, '中国大学MOOC', 8, 'https://www.icourse163.org', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhongguodaxueMOOC\"></use></svg>', '优质中文慕课在线学习平台', '中国大学MOOC,慕课,在线学习,公开课', 0, 8, 1, 0, NULL),
(100, '网易公开课', 8, 'https://open.163.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-wangyigongkaike\"></use></svg>', '海量名校公开课免费学习平台', '网易公开课,公开课,名校课程,免费学习', 0, 9, 1, 0, NULL),
(101, '高等智慧教育平台', 8, 'https://higher.smartedu.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-gaodengjiaoyu\"></use></svg>', '国家高等教育在线课程平台', '智慧教育,高等教育,在线课程,国家平台', 0, 10, 1, 0, NULL),
(102, '中小学智慧教育平台', 8, 'https://basic.smartedu.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhongxiaoxuezhihuijiaoyu\"></use></svg>', '中小学国家课程资源平台', '智慧教育,中小学,课程资源,国家平台', 0, 11, 1, 0, NULL),
(103, '我要自学网', 8, 'https://www.51zxw.net', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-yuxizixue\"></use></svg>', '免费视频教程自学平台', '自学网,视频教程,软件学习,免费教程', 0, 12, 1, 0, NULL),
(104, '慕课网', 8, 'https://www.imooc.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-imooc\"></use></svg>', 'IT技能实战在线学习平台', '慕课网,IT课程,编程学习,实战教程', 0, 13, 1, 0, NULL),
(105, '淘宝', 9, 'https://www.taobao.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-taobao\"></use></svg>', '阿里旗下大型综合网购平台', '淘宝,网购,网上购物,电商平台', 0, 0, 1, 0, NULL),
(106, '京东', 9, 'https://www.jd.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-jingdong\"></use></svg>', '正品低价的综合网购商城', '京东,网上商城,正品,家电数码', 0, 1, 1, 0, NULL),
(107, '唯品会', 9, 'https://www.vip.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-weipinhui\"></use></svg>', '品牌特卖折扣购物网站', '唯品会,品牌折扣,特卖,正品', 0, 2, 1, 0, NULL),
(108, '高德地图', 9, 'https://www.amap.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-gaode\"></use></svg>', '专业地图导航与出行服务平台', '高德地图,地图导航,路线查询,出行', 0, 3, 1, 0, NULL),
(109, '百度地图', 9, 'https://map.baidu.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-baiduditu\"></use></svg>', '百度地图导航与出行服务', '百度地图,地图导航,实时路况,路线查询', 0, 4, 1, 0, NULL),
(110, '中国天气网', 9, 'http://www.weather.com.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhongguotianqiwang\"></use></svg>', '中国气象局官方天气预报网站', '天气预报,天气查询,空气质量,气象', 0, 5, 1, 0, NULL),
(111, '铁路12306', 9, 'https://www.12306.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tl12306\"></use></svg>', '中国铁路官方购票平台', '12306,火车票,购票,铁路查询', 0, 6, 1, 0, NULL),
(112, '闲鱼', 9, 'https://www.goofish.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-xianyu\"></use></svg>', '阿里旗下闲置二手交易平台', '闲鱼,二手交易,闲置买卖,二手市场', 0, 7, 1, 0, NULL),
(113, '58同城', 9, 'http://58.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tongcheng\"></use></svg>', '本地生活分类信息服务平台', '58同城,分类信息,房产,招聘,本地生活', 0, 8, 1, 0, NULL),
(114, '墨迹天气', 9, 'https://www.moji.com/zh-CN', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-QX-mojitianqi\"></use></svg>', '精准天气预报与生活指数服务', '墨迹天气,天气预报,天气查询,生活指数', 0, 9, 1, 0, NULL),
(115, 'Vue.js', 10, 'https://cn.vuejs.org/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-vuejs-seeklogocom\"></use></svg>', '渐进式JavaScript前端框架', 'Vue.js,前端框架,JavaScript,渐进式', 0, 0, 1, 0, NULL),
(116, '清华开源镜像', 10, 'https://mirrors.tuna.tsinghua.edu.cn/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-qinghuajingxiang\"></use></svg>', '清华大学开源软件镜像站', '清华镜像,开源镜像,Linux镜像,镜像下载', 0, 1, 1, 0, NULL),
(117, '阿里开源镜像', 10, 'https://developer.aliyun.com/mirror/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-aliyun\"></use></svg>', '阿里开源软件镜像加速下载站', '阿里镜像,开源镜像,Linux,镜像下载', 0, 2, 1, 0, NULL),
(118, 'OSCHINA', 10, 'https://www.oschina.net', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-oschina\"></use></svg>', '中文开源技术社区与资讯平台', '开源中国,OSCHINA,开源社区,技术资讯', 0, 3, 1, 0, NULL),
(119, '稀土掘金', 10, 'https://juejin.cn', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-xitujuejinicon\"></use></svg>', '中文开发者技术内容分享社区', '掘金,技术社区,开发者,技术文章', 0, 4, 1, 0, NULL),
(120, 'Gitee', 10, 'https://gitee.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-gitee\"></use></svg>', '国产代码托管与研发协作平台', 'Gitee,码云,代码托管,研发管理,git', 0, 5, 1, 0, NULL),
(121, '博客园', 10, 'https://www.cnblogs.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-cnblogs\"></use></svg>', '面向开发者的知识分享社区', '博客园,开发者,技术博客,知识分享', 0, 6, 1, 0, NULL),
(122, '思否', 10, 'https://segmentfault.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-a-SegmentFaultsifou\"></use></svg>', '专业开发者技术问答社区', '思否,SegmentFault,技术问答,开发者社区', 0, 7, 1, 0, NULL),
(123, 'CSDN', 10, 'https://www.csdn.net', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-csdn\"></use></svg>', '知名中文IT技术交流平台', 'CSDN,IT技术,技术博客,开发者社区', 0, 8, 1, 0, NULL),
(124, '阿里云计算', 10, 'https://www.aliyun.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-aliyun\"></use></svg>', '阿里旗下云计算与AI服务平台', '阿里云,云服务器,云计算,AI大模型', 0, 9, 1, 0, NULL),
(125, 'Github', 10, 'https://github.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-github\"></use></svg>', '全球最大开源代码托管平台', 'GitHub,开源,代码托管,git', 0, 10, 1, 0, NULL),
(126, '腾讯云计算', 10, 'https://cloud.tencent.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tengxunyun\"></use></svg>', '腾讯旗下云计算服务平台', '腾讯云,云服务器,云计算,云数据库', 0, 11, 1, 0, NULL),
(127, '华为云', 10, 'https://www.huaweicloud.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-huaweiyun\"></use></svg>', '华为旗下稳定可靠的云服务平台', '华为云,云服务器,云计算,云数据库', 0, 12, 1, 0, NULL),
(128, '七牛云', 10, 'https://www.qiniu.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-qiniuyun\"></use></svg>', '一站式音视频云与AI服务商', '七牛云,音视频云,CDN,云存储,直播云', 0, 13, 1, 0, NULL),
(129, 'Cloudflare', 10, 'https://www.cloudflare.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-cloudflare\"></use></svg>', '全球CDN与网络安全服务商', 'Cloudflare,CDN,网络安全,DNS', 0, 14, 1, 0, NULL),
(130, '菜鸟教程', 10, 'https://www.runoob.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-cainiaojiaocheng\"></use></svg>', '编程基础技术与实例教程网站', '菜鸟教程,编程教程,在线实例,编程入门', 0, 15, 1, 0, NULL),
(131, '豆包', 1, 'https://www.doubao.com/chat', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-doubao\"></use></svg>', '字节跳动AI助手，支持对话、写作与编程', '豆包,AI对话,AI聊天,AI写作,AI图片生成', 0, 1, 1, 0, NULL),
(132, 'DeepSeek', 1, 'https://chat.deepseek.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-deepseek\"></use></svg>', '深度求索AI助手，擅长代码与长文本对话', 'DeepSeek,深度求索,AI助手,代码助手,大语言模型', 0, 2, 1, 0, NULL),
(133, '腾讯元宝', 1, 'https://yuanbao.tencent.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tengxunyuanbao\"></use></svg>', '腾讯AI助手，接入混元大模型，支持搜索问答', '腾讯元宝,混元大模型,AI助手,AI搜索,AI对话', 0, 3, 1, 0, NULL),
(134, '腾讯新闻', 1, 'https://news.qq.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tengxunxinwen\"></use></svg>', '腾讯旗下综合资讯平台，海量新闻频道', '腾讯新闻,新闻资讯,财经,科技,体育', 0, 14, 1, 0, NULL),
(135, '抖音网页版', 1, 'https://www.douyin.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-douyin\"></use></svg>', '抖音官方网页版，在线刷短视频', '抖音,短视频,抖音网页版,直播', 0, 4, 1, 0, NULL),
(136, '快手网页版', 1, 'https://www.kuaishou.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-kuaishou\"></use></svg>', '快手官网，短视频创作与分享平台', '快手,短视频,快手官网,直播', 0, 5, 1, 0, NULL),
(137, '哔哩哔哩', 1, 'https://www.bilibili.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-bilibilixds\"></use></svg>', '国内知名弹幕视频网站，ACG聚集地', '哔哩哔哩,B站,弹幕视频,动漫,ACG', 0, 8, 1, 0, NULL),
(138, '腾讯视频', 1, 'https://v.qq.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-tencent\"></use></svg>', '腾讯旗下正版高清视频平台', '腾讯视频,在线视频,电视剧,电影,综艺', 0, 9, 1, 0, NULL),
(139, '爱奇艺', 1, 'https://www.iqiyi.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-aiqiyi\"></use></svg>', '正版高清视频，剧集综艺动漫齐全', '爱奇艺,在线视频,电影,电视剧,综艺', 0, 10, 1, 0, NULL),
(140, '优酷视频', 1, 'https://www.youku.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-youku\"></use></svg>', '阿里旗下正版在线视频平台', '优酷,在线视频,电视剧,电影,综艺', 0, 11, 1, 0, NULL),
(141, 'QQ音乐', 1, 'https://y.qq.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-QQmusic\"></use></svg>', '腾讯旗下正版音乐在线试听平台', 'QQ音乐,在线音乐,无损音乐,音乐下载', 0, 12, 1, 0, NULL),
(142, '网易云音乐', 1, 'https://music.163.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-wangyiyunyinle\"></use></svg>', '专注发现与分享的音乐社区', '网易云音乐,在线音乐,歌单,音乐推荐', 0, 13, 1, 0, NULL),
(143, 'CCTV官网', 1, 'http://tv.cctv.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-cctv\"></use></svg>', '央视节目官方平台，直播点播齐全', 'CCTV,央视,央视直播,节目点播', 0, 15, 1, 0, NULL),
(144, '微信文件传输助手', 1, 'https://filehelper.weixin.qq.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-wenjianchuanshuzhushou\"></use></svg>', '微信文件传输助手网页版', '微信,文件传输,网页版,传文件', 0, 0, 1, 0, NULL),
(145, '淘宝', 1, 'https://www.taobao.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-taobao\"></use></svg>', '阿里旗下大型综合网购平台', '淘宝,网购,网上购物,电商平台', 0, 6, 1, 0, NULL),
(146, '京东', 1, 'https://www.jd.com/', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-jingdong\"></use></svg>', '正品低价的综合网购商城', '京东,网上商城,正品,家电数码', 0, 7, 1, 0, NULL),
(147, '申请收录', 11, '/apply', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-gonggao\">\r\n</use></svg>', '申请本站收录', '申请收录.六零导航页', 0, 0, 1, 0, NULL),
(148, '关于本站', 11, '/about', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-lylme\">\r\n</use></svg>', '简洁高效的网址导航', '关于本站,六零导航页', 0, 1, 1, 0, NULL),
(149, '今日热点', 1, 'https://60s.lylme.com', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-rebang\">\r\n</use></svg>', '每天120秒看世界', 'LyToday,60秒读懂世界,历史上的今天,今日黄历,上云六零,六零,LyLme,今日120秒视界', 0, 148, 1, 0, NULL),
(150, '站内博客', 11, '/article', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zixun\">\r\n</use></svg>', '访问站内博客', 'LyLmecho,博客.六零导航页', 0, 2, 1, 0, NULL);

DROP TABLE IF EXISTS `lylme_pwd`;
CREATE TABLE `lylme_pwd` (
  `pwd_id` int(11) NOT NULL COMMENT '加密组ID',
  `pwd_name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '加密组名称',
  `pwd_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '加密组密码',
  `pwd_ps` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '加密组备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lylme_sou`;
CREATE TABLE `lylme_sou` (
  `sou_id` int(11) NOT NULL COMMENT '搜索引擎ID',
  `sou_alias` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '搜索引擎别名',
  `sou_name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '搜索引擎名称',
  `sou_hint` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '请输入搜索关键词' COMMENT '搜索引擎提示文字',
  `sou_color` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#696a6d' COMMENT '搜索引擎字体颜色',
  `sou_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '搜索引擎地址',
  `sou_waplink` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '搜索引擎移动端地址',
  `sou_icon` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '搜索引擎图标',
  `sou_st` tinyint(1) NOT NULL DEFAULT '1' COMMENT '搜索引擎开关',
  `sou_order` int(4) NOT NULL COMMENT '搜索引擎排序'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索引擎';

INSERT INTO `lylme_sou` (`sou_id`, `sou_alias`, `sou_name`, `sou_hint`, `sou_color`, `sou_link`, `sou_waplink`, `sou_icon`, `sou_st`, `sou_order`) VALUES
(1, 'bing', 'Bing必应', '微软必应搜索', '#696a6d', 'https://cn.bing.com/search?q=', NULL, '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-bing\"></use></svg>', 1, 1),
(2, 'baidu', '百度一下', '百度一下，你就知道', '#0c498c', 'https://www.baidu.com/s?word=', 'https://m.baidu.com/s?word=', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-baidu\"></use></svg>', 1, 2),
(3, 'sogou', '搜狗搜索', '上网从搜狗开始', '#696a6d', 'https://www.sogou.com/web?query=', NULL, '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-sougousousuo\"></use></svg>', 1, 3),
(4, 'zhihu', '知乎搜索', '有问题，上知乎', '#0084fe', 'https://www.zhihu.com/search?q=', NULL, '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-zhihu\"></use></svg>', 1, 4),
(5, 'bilibili', '哔哩哔哩', '(゜-゜)つロ 干杯~', '#00aeec', 'https://search.bilibili.com/all?keyword=', NULL, '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-bilibili\"></use></svg>', 1, 5),
(6, 'weibo', '微博搜索', '随时随地发现新鲜事', '#ff5722', 'https://s.weibo.com/weibo/', NULL, '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-weibo\"></use></svg>', 1, 6),
(7, 'google', '谷歌搜索', '值得信任的搜索引擎', '#3B83FA', 'https://www.google.com/search?q=', NULL, '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-google\"></use></svg>', 1, 7),
(8, 'fanyi', '在线翻译', '输入翻译内容（自动检测语言）', '#0084fe', 'https://fanyi.baidu.com/#auto/zh/', NULL, '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-fanyi\"></use></svg>', 1, 8),
(9, 'dayi', '医药信息查询', '搜疾病、症状、医院、医生、药品', '#696a6d', 'https://www.dayi.org.cn/search?keyword=', 'https://m.dayi.org.cn/search?keyword=', '<svg class=\"icon\" aria-hidden=\"true\"><use xlink:href=\"#lyicon-yiyao\"></use></svg>', 1, 9);

DROP TABLE IF EXISTS `lylme_tags`;
CREATE TABLE `lylme_tags` (
  `tag_id` int(11) NOT NULL COMMENT '导航菜单ID',
  `tag_name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '导航菜单名称',
  `tag_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '导航菜单链接',
  `tag_target` tinyint(1) NOT NULL DEFAULT '1' COMMENT '打开方式(1新标签,0当前标签)',
  `sort` int(11) NOT NULL DEFAULT '10' COMMENT '排序'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lylme_tags` (`tag_id`, `tag_name`, `tag_link`, `tag_target`, `sort`) VALUES
(1, '关于本站', '/about', 1, 10),
(2, '申请收录', '/apply', 1, 10),
(3, '访问管理', '/pwd', 0, 10),
(4, '站内博客', '/article', 1, 5);


ALTER TABLE `lylme_apply`
  ADD PRIMARY KEY (`apply_id`),
  ADD KEY `idx_apply_group` (`apply_group`),
  ADD KEY `idx_apply_status` (`apply_status`);

ALTER TABLE `lylme_article`
  ADD PRIMARY KEY (`art_id`),
  ADD KEY `idx_cat_id` (`cat_id`),
  ADD KEY `idx_art_status` (`art_status`),
  ADD KEY `idx_art_top` (`art_top`),
  ADD KEY `idx_art_time` (`art_time`),
  ADD KEY `idx_art_slug` (`art_slug`),
  ADD KEY `idx_author_uid` (`art_author_uid`);

ALTER TABLE `lylme_article_cat`
  ADD PRIMARY KEY (`cat_id`),
  ADD KEY `idx_cat_alias` (`cat_alias`),
  ADD KEY `idx_cat_order` (`cat_order`),
  ADD KEY `idx_cat_status` (`cat_status`);

ALTER TABLE `lylme_article_comment`
  ADD PRIMARY KEY (`com_id`),
  ADD KEY `idx_comment_art_id` (`art_id`),
  ADD KEY `idx_com_pid` (`com_pid`),
  ADD KEY `idx_com_status` (`com_status`),
  ADD KEY `idx_com_uid` (`com_uid`),
  ADD KEY `idx_com_time` (`com_time`);

ALTER TABLE `lylme_article_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_article_k` (`k`);

ALTER TABLE `lylme_article_page`
  ADD PRIMARY KEY (`page_id`),
  ADD KEY `idx_page_slug` (`page_slug`),
  ADD KEY `idx_page_status` (`page_status`),
  ADD KEY `idx_page_order` (`page_order`);

ALTER TABLE `lylme_article_user`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `uk_member_username` (`username`),
  ADD KEY `idx_member_email` (`email`),
  ADD KEY `idx_member_status` (`status`);

ALTER TABLE `lylme_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_k` (`k`);

ALTER TABLE `lylme_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `idx_group_pwd` (`group_pwd`);

ALTER TABLE `lylme_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_group_id` (`group_id`),
  ADD KEY `idx_link_pwd` (`link_pwd`),
  ADD KEY `idx_link_status` (`link_status`);

ALTER TABLE `lylme_pwd`
  ADD PRIMARY KEY (`pwd_id`);

ALTER TABLE `lylme_sou`
  ADD PRIMARY KEY (`sou_id`),
  ADD KEY `idx_sou_st` (`sou_st`),
  ADD KEY `idx_sou_order` (`sou_order`);

ALTER TABLE `lylme_tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD KEY `idx_tag_sort` (`sort`);


ALTER TABLE `lylme_apply`
  MODIFY `apply_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '收录ID';

ALTER TABLE `lylme_article`
  MODIFY `art_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章ID', AUTO_INCREMENT=2;

ALTER TABLE `lylme_article_cat`
  MODIFY `cat_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID', AUTO_INCREMENT=2;

ALTER TABLE `lylme_article_comment`
  MODIFY `com_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '评论ID';

ALTER TABLE `lylme_article_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=109;

ALTER TABLE `lylme_article_page`
  MODIFY `page_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '页面ID';

ALTER TABLE `lylme_article_user`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员ID';

ALTER TABLE `lylme_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=25;

ALTER TABLE `lylme_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分组ID', AUTO_INCREMENT=12;

ALTER TABLE `lylme_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '链接ID', AUTO_INCREMENT=151;

ALTER TABLE `lylme_pwd`
  MODIFY `pwd_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '加密组ID';

ALTER TABLE `lylme_sou`
  MODIFY `sou_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '搜索引擎ID', AUTO_INCREMENT=10;

ALTER TABLE `lylme_tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '导航菜单ID', AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

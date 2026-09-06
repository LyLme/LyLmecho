<?php

if (!defined("VERSION")) {
    return 0;
}

function get_vernum($version)
{
    // 移除版本号中的'v'前缀，并分割为数组
    $vn = explode('.', str_replace('v', '', (string) $version));

    // 确保数组至少有3个元素，避免未定义偏移错误
    $vn[0] = isset($vn[0]) ? $vn[0] : 0;
    $vn[1] = isset($vn[1]) ? $vn[1] : 0;
    $vn[2] = isset($vn[2]) ? $vn[2] : 0;

    // 格式化版本号：主版本 + 两位次版本 + 两位修订版本
    return $vn[0] . sprintf("%02d", $vn[1]) . sprintf("%02d", $vn[2]);
}

// 确保配置存在且包含版本信息
if (!isset($conf['version']) || empty($conf['version'])) {
    return 0;
}

$sqlvn = get_vernum($conf['version']);  // 数据库版本
$filevn = get_vernum(constant("VERSION"));  // 文件版本

if (!(isset($conf['build']) ? $conf['build'] : "")) {
    saveSetting('build', date("Y-m-d H:i"));
}
if ($sqlvn < $filevn) {
    // 文件版本大于数据库版本，执行更新
    $sql = '';
    $version = '';
    if ($sqlvn < 20300) {
        $version = 'v2.3.0';
        $sql .= "ALTER TABLE `lylme_links` ADD `link_keywords` VARCHAR(512) NULL DEFAULT NULL COMMENT '链接关键词' AFTER `link_desc`;";
    }

    if ($sqlvn < 20600) {
        saveSetting('copyright',  $conf['copyright'] . '<script src="/assets/js/svg.js"></script>'); //注入旧版svg图标
        $version = 'v2.6.0';
    }
    if ($sqlvn < 20700) {
        $version = 'v2.7.0';
        // 文章模块数据表
        $sql .= "CREATE TABLE IF NOT EXISTS `lylme_article` (
  `art_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `cat_id` int(11) NOT NULL DEFAULT 0 COMMENT '分类ID',
  `art_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文章标题',
  `art_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'URL别名',
  `art_author` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '管理员' COMMENT '作者',
  `art_content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文章内容',
  `art_excerpt` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文章摘要',
  `art_cover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面图URL',
  `art_keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `art_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `art_views` int(11) NOT NULL DEFAULT 0 COMMENT '浏览次数',
  `art_likes` int(11) NOT NULL DEFAULT 0 COMMENT '点赞数',
  `art_comments` int(11) NOT NULL DEFAULT 0 COMMENT '评论数',
  `art_top` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶',
  `art_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(0草稿1发布)',
  `art_allow_comment` tinyint(1) NOT NULL DEFAULT 1 COMMENT '允许评论',
  `art_time` datetime NOT NULL COMMENT '发布时间',
  `art_update` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`art_id`),
  KEY `idx_cat_id` (`cat_id`),
  KEY `idx_art_status` (`art_status`),
  KEY `idx_art_time` (`art_time`),
  KEY `idx_art_slug` (`art_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章表';";
        $sql .= "CREATE TABLE IF NOT EXISTS `lylme_article_cat` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `cat_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `cat_alias` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类别名',
  `cat_desc` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类描述',
  `cat_order` int(4) NOT NULL DEFAULT 10 COMMENT '排序',
  `cat_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `cat_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`cat_id`),
  KEY `idx_cat_alias` (`cat_alias`),
  KEY `idx_cat_order` (`cat_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章分类表';";
        $sql .= "CREATE TABLE IF NOT EXISTS `lylme_article_comment` (
  `com_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '评论ID',
  `art_id` int(11) NOT NULL COMMENT '文章ID',
  `com_pid` int(11) NOT NULL DEFAULT 0 COMMENT '父评论ID',
  `com_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `com_email` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `com_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '网站',
  `com_ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
  `com_agent` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UA',
  `com_content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '评论内容',
  `com_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态(0待审核1通过)',
  `com_time` datetime NOT NULL COMMENT '评论时间',
  PRIMARY KEY (`com_id`),
  KEY `idx_art_id` (`art_id`),
  KEY `idx_com_pid` (`com_pid`),
  KEY `idx_com_status` (`com_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章评论表';";
        $sql .= "CREATE TABLE IF NOT EXISTS `lylme_article_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `k` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置项',
  `v` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '配置值',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_article_k` (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章模块配置';";
        $sql .= "INSERT IGNORE INTO `lylme_article_config` (`k`, `v`) VALUES
('article_status','1'),('article_perpage','10'),('article_comment','1'),
('article_audit','0'),('article_name','管理员'),('article_theme','typecho'),
('article_web_title','博客'),('article_web_keywords','lylme,lylme_spage'),
('article_web_description','LyLme Spage Blog'),('article_url_style','default'),
('article_url_custom','article/post{id}.html'),
('article_member_status','1'),('article_member_verify','0'),('article_member_role','subscriber'),
('article_mail_status','0'),('article_mail_host',''),('article_mail_port','465'),
('article_mail_secure','ssl'),('article_mail_user',''),('article_mail_pass',''),
('article_mail_from',''),('article_mail_from_name','');";
        // 文章前台会员表(方案C)
        $sql .= "CREATE TABLE IF NOT EXISTS `lylme_member` (
  `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员ID',
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码哈希',
  `nickname` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '显示昵称',
  `email` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '个人网站',
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'subscriber' COMMENT '角色',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(0待验证/禁用,1正常)',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像URL',
  `verify_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱验证令牌',
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '记住我令牌哈希',
  `token_exp` int(11) NOT NULL DEFAULT 0 COMMENT '记住我令牌过期时间戳',
  `reg_ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '注册IP',
  `reg_time` datetime NOT NULL COMMENT '注册时间',
  `last_login` datetime DEFAULT NULL COMMENT '最后登录时间',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `uk_member_username` (`username`),
  KEY `idx_member_email` (`email`),
  KEY `idx_member_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章前台会员表';";
        // 评论表新增 com_uid(绑定会员), 列/索引已存在时由 try/catch 容错跳过
        $sql .= "ALTER TABLE `lylme_article_comment` ADD `com_uid` int(11) NOT NULL DEFAULT 0 COMMENT '发表评论的会员ID(0为游客)' AFTER `com_status`;";
       $sql .= "ALTER TABLE `lylme_article_comment` ADD KEY `idx_com_uid` (`com_uid`);";
    }
    if ($sqlvn < 20701) {
        $version = 'v2.7.1';
        // 文章投稿/审核工作流: 新增投稿会员归属列(art_author_uid) + 前台投稿总开关种子
        // art_status 语义扩展(2=待审核)仅体现在注释中, 无需改列类型; 列/索引/种子已存在时由 try/catch 容错跳过
        $sql .= "ALTER TABLE `lylme_article` ADD `art_author_uid` int(11) NOT NULL DEFAULT 0 COMMENT '投稿会员UID(0为管理员/系统)' AFTER `art_author`;";
        $sql .= "ALTER TABLE `lylme_article` ADD KEY `idx_author_uid` (`art_author_uid`);";
        $sql .= "INSERT IGNORE INTO `lylme_article_config` (`k`, `v`) VALUES ('article_post_status','0');";
    }
    // 执行SQL语句
    if (!empty($sql)) {
        $sqlStatements = explode(';', $sql);

        foreach ($sqlStatements as $sqlStatement) {
            $sqlStatement = trim($sqlStatement);
            if (empty($sqlStatement)) {
                continue;
            }

            try {
                $DB->query($sqlStatement);
            } catch (Exception $e) {
                // 可以选择记录错误日志，但不中断升级流程
                error_log("SQL执行失败: " . $e->getMessage());
            }
        }
    }

    // 保存新版本号
    if (!empty($version)) {
        saveSetting('version', $version);
    }
}

<?php
/**
 * lyLmecho 默认主题 lylmeblog —— 主题自定义配置
 *
 * 表单支持 type：text / textarea / select / checkbox / radio / color
 * 每个配置项说明：
 *   name：参数键，主题内 theme_config('name', 默认值) 读取（需唯一）
 *   value：默认值  enum：可选值（select/radio/checkbox 使用，键为存储值、值为显示文字）
 */

$theme_config = array(

    // ---------- 外观 ----------
    array(
        'type'  => 'color',
        'name'  => 'color',
        'title' => '主题主色',
        'description' => '与博客模块保持同一主色（按钮、链接、强调色），留空使用默认蓝 #4f7cf7',
        'value' => '#4f7cf7',
    ),
    array(
        'type'  => 'select',
        'name'  => 'scheme',
        'title' => '默认配色方案',
        'description' => '跟随系统=自动，或强制浅色/深色；访客仍可在顶栏手动切换（与博客共用记忆）',
        'value' => 'auto',
        'enum'  => array(
            'auto'    => '跟随系统',
            'default' => '浅色',
            'dark'    => '深色',
        ),
    ),
    array(
        'type'  => 'select',
        'name'  => 'link_cols',
        'title' => '链接列表列数',
        'description' => '导航分组内每行展示的链接数（PC），手机端自动变为 2 列',
        'value' => 4,
        'enum'  => array(
            3 => '3 列',
            4 => '4 列',
            5 => '5 列',
            6 => '6 列',
            7 => '7 列',
        ),
    ),

    // ---------- 首页模块 ----------
    array(
        'type'  => 'checkbox',
        'name'  => 'modules',
        'title' => '首页显示模块',
        'description' => '可多选。blog 控制全部「博客联动」区块（关于卡、最新文章、热门、分类、RSS 等）',
        'value' => array('blog', 'hot','clock'),
        'enum'  => array(
            'blog' => '博客联动区块',
            'hot'  => '热门文章榜',
            'clock'=> '顶栏时间显示',
        ),
    ),
    array(
        'type'  => 'textarea',
        'name'  => 'notice',
        'title' => '首页公告',
        'description' => '显示在搜索区下方，<code>支持 HTML 代码</code>，留空不显示',
        'value' => '',
    ),
    array(
        'type'  => 'text',
        'name'  => 'blog_latest',
        'title' => '侧栏「最新文章」条数',
        'description' => '数字 3~10，默认 6',
        'value' => '6',
        'verify' => 'number',
    ),

    // ---------- 备案 ----------
    array(
        'type'  => 'text',
        'name'  => 'gonganbei',
        'title' => '公安备案号',
        'description' => '公安备案号，留空不显示',
        'value' => '',
        'placeholder' => '京公安网备xxxxxxxxxx号',
    ),

);

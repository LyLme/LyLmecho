<?php
/**
 * Markdown 渲染器 (自包含轻量实现)
 * 将 Vditor 保存的 Markdown 源码渲染为 HTML, 覆盖 Typecho 主题常见语法。
 * 命名空间: Compat
 */
namespace Compat;

class Markdown
{
    protected $holders = array();
    protected $seq = 0;
    protected $mark = "\x1E"; // 占位符包裹符(控制字符)

    public static function convert($text)
    {
        $p = new self();
        return $p->transform($text);
    }

    public function transform($text)
    {
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        $text = str_replace("\t", '    ', $text);
        $text = $this->extractFenced($text);
        return $this->restore($this->parseBlocks($text));
    }

    protected function store($content)
    {
        $k = $this->mark . 'MDK' . $this->seq++ . $this->mark;
        $this->holders[$k] = $content;
        return $k;
    }

    protected function restore($text)
    {
        return $this->holders ? strtr($text, $this->holders) : $text;
    }

    /** 提取 ``` 或 ~~~ 围栏代码块 */
    protected function extractFenced($text)
    {
        while (preg_match('/^ {0,3}(`{3,}|~{3,})[ \t]*([^\n]*)\n(.*?)\n {0,3}\1[ \t]*$/ms', $text, $m)) {
            $lang = trim($m[2]);
            $code = htmlspecialchars(rtrim($m[3], "\n"), ENT_QUOTES, 'UTF-8');
            $class = $lang !== '' ? ' class="language-' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '"' : '';
            $text = str_replace($m[0], $this->store('<pre><code' . $class . '>' . $code . '</code></pre>'), $text);
        }
        return $text;
    }

    /* ------------------- 块级解析 ------------------- */

    protected function parseBlocks($text)
    {
        $lines = explode("\n", $text);
        $n = count($lines);
        $out = '';
        $listStack = array(); // [['type'=>'ul'|'ol','indent'=>int]]
        $openLi = false;
        $para = array();
        $i = 0;

        while ($i < $n) {
            $trimmed = rtrim($lines[$i]);
            $stripped = ltrim($trimmed);
            $indent = strlen($trimmed) - strlen($stripped);

            if ($stripped === '') {
                $this->flushPara($out, $para);
                $i++;
                continue;
            }

            // 围栏代码块占位符
            if (strpos($stripped, $this->mark . 'MDK') === 0) {
                $this->flushPara($out, $para);
                $this->closeList($out, $listStack, $openLi);
                $out .= $stripped . "\n";
                $i++;
                continue;
            }

            // ATX 标题
            if (preg_match('/^ {0,3}(#{1,6})[ \t]+(.*?)[ \t]*#*[ \t]*$/', $trimmed, $m)) {
                $this->flushPara($out, $para);
                $this->closeList($out, $listStack, $openLi);
                $lv = strlen($m[1]);
                $out .= '<h' . $lv . '>' . $this->inline($m[2]) . '</h' . $lv . ">\n";
                $i++;
                continue;
            }

            // 分割线 (--- / *** / ___)
            if (preg_match('/^ {0,3}([-_*])(\s*\1){2,}\s*$/', $trimmed)) {
                $this->flushPara($out, $para);
                $this->closeList($out, $listStack, $openLi);
                $out .= "<hr />\n";
                $i++;
                continue;
            }

            // 块级 HTML 行: 原样输出直到空行
            if (preg_match('/^<(?:p|div|h[1-6]|ul|ol|li|pre|blockquote|table|thead|tbody|tr|td|th|hr|br|img|figure|figcaption|section|article|aside|header|footer|nav|video|audio|iframe|script|style|form|input|button|textarea|select|span|a|center|font|u|del|sup|sub)\b/i', $stripped)) {
                $this->flushPara($out, $para);
                $this->closeList($out, $listStack, $openLi);
                $block = array($trimmed);
                $i++;
                while ($i < $n && trim($lines[$i]) !== '') {
                    $block[] = rtrim($lines[$i]);
                    $i++;
                }
                $out .= implode("\n", $block) . "\n";
                continue;
            }

            // 引用块 (含嵌套)
            if (preg_match('/^ {0,3}>/', $trimmed)) {
                $this->flushPara($out, $para);
                $this->closeList($out, $listStack, $openLi);
                $quoteLines = array();
                while ($i < $n && preg_match('/^ {0,3}>/', $lines[$i])) {
                    $quoteLines[] = preg_replace('/^ {0,3}>\s?/', '', $lines[$i]);
                    $i++;
                }
                $out .= '<blockquote>' . "\n" . $this->parseBlocks(implode("\n", $quoteLines)) . '</blockquote>' . "\n";
                continue;
            }

            // 表格: 当前行含 | 且下一行是分隔行
            if (strpos($trimmed, '|') !== false && $i + 1 < $n
                && $this->isTableSeparator($lines[$i + 1])) {
                $this->flushPara($out, $para);
                $this->closeList($out, $listStack, $openLi);
                $rows = array($trimmed);
                $i++;
                while ($i < $n && strpos($lines[$i], '|') !== false) {
                    $rows[] = rtrim($lines[$i]);
                    $i++;
                }
                $out .= $this->renderTable($rows) . "\n";
                continue;
            }

            // 4 空格缩进代码块
            if ($indent >= 4 && empty($listStack)) {
                $this->flushPara($out, $para);
                $codeLines = array();
                while ($i < $n) {
                    $l = $lines[$i];
                    if (trim($l) === '') {
                        $codeLines[] = '';
                        $i++;
                        if ($i < $n && strlen($l) - strlen(ltrim($l)) < 4 && trim($lines[$i]) !== '') break;
                        continue;
                    }
                    if (strlen($l) - strlen(ltrim($l)) < 4) break;
                    $codeLines[] = substr($l, 4);
                    $i++;
                }
                $out .= '<pre><code>' . htmlspecialchars(rtrim(implode("\n", $codeLines), "\n"), ENT_QUOTES, 'UTF-8') . '</code></pre>' . "\n";
                continue;
            }

            // 无序列表
            if (preg_match('/^(\s*)([-*+])\s+(.*)$/', $trimmed, $m)) {
                $this->flushPara($out, $para);
                $out .= $this->parseListItem($m[1], 'ul', $m[3], $listStack, $openLi);
                $j = $i + 1;
                $cont = array();
                while ($j < $n) {
                    $l = $lines[$j];
                    if (trim($l) === '') break;
                    if (preg_match('/^ {2,}\S/', $l) && !preg_match('/^ *([-*+]|\d+[.)])\s/', ltrim($l))) {
                        $cont[] = trim($l);
                        $j++;
                    } else break;
                }
                if ($cont) {
                    $out .= '<br />' . $this->inline(implode("\n", $cont));
                    $i = $j;
                } else {
                    $i++;
                }
                continue;
            }

            // 有序列表
            if (preg_match('/^(\s*)(\d+[.)])\s+(.*)$/', $trimmed, $m)) {
                $this->flushPara($out, $para);
                $out .= $this->parseListItem($m[1], 'ol', $m[3], $listStack, $openLi);
                $j = $i + 1;
                $cont = array();
                while ($j < $n) {
                    $l = $lines[$j];
                    if (trim($l) === '') break;
                    if (preg_match('/^ {2,}\S/', $l) && !preg_match('/^ *([-*+]|\d+[.)])\s/', ltrim($l))) {
                        $cont[] = trim($l);
                        $j++;
                    } else break;
                }
                if ($cont) {
                    $out .= '<br />' . $this->inline(implode("\n", $cont));
                    $i = $j;
                } else {
                    $i++;
                }
                continue;
            }

            // 普通段落行
            $para[] = $trimmed;
            $i++;
        }

        $this->flushPara($out, $para);
        $this->closeList($out, $listStack, $openLi);
        return $out;
    }

    /** 处理单个列表项, 维护列表栈, 返回列表标签+内容 */
    protected function parseListItem($indentStr, $type, $content, &$listStack, &$openLi)
    {
        $out = '';
        $indent = strlen($indentStr);

        if (empty($listStack)) {
            $listStack[] = array('type' => $type, 'indent' => $indent);
            $out = '<' . $type . ">\n<li>";
            $openLi = true;
        } else {
            $top = count($listStack) - 1;
            $cur = $listStack[$top]['indent'];
            if ($indent === $cur) {
                if ($listStack[$top]['type'] !== $type) {
                    $out = "</li>\n</" . $listStack[$top]['type'] . ">\n<" . $type . ">\n<li>";
                    $listStack[$top]['type'] = $type;
                } else {
                    $out = "</li>\n<li>";
                }
            } elseif ($indent > $cur) {
                $listStack[] = array('type' => $type, 'indent' => $indent);
                $out = "\n<" . $type . ">\n<li>";
            } else {
                $popped = array();
                while (!empty($listStack) && $indent < $listStack[$top]['indent']) {
                    $popped[] = array_pop($listStack);
                    $top = count($listStack) - 1;
                }
                foreach ($popped as $p) {
                    $out .= "</li>\n</" . $p['type'] . ">";
                }
                if (empty($listStack)) {
                    $listStack[] = array('type' => $type, 'indent' => $indent);
                    $out .= "\n<" . $type . ">\n<li>";
                } elseif ($indent === $listStack[$top]['indent']) {
                    if ($listStack[$top]['type'] !== $type) {
                        $out .= "</li>\n</" . $listStack[$top]['type'] . ">\n<" . $type . ">\n<li>";
                        $listStack[$top]['type'] = $type;
                    } else {
                        $out .= "</li>\n<li>";
                    }
                } else {
                    $listStack[] = array('type' => $type, 'indent' => $indent);
                    $out .= "\n<" . $type . ">\n<li>";
                }
            }
            $openLi = true;
        }

        // 任务列表
        if ($type === 'ul' && preg_match('/^\[([ xX])\]\s+(.*)$/', $content, $tm)) {
            $checked = strtolower($tm[1]) === 'x' ? 'checked' : '';
            return $out . '<input type="checkbox" disabled ' . $checked . ' /> ' . $this->inline($tm[2]);
        }
        return $out . $this->inline($content);
    }

    /** 关闭所有打开的列表 */
    protected function closeList(&$out, &$listStack, &$openLi)
    {
        if (!empty($listStack)) {
            if ($openLi) {
                $out .= "</li>\n";
                $openLi = false;
            }
            while (!empty($listStack)) {
                $item = array_pop($listStack);
                $out .= '</' . $item['type'] . ">\n";
            }
        }
    }

    /** 输出段落缓冲 */
    protected function flushPara(&$out, &$para)
    {
        if (!empty($para)) {
            $out .= '<p>' . $this->inline(implode("\n", $para)) . "</p>\n";
            $para = array();
        }
    }

    /** 渲染表格 */
    protected function renderTable($rows)
    {
        $cells = function ($row) {
            $row = trim($row);
            $row = preg_replace('/^\|/', '', $row);
            $row = preg_replace('/\|$/', '', $row);
            return array_map('trim', explode('|', $row));
        };

        $header = $cells($rows[0]);
        $sep = $cells(isset($rows[1]) ? $rows[1] : '');
        $aligns = array();
        foreach ($sep as $s) {
            $left = strpos($s, ':') === 0;
            $right = substr($s, -1) === ':';
            $aligns[] = $left && $right ? 'center' : ($left ? 'left' : ($right ? 'right' : ''));
        }

        $html = "<table>\n<thead>\n<tr>\n";
        foreach ($header as $k => $h) {
            $st = isset($aligns[$k]) && $aligns[$k] ? ' style="text-align:' . $aligns[$k] . '"' : '';
            $html .= '<th' . $st . '>' . $this->inline($h) . "</th>\n";
        }
        $html .= "</tr>\n</thead>\n<tbody>\n";
        for ($i = 2; $i < count($rows); $i++) {
            $html .= "<tr>\n";
            foreach ($cells($rows[$i]) as $k => $c) {
                $st = isset($aligns[$k]) && $aligns[$k] ? ' style="text-align:' . $aligns[$k] . '"' : '';
                $html .= '<td' . $st . '>' . $this->inline($c) . "</td>\n";
            }
            $html .= "</tr>\n";
        }
        return $html . "</tbody>\n</table>";
    }

    /** 判断是否为表格分隔行 (支持多列与 :---: 对齐) */
    protected function isTableSeparator($line)
    {
        $t = trim((string) $line);
        if ($t === '' || strpos($t, '-') === false) {
            return false;
        }
        $t = preg_replace('/^\|/', '', $t);
        $t = preg_replace('/\|$/', '', $t);
        $cells = explode('|', $t);
        if (empty($cells)) {
            return false;
        }
        $hasCol = false;
        foreach ($cells as $c) {
            if (!preg_match('/^\s*:?-+:?\s*$/', $c)) {
                return false;
            }
            $hasCol = true;
        }
        return $hasCol;
    }

    /* ------------------- 行内解析 ------------------- */

    protected function inline($text)
    {
        // 1. 保护转义符
        $text = preg_replace_callback('/\\\\([\\\\`*_{}\[\]()#+\-.!>|])/', function ($m) {
            return $this->store($m[1]);
        }, $text);

        // 2. 自动链接 <https://...>
        $text = preg_replace_callback('~<(https?|ftp)://[^<>\s]+>~i', function ($m) {
            $inner = substr($m[0], 1, -1);
            return $this->store('<a href="' . $inner . '">' . $inner . '</a>');
        }, $text);

        // 3. 保护 HTML 标签
        $text = preg_replace_callback('/<[^>]+>/', function ($m) {
            return $this->store($m[0]);
        }, $text);

        // 4. 转义 HTML 特殊字符
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // 5. 行内代码
        $text = preg_replace_callback('/(`+)(.+?)\1/', function ($m) {
            return $this->store('<code>' . $m[2] . '</code>');
        }, $text);

        // 6. 图片
        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)(?:\s+["\']([^"\']*)["\'])?\)/', function ($m) {
            $title = isset($m[3]) && $m[3] !== '' ? ' title="' . $m[3] . '"' : '';
            return $this->store('<img src="' . $m[2] . '" alt="' . $m[1] . '"' . $title . ' />');
        }, $text);

        // 7. 链接
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)(?:\s+["\']([^"\']*)["\'])?\)/', function ($m) {
            $title = isset($m[3]) && $m[3] !== '' ? ' title="' . $m[3] . '"' : '';
            return $this->store('<a href="' . $m[2] . '"' . $title . '>' . $this->inline($m[1]) . '</a>');
        }, $text);

        // 8. 裸 URL
        $text = preg_replace_callback('/(?<!["\'])(https?:\/\/[^\s<>"\'()]+)/i', function ($m) {
            $url = preg_replace('/[.,;:!?]+$/', '', $m[1]);
            return $this->store('<a href="' . $url . '">' . $url . '</a>');
        }, $text);

        // 9. 粗体
        $text = preg_replace_callback('/\*\*([^*\n]+)\*\*/', function ($m) {
            return $this->store('<strong>' . $m[1] . '</strong>');
        }, $text);
        $text = preg_replace_callback('/__([^_\n]+)__/', function ($m) {
            return $this->store('<strong>' . $m[1] . '</strong>');
        }, $text);

        // 10. 斜体
        $text = preg_replace_callback('/(?<!\*)\*([^*\n]+)\*(?!\*)/', function ($m) {
            return $this->store('<em>' . $m[1] . '</em>');
        }, $text);
        $text = preg_replace_callback('/(?<!_)_([^_\n]+)_(?!_)/', function ($m) {
            return $this->store('<em>' . $m[1] . '</em>');
        }, $text);

        // 11. 删除线
        $text = preg_replace_callback('/~~([^~\n]+)~~/', function ($m) {
            return $this->store('<del>' . $m[1] . '</del>');
        }, $text);

        // 12. 硬换行 (行尾两个空格)
        $text = str_replace("  \n", "<br />\n", $text);

        return $text;
    }
}

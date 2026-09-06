<?php
/**
 * Typecho 兼容层 - 作者对象
 * 支持 $archive->author->permalink() / name() 等调用
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Author
{
    /** @var string 作者名 */
    protected $name = '';

    /** @var string 作者链接 */
    protected $url = '';

    public function __construct($name = '', $url = '')
    {
        $this->name = $name;
        $this->url = $url;
    }

    /**
     * 输出作者链接
     */
    public function permalink()
    {
        echo $this->url;
        return $this;
    }

    /**
     * 输出作者名
     */
    public function name($last = '')
    {
        echo htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . $last;
        return $this;
    }

    /**
     * 输出作者邮箱(本站无, 输出空)
     */
    public function mail($last = '')
    {
        echo $last;
        return $this;
    }

    /**
     * 输出作者主页
     */
    public function url($last = '')
    {
        echo $last;
        return $this;
    }

    /**
     * 输出作者昵称 (Typecho: $archive->author->screenName())
     */
    public function screenName($last = '')
    {
        echo htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . $last;
        return $this;
    }

    /**
     * 字符串化
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * 属性访问兼容 (如 $author->name / $author->screenName)
     */
    public function __get($name)
    {
        if ($name === 'name' || $name === 'screenName') {
            return $this->name;
        }
        if ($name === 'url' || $name === 'permalink' || $name === 'home') {
            return $this->url;
        }
        return null;
    }

    /**
     * 未知方法兼容, 避免致命错误
     */
    public function __call($name, $args)
    {
        return $this;
    }
}

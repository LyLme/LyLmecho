<?php
/**
 * Typecho 兼容层 - 用户组件
 * 登录态来源: 文章前台会员(Compat\Member) 或 导航后台管理员(admin_token)
 * 支持 $this->user->hasLogin() / screenName() / permalink() / pass() 等
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class User extends BaseWidget
{
    /** @var bool 是否已登录(会员或管理员) */
    protected $loggedIn = false;
    /** @var array|false 当前会员行 */
    protected $member = false;
    /** @var bool 是否为导航后台管理员 */
    protected $admin = false;
    /** @var string 显示名 */
    protected $name = '游客';
    /** @var string 角色 */
    protected $role = 'visitor';

    public function __construct($parameter = null)
    {
        $this->parameter = new \stdClass();
        $this->options = App::$options;

        // 1) 文章前台会员
        $member = Member::current();
        if (is_array($member) && !empty($member['uid'])) {
            $this->loggedIn = true;
            $this->member = $member;
            $this->role = isset($member['role']) ? $member['role'] : 'subscriber';
            $this->name = !empty($member['nickname']) ? $member['nickname'] : (string) $member['username'];
            return;
        }

        // 2) 导航后台管理员 (复用 admin_token)
        if (Member::isAdminLoggedIn()) {
            $this->loggedIn = true;
            $this->admin = true;
            $this->role = 'administrator';
            $conf = App::$config;
            $this->name = !empty($conf['article_name']) ? (string) $conf['article_name'] : '管理员';
        }
    }

    protected function execute()
    {
    }

    public function hasLogin()
    {
        return $this->loggedIn;
    }

    /** 是否为文章前台会员(区别于后台管理员) */
    public function isMember()
    {
        return $this->member !== false;
    }

    /** 是否为后台管理员 */
    public function isAdmin()
    {
        return $this->admin;
    }

    /** 会员 uid, 未登录返回 0 */
    public function uid()
    {
        if ($this->member !== false) {
            echo intval($this->member['uid']);
            return;
        }
        echo $this->admin ? 1 : 0;
    }

    public function screenName($last = '')
    {
        echo htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . $last;
    }

    public function name()
    {
        return $this->name;
    }

    public function email()
    {
        $email = $this->member !== false && isset($this->member['email']) ? $this->member['email'] : '';
        echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    }

    /** 会员中心链接 */
    public function permalink()
    {
        if ($this->admin) {
            echo rtrim((string) (App::$options->get('adminUrl', '')), '/') . '/';
        } else {
            echo App::$articleUrl . '?member=center';
        }
    }

    public function url()
    {
        $url = $this->member !== false && isset($this->member['url']) ? $this->member['url'] : '';
        echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    /** 权限检查: $group 可为角色名(按等级判定)或自定义权限名 */
    public function pass($group, $return = false)
    {
        $ok = false;
        if ($this->admin) {
            $ok = true; // 管理员拥有全部权限
        } elseif ($this->member !== false) {
            if ($group === 'administrator') {
                $ok = false; // 会员不可越过管理员
            } else {
                $need = Member::roleLevel($group);
                if ($need > 0) {
                    // 角色名: 当前会员等级 >= 要求等级
                    $ok = Member::roleLevel($this->role) >= $need;
                } else {
                    // 非角色名的自定义权限: 精确匹配
                    $ok = $this->role === $group;
                }
            }
        }
        if ($return) {
            return $ok;
        }
        return $ok;
    }

    /** 登出 (委托 Member, 幂等) */
    public function logout()
    {
        Member::logout();
        $this->loggedIn = false;
        $this->member = false;
        $this->admin = false;
        $this->role = 'visitor';
        $this->name = '游客';
    }

    /** 登录 (由控制器 Member::attempt + login 完成, 此处占位) */
    public function login($name, $password)
    {
        return false;
    }

    /** 用户组映射 */
    public function __get($name)
    {
        if ($name === 'groups') {
            return ['administrator' => '管理员', 'editor' => '编辑', 'contributor' => '贡献者', 'subscriber' => '关注者'];
        }
        if ($name === 'role' || $name === 'group') {
            return $this->role;
        }
        if ($name === 'mail' || $name === 'email') {
            return $this->member !== false && isset($this->member['email']) ? $this->member['email'] : '';
        }
        return parent::__get($name);
    }
}

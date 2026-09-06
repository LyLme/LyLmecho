<?php
/**
 * Typecho 兼容层 - 文章前台会员体系 (方案 C)
 *
 * 职责:
 *   1) 会员数据 CRUD / 校验 / 认证 (password_hash)
 *   2) 登录态解析 (session 优先, "记住我" cookie 兜底, DB 存令牌哈希可撤销)
 *   3) 后台管理员登录态识别 (与导航后台 admin_token 复用同一校验)
 *   4) 后台可配置开关读取: 注册开放 / 邮箱验证 / 默认角色
 *
 * 与导航单管理员后台权限边界隔离: 管理员不写入 lylme_member 表。
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Member
{
    /** 记住我 cookie 名 */
    const COOKIE = 'lylme_member';
    /** session 键 */
    const SESSION_KEY = 'lylme_member_uid';
    /** 记住我有效期(秒) 30 天 */
    const REMEMBER_TTL = 2592000;

    /** 可分配给前台会员的角色 */
    const ROLES = ['subscriber', 'contributor', 'editor'];

    /** @var array 校验/操作错误信息 */
    protected static $errors = [];

    /** @var array|null 当前登录会员行缓存 (null=未解析, false 用单独标记) */
    protected static $resolved = null;
    protected static $hasResolved = false;

    /** @return \DB|null */
    public static function db()
    {
        return App::$db;
    }

    // ---------- 配置开关 ----------

    public static function cfg($key, $default = null)
    {
        $conf = App::$config;
        return isset($conf[$key]) && $conf[$key] !== '' ? $conf[$key] : $default;
    }

    /** 是否开放自助注册 */
    public static function registrationOpen()
    {
        return intval(self::cfg('article_member_status', 0)) === 1;
    }

    /** 注册是否需要邮箱验证 */
    public static function requireVerify()
    {
        return intval(self::cfg('article_member_verify', 0)) === 1;
    }

    /** 新注册默认角色 */
    public static function defaultRole()
    {
        $role = (string) self::cfg('article_member_role', 'subscriber');
        return in_array($role, ['subscriber', 'contributor'], true) ? $role : 'subscriber';
    }

    // ---------- 角色 / 能力 ----------

    /** 角色等级: 数值越大权限越高; 未知角色返回 0 */
    public static function roleLevel($role)
    {
        switch ((string) $role) {
            case 'editor':
                return 3;
            case 'contributor':
                return 2;
            case 'subscriber':
                return 1;
            default:
                return 0;
        }
    }

    /**
     * 统一能力判定 (针对前台会员角色; 管理员能力另由 /admin 承担)
     * @param string        $cap    comment / submit / publish_direct / moderate
     * @param array|false|null $member 会员行, null 表示取当前登录会员
     */
    public static function can($cap, $member = null)
    {
        if ($member === null) {
            $member = self::current();
        }
        if (!is_array($member) || empty($member['uid'])) {
            return false;
        }
        if (intval(isset($member['status']) ? $member['status'] : 0) !== 1) {
            return false;
        }
        $level = self::roleLevel(isset($member['role']) ? $member['role'] : 'subscriber');
        switch ($cap) {
            case 'comment':
                return $level >= 1;
            case 'submit':
                return $level >= 2;
            case 'publish_direct':
            case 'moderate':
                return $level >= 3;
            default:
                return false;
        }
    }

    /** 前台投稿功能是否开放(总开关) */
    public static function postOpen()
    {
        return intval(self::cfg('article_post_status', 0)) === 1;
    }

    // ---------- 邮件发送 (邮箱验证) ----------

    /** @var string 最近一次邮件发送错误 */
    protected static $mailError = '';

    public static function mailError()
    {
        return self::$mailError;
    }

    /** 是否已配置可用的 SMTP 发信通道 */
    public static function mailConfigured()
    {
        return intval(self::cfg('article_mail_status', 0)) === 1
            && trim((string) self::cfg('article_mail_host', '')) !== ''
            && trim((string) self::cfg('article_mail_from', '')) !== '';
    }

    /**
     * 通过配置的 SMTP 发送邮件
     * @return bool 成功返回 true; 失败时 self::mailError() 含原因
     */
    public static function sendMail($to, $subject, $html)
    {
        self::$mailError = '';
        if (!self::mailConfigured()) {
            self::$mailError = '邮件服务未启用或未配置';
            return false;
        }
        if (!class_exists('\Compat\Mailer')) {
            self::$mailError = '邮件组件未加载';
            return false;
        }
        $from = trim((string) self::cfg('article_mail_from', ''));
        $fromName = (string) self::cfg('article_mail_from_name', '');
        if ($fromName === '') {
            $siteName = (string) self::cfg('article_name', '');
            $fromName = $siteName !== '' ? $siteName : '站点通知';
        }
        $mailer = new \Compat\Mailer([
            'host'   => (string) self::cfg('article_mail_host', ''),
            'port'   => intval(self::cfg('article_mail_port', 465)),
            'secure' => (string) self::cfg('article_mail_secure', 'ssl'),
            'user'   => (string) self::cfg('article_mail_user', ''),
            'pass'   => (string) self::cfg('article_mail_pass', ''),
        ]);
        if ($mailer->send($from, $fromName, $to, $subject, $html)) {
            return true;
        }
        self::$mailError = $mailer->getError();
        return false;
    }

    /** 发送注册邮箱验证邮件 */
    public static function sendVerifyEmail($email, $link, $username = '')
    {
        $site = (string) self::cfg('article_name', '');
        $who = $username !== '' ? $username : $email;
        $subject = '请完成邮箱验证' . ($site !== '' ? ' - ' . $site : '');
        $safeLink = htmlspecialchars((string) $link, ENT_QUOTES, 'UTF-8');
        $safeWho = htmlspecialchars((string) $who, ENT_QUOTES, 'UTF-8');
        $html = '<div style="font-family:-apple-system,Segoe UI,Microsoft YaHei,Arial,sans-serif;line-height:1.8;color:#333">'
            . '<p>你好 <b>' . $safeWho . '</b>，</p>'
            . '<p>感谢注册，请点击下方按钮完成邮箱验证（验证后方可登录）：</p>'
            . '<p style="margin:24px 0"><a href="' . $safeLink . '" style="display:inline-block;padding:10px 22px;background:#2d8cf0;color:#fff;text-decoration:none;border-radius:4px">验证邮箱</a></p>'
            . '<p style="color:#888;font-size:13px">如按钮无法点击，请复制以下链接到浏览器打开：</p>'
            . '<p style="word-break:break-all;font-size:13px">' . $safeLink . '</p>'
            . '<p style="color:#aaa;font-size:12px;margin-top:24px">此邮件由系统自动发送，请勿回复。</p>'
            . '</div>';
        return self::sendMail($email, $subject, $html);
    }

    // ---------- 查询 ----------

    public static function findByUid($uid)
    {
        $uid = intval($uid);
        if ($uid <= 0 || !self::db()) {
            return null;
        }
        $row = self::db()->get_row("SELECT * FROM `lylme_member` WHERE `uid` = {$uid} LIMIT 1");
        return $row ?: null;
    }

    public static function findByUsername($username)
    {
        $username = trim((string) $username);
        if ($username === '' || !self::db()) {
            return null;
        }
        $esc = self::db()->escape($username);
        $row = self::db()->get_row("SELECT * FROM `lylme_member` WHERE `username` = '{$esc}' LIMIT 1");
        return $row ?: null;
    }

    public static function findByEmail($email)
    {
        $email = trim((string) $email);
        if ($email === '' || !self::db()) {
            return null;
        }
        $esc = self::db()->escape($email);
        $row = self::db()->get_row("SELECT * FROM `lylme_member` WHERE `email` = '{$esc}' LIMIT 1");
        return $row ?: null;
    }

    // ---------- 校验 ----------

    public static function errors()
    {
        return self::$errors;
    }

    public static function addError($msg)
    {
        self::$errors[] = $msg;
    }

    public static function resetErrors()
    {
        self::$errors = [];
    }

    /**
     * 校验注册数据, 返回布尔; 失败时填充 self::$errors
     */
    public static function validateRegistration($username, $email, $password, $nickname = '')
    {
        self::resetErrors();
        $username = trim((string) $username);
        $email = trim((string) $email);
        $nickname = trim((string) $nickname);

        if (!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]{3,30}$/u', $username)) {
            self::addError('用户名需为 3-30 位字母、数字、下划线或中文');
        } elseif (self::findByUsername($username)) {
            self::addError('该用户名已被注册');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::addError('邮箱格式不正确');
        } elseif (self::findByEmail($email)) {
            self::addError('该邮箱已被注册');
        }

        if (strlen((string) $password) < 6 || strlen((string) $password) > 64) {
            self::addError('密码长度需为 6-64 位');
        }

        if ($nickname !== '' && self::strLen($nickname) > 30) {
            self::addError('昵称过长(最多 30 字)');
        }

        return empty(self::$errors);
    }

    // ---------- 创建 / 认证 ----------

    /**
     * 创建会员; 成功返回 uid(int), 失败返回 false 并填充 errors
     */
    public static function create($username, $email, $password, $nickname = '', $url = '')
    {
        if (!self::db()) {
            return false;
        }
        if (!self::validateRegistration($username, $email, $password, $nickname)) {
            return false;
        }

        $username = trim((string) $username);
        $email = trim((string) $email);
        $nickname = trim(strip_tags((string) $nickname));
        if ($nickname === '') {
            $nickname = $username;
        }
        $url = self::sanitizeUrl($url);

        $insert = [
            'username'   => $username,
            'password'   => self::hashPassword($password),
            'nickname'   => $nickname,
            'email'      => $email,
            'url'        => $url,
            'role'       => self::defaultRole(),
            'status'     => self::requireVerify() ? 0 : 1,
            'reg_ip'     => self::getIp(),
            'reg_time'   => date('Y-m-d H:i:s'),
        ];

        // verify_token 单独处理 (非 insert 列时忽略)
        if (self::requireVerify()) {
            $insert['verify_token'] = self::randomToken();
        }

        $uid = self::db()->insert_array('lylme_member', $insert);
        return $uid ? intval($uid) : false;
    }

    /**
     * 认证: 成功返回会员行(且 status=1), 失败返回 false
     */
    public static function attempt($username, $password)
    {
        $member = self::findByUsername($username);
        if (!$member || !self::verifyPassword($password, $member['password'])) {
            return false;
        }
        if (intval($member['status']) !== 1) {
            return false;
        }
        return $member;
    }

    public static function hashPassword($pw)
    {
        return password_hash((string) $pw, PASSWORD_DEFAULT);
    }

    public static function verifyPassword($pw, $hash)
    {
        return $hash !== '' && password_verify((string) $pw, (string) $hash);
    }

    // ---------- 登录态 ----------

    /**
     * 建立登录态: 写 session; 可选写"记住我"cookie(DB 存令牌哈希)
     */
    public static function login($member, $remember = false)
    {
        if (!is_array($member) || empty($member['uid'])) {
            return false;
        }
        $uid = intval($member['uid']);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION[self::SESSION_KEY] = $uid;

        if ($remember) {
            $raw = self::randomToken();
            $hash = hash('sha256', $raw);
            $exp = time() + self::REMEMBER_TTL;
            self::db()->query("UPDATE `lylme_member` SET `token` = '" . self::db()->escape($hash) . "', `token_exp` = {$exp} WHERE `uid` = {$uid}");
            self::setCookie($uid . ':' . $raw, $exp);
        }

        // 重新解析
        self::$hasResolved = false;
        self::$resolved = null;

        // 更新最后登录时间
        self::db()->query("UPDATE `lylme_member` SET `last_login` = '" . date('Y-m-d H:i:s') . "' WHERE `uid` = {$uid}");
        return true;
    }

    /** 清除登录态 (撤销记住我令牌) */
    public static function logout()
    {
        $member = self::current();
        if ($member && !empty($member['uid'])) {
            $uid = intval($member['uid']);
            self::db()->query("UPDATE `lylme_member` SET `token` = '', `token_exp` = 0 WHERE `uid` = {$uid}");
        }
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);
        self::setCookie('', time() - 3600);

        self::$hasResolved = false;
        self::$resolved = null;
    }

    /** 解析当前登录会员(带请求级缓存) */
    public static function current()
    {
        if (self::$hasResolved) {
            return self::$resolved;
        }
        self::$hasResolved = true;
        self::$resolved = false;

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // 1) session
        $uid = isset($_SESSION[self::SESSION_KEY]) ? intval($_SESSION[self::SESSION_KEY]) : 0;
        if ($uid > 0) {
            $member = self::findByUid($uid);
            if ($member && intval($member['status']) === 1) {
                self::$resolved = $member;
                return $member;
            }
        }

        // 2) 记住我 cookie: "uid:rawtoken"
        if (isset($_COOKIE[self::COOKIE]) && $_COOKIE[self::COOKIE] !== '') {
            $parts = explode(':', (string) $_COOKIE[self::COOKIE], 2);
            if (count($parts) === 2) {
                $cid = intval($parts[0]);
                $raw = $parts[1];
                $member = self::findByUid($cid);
                if (
                    $member
                    && intval($member['status']) === 1
                    && $member['token'] !== ''
                    && intval($member['token_exp']) > time()
                    && hash_equals((string) $member['token'], hash('sha256', $raw))
                ) {
                    $_SESSION[self::SESSION_KEY] = $cid;
                    self::$resolved = $member;
                    return $member;
                }
            }
        }

        return false;
    }

    public static function isLoggedIn()
    {
        return self::current() !== false && self::current() !== null;
    }

    /**
     * 导航后台管理员是否已登录 (复用 admin_token cookie + authcode 校验)
     */
    public static function isAdminLoggedIn()
    {
        if (empty($_COOKIE['admin_token'])) {
            return false;
        }
        if (!function_exists('authcode')) {
            return false;
        }
        $conf = App::$config;
        $adminUser = isset($conf['admin_user']) ? $conf['admin_user'] : '';
        $adminPwd = isset($conf['admin_pwd']) ? $conf['admin_pwd'] : '';
        if ($adminUser === '' || $adminPwd === '') {
            return false;
        }
        $tokenRaw = (string) $_COOKIE['admin_token'];
        $decoded = authcode($tokenRaw, 'DECODE', defined('SYS_KEY') ? SYS_KEY : '');
        if ($decoded === '' || strpos($decoded, "\t") === false) {
            return false;
        }
        $parts = explode("\t", $decoded);
        $sid = isset($parts[1]) ? $parts[1] : '';
        return $sid !== '' && hash_equals(md5($adminUser . $adminPwd), $sid);
    }

    // ---------- 工具 ----------

    public static function randomToken()
    {
        return bin2hex(random_bytes(16));
    }

    public static function getIp()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $k) {
            if (!empty($_SERVER[$k])) {
                $candidate = trim(explode(',', (string) $_SERVER[$k])[0]);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    $ip = $candidate;
                    break;
                }
            }
        }
        return substr($ip, 0, 64);
    }

    /** 网址净化: 仅允许 http/https, 补协议, 非法置空 */
    public static function sanitizeUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'http://' . $url;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $url)) {
            return '';
        }
        return substr($url, 0, 255);
    }

    public static function isHttps()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }
        return false;
    }

    /** 安全 cookie 写入 (options 数组, 兼容 PHP<7.3 回退) */
    public static function setCookie($value, $expires)
    {
        if (headers_sent()) {
            return false;
        }
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            return setcookie(self::COOKIE, $value, [
                'expires'  => $expires,
                'path'     => '/',
                'secure'   => self::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return setcookie(self::COOKIE, $value, $expires, '/');
    }

    public static function strLen($str)
    {
        return function_exists('mb_strlen') ? mb_strlen((string) $str, 'UTF-8') : strlen((string) $str);
    }

    public static function strCut($str, $max)
    {
        $str = (string) $str;
        $max = (int) $max;
        return function_exists('mb_substr') ? mb_substr($str, 0, $max, 'UTF-8') : substr($str, 0, $max);
    }

    // ---------- CSRF ----------

    public static function csrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['lylme_member_csrf'])) {
            $_SESSION['lylme_member_csrf'] = self::randomToken();
        }
        return $_SESSION['lylme_member_csrf'];
    }

    public static function checkCsrf($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return !empty($_SESSION['lylme_member_csrf'])
            && is_string($token)
            && hash_equals((string) $_SESSION['lylme_member_csrf'], $token);
    }

    /** 更新资料(昵称/邮箱/URL/密码可选) */
    public static function updateProfile($uid, $fields)
    {
        $uid = intval($uid);
        if ($uid <= 0 || !self::db()) {
            return false;
        }
        $allowed = ['nickname', 'email', 'url', 'password'];
        $sets = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) {
                continue;
            }
            if ($k === 'password') {
                if (strlen((string) $v) < 6 || strlen((string) $v) > 64) {
                    self::addError('新密码长度需为 6-64 位');
                    return false;
                }
                $v = self::hashPassword($v);
            } elseif ($k === 'url') {
                $v = self::sanitizeUrl($v);
            } elseif ($k === 'email') {
                $v = trim((string) $v);
                if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    self::addError('邮箱格式不正确');
                    return false;
                }
                $other = self::findByEmail($v);
                if ($other && intval($other['uid']) !== $uid) {
                    self::addError('该邮箱已被使用');
                    return false;
                }
            } elseif ($k === 'nickname') {
                $v = trim(strip_tags((string) $v));
                if ($v === '') {
                    continue;
                }
                if (self::strLen($v) > 30) {
                    $v = self::strCut($v, 30);
                }
            }
            $sets[] = "`{$k}` = '" . self::db()->escape($v) . "'";
        }
        if (empty($sets)) {
            return true;
        }
        self::db()->query("UPDATE `lylme_member` SET " . implode(', ', $sets) . " WHERE `uid` = {$uid}");
        self::$hasResolved = false;
        self::$resolved = null;
        return true;
    }
}

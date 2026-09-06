<?php
/**
 * Typecho 兼容层 - 自包含 SMTP 邮件客户端 (方案 C 邮箱验证发信)
 *
 * 说明:
 *   本站无 composer/vendor, 沿用项目"自包含实现"风格, 不引入 PHPMailer。
 *   仅实现会员验证邮件所需的可靠子集: ESMTP + AUTH LOGIN + ssl(隐式)/tls(STARTTLS)/none。
 *   正文统一 base64 编码(Content-Transfer-Encoding: base64), 规避点 stuffing 与裸换行透传问题。
 *
 * 用法:
 *   $m = new \Compat\Mailer(['host'=>..,'port'=>465,'secure'=>'ssl','user'=>..,'pass'=>..]);
 *   if (!$m->send($from, $fromName, $to, $subject, $html)) { $err = $m->getError(); }
 */
namespace Compat;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Mailer
{
    /** @var string SMTP 主机 */
    protected $host = '';
    /** @var int 端口 */
    protected $port = 25;
    /** @var string 加密方式: ssl|tls|none */
    protected $secure = 'none';
    /** @var string 用户名 */
    protected $user = '';
    /** @var string 密码 */
    protected $pass = '';
    /** @var int 连接/读写超时(秒) */
    protected $timeout = 15;

    /** @var resource|null socket 句柄 */
    protected $sock = null;
    /** @var string 最后一条错误信息 */
    protected $error = '';
    /** @var array 服务器 EHLO 能力 */
    protected $caps = [];

    /**
     * @param array $config host/port/secure/user/pass/timeout
     */
    public function __construct($config = [])
    {
        $this->host    = isset($config['host']) ? trim((string) $config['host']) : '';
        $this->port    = isset($config['port']) ? intval($config['port']) : 25;
        $this->secure  = isset($config['secure']) ? strtolower(trim((string) $config['secure'])) : 'none';
        $this->user    = isset($config['user']) ? (string) $config['user'] : '';
        $this->pass    = isset($config['pass']) ? (string) $config['pass'] : '';
        $this->timeout = isset($config['timeout']) ? max(3, intval($config['timeout'])) : 15;
        if ($this->port <= 0) {
            $this->port = $this->secure === 'ssl' ? 465 : 25;
        }
        if (!in_array($this->secure, ['ssl', 'tls', 'none'], true)) {
            $this->secure = 'none';
        }
    }

    /** 取最后一条错误信息 */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 发送邮件
     * @param string $from    发件地址(信封 + From 头)
     * @param string $fromName 发件显示名
     * @param string|array $to 收件地址(字符串或数组)
     * @param string $subject  主题(支持 UTF-8)
     * @param string $html     HTML 正文
     * @return bool
     */
    public function send($from, $fromName, $to, $subject, $html)
    {
        $this->error = '';
        $from = trim((string) $from);
        if ($this->host === '' || $from === '') {
            $this->error = 'SMTP 主机或发件地址未配置';
            return false;
        }
        $recipients = array_values(array_filter(array_map(function ($x) {
            return trim((string) $x);
        }, (array) $to), function ($x) {
            return $x !== '' && filter_var($x, FILTER_VALIDATE_EMAIL);
        }));
        if (empty($recipients)) {
            $this->error = '收件邮箱地址无效';
            return false;
        }

        if (!$this->connect()) {
            return false;
        }
        try {
            if (!$this->handshake()) {
                return false;
            }
            if (!$this->login()) {
                return false;
            }
            if (!$this->cmd('MAIL FROM:<' . $from . '>', [250])) {
                return false;
            }
            foreach ($recipients as $rcpt) {
                if (!$this->cmd('RCPT TO:<' . $rcpt . '>', [250, 251])) {
                    return false;
                }
            }
            if (!$this->cmd('DATA', [354])) {
                return false;
            }
            $message = $this->buildMessage($from, $fromName, $recipients, $subject, $html);
            // 正文已 base64 编码, 不会出现以 . 开头的行, 无需额外点转义
            if (!$this->writeRaw($message . "\r\n.\r\n")) {
                return false;
            }
            $code = $this->readCode();
            if (!in_array($code, [250, 251], true)) {
                $this->error = '服务器拒绝邮件 (响应 ' . $code . ')';
                return false;
            }
            $this->writeRaw("QUIT\r\n");
            return true;
        } finally {
            $this->close();
        }
    }

    /** 建立 socket 连接 */
    protected function connect()
    {
        $remote = ($this->secure === 'ssl' ? 'ssl://' : 'tcp://') . $this->host . ':' . $this->port;
        $ctx = stream_context_create([
            'ssl' => [
                // 与多数邮件服务商对接: 校验对端但宽松, 避免因 SNI/CA 缺失直接失败
                'verify_peer'      => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'SNI_ENABLED'      => true,
                'crypto_method'    => STREAM_CRYPTO_METHOD_TLS_CLIENT,
            ],
        ]);
        $errno = 0;
        $errstr = '';
        $this->sock = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $ctx
        );
        if (!$this->sock) {
            $this->error = '无法连接 SMTP 服务器: ' . ($errstr !== '' ? $errstr : 'errno ' . $errno);
            return false;
        }
        stream_set_timeout($this->sock, $this->timeout);
        $greet = $this->readCode();
        if ($greet !== 220) {
            $this->error = 'SMTP 握手失败 (greeting ' . $greet . ')';
            return false;
        }
        return true;
    }

    /** EHLO/STARTTLS 协商 */
    protected function handshake()
    {
        $name = function_exists('php_uname') ? php_uname('n') : 'localhost';
        if ($name === '' || strpos($name, ' ') !== false) {
            $name = 'localhost';
        }
        if (!$this->cmd('EHLO ' . $name, [250], true)) {
            // EHLO 失败退回 HELO (HELO 无能力列表)
            if (!$this->cmd('HELO ' . $name, [250])) {
                return false;
            }
            $this->caps = [];
        }
        if ($this->secure === 'tls') {
            if (!$this->cmd('STARTTLS', [220])) {
                $this->error = 'STARTTLS 不被支持或失败';
                return false;
            }
            $ok = @stream_socket_enable_crypto(
                $this->sock,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );
            if (!$ok) {
                $this->error = 'TLS 协商失败';
                return false;
            }
            // STARTTLS 后需重新 EHLO
            $this->caps = [];
            $this->cmd('EHLO ' . $name, [250], true);
        }
        return true;
    }

    /** AUTH LOGIN */
    protected function login()
    {
        if ($this->user === '') {
            return true; // 无需鉴权(内网 relay 等)
        }
        if (!$this->cmd('AUTH LOGIN', [334, 235])) {
            return false;
        }
        if (!$this->cmd(base64_encode($this->user), [334, 235])) {
            return false;
        }
        if (!$this->cmd(base64_encode($this->pass), [235])) {
            $this->error = 'SMTP 认证失败(用户名或密码错误)';
            return false;
        }
        return true;
    }

    /** 组装 MIME 邮件(正文 base64) */
    protected function buildMessage($from, $fromName, $recipients, $subject, $html)
    {
        $domain = $this->host !== '' ? $this->host : 'localhost';
        $fromHeader = ($fromName !== '' && $fromName !== null)
            ? $this->encodeMimeHeader($fromName) . ' <' . $from . '>'
            : $from;
        $toHeader = implode(', ', $recipients);
        $headers = [
            'Date: ' . @date('r'),
            'From: ' . $fromHeader,
            'To: ' . $toHeader,
            'Message-ID: <' . $this->makeMessageId($domain) . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset="UTF-8"',
            'Content-Transfer-Encoding: base64',
            'Subject: ' . $this->encodeMimeHeader((string) $subject),
            'X-Mailer: LyLme-Spage-SMTP',
        ];
        $body = chunk_split(base64_encode((string) $html), 76, "\r\n");
        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    /** UTF-8 头编码为 encoded-word */
    protected function encodeMimeHeader($value)
    {
        $value = (string) $value;
        // 纯 ASCII 直接返回(去除换行防头注入)
        if (!preg_match('/[^\x20-\x7E]/', $value)) {
            return str_replace(["\r", "\n"], '', $value);
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    protected function makeMessageId($domain)
    {
        $domain = preg_replace('/[^\w\.\-]/', '', $domain);
        return uniqid('', true) . '.' . dechex(crc32($domain)) . '@' . ($domain !== '' ? $domain : 'localhost');
    }

    /** 发送命令并校验响应码 */
    protected function cmd($line, $expect = [250], $collectCaps = false)
    {
        if (!$this->writeRaw($line . "\r\n")) {
            return false;
        }
        list($code, $text) = $this->readResponse();
        if (!in_array($code, $expect, true)) {
            $this->error = '命令 "' . strtok($line, ' ') . '" 失败 (响应 ' . $code . ')';
            return false;
        }
        if ($collectCaps) {
            $this->caps = $text;
        }
        return true;
    }

    /** 写原始数据(处理部分写) */
    protected function writeRaw($data)
    {
        $len = strlen($data);
        $written = 0;
        while ($written < $len) {
            $n = @fwrite($this->sock, substr($data, $written));
            if ($n === false || $n === 0) {
                $meta = stream_get_meta_data($this->sock);
                if (!empty($meta['timed_out'])) {
                    $this->error = '写入超时';
                } else {
                    $this->error = '写入失败(连接中断)';
                }
                return false;
            }
            $written += $n;
        }
        return true;
    }

    /** 读取多行响应, 返回 [code, lines[]] */
    protected function readResponse()
    {
        $code = 0;
        $lines = [];
        while ($raw = @fgets($this->sock, 1024)) {
            $raw = rtrim($raw, "\r\n");
            if (strlen($raw) < 3) {
                continue;
            }
            $code = (int) substr($raw, 0, 3);
            $sep = substr($raw, 3, 1);
            $lines[] = substr($raw, 4);
            if ($sep !== '-') {
                break; // 最后一行(第4字符为空格)
            }
        }
        return [$code, $lines];
    }

    /** 仅取响应码 */
    protected function readCode()
    {
        list($code) = $this->readResponse();
        return $code;
    }

    protected function close()
    {
        if (is_resource($this->sock)) {
            @fclose($this->sock);
        }
        $this->sock = null;
    }
}

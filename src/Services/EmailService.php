<?php
declare(strict_types=1);

namespace Services;

use Core\Config;
use Models\User;

/**
 * 邮件发送服务
 */
class EmailService
{
    /**
     * 发送邮箱验证邮件
     */
    public function sendVerificationEmail(User $user, string $token): void
    {
        $verifyUrl = base_url('verify?token=' . urlencode($token));
        $subject = '【子域分发管理平台】邮箱验证';
        $body = <<<HTML
<p>您好 {$user->username}，</p>
<p>感谢注册子域分发管理平台，请点击以下链接完成邮箱验证：</p>
<p><a href="{$verifyUrl}">{$verifyUrl}</a></p>
<p>如果无法点击，请复制链接到浏览器打开。</p>
HTML;
        $this->send($user->email, $subject, $body);
    }

    /**
     * 统一邮件发送入口
     */
    public function send(string $to, string $subject, string $htmlMessage): void
    {
        $config = Config::getInstance()->get('mail');
        $fromAddress = $config['from_address'] ?? 'no-reply@example.com';
        $fromName = $config['from_name'] ?? '系统通知';

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $fromName . ' <' . $fromAddress . '>';

        if (($config['transport'] ?? 'mail') === 'mail') {
            @mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
        }

        $this->logEmail($to, $subject, $htmlMessage);
    }

    /**
     * 将邮件记录到日志文件，方便开发调试
     */
    private function logEmail(string $to, string $subject, string $body): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $message = sprintf(
            "[%s] To: %s\nSubject: %s\nBody:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            strip_tags($body)
        );

        file_put_contents($logDir . '/emails.log', $message, FILE_APPEND);
    }
}

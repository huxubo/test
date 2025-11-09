<?php
declare(strict_types=1);

namespace Services;

use Core\Config;
use Models\User;
use RuntimeException;
use Services\Mail\SmtpMailer;

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
        if (!is_array($config)) {
            $config = [];
        }

        $fromAddress = (string)($config['from_address'] ?? 'no-reply@example.com');
        $fromName = (string)($config['from_name'] ?? '系统通知');

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromAddress . '>',
        ];

        $transport = strtolower((string)($config['transport'] ?? 'mail'));
        $success = false;
        $errorMessage = null;

        if ($transport === 'smtp') {
            $smtpConfig = $config['smtp'] ?? [];
            if (!is_array($smtpConfig)) {
                $smtpConfig = [];
            }

            try {
                $mailer = new SmtpMailer($smtpConfig);
                $mailer->send($fromAddress, $fromName, $to, $subject, $htmlMessage, $headers);
                $success = true;
            } catch (\Throwable $e) {
                $errorMessage = $e->getMessage();
            }
        } elseif ($transport === 'mail') {
            if (!function_exists('mail')) {
                $errorMessage = 'mail() 函数不可用';
            } else {
                $success = mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
                if (!$success) {
                    $errorMessage = 'mail() 调用失败';
                }
            }
        } else {
            $errorMessage = '不支持的邮件发送方式: ' . $transport;
        }

        $this->logEmail($to, $subject, $htmlMessage, $success, $errorMessage);

        if (!$success) {
            throw new RuntimeException('邮件发送失败，请稍后重试');
        }
    }

    /**
     * 将邮件记录到日志文件，方便开发调试
     */
    private function logEmail(string $to, string $subject, string $body, bool $success, ?string $errorMessage = null): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $statusText = $success ? 'SENT' : 'FAILED';
        $errorDetails = '';
        if ($errorMessage !== null && $errorMessage !== '') {
            $errorDetails = ' (' . str_replace(["\r", "\n"], ' ', $errorMessage) . ')';
        }

        $message = sprintf(
            "[%s] To: %s\nSubject: %s\nStatus: %s%s\nBody:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $statusText,
            $errorDetails,
            strip_tags($body)
        );

        file_put_contents($logDir . '/emails.log', $message, FILE_APPEND);
    }
}

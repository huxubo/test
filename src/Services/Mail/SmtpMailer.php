<?php
declare(strict_types=1);

namespace Services\Mail;

use RuntimeException;

/**
 * 简易 SMTP 邮件发送器
 */
class SmtpMailer
{
    /** @var resource|null */
    private $connection = null;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @param array<int,string> $rawHeaders
     */
    public function send(string $fromAddress, string $fromName, string $to, string $subject, string $htmlBody, array $rawHeaders = []): void
    {
        $host = trim((string)($this->config['host'] ?? ''));
        if ($host === '') {
            throw new RuntimeException('SMTP 服务器地址未配置');
        }

        $port = (int)($this->config['port'] ?? 587);
        if ($port <= 0) {
            $port = 587;
        }

        $encryption = strtolower((string)($this->config['encryption'] ?? 'tls'));
        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $connection = @stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT);
        if (!is_resource($connection)) {
            throw new RuntimeException('无法连接 SMTP 服务器: ' . $errstr);
        }

        stream_set_timeout($connection, 30);
        $this->connection = $connection;

        try {
            $this->expect([220]);
            $hostname = gethostname() ?: 'localhost';
            $this->sendCommand('EHLO ' . $hostname, [250]);

            if ($encryption === 'tls') {
                $this->sendCommand('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('无法建立 TLS 连接');
                }
                $this->sendCommand('EHLO ' . $hostname, [250]);
            }

            $username = (string)($this->config['username'] ?? '');
            $password = (string)($this->config['password'] ?? '');
            if ($username !== '' || $password !== '') {
                if ($username === '' || $password === '') {
                    throw new RuntimeException('SMTP 账号或密码配置不完整');
                }
                $this->sendCommand('AUTH LOGIN', [334]);
                $this->sendCommand(base64_encode($username), [334]);
                $this->sendCommand(base64_encode($password), [235]);
            }

            $this->sendCommand('MAIL FROM:<' . $fromAddress . '>', [250]);
            $this->sendCommand('RCPT TO:<' . $to . '>', [250, 251]);
            $this->sendCommand('DATA', [354]);

            $message = $this->buildMessage($fromAddress, $fromName, $to, $subject, $htmlBody, $rawHeaders, $hostname);
            $this->write($message . "\r\n.\r\n");
            $this->expect([250]);

            $this->sendCommand('QUIT', [221]);
        } catch (\Throwable $e) {
            if (is_resource($this->connection)) {
                fclose($this->connection);
            }
            $this->connection = null;
            throw $e;
        }

        fclose($connection);
        $this->connection = null;
    }

    /**
     * @param array<int,string> $rawHeaders
     * @return array<string,array{name:string,value:string}>
     */
    private function normalizeHeaders(array $rawHeaders): array
    {
        $headers = [];
        foreach ($rawHeaders as $header) {
            if (!is_string($header)) {
                continue;
            }
            $parts = explode(':', $header, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if ($name === '') {
                continue;
            }
            $key = strtolower($name);
            $headers[$key] = [
                'name' => $this->formatHeaderName($name),
                'value' => $value,
            ];
        }

        return $headers;
    }

    /**
     * @param array<string,array{name:string,value:string}> $headers
     */
    private function ensureHeader(array &$headers, string $name, string $value): void
    {
        $key = strtolower($name);
        if (!isset($headers[$key]) || $headers[$key]['value'] === '') {
            $headers[$key] = [
                'name' => $this->formatHeaderName($name),
                'value' => $value,
            ];
        }
    }

    private function buildMessage(string $fromAddress, string $fromName, string $to, string $subject, string $body, array $rawHeaders, string $hostname): string
    {
        $headers = $this->normalizeHeaders($rawHeaders);

        $this->ensureHeader($headers, 'From', sprintf('%s <%s>', $fromName, $fromAddress));
        $this->ensureHeader($headers, 'To', $to);
        $this->ensureHeader($headers, 'Subject', $subject);
        $this->ensureHeader($headers, 'Date', date(DATE_RFC2822));
        $this->ensureHeader($headers, 'Message-ID', sprintf('<%s@%s>', bin2hex(random_bytes(16)), $hostname));
        $this->ensureHeader($headers, 'MIME-Version', '1.0');
        $this->ensureHeader($headers, 'Content-Type', 'text/html; charset=UTF-8');

        $lines = [];
        foreach ($headers as $header) {
            $lines[] = $header['name'] . ': ' . $header['value'];
        }

        $normalizedBody = $this->normalizeBody($body);

        return implode("\r\n", $lines) . "\r\n\r\n" . $normalizedBody;
    }

    private function formatHeaderName(string $name): string
    {
        $segments = explode('-', $name);
        $segments = array_map(static fn(string $segment): string => ucfirst(strtolower($segment)), $segments);
        return implode('-', $segments);
    }

    private function normalizeBody(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = str_replace("\n", "\r\n", $body);
        return (string)preg_replace('/^\./m', '..', $body);
    }

    /**
     * @param array<int,int> $expectedCodes
     */
    private function sendCommand(string $command, array $expectedCodes): void
    {
        $this->write($command . "\r\n");
        $this->expect($expectedCodes);
    }

    /**
     * @param array<int,int> $codes
     */
    private function expect(array $codes): void
    {
        if (!is_resource($this->connection)) {
            throw new RuntimeException('SMTP 连接尚未建立');
        }

        $response = '';
        while (($line = fgets($this->connection, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^\d{3} /', $line) === 1) {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('SMTP 服务器无响应');
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('SMTP 错误：' . trim($response));
        }
    }

    private function write(string $data): void
    {
        if (!is_resource($this->connection)) {
            throw new RuntimeException('SMTP 连接尚未建立');
        }
        $result = fwrite($this->connection, $data);
        if ($result === false) {
            throw new RuntimeException('写入 SMTP 数据失败');
        }
    }
}

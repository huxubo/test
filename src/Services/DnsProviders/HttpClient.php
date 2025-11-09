<?php
declare(strict_types=1);

namespace Services\DnsProviders;

use RuntimeException;

/**
 * 简易 HTTP 客户端，封装 cURL 调用
 */
class HttpClient
{
    /**
     * 发起请求
     * @param string $method
     * @param string $url
     * @param array<string,string> $headers
     * @param array<string,mixed>|string|null $body
     * @return array{status:int,body:string}
     */
    public function request(string $method, string $url, array $headers = [], array|string|null $body = null): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($body !== null) {
            if (is_array($body)) {
                $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
                $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
            } else {
                $payload = $body;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        if (!empty($headers)) {
            $formattedHeaders = [];
            foreach ($headers as $key => $value) {
                $formattedHeaders[] = $key . ': ' . $value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('HTTP 请求失败: ' . $error);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'body' => $response];
    }
}

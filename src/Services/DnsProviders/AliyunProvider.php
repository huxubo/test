<?php
declare(strict_types=1);

namespace Services\DnsProviders;

use Models\DomainProvider;
use Models\PrimaryDomain;
use RuntimeException;

/**
 * 阿里云 DNS API 适配器
 */
class AliyunProvider implements DnsProviderInterface
{
    private const ENDPOINT = 'https://alidns.aliyuncs.com/';

    public function __construct(private readonly DomainProvider $config, private readonly HttpClient $http)
    {
    }

    public function subdomainExists(PrimaryDomain $domain, string $label): bool
    {
        $result = $this->request([
            'Action' => 'DescribeSubDomainRecords',
            'SubDomain' => $label . '.' . $domain->domain_name,
        ]);

        return (int)($result['TotalCount'] ?? 0) > 0;
    }

    public function upsertNsRecords(PrimaryDomain $domain, string $label, array $nsRecords): bool
    {
        $subDomain = $label . '.' . $domain->domain_name;
        $existing = $this->request([
            'Action' => 'DescribeSubDomainRecords',
            'SubDomain' => $subDomain,
        ]);

        if (($existing['TotalCount'] ?? 0) > 0) {
            $this->request([
                'Action' => 'DeleteSubDomainRecords',
                'DomainName' => $domain->domain_name,
                'RR' => $label,
                'Type' => 'NS',
            ]);
        }

        foreach ($nsRecords as $ns) {
            $this->request([
                'Action' => 'AddDomainRecord',
                'DomainName' => $domain->domain_name,
                'RR' => $label,
                'Type' => 'NS',
                'Value' => $ns,
                'TTL' => 600,
            ]);
        }

        return true;
    }

    /**
     * @param array<string,string|int> $params
     * @return array<string,mixed>
     */
    private function request(array $params): array
    {
        $accessKeyId = $this->config->api_key;
        $accessSecret = $this->config->api_secret;
        if (!$accessKeyId || !$accessSecret) {
            throw new RuntimeException('阿里云提供商必须配置 API Key 与 Secret');
        }

        $common = [
            'Format' => 'JSON',
            'Version' => '2015-01-09',
            'AccessKeyId' => $accessKeyId,
            'SignatureMethod' => 'HMAC-SHA1',
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'SignatureVersion' => '1.0',
            'SignatureNonce' => uniqid('', true),
        ];

        $params = array_merge($common, $params);
        ksort($params);

        $query = [];
        foreach ($params as $key => $value) {
            $query[] = $this->percentEncode($key) . '=' . $this->percentEncode((string)$value);
        }
        $queryString = implode('&', $query);
        $stringToSign = 'GET&%2F&' . $this->percentEncode($queryString);
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessSecret . '&', true));
        $finalUrl = self::ENDPOINT . '?' . $queryString . '&Signature=' . $this->percentEncode($signature);

        $response = $this->http->request('GET', $finalUrl);
        $data = json_decode($response['body'], true);
        if (!is_array($data)) {
            throw new RuntimeException('阿里云返回数据异常');
        }
        if (isset($data['Code']) && $data['Code'] !== 'OK') {
            throw new RuntimeException('阿里云 API 错误：' . ($data['Message'] ?? '未知错误'));
        }
        return $data;
    }

    private function percentEncode(string $value): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], rawurlencode($value));
    }
}

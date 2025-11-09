<?php
declare(strict_types=1);

namespace Services\DnsProviders;

use Models\DomainProvider;
use Models\PrimaryDomain;
use RuntimeException;

/**
 * Cloudflare API 适配器
 */
class CloudflareProvider implements DnsProviderInterface
{
    public function __construct(private readonly DomainProvider $config, private readonly HttpClient $http)
    {
    }

    public function subdomainExists(PrimaryDomain $domain, string $label): bool
    {
        $zoneId = $this->getZoneId($domain);
        $recordName = $label . '.' . $domain->domain_name;
        $url = sprintf('https://api.cloudflare.com/client/v4/zones/%s/dns_records?type=NS&name=%s', $zoneId, urlencode($recordName));
        $response = $this->request('GET', $url);
        $data = json_decode($response['body'], true);
        if (!($data['success'] ?? false)) {
            return false;
        }

        return !empty($data['result']);
    }

    public function upsertNsRecords(PrimaryDomain $domain, string $label, array $nsRecords): bool
    {
        $zoneId = $this->getZoneId($domain);
        $recordName = $label . '.' . $domain->domain_name;
        $url = sprintf('https://api.cloudflare.com/client/v4/zones/%s/dns_records?type=NS&name=%s', $zoneId, urlencode($recordName));
        $response = $this->request('GET', $url);
        $data = json_decode($response['body'], true);

        if (!($data['success'] ?? false)) {
            throw new RuntimeException('Cloudflare 查询失败：' . ($data['errors'][0]['message'] ?? '未知错误'));
        }

        if (!empty($data['result'])) {
            foreach ($data['result'] as $record) {
                $this->request('DELETE', sprintf('https://api.cloudflare.com/client/v4/zones/%s/dns_records/%s', $zoneId, $record['id']));
            }
        }

        foreach ($nsRecords as $ns) {
            $payload = [
                'type' => 'NS',
                'name' => $recordName,
                'content' => $ns,
                'ttl' => 3600,
            ];
            $this->request('POST', sprintf('https://api.cloudflare.com/client/v4/zones/%s/dns_records', $zoneId), $payload);
        }

        return true;
    }

    private function request(string $method, string $url, ?array $body = null): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . ($this->config->api_key ?? ''),
            'Content-Type' => 'application/json',
        ];

        return $this->http->request($method, $url, $headers, $body);
    }

    private function getZoneId(PrimaryDomain $domain): string
    {
        if (empty($domain->provider_reference)) {
            throw new RuntimeException('Cloudflare 需要在主域名的 provider_reference 中填写 Zone ID');
        }

        return $domain->provider_reference;
    }
}

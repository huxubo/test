<?php
declare(strict_types=1);

namespace Services\DnsProviders;

use Models\DomainProvider;
use Models\PrimaryDomain;
use RuntimeException;

/**
 * PowerDNS API 适配器
 */
class PowerDnsProvider implements DnsProviderInterface
{
    public function __construct(private readonly DomainProvider $config, private readonly HttpClient $http)
    {
    }

    public function subdomainExists(PrimaryDomain $domain, string $label): bool
    {
        $zoneName = $this->zoneName($domain);
        $recordName = $this->recordName($domain, $label);
        $url = sprintf('%s/servers/%s/zones/%s', rtrim($this->baseUrl(), '/'), rawurlencode($this->serverId()), rawurlencode($zoneName));
        $response = $this->http->request('GET', $url, $this->headers());
        if ($response['status'] !== 200) {
            return false;
        }

        $data = json_decode($response['body'], true);
        if (!is_array($data['rrsets'] ?? null)) {
            return false;
        }

        foreach ($data['rrsets'] as $rrset) {
            if (($rrset['name'] ?? '') === $recordName && ($rrset['type'] ?? '') === 'NS') {
                return true;
            }
        }

        return false;
    }

    public function upsertNsRecords(PrimaryDomain $domain, string $label, array $nsRecords): bool
    {
        $zoneName = $this->zoneName($domain);
        $recordName = $this->recordName($domain, $label);
        $url = sprintf('%s/servers/%s/zones/%s', rtrim($this->baseUrl(), '/'), rawurlencode($this->serverId()), rawurlencode($zoneName));
        $records = array_map(
            static function (string $ns): array {
                $normalized = rtrim(trim($ns), '.') . '.';
                return [
                    'content' => $normalized,
                    'disabled' => false,
                ];
            },
            $nsRecords
        );

        $rrset = [
            'name' => $recordName,
            'type' => 'NS',
            'ttl' => 3600,
            'changetype' => empty($records) ? 'DELETE' : 'REPLACE',
        ];

        if (!empty($records)) {
            $rrset['records'] = $records;
        }

        $payload = [
            'rrsets' => [$rrset],
        ];

        $response = $this->http->request('PATCH', $url, $this->headers(), $payload);
        return in_array($response['status'], [200, 204], true);
    }

    private function serverId(): string
    {
        $extra = $this->config->extraParams();
        if (empty($extra['server_id'])) {
            throw new RuntimeException('PowerDNS 需要在提供商配置中设置 server_id');
        }
        return (string)$extra['server_id'];
    }

    private function baseUrl(): string
    {
        $extra = $this->config->extraParams();
        if (empty($extra['base_url'])) {
            throw new RuntimeException('PowerDNS 需要在提供商配置中设置 base_url');
        }
        return (string)$extra['base_url'];
    }

    private function headers(): array
    {
        return [
            'X-API-Key' => (string)$this->config->api_key,
            'Content-Type' => 'application/json',
        ];
    }

    private function zoneName(PrimaryDomain $domain): string
    {
        return rtrim($domain->domain_name, '.') . '.';
    }

    private function recordName(PrimaryDomain $domain, string $label): string
    {
        return rtrim($label . '.' . $domain->domain_name, '.') . '.';
    }
}

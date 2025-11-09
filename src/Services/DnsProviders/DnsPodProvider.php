<?php
declare(strict_types=1);

namespace Services\DnsProviders;

use Models\DomainProvider;
use Models\PrimaryDomain;
use RuntimeException;

/**
 * DNSPod API 适配器
 */
class DnsPodProvider implements DnsProviderInterface
{
    private const ENDPOINT = 'https://dnsapi.cn/';

    public function __construct(private readonly DomainProvider $config, private readonly HttpClient $http)
    {
    }

    public function subdomainExists(PrimaryDomain $domain, string $label): bool
    {
        $result = $this->request('Record.List', [
            'domain' => $domain->domain_name,
            'sub_domain' => $label,
            'record_type' => 'NS',
        ]);

        if (($result['status']['code'] ?? '') !== '1') {
            return false;
        }

        return (int)($result['info']['record_total'] ?? 0) > 0;
    }

    public function upsertNsRecords(PrimaryDomain $domain, string $label, array $nsRecords): bool
    {
        $existing = $this->request('Record.List', [
            'domain' => $domain->domain_name,
            'sub_domain' => $label,
            'record_type' => 'NS',
        ]);

        if (($existing['status']['code'] ?? '') === '1' && !empty($existing['records'])) {
            foreach ($existing['records'] as $record) {
                if (!empty($record['id'])) {
                    $this->request('Record.Remove', [
                        'domain' => $domain->domain_name,
                        'record_id' => $record['id'],
                    ]);
                }
            }
        }

        foreach ($nsRecords as $ns) {
            $this->request('Record.Create', [
                'domain' => $domain->domain_name,
                'sub_domain' => $label,
                'record_type' => 'NS',
                'record_line' => '默认',
                'value' => $ns,
                'ttl' => 600,
            ]);
        }

        return true;
    }

    /**
     * @param array<string,string> $params
     */
    private function request(string $action, array $params): array
    {
        $loginToken = $this->getLoginToken();
        $body = array_merge([
            'login_token' => $loginToken,
            'format' => 'json',
        ], $params);

        $url = self::ENDPOINT . $action;
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $response = $this->http->request('POST', $url, $headers, http_build_query($body));
        $data = json_decode($response['body'], true);

        if (!is_array($data)) {
            throw new RuntimeException('DNSPod 返回数据异常');
        }

        if (($data['status']['code'] ?? '') !== '1') {
            throw new RuntimeException('DNSPod API 错误：' . ($data['status']['message'] ?? '未知错误'));
        }

        return $data;
    }

    private function getLoginToken(): string
    {
        if (!$this->config->api_account || !$this->config->api_key) {
            throw new RuntimeException('DNSPod 需要在提供商中配置 api_account（Token ID）与 api_key（Token Key）');
        }

        return $this->config->api_account . ',' . $this->config->api_key;
    }
}

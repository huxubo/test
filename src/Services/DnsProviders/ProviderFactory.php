<?php
declare(strict_types=1);

namespace Services\DnsProviders;

use Models\DomainProvider;
use RuntimeException;

/**
 * 提供商工厂，根据类型实例化对应的适配器
 */
class ProviderFactory
{
    public static function make(DomainProvider $provider): DnsProviderInterface
    {
        $http = new HttpClient();
        return match ($provider->provider_type) {
            'cloudflare' => new CloudflareProvider($provider, $http),
            'powerdns' => new PowerDnsProvider($provider, $http),
            'aliyun' => new AliyunProvider($provider, $http),
            'dnspod' => new DnsPodProvider($provider, $http),
            default => throw new RuntimeException('暂不支持的 DNS 提供商类型: ' . $provider->provider_type),
        };
    }
}

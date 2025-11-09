<?php
declare(strict_types=1);

namespace Services\DnsProviders;

use Models\PrimaryDomain;

/**
 * DNS 服务提供商统一接口
 */
interface DnsProviderInterface
{
    /**
     * 检查指定子域是否已存在
     */
    public function subdomainExists(PrimaryDomain $domain, string $label): bool;

    /**
     * 创建或更新 NS 记录
     * @param array<int,string> $nsRecords
     */
    public function upsertNsRecords(PrimaryDomain $domain, string $label, array $nsRecords): bool;
}

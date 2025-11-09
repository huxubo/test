<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Session;
use Models\DomainProvider;
use Models\PrimaryDomain;
use Models\Setting;
use Models\Subdomain;
use Models\SubdomainTransfer;
use Models\User;
use RuntimeException;
use Services\DnsProviders\ProviderFactory;

/**
 * 子域业务逻辑服务
 */
class SubdomainService
{
    /**
     * 检查子域是否已存在
     */
    public function subdomainExists(PrimaryDomain $domain, string $label): bool
    {
        $normalized = $this->normalizeLabel($label);
        $existing = Subdomain::findByLabel($domain->id, $normalized);
        if ($existing) {
            return true;
        }

        $provider = $this->resolveProvider($domain);
        return $provider->subdomainExists($domain, $normalized);
    }

    /**
     * 注册子域
     * @param array<int,string> $nsRecords
     */
    public function register(User $user, PrimaryDomain $domain, string $label, array $nsRecords): Subdomain
    {
        $normalized = $this->normalizeLabel($label);
        if (Subdomain::findByLabel($domain->id, $normalized)) {
            throw new RuntimeException('该子域在系统中已存在');
        }

        $nsRecords = array_values(array_filter($nsRecords));
        if (count($nsRecords) === 0) {
            throw new RuntimeException('请至少填写一条 NS 记录');
        }

        $provider = $this->resolveProvider($domain);
        if ($provider->subdomainExists($domain, $normalized)) {
            throw new RuntimeException('该子域在 DNS 提供商中已存在，请使用其他名称');
        }

        $quota = $user->subdomain_quota;
        if ($quota > 0) {
            $currentCount = count(Subdomain::forUser($user->id));
            if ($currentCount >= $quota) {
                throw new RuntimeException('已达到当前账号可申请的子域数量上限');
            }
        }

        $autoReview = (int)Setting::get('subdomain.auto_review', '1') === 1;
        $validDays = (int)Setting::get('subdomain.initial_valid_days', '365');
        $status = $autoReview ? 'active' : 'pending';
        $registeredAt = $autoReview ? date('Y-m-d H:i:s') : null;
        $expiresAt = $autoReview ? date('Y-m-d H:i:s', strtotime('+' . $validDays . ' days')) : null;

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $subdomain = Subdomain::create([
                'primary_domain_id' => $domain->id,
                'user_id' => $user->id,
                'label' => $normalized,
                'status' => $status,
                'ns_records' => json_encode($nsRecords, JSON_UNESCAPED_UNICODE),
                'registered_at' => $registeredAt,
                'expires_at' => $expiresAt,
            ]);

            if ($autoReview) {
                $provider->upsertNsRecords($domain, $normalized, $nsRecords);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $message = $autoReview ? '子域已自动开通并完成解析' : '子域申请已提交，请等待管理员审核';
        Session::flash('success', $message);
        return $subdomain;
    }

    /**
     * 审核通过子域（供后台使用）
     * @param array<int,string> $nsRecords
     */
    public function approve(Subdomain $subdomain, array $nsRecords): void
    {
        $domain = $subdomain->primaryDomain();
        if (!$domain) {
            throw new RuntimeException('主域不存在');
        }

        $nsRecords = array_values(array_filter($nsRecords));
        if (empty($nsRecords)) {
            throw new RuntimeException('请至少填写一条有效的 NS 记录');
        }

        $provider = $this->resolveProvider($domain);
        $provider->upsertNsRecords($domain, $subdomain->label, $nsRecords);

        $subdomain->updateStatus('active');
        $subdomain->updateNsRecords($nsRecords);
        $registeredAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int)Setting::get('subdomain.initial_valid_days', '365') . ' days'));
        $subdomain->markRegistered($registeredAt, $expiresAt);
    }

    public function reject(Subdomain $subdomain): void
    {
        $subdomain->updateStatus('rejected');
    }

    public function transfer(Subdomain $subdomain, User $from, User $to): SubdomainTransfer
    {
        if ($subdomain->user_id !== $from->id) {
            throw new RuntimeException('仅子域所有者可发起转移');
        }

        if ($to->id === $from->id) {
            throw new RuntimeException('不能将子域转移给自己');
        }

        $transfer = SubdomainTransfer::create([
            'subdomain_id' => $subdomain->id,
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'status' => 'completed',
        ]);

        $subdomain->transferTo($to->id);
        Session::flash('success', '子域已成功转移');

        return $transfer;
    }

    /**
     * 更新子域 NS 记录
     * @param array<int,string> $nsRecords
     */
    public function updateRecords(Subdomain $subdomain, array $nsRecords): void
    {
        $domain = $subdomain->primaryDomain();
        if (!$domain) {
            throw new RuntimeException('主域不存在');
        }

        $nsRecords = array_values(array_filter(array_map(
            static fn(string $ns): string => trim($ns),
            $nsRecords
        )));
        $nsRecords = array_values(array_unique($nsRecords));

        if (empty($nsRecords)) {
            throw new RuntimeException('请至少填写一条有效的 NS 记录');
        }

        if ($subdomain->status === 'active') {
            $provider = $this->resolveProvider($domain);
            $provider->upsertNsRecords($domain, $subdomain->label, $nsRecords);
        }

        $subdomain->updateNsRecords($nsRecords);
    }

    /**
     * 续期子域
     */
    public function renew(Subdomain $subdomain): void
    {
        if ($subdomain->status !== 'active') {
            throw new RuntimeException('仅已激活的子域可续期');
        }

        if (!$subdomain->expires_at) {
            throw new RuntimeException('子域尚未设置到期时间');
        }

        $daysSetting = Setting::get('subdomain.renew_valid_days');
        if ($daysSetting === null) {
            $daysSetting = Setting::get('subdomain.initial_valid_days', '365');
        }

        $days = (int)$daysSetting;
        if ($days <= 0) {
            $days = 365;
        }

        $currentExpiry = strtotime((string)$subdomain->expires_at);
        if ($currentExpiry === false) {
            $currentExpiry = time();
        }

        $base = max($currentExpiry, time());
        $newExpiry = date('Y-m-d H:i:s', strtotime('+' . $days . ' days', $base));

        $pdo = Database::connection();
        $table = Subdomain::table();
        $stmt = $pdo->prepare("UPDATE `{$table}` SET expires_at = :expires_at, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            'expires_at' => $newExpiry,
            'id' => $subdomain->id,
        ]);

        $subdomain->expires_at = $newExpiry;
    }

    /**
     * 删除子域
     */
    public function delete(Subdomain $subdomain): void
    {
        $domain = $subdomain->primaryDomain();
        if ($domain && $subdomain->status === 'active') {
            $provider = $this->resolveProvider($domain);
            $provider->upsertNsRecords($domain, $subdomain->label, []);
        }

        $subdomain->delete();
    }

    private function normalizeLabel(string $label): string
    {
        $label = strtolower(trim($label));
        if (!preg_match('/^[a-z0-9-]+$/', $label)) {
            throw new RuntimeException('子域名仅支持小写字母、数字和短横线');
        }

        return $label;
    }

    private function resolveProvider(PrimaryDomain $domain)
    {
        $provider = $domain->provider();
        if (!$provider instanceof DomainProvider) {
            throw new RuntimeException('主域未配置 DNS 服务商');
        }

        return ProviderFactory::make($provider);
    }
}

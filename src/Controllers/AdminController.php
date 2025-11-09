<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Session;
use Models\DomainProvider;
use Models\PrimaryDomain;
use Models\Setting;
use Models\Subdomain;
use Services\SubdomainService;
use RuntimeException;

/**
 * 后台管理控制器
 */
class AdminController
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            Session::flash('error', '无权访问后台');
            redirect('/login');
        }
    }

    public function dashboard(): string
    {
        $this->ensureAdmin();

        $pendingCount = $this->countByStatus('pending');
        $activeCount = $this->countByStatus('active');
        $providers = DomainProvider::all();
        $domains = PrimaryDomain::all();

        return view('admin/dashboard', [
            'pendingCount' => $pendingCount,
            'activeCount' => $activeCount,
            'providers' => $providers,
            'domains' => $domains,
        ]);
    }

    public function settings(): string
    {
        $this->ensureAdmin();

        $settings = [
            'subdomain.auto_review' => Setting::get('subdomain.auto_review', '1'),
            'subdomain.initial_valid_days' => Setting::get('subdomain.initial_valid_days', '365'),
            'user.initial_subdomain_quota' => Setting::get('user.initial_subdomain_quota', '3'),
        ];

        return view('admin/settings', ['settings' => $settings]);
    }

    public function updateSettings(): void
    {
        $this->ensureAdmin();

        Setting::set('subdomain.auto_review', (string)((int)($_POST['subdomain_auto_review'] ?? 0)));
        Setting::set('subdomain.initial_valid_days', (string)((int)($_POST['subdomain_initial_valid_days'] ?? 365)));
        Setting::set('user.initial_subdomain_quota', (string)((int)($_POST['user_initial_subdomain_quota'] ?? 3)));

        Session::flash('success', '设置已更新');
        redirect('/admin/settings');
    }

    public function providers(): string
    {
        $this->ensureAdmin();
        $providers = DomainProvider::all();
        $editingId = (int)($_GET['id'] ?? 0);
        $editingProvider = $editingId ? DomainProvider::find($editingId) : null;
        return view('admin/providers', [
            'providers' => $providers,
            'editingProvider' => $editingProvider,
        ]);
    }

    public function saveProvider(): void
    {
        $this->ensureAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'provider_type' => trim((string)($_POST['provider_type'] ?? '')),
            'api_key' => trim((string)($_POST['api_key'] ?? '')),
            'api_secret' => trim((string)($_POST['api_secret'] ?? '')),
            'api_account' => trim((string)($_POST['api_account'] ?? '')),
            'extra_params' => $this->prepareExtraParams($_POST['extra_params'] ?? ''),
        ];

        if ($id > 0) {
            $provider = DomainProvider::find($id);
            if ($provider) {
                $provider->update($data);
            }
        } else {
            DomainProvider::create($data);
        }

        Session::flash('success', 'DNS 提供商信息已保存');
        redirect('/admin/providers');
    }

    public function domains(): string
    {
        $this->ensureAdmin();
        $domains = PrimaryDomain::all();
        $providers = DomainProvider::all();
        $editingId = (int)($_GET['id'] ?? 0);
        $editingDomain = $editingId ? PrimaryDomain::find($editingId) : null;
        return view('admin/domains', [
            'domains' => $domains,
            'providers' => $providers,
            'editingDomain' => $editingDomain,
        ]);
    }

    public function saveDomain(): void
    {
        $this->ensureAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'domain_provider_id' => (int)($_POST['domain_provider_id'] ?? 0),
            'domain_name' => trim((string)($_POST['domain_name'] ?? '')),
            'provider_reference' => trim((string)($_POST['provider_reference'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')) ?: null,
        ];

        if ($id > 0) {
            $domain = PrimaryDomain::find($id);
            if ($domain) {
                $domain->update($data);
            }
        } else {
            PrimaryDomain::create($data);
        }

        Session::flash('success', '主域信息已保存');
        redirect('/admin/domains');
    }

    public function subdomains(): string
    {
        $this->ensureAdmin();
        $pending = $this->fetchByStatus('pending');
        return view('admin/subdomains', ['pendingSubdomains' => $pending]);
    }

    public function approveSubdomain(): void
    {
        $this->ensureAdmin();
        $id = (int)($_POST['subdomain_id'] ?? 0);
        $ns1 = trim((string)($_POST['ns1'] ?? ''));
        $ns2 = trim((string)($_POST['ns2'] ?? ''));
        $subdomain = Subdomain::find($id);
        if (!$subdomain) {
            Session::flash('error', '子域不存在');
            redirect('/admin/subdomains');
        }

        $service = new SubdomainService();
        try {
            $service->approve($subdomain, array_filter([$ns1, $ns2]));
            Session::flash('success', '子域已审核通过');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('/admin/subdomains');
    }

    public function rejectSubdomain(): void
    {
        $this->ensureAdmin();
        $id = (int)($_POST['subdomain_id'] ?? 0);
        $subdomain = Subdomain::find($id);
        if ($subdomain) {
            $service = new SubdomainService();
            $service->reject($subdomain);
            Session::flash('success', '子域已拒绝');
        }
        redirect('/admin/subdomains');
    }

    private function countByStatus(string $status): int
    {
        $pdo = \Core\Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM subdomains WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    private function fetchByStatus(string $status): array
    {
        $pdo = \Core\Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM subdomains WHERE status = :status ORDER BY created_at DESC');
        $stmt->execute(['status' => $status]);
        $rows = $stmt->fetchAll();
        return array_map(fn($row) => Subdomain::fromArray($row), $rows);
    }

    private function prepareExtraParams(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            Session::flash('error', '扩展参数必须是合法的 JSON 字符串');
            redirect('/admin/providers');
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}

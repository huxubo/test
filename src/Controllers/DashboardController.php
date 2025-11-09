<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Session;
use Models\PrimaryDomain;
use Models\Subdomain;
use Services\SubdomainService;
use RuntimeException;

/**
 * 用户中心控制器
 */
class DashboardController
{
    /**
     * 显示控制台首页
     */
    public function index(): string
    {
        $user = Auth::user();
        if (!$user) {
            redirect('/login');
        }

        $subdomains = Subdomain::forUser($user->id);
        $primaryDomains = PrimaryDomain::all();

        return view('dashboard/index', [
            'user' => $user,
            'subdomains' => $subdomains,
            'primaryDomains' => $primaryDomains,
        ]);
    }

    /**
     * 处理子域申请表单
     */
    public function storeSubdomain(): void
    {
        $user = Auth::user();
        if (!$user) {
            redirect('/login');
        }

        $primaryDomainId = (int)($_POST['primary_domain_id'] ?? 0);
        $label = trim((string)($_POST['label'] ?? ''));
        $ns1 = trim((string)($_POST['ns1'] ?? ''));
        $ns2 = trim((string)($_POST['ns2'] ?? ''));

        if (!$primaryDomainId || !$label || !$ns1 || !$ns2) {
            Session::flash('error', '请完整填写子域和 NS 记录信息');
            redirect('/dashboard');
        }

        $domain = PrimaryDomain::find($primaryDomainId);
        if (!$domain) {
            Session::flash('error', '主域不存在');
            redirect('/dashboard');
        }

        $service = new SubdomainService();

        try {
            $service->register($user, $domain, $label, [$ns1, $ns2]);
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            redirect('/dashboard');
        }

        redirect('/dashboard');
    }

    /**
     * AJAX 检查子域是否可用
     */
    public function checkAvailability(): void
    {
        $primaryDomainId = (int)($_GET['primary_domain_id'] ?? 0);
        $label = trim((string)($_GET['label'] ?? ''));

        $domain = PrimaryDomain::find($primaryDomainId);
        if (!$domain) {
            $this->json(['available' => false, 'message' => '主域不存在']);
            return;
        }

        try {
            $service = new SubdomainService();
            $available = !$service->subdomainExists($domain, $label);
            $this->json(['available' => $available]);
        } catch (RuntimeException $e) {
            $this->json(['available' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 发起子域转移
     */
    public function transfer(): void
    {
        $user = Auth::user();
        if (!$user) {
            redirect('/login');
        }

        $subdomainId = (int)($_POST['subdomain_id'] ?? 0);
        $toEmail = trim((string)($_POST['to_email'] ?? ''));

        $subdomain = Subdomain::find($subdomainId);
        if (!$subdomain || $subdomain->user_id !== $user->id) {
            Session::flash('error', '只能转移属于自己的子域');
            redirect('/dashboard');
        }

        $toUser = \Models\User::findByEmail($toEmail);
        if (!$toUser) {
            Session::flash('error', '目标用户不存在');
            redirect('/dashboard');
        }

        if (!$toUser->isVerified()) {
            Session::flash('error', '目标用户尚未完成邮箱验证');
            redirect('/dashboard');
        }

        $service = new SubdomainService();
        try {
            $service->transfer($subdomain, $user, $toUser);
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('/dashboard');
    }

    /**
     * 输出 JSON 响应
     */
    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

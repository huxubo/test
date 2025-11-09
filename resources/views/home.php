<?php
$title = '欢迎使用子域分发管理平台';
ob_start();
?>
<div class="card">
    <h2>平台功能概览</h2>
    <p>本系统支持在 PowerDNS、Cloudflare、阿里云 DNS、DNSPod 等平台上统一管理主域名，并将子域名分发给终端用户使用。</p>
    <ul>
        <li>用户注册并完成邮箱验证后即可申请子域</li>
        <li>系统根据管理员配置自动或手动审核子域申请</li>
        <li>支持跨账号转移子域归属</li>
        <li>所有下发子域仅支持 NS 记录，方便接入第三方权威 DNS 服务</li>
    </ul>
    <p>立即<a class="button" href="<?= base_url('register') ?>">创建账号</a>或<a class="button" href="<?= base_url('login') ?>">登录</a>开始使用。</p>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';

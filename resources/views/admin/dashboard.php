<?php
$title = '后台总览';
ob_start();
?>
<div class="card">
    <h2>统计数据</h2>
    <p>待审核子域数量：<strong><?= e((string)$pendingCount) ?></strong></p>
    <p>已激活子域数量：<strong><?= e((string)$activeCount) ?></strong></p>
</div>

<div class="card">
    <h2>已配置的 DNS 提供商</h2>
    <ul>
        <?php foreach ($providers as $provider): ?>
            <li><?= e($provider->name) ?> （类型：<?= e($provider->provider_type) ?>）</li>
        <?php endforeach; ?>
    </ul>
    <a class="button" href="<?= base_url('admin/providers') ?>">管理提供商</a>
</div>

<div class="card">
    <h2>已配置的主域</h2>
    <ul>
        <?php foreach ($domains as $domain): ?>
            <li><?= e($domain->domain_name) ?></li>
        <?php endforeach; ?>
    </ul>
    <a class="button" href="<?= base_url('admin/domains') ?>">管理主域</a>
</div>

<div class="card">
    <a class="button" href="<?= base_url('admin/subdomains') ?>">审核子域申请</a>
    <a class="button" href="<?= base_url('admin/settings') ?>">系统设置</a>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

<?php
$title = 'DNS 提供商管理';
$editing = $editingProvider ?? null;
ob_start();
?>
<div class="card">
    <h2>提供商列表</h2>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>名称</th>
            <th>类型</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($providers as $provider): ?>
            <tr>
                <td><?= e((string)$provider->id) ?></td>
                <td><?= e($provider->name) ?></td>
                <td><?= e($provider->provider_type) ?></td>
                <td><a class="button" href="<?= base_url('admin/providers') ?>?id=<?= $provider->id ?>">编辑</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2><?= $editing ? '编辑提供商' : '新增提供商' ?></h2>
    <form method="post" action="<?= base_url('admin/providers/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $editing?->id ?? 0 ?>">
        <div class="form-group">
            <label for="name">名称</label>
            <input type="text" id="name" name="name" value="<?= e($editing?->name ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="provider_type">类型</label>
            <select id="provider_type" name="provider_type" required>
                <?php
                $types = [
                    'powerdns' => 'PowerDNS',
                    'cloudflare' => 'Cloudflare',
                    'aliyun' => '阿里云 DNS',
                    'dnspod' => 'DNSPod',
                ];
                ?>
                <option value="">请选择</option>
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($editing?->provider_type ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="api_account">账号信息（部分提供商需要，如 DNSPod Token ID）</label>
            <input type="text" id="api_account" name="api_account" value="<?= e($editing?->api_account ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="api_key">API Key 或 Token</label>
            <input type="text" id="api_key" name="api_key" value="<?= e($editing?->api_key ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="api_secret">API Secret</label>
            <input type="text" id="api_secret" name="api_secret" value="<?= e($editing?->api_secret ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="extra_params">扩展参数（JSON 格式，例如：{"base_url":"https://dns.example.com/api","server_id":"localhost"}）</label>
            <textarea id="extra_params" name="extra_params" rows="4"><?= e($editing?->extra_params ?? '') ?></textarea>
        </div>
        <button type="submit">保存</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

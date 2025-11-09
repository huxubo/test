<?php
$title = '主域管理';
$editing = $editingDomain ?? null;
ob_start();
?>
<div class="card">
    <h2>主域列表</h2>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>域名</th>
            <th>所属提供商</th>
            <th>标识信息</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($domains as $domain): ?>
            <?php $provider = $domain->provider(); ?>
            <tr>
                <td><?= e((string)$domain->id) ?></td>
                <td><?= e($domain->domain_name) ?></td>
                <td><?= e($provider?->name ?? '-') ?></td>
                <td><?= e($domain->provider_reference ?? '-') ?></td>
                <td><a class="button" href="<?= base_url('admin/domains') ?>?id=<?= $domain->id ?>">编辑</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2><?= $editing ? '编辑主域' : '新增主域' ?></h2>
    <form method="post" action="<?= base_url('admin/domains/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $editing?->id ?? 0 ?>">
        <div class="form-group">
            <label for="domain_provider_id">选择提供商</label>
            <select id="domain_provider_id" name="domain_provider_id" required>
                <option value="">请选择</option>
                <?php foreach ($providers as $provider): ?>
                    <option value="<?= $provider->id ?>" <?= ($editing?->domain_provider_id ?? 0) === $provider->id ? 'selected' : '' ?>><?= e($provider->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="domain_name">主域名</label>
            <input type="text" id="domain_name" name="domain_name" value="<?= e($editing?->domain_name ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="provider_reference">提供商引用信息（例如 Cloudflare Zone ID）</label>
            <input type="text" id="provider_reference" name="provider_reference" value="<?= e($editing?->provider_reference ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="description">备注说明</label>
            <textarea id="description" name="description" rows="3"><?= e($editing?->description ?? '') ?></textarea>
        </div>
        <button type="submit">保存</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

<?php
$title = '系统设置';
ob_start();
?>
<div class="card">
    <h2>基础设置</h2>
    <form method="post" action="<?= base_url('admin/settings') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="subdomain_auto_review">子域审核模式</label>
            <select name="subdomain_auto_review" id="subdomain_auto_review">
                <option value="1" <?= ($settings['subdomain.auto_review'] ?? '1') === '1' ? 'selected' : '' ?>>自动审核</option>
                <option value="0" <?= ($settings['subdomain.auto_review'] ?? '1') === '0' ? 'selected' : '' ?>>人工审核</option>
            </select>
        </div>
        <div class="form-group">
            <label for="subdomain_initial_valid_days">子域初次有效期（天）</label>
            <input type="number" name="subdomain_initial_valid_days" id="subdomain_initial_valid_days" value="<?= e($settings['subdomain.initial_valid_days'] ?? '365') ?>">
        </div>
        <div class="form-group">
            <label for="user_initial_subdomain_quota">新用户初始子域配额</label>
            <input type="number" name="user_initial_subdomain_quota" id="user_initial_subdomain_quota" value="<?= e($settings['user.initial_subdomain_quota'] ?? '3') ?>">
        </div>
        <button type="submit">保存设置</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

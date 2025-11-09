<?php
$title = '我的控制台';
ob_start();
?>
<div class="card">
    <h2>账号信息</h2>
    <p><strong>用户名：</strong><?= e($user->username) ?></p>
    <p><strong>邮箱：</strong><?= e($user->email) ?> <?= $user->isVerified() ? '（已验证）' : '（未验证）' ?></p>
    <p><strong>手机号：</strong><?= e($user->phone ?? '未填写') ?></p>
    <p><strong>可申请子域数量：</strong><?= $user->subdomain_quota > 0 ? e((string)$user->subdomain_quota) : '无限制' ?></p>
</div>

<div class="card">
    <h2>申请新的子域</h2>
    <form method="post" action="<?= base_url('subdomains') ?>" id="subdomain-form">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="primary_domain_id">选择主域</label>
            <select name="primary_domain_id" id="primary_domain_id" required>
                <option value="">请选择</option>
                <?php foreach ($primaryDomains as $domain): ?>
                    <option value="<?= $domain->id ?>"><?= e($domain->domain_name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="label">子域前缀</label>
            <input type="text" name="label" id="label" required placeholder="例如：customer1">
            <small id="availability-message"></small>
        </div>
        <div class="form-group">
            <label for="ns1">NS 记录 1</label>
            <input type="text" name="ns1" id="ns1" required placeholder="例如：ns1.provider.com.">
        </div>
        <div class="form-group">
            <label for="ns2">NS 记录 2</label>
            <input type="text" name="ns2" id="ns2" required placeholder="例如：ns2.provider.com.">
        </div>
        <button type="submit">提交申请</button>
    </form>
</div>

<div class="card">
    <h2>我的子域</h2>
    <table class="table">
        <thead>
        <tr>
            <th>子域名</th>
            <th>状态</th>
            <th>NS 记录</th>
            <th>注册时间</th>
            <th>到期时间</th>
            <th>转移</th>
            <th>管理</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($subdomains)): ?>
            <tr>
                <td colspan="7">暂无子域记录</td>
            </tr>
        <?php else: ?>
            <?php foreach ($subdomains as $subdomain): ?>
                <?php
                $primary = $subdomain->primaryDomain();
                $primaryDomainName = $primary?->domain_name ?? '';
                $displayDomain = $primaryDomainName !== '' ? $subdomain->label . '.' . $primaryDomainName : $subdomain->label;
                $nsList = $subdomain->nsRecordArray();
                $nsTextareaValue = implode(PHP_EOL, $nsList);
                ?>
                <tr>
                    <td><?= e($displayDomain) ?></td>
                    <td><?= e($subdomain->status) ?></td>
                    <td>
                        <?php if (empty($nsList)): ?>
                            <div>-</div>
                        <?php else: ?>
                            <?php foreach ($nsList as $ns): ?>
                                <div><?= e($ns) ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e($subdomain->registered_at ?? '-') ?></td>
                    <td><?= e($subdomain->expires_at ?? '-') ?></td>
                    <td>
                        <form method="post" action="<?= base_url('subdomains/transfer') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="subdomain_id" value="<?= $subdomain->id ?>">
                            <input type="email" name="to_email" placeholder="目标用户邮箱" required>
                            <button type="submit" class="secondary">转移</button>
                        </form>
                    </td>
                    <td>
                        <details style="margin-bottom:8px;">
                            <summary>编辑 NS</summary>
                            <form method="post" action="<?= base_url('subdomains/update') ?>" style="margin-top:8px;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="subdomain_id" value="<?= $subdomain->id ?>">
                                <textarea name="ns_records" rows="3" required placeholder="每行一条 NS 记录" style="width:100%; margin-bottom:8px;"><?= e($nsTextareaValue) ?></textarea>
                                <button type="submit">保存</button>
                            </form>
                        </details>
                        <?php if ($subdomain->status === 'active' && $subdomain->expires_at): ?>
                            <form method="post" action="<?= base_url('subdomains/renew') ?>" style="margin-bottom:8px;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="subdomain_id" value="<?= $subdomain->id ?>">
                                <button type="submit">续期</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= base_url('subdomains/delete') ?>" onsubmit="return confirm('确认删除该子域？此操作不可恢复。');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="subdomain_id" value="<?= $subdomain->id ?>">
                            <button type="submit" class="secondary">删除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    const form = document.getElementById('subdomain-form');
    const labelInput = document.getElementById('label');
    const domainSelect = document.getElementById('primary_domain_id');
    const message = document.getElementById('availability-message');

    function checkAvailability() {
        const label = labelInput.value.trim();
        const domainId = domainSelect.value;
        if (!label || !domainId) {
            message.textContent = '';
            return;
        }
        fetch(`<?= base_url('subdomains/check') ?>?primary_domain_id=${domainId}&label=${encodeURIComponent(label)}`)
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    message.textContent = '子域可用';
                    message.style.color = 'green';
                } else {
                    message.textContent = data.message || '子域已存在';
                    message.style.color = 'red';
                }
            })
            .catch(() => {
                message.textContent = '检查失败，请稍后重试';
                message.style.color = 'red';
            });
    }

    labelInput.addEventListener('blur', checkAvailability);
    domainSelect.addEventListener('change', checkAvailability);
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

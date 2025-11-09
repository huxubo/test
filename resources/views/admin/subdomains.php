<?php
$title = '子域审核';
ob_start();
?>
<div class="card">
    <h2>待审核子域列表</h2>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>子域</th>
            <th>申请用户</th>
            <th>NS 记录</th>
            <th>申请时间</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($pendingSubdomains)): ?>
            <tr><td colspan="6">当前没有待审核的子域申请</td></tr>
        <?php else: ?>
            <?php foreach ($pendingSubdomains as $item): ?>
                <?php $domain = $item->primaryDomain(); ?>
                <?php $owner = $item->owner(); ?>
                <tr>
                    <td><?= e((string)$item->id) ?></td>
                    <td><?= e($item->label . '.' . $domain->domain_name) ?></td>
                    <td>
                        <?= e($owner?->username ?? '-') ?><br>
                        <?= e($owner?->email ?? '-') ?><br>
                        <?= e($owner?->phone ?? '-') ?>
                    </td>
                    <td>
                        <?php foreach ($item->nsRecordArray() as $ns): ?>
                            <div><?= e($ns) ?></div>
                        <?php endforeach; ?>
                    </td>
                    <td><?= e($item->created_at ?? '-') ?></td>
                    <td>
                        <form style="margin-bottom:8px;" method="post" action="<?= base_url('admin/subdomains/approve') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="subdomain_id" value="<?= $item->id ?>">
                            <input type="text" name="ns1" placeholder="NS 记录 1" value="<?= e($item->nsRecordArray()[0] ?? '') ?>" required>
                            <input type="text" name="ns2" placeholder="NS 记录 2" value="<?= e($item->nsRecordArray()[1] ?? '') ?>" required>
                            <button type="submit">审核通过</button>
                        </form>
                        <form method="post" action="<?= base_url('admin/subdomains/reject') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="subdomain_id" value="<?= $item->id ?>">
                            <button type="submit" class="secondary">拒绝</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

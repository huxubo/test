<?php
$title = '用户登录';
ob_start();
?>
<div class="card">
    <h2>登录账号</h2>
    <form method="post" action="<?= base_url('login') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email">邮箱</label>
            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required>
        </div>
        <div class="form-group">
            <label for="password">密码</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">登录</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

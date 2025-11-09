<?php
$title = '用户注册';
ob_start();
?>
<div class="card">
    <h2>注册新账号</h2>
    <form method="post" action="<?= base_url('register') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email">邮箱（将用于登录与验证）</label>
            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required>
        </div>
        <div class="form-group">
            <label for="username">用户名</label>
            <input type="text" id="username" name="username" value="<?= e(old('username')) ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">手机号</label>
            <input type="text" id="phone" name="phone" value="<?= e(old('phone')) ?>">
        </div>
        <div class="form-group">
            <label for="password">密码</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="password_confirmation">确认密码</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit">提交注册</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

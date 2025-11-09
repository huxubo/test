<?php
$title = '页面未找到';
ob_start();
?>
<div class="card">
    <h2>404 - 页面不存在</h2>
    <p>抱歉，您访问的页面不存在。</p>
    <a class="button" href="<?= base_url('/') ?>">返回首页</a>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';

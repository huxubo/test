<?php
declare(strict_types=1);

use Core\Auth;
use Core\Session;

$title = $title ?? '子域分发管理平台';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
<header class="app-header">
    <div class="container">
        <h1>子域分发管理平台</h1>
        <nav>
            <ul>
                <li><a href="<?= base_url('/') ?>">首页</a></li>
                <?php if (Auth::check()): ?>
                    <li><a href="<?= base_url('dashboard') ?>">控制台</a></li>
                    <?php if (Auth::user() && Auth::user()->isAdmin()): ?>
                        <li><a href="<?= base_url('admin') ?>">后台管理</a></li>
                    <?php endif; ?>
                    <li><a href="<?= base_url('logout') ?>">退出</a></li>
                <?php else: ?>
                    <li><a href="<?= base_url('login') ?>">登录</a></li>
                    <li><a href="<?= base_url('register') ?>">注册</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main class="app-main container">
    <?php foreach (Session::allFlashes() as $key => $message): ?>
        <?php if (in_array($key, ['success', 'error'])): ?>
            <div class="alert alert-<?= $key ?>"><?= e($message) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?= $content ?? '' ?>
</main>
<footer class="app-footer">
    <div class="container">
        <p>© <?= date('Y') ?> 子域分发管理平台</p>
    </div>
</footer>
</body>
</html>

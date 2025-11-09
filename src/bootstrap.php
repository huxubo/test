<?php
declare(strict_types=1);

// 自动加载类文件
spl_autoload_register(function (string $class): void {
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = $baseDir . $classPath . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 加载帮助函数
require_once __DIR__ . '/Support/helpers.php';

// 加载配置文件
$configFile = __DIR__ . '/../config/config.php';

if (!file_exists($configFile)) {
    throw new RuntimeException('缺少配置文件 config/config.php，请复制 config/config.example.php 并根据实际环境填写。');
}

$config = require $configFile;

// 设置默认时区
if (!empty($config['app']['timezone'])) {
    date_default_timezone_set($config['app']['timezone']);
}

// 将配置保存到全局容器
\Core\Config::init($config);

// 启动 Session
\Core\Session::start($config['app']['session_name'] ?? 'subdomain_app_session');

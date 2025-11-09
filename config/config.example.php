<?php
return [
    // 数据库配置信息
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'subdomain_manager',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    // 邮件发送配置，用于注册验证邮件
    'mail' => [
        'from_address' => 'no-reply@example.com',
        'from_name' => '子域分发管理平台',
        // 可以选择使用 PHP 自带 mail() 或者第三方 SMTP，这里提供占位符
        'transport' => 'mail', // 可选 mail / smtp
        'smtp' => [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
        ],
    ],

    // 应用基础配置
    'app' => [
        'base_url' => 'http://localhost',
        'session_name' => 'subdomain_app_session',
        'default_locale' => 'zh_CN',
        'timezone' => 'Asia/Shanghai',
    ],
];

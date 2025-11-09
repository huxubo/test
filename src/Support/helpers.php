<?php
declare(strict_types=1);

use Core\Config;
use Core\Session;

if (!function_exists('config')) {
    /**
     * 读取配置项
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        return Config::getInstance()->get($key, $default);
    }
}

if (!function_exists('base_url')) {
    /**
     * 获取带有基础域名的地址
     * @param string $path
     * @return string
     */
    function base_url(string $path = ''): string
    {
        $base = rtrim((string)config('app.base_url', ''), '/');
        $path = ltrim($path, '/');
        return $base . ($path ? '/' . $path : '');
    }
}

if (!function_exists('redirect')) {
    /**
     * 页面跳转
     * @param string $path
     */
    function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('old')) {
    /**
     * 获取上一次提交的表单值
     */
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::getFlash('_old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * 获取 CSRF Token
     */
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * 输出 CSRF 隐藏字段
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('e')) {
    /**
     * HTML 实体编码
     */
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('view')) {
    /**
     * 渲染视图
     * @param string $view
     * @param array $data
     * @return string
     */
    function view(string $view, array $data = []): string
    {
        $viewPath = __DIR__ . '/../../resources/views/' . trim($view, '/') . '.php';
        if (!file_exists($viewPath)) {
            throw new RuntimeException('视图文件不存在: ' . $viewPath);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
}

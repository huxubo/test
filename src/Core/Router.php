<?php
declare(strict_types=1);

namespace Core;

/**
 * 极简路由器
 */
class Router
{
    /** @var array<string,array<string,callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);

        if (isset($this->routes[$method][$normalizedPath])) {
            echo call_user_func($this->routes[$method][$normalizedPath]);
            return;
        }

        http_response_code(404);
        echo view('errors/404');
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }
}

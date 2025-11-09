<?php
declare(strict_types=1);

namespace think;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use RuntimeException;

class Route
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $routes = [];

    public function get(string $path, mixed $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, mixed $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function addRoute(string $method, string $path, mixed $handler): void
    {
        $method = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);
        $this->routes[$method][$normalizedPath] = $handler;
    }

    public function dispatch(Request $request): mixed
    {
        $method = strtoupper($request->method());
        $path = $this->normalizePath($request->path());

        if (!isset($this->routes[$method][$path])) {
            return Response::make(view('errors/404'), 404);
        }

        $handler = $this->routes[$method][$path];
        return $this->callHandler($handler, $request);
    }

    private function callHandler(mixed $handler, Request $request): mixed
    {
        if (is_string($handler)) {
            $handler = $this->createControllerHandler($handler);
        }

        if (is_array($handler) && isset($handler[0]) && is_string($handler[0])) {
            $handler[0] = $this->resolveControllerInstance($handler[0]);
        }

        if (is_callable($handler)) {
            $reflection = $this->reflectHandler($handler);
            if ($reflection->getNumberOfParameters() > 0) {
                return call_user_func($handler, $request);
            }

            return call_user_func($handler);
        }

        throw new RuntimeException('无法处理的路由处理器');
    }

    private function createControllerHandler(string $handler): array
    {
        if (!str_contains($handler, '@')) {
            if (str_contains($handler, '/')) {
                $handler = str_replace('/', '@', $handler, 1);
            } else {
                $handler .= '@index';
            }
        }

        [$controller, $method] = explode('@', $handler, 2);
        $instance = $this->resolveControllerInstance($controller);

        return [$instance, $method];
    }

    private function resolveControllerInstance(string $controller): object
    {
        $candidates = [];
        $original = $controller;

        if (str_contains($controller, '\\')) {
            $candidates[] = $controller;
            if (!str_ends_with($controller, 'Controller')) {
                $candidates[] = $controller . 'Controller';
            }
        } else {
            $candidates[] = 'app\\controller\\' . $controller;
            if (!str_ends_with($controller, 'Controller')) {
                $candidates[] = 'app\\controller\\' . $controller . 'Controller';
            }
        }

        foreach (array_unique($candidates) as $class) {
            if (class_exists($class)) {
                return new $class();
            }
        }

        throw new RuntimeException('控制器不存在: ' . $original);
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    private function reflectHandler(callable $handler): ReflectionFunctionAbstract
    {
        if (is_array($handler)) {
            return new ReflectionMethod($handler[0], $handler[1]);
        }

        if ($handler instanceof Closure) {
            return new ReflectionFunction($handler);
        }

        return new ReflectionFunction(Closure::fromCallable($handler));
    }
}

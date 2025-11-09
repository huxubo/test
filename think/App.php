<?php
declare(strict_types=1);

namespace think;

use Core\Session;

class App
{
    private Route $route;

    public function __construct(?Route $route = null)
    {
        $this->route = $route ?? new Route();
    }

    public function route(): Route
    {
        return $this->route;
    }

    public function loadRoutes(?string $routeFile = null): void
    {
        $routeFile ??= dirname(__DIR__) . '/route/app.php';

        if (is_file($routeFile)) {
            $route = $this->route;
            $app = $this;
            require $routeFile;
        }
    }

    public function run(): Response
    {
        $request = Request::capture();

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            if (!Session::verifyCsrf($request->input('_token'))) {
                return Response::make('CSRF Token 校验失败', 419);
            }
        }

        $result = $this->route->dispatch($request);

        if ($result instanceof Response) {
            return $result;
        }

        return Response::make((string)$result);
    }
}

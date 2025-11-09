<?php
declare(strict_types=1);

use think\App;

if (!function_exists('app')) {
    function app(?App $instance = null): App
    {
        static $app;

        if ($instance instanceof App) {
            $app = $instance;
        }

        if ($app === null) {
            $app = new App();
        }

        return $app;
    }
}

if (!function_exists('route')) {
    function route(): \think\Route
    {
        return app()->route();
    }
}

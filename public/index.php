<?php
declare(strict_types=1);

require_once __DIR__ . '/../think/bootstrap.php';

$app = new \think\App();
app($app);

$app->loadRoutes();
$app->run()->send();

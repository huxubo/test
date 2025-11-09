<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use Controllers\AdminController;
use Controllers\AuthController;
use Controllers\DashboardController;
use Core\Router;
use Core\Session;

$router = new Router();

$authController = new AuthController();
$dashboardController = new DashboardController();
$adminController = new AdminController();

$router->get('/', function () {
    if (\Core\Auth::check()) {
        redirect('/dashboard');
    }
    return view('home');
});

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/register', [$authController, 'showRegister']);
$router->post('/register', [$authController, 'register']);
$router->get('/verify', [$authController, 'verify']);
$router->get('/logout', [$authController, 'logout']);

$router->get('/dashboard', [$dashboardController, 'index']);
$router->post('/subdomains', [$dashboardController, 'storeSubdomain']);
$router->get('/subdomains/check', [$dashboardController, 'checkAvailability']);
$router->post('/subdomains/transfer', [$dashboardController, 'transfer']);

$router->get('/admin', [$adminController, 'dashboard']);
$router->get('/admin/settings', [$adminController, 'settings']);
$router->post('/admin/settings', [$adminController, 'updateSettings']);
$router->get('/admin/providers', [$adminController, 'providers']);
$router->post('/admin/providers/save', [$adminController, 'saveProvider']);
$router->get('/admin/domains', [$adminController, 'domains']);
$router->post('/admin/domains/save', [$adminController, 'saveDomain']);
$router->get('/admin/subdomains', [$adminController, 'subdomains']);
$router->post('/admin/subdomains/approve', [$adminController, 'approveSubdomain']);
$router->post('/admin/subdomains/reject', [$adminController, 'rejectSubdomain']);

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

if ($method === 'POST') {
    if (!Session::verifyCsrf($_POST['_token'] ?? null)) {
        http_response_code(419);
        echo 'CSRF Token 校验失败';
        exit;
    }
}

$router->dispatch($method, $path);

<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Session;
use Models\User;
use Services\EmailService;
use Services\UserService;
use RuntimeException;

/**
 * 认证相关控制器
 */
class AuthController
{
    /**
     * 显示登录页面
     */
    public function showLogin(): string
    {
        return view('auth/login');
    }

    /**
     * 处理登录请求
     */
    public function login(): void
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (!$email || !$password) {
            Session::flash('error', '请输入邮箱和密码');
            Session::flash('_old_input', ['email' => $email]);
            redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            Session::flash('error', '登录失败，请检查邮箱是否已验证或密码是否正确');
            Session::flash('_old_input', ['email' => $email]);
            redirect('/login');
        }

        redirect('/dashboard');
    }

    /**
     * 显示注册页面
     */
    public function showRegister(): string
    {
        return view('auth/register');
    }

    /**
     * 处理注册请求
     */
    public function register(): void
    {
        $input = [
            'email' => trim((string)($_POST['email'] ?? '')),
            'username' => trim((string)($_POST['username'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'password_confirmation' => (string)($_POST['password_confirmation'] ?? ''),
        ];

        $oldInput = $input;
        unset($oldInput['password'], $oldInput['password_confirmation']);
        Session::flash('_old_input', $oldInput);

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', '邮箱格式不正确');
            redirect('/register');
        }

        if ($input['password'] !== $input['password_confirmation']) {
            Session::flash('error', '两次密码输入不一致');
            redirect('/register');
        }

        if (strlen($input['password']) < 6) {
            Session::flash('error', '密码长度至少为 6 位');
            redirect('/register');
        }

        $userService = new UserService(new EmailService());

        try {
            $userService->register($input);
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            redirect('/register');
        }

        Session::flash('_old_input', []);
        redirect('/login');
    }

    /**
     * 邮箱验证处理
     */
    public function verify(): void
    {
        $token = (string)($_GET['token'] ?? '');
        if (!$token) {
            Session::flash('error', '验证链接无效');
            redirect('/login');
        }

        $userService = new UserService(new EmailService());
        if ($userService->verifyByToken($token)) {
            Session::flash('success', '邮箱验证成功，请登录');
        } else {
            Session::flash('error', '验证链接已失效或不存在');
        }

        redirect('/login');
    }

    /**
     * 退出登录
     */
    public function logout(): void
    {
        Auth::logout();
        redirect('/');
    }
}

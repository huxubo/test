<?php
declare(strict_types=1);

namespace Core;

use Models\User;

/**
 * 简易认证辅助类
 */
class Auth
{
    private const AUTH_KEY = 'auth_user_id';

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (!$user || !$user->isVerified()) {
            return false;
        }

        if (!password_verify($password, $user->password_hash)) {
            return false;
        }

        Session::set(self::AUTH_KEY, $user->id);
        Session::regenerate();
        return true;
    }

    public static function user(): ?User
    {
        $id = Session::get(self::AUTH_KEY);
        if (!$id) {
            return null;
        }

        return User::find((int)$id);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        Session::forget(self::AUTH_KEY);
        Session::regenerate();
    }
}

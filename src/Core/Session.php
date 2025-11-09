<?php
declare(strict_types=1);

namespace Core;

/**
 * Session 助手类
 */
class Session
{
    private const FLASH_KEY = '_flash';

    public static function start(string $sessionName): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($sessionName);
            session_start();
            self::sweepFlashes();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION[self::FLASH_KEY]['new'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $flash = $_SESSION[self::FLASH_KEY]['old'][$key] ?? $default;
        return $flash;
    }

    public static function allFlashes(): array
    {
        return $_SESSION[self::FLASH_KEY]['old'] ?? [];
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function csrfToken(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return hash_equals(self::csrfToken(), (string)$token);
    }

    private static function sweepFlashes(): void
    {
        if (!isset($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = ['new' => [], 'old' => []];
        }

        $_SESSION[self::FLASH_KEY]['old'] = $_SESSION[self::FLASH_KEY]['new'] ?? [];
        $_SESSION[self::FLASH_KEY]['new'] = [];
    }
}

<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

/**
 * 全局配置项模型
 */
class Setting extends BaseModel
{
    public string $key;
    public ?string $value = null;

    public static function table(): string
    {
        return 'settings';
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT value FROM `' . self::table() . '` WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string)$value : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO `' . self::table() . '` (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
        $stmt->execute([
            'key' => $key,
            'value' => $value,
        ]);
    }

    public static function all(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT * FROM `' . self::table() . '`');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => self::fromArray($row), $rows);
    }
}

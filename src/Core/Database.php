<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

/**
 * PDO 数据库连接管理
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $config = Config::getInstance()->get('db');
            if (!$config) {
                throw new \RuntimeException('数据库配置缺失');
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'] ?? 3306,
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            );

            try {
                self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new \RuntimeException('数据库连接失败: ' . $e->getMessage(), previous: $e);
            }
        }

        return self::$pdo;
    }
}

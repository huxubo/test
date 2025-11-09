<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

/**
 * 子域转移记录模型
 */
class SubdomainTransfer extends BaseModel
{
    public int $id;
    public int $subdomain_id;
    public int $from_user_id;
    public int $to_user_id;
    public string $status;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function table(): string
    {
        return 'subdomain_transfers';
    }

    public static function create(array $data): self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO `' . self::table() . '` (subdomain_id, from_user_id, to_user_id, status, created_at, updated_at) VALUES (:subdomain_id, :from_user_id, :to_user_id, :status, NOW(), NOW())');
        $stmt->execute([
            'subdomain_id' => $data['subdomain_id'],
            'from_user_id' => $data['from_user_id'],
            'to_user_id' => $data['to_user_id'],
            'status' => $data['status'],
        ]);

        return self::find((int)$pdo->lastInsertId());
    }

    public static function find(int $id): ?self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `' . self::table() . '` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromArray($row) : null;
    }

    public function markCompleted(): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status' => 'completed',
            'id' => $this->id,
        ]);
        $this->status = 'completed';
    }
}

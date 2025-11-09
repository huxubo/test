<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

/**
 * 子域模型
 */
class Subdomain extends BaseModel
{
    public int $id;
    public int $primary_domain_id;
    public int $user_id;
    public string $label;
    public string $status;
    public ?string $ns_records = null;
    public ?string $registered_at = null;
    public ?string $expires_at = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function table(): string
    {
        return 'subdomains';
    }

    public static function find(int $id): ?self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `' . self::table() . '` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromArray($row) : null;
    }

    public static function findByLabel(int $primaryDomainId, string $label): ?self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `' . self::table() . '` WHERE primary_domain_id = :primary_domain_id AND label = :label LIMIT 1');
        $stmt->execute([
            'primary_domain_id' => $primaryDomainId,
            'label' => $label,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromArray($row) : null;
    }

    /**
     * 获取用户拥有的子域列表
     * @return array<int,Subdomain>
     */
    public static function forUser(int $userId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `' . self::table() . '` WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function create(array $data): self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO `' . self::table() . '` (primary_domain_id, user_id, label, status, ns_records, registered_at, expires_at, created_at, updated_at) VALUES (:primary_domain_id, :user_id, :label, :status, :ns_records, :registered_at, :expires_at, NOW(), NOW())');
        $stmt->execute([
            'primary_domain_id' => $data['primary_domain_id'],
            'user_id' => $data['user_id'],
            'label' => $data['label'],
            'status' => $data['status'],
            'ns_records' => $data['ns_records'] ?? null,
            'registered_at' => $data['registered_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return self::find((int)$pdo->lastInsertId());
    }

    public function updateStatus(string $status): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => $this->id,
        ]);
        $this->status = $status;
    }

    public function updateNsRecords(array $records): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET ns_records = :ns, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'ns' => json_encode($records, JSON_UNESCAPED_UNICODE),
            'id' => $this->id,
        ]);
        $this->ns_records = json_encode($records, JSON_UNESCAPED_UNICODE);
    }

    public function markRegistered(string $registeredAt, string $expiresAt): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET registered_at = :registered_at, expires_at = :expires_at, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'registered_at' => $registeredAt,
            'expires_at' => $expiresAt,
            'id' => $this->id,
        ]);
        $this->registered_at = $registeredAt;
        $this->expires_at = $expiresAt;
    }

    public function transferTo(int $userId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET user_id = :user_id, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'user_id' => $userId,
            'id' => $this->id,
        ]);
        $this->user_id = $userId;
    }

    /**
     * 解析 NS 记录
     * @return array<int,string>
     */
    public function nsRecordArray(): array
    {
        if (!$this->ns_records) {
            return [];
        }

        $decoded = json_decode($this->ns_records, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function primaryDomain(): ?PrimaryDomain
    {
        return PrimaryDomain::find($this->primary_domain_id);
    }

    public function owner(): ?User
    {
        return User::find($this->user_id);
    }
}

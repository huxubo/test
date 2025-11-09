<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

/**
 * 主域名模型
 */
class PrimaryDomain extends BaseModel
{
    public int $id;
    public int $domain_provider_id;
    public string $domain_name;
    public ?string $provider_reference = null;
    public ?string $description = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function table(): string
    {
        return 'primary_domains';
    }

    public static function all(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT * FROM `' . self::table() . '` ORDER BY domain_name ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => self::fromArray($row), $rows);
    }

    public static function find(int $id): ?self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `' . self::table() . '` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromArray($row) : null;
    }

    public static function findByDomain(string $domain): ?self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `' . self::table() . '` WHERE domain_name = :domain LIMIT 1');
        $stmt->execute(['domain' => $domain]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromArray($row) : null;
    }

    public static function create(array $data): self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO `' . self::table() . '` (domain_provider_id, domain_name, provider_reference, description, created_at, updated_at) VALUES (:domain_provider_id, :domain_name, :provider_reference, :description, NOW(), NOW())');
        $stmt->execute([
            'domain_provider_id' => $data['domain_provider_id'],
            'domain_name' => $data['domain_name'],
            'provider_reference' => $data['provider_reference'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return self::find((int)$pdo->lastInsertId());
    }

    public function update(array $data): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET domain_provider_id = :domain_provider_id, domain_name = :domain_name, provider_reference = :provider_reference, description = :description, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'domain_provider_id' => $data['domain_provider_id'],
            'domain_name' => $data['domain_name'] ?? $this->domain_name,
            'provider_reference' => $data['provider_reference'] ?? null,
            'description' => $data['description'] ?? null,
            'id' => $this->id,
        ]);
        $this->fill($data);
    }

    public function provider(): ?DomainProvider
    {
        return DomainProvider::find($this->domain_provider_id);
    }
}

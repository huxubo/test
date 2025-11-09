<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

/**
 * DNS 服务提供商配置模型
 */
class DomainProvider extends BaseModel
{
    public int $id;
    public string $name;
    public string $provider_type;
    public ?string $api_key = null;
    public ?string $api_secret = null;
    public ?string $api_account = null;
    public ?string $extra_params = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function table(): string
    {
        return 'domain_providers';
    }

    public static function all(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT * FROM `' . self::table() . '` ORDER BY id DESC');
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

    public static function create(array $data): self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO `' . self::table() . '` (name, provider_type, api_key, api_secret, api_account, extra_params, created_at, updated_at) VALUES (:name, :provider_type, :api_key, :api_secret, :api_account, :extra_params, NOW(), NOW())');
        $stmt->execute([
            'name' => $data['name'],
            'provider_type' => $data['provider_type'],
            'api_key' => $data['api_key'] ?? null,
            'api_secret' => $data['api_secret'] ?? null,
            'api_account' => $data['api_account'] ?? null,
            'extra_params' => $data['extra_params'] ?? null,
        ]);

        return self::find((int)$pdo->lastInsertId());
    }

    public function update(array $data): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET name = :name, provider_type = :provider_type, api_key = :api_key, api_secret = :api_secret, api_account = :api_account, extra_params = :extra_params, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'name' => $data['name'],
            'provider_type' => $data['provider_type'],
            'api_key' => $data['api_key'] ?? null,
            'api_secret' => $data['api_secret'] ?? null,
            'api_account' => $data['api_account'] ?? null,
            'extra_params' => $data['extra_params'] ?? null,
            'id' => $this->id,
        ]);
        $this->fill($data);
    }

    /**
     * 返回解析后的扩展参数
     * @return array<string,mixed>
     */
    public function extraParams(): array
    {
        if (!$this->extra_params) {
            return [];
        }
        $decoded = json_decode($this->extra_params, true);
        return is_array($decoded) ? $decoded : [];
    }
}

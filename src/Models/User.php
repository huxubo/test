<?php
declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;
use PDOException;

/**
 * 用户模型
 */
class User extends BaseModel
{
    public int $id;
    public string $email;
    public string $password_hash;
    public string $username;
    public ?string $phone = null;
    public int $is_verified = 0;
    public int $is_admin = 0;
    public ?string $verification_token = null;
    public ?string $verification_sent_at = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public int $subdomain_quota = 0;

    public static function table(): string
    {
        return 'users';
    }

    public static function find(int $id): ?self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `' . self::table() . '` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromArray($row) : null;
    }

    public static function findByEmail(string $email): ?self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `' . self::table() . '` WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromArray($row) : null;
    }

    public static function create(array $data): self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO `' . self::table() . '` (email, password_hash, username, phone, is_verified, is_admin, verification_token, verification_sent_at, subdomain_quota, created_at, updated_at) VALUES (:email, :password_hash, :username, :phone, :is_verified, :is_admin, :verification_token, :verification_sent_at, :subdomain_quota, NOW(), NOW())');
        $stmt->execute([
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'username' => $data['username'],
            'phone' => $data['phone'] ?? null,
            'is_verified' => $data['is_verified'] ?? 0,
            'is_admin' => $data['is_admin'] ?? 0,
            'verification_token' => $data['verification_token'] ?? null,
            'verification_sent_at' => $data['verification_sent_at'] ?? null,
            'subdomain_quota' => $data['subdomain_quota'] ?? 0,
        ]);

        $id = (int)$pdo->lastInsertId();
        return self::find($id);
    }

    public function markVerified(): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET is_verified = 1, verification_token = NULL, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
        $this->is_verified = 1;
        $this->verification_token = null;
    }

    public function updateProfile(array $data): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET username = :username, phone = :phone, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'username' => $data['username'],
            'phone' => $data['phone'] ?? null,
            'id' => $this->id,
        ]);
        $this->username = $data['username'];
        $this->phone = $data['phone'] ?? null;
    }

    public function adjustQuota(int $difference): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE `' . self::table() . '` SET subdomain_quota = subdomain_quota + :diff WHERE id = :id');
        $stmt->execute([
            'diff' => $difference,
            'id' => $this->id,
        ]);
        $this->subdomain_quota += $difference;
    }

    public function isVerified(): bool
    {
        return (bool)$this->is_verified;
    }

    public function isAdmin(): bool
    {
        return (bool)$this->is_admin;
    }
}

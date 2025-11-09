<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Session;
use Models\Setting;
use Models\User;
use RuntimeException;

/**
 * 用户业务逻辑服务
 */
class UserService
{
    public function __construct(private readonly EmailService $emailService)
    {
    }

    /**
     * 注册用户并发送邮箱验证
     * @param array<string,mixed> $input
     */
    public function register(array $input): User
    {
        $existing = User::findByEmail($input['email']);
        if ($existing) {
            throw new RuntimeException('该邮箱已被注册');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $token = bin2hex(random_bytes(32));
            $quota = (int)(Setting::get('user.initial_subdomain_quota', '3'));
            $user = User::create([
                'email' => $input['email'],
                'password_hash' => password_hash($input['password'], PASSWORD_BCRYPT),
                'username' => $input['username'],
                'phone' => $input['phone'] ?? null,
                'is_verified' => 0,
                'verification_token' => $token,
                'verification_sent_at' => date('Y-m-d H:i:s'),
                'subdomain_quota' => $quota,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $this->emailService->sendVerificationEmail($user, $token);

        Session::flash('success', '注册成功，请前往邮箱完成验证');

        return $user;
    }

    /**
     * 激活邮箱
     */
    public function verifyByToken(string $token): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM `users` WHERE verification_token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        $user = User::fromArray($row);
        $user->markVerified();
        return true;
    }
}

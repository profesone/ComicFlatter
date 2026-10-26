<?php

declare(strict_types=1);

namespace App\Domain;

use App\Support\Db;
use PDO;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(string $id): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $email, string $password, ?string $name): string
    {
        $id = self::uuid();
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = Db::pdo()->prepare(
            'INSERT INTO users (id, email, password_hash, name) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$id, strtolower(trim($email)), $hash, $name !== '' ? $name : null]);
        return $id;
    }

    public function updatePassword(string $id, string $password): void
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = Db::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    public function updateProfile(string $id, string $email, ?string $name): void
    {
        $stmt = Db::pdo()->prepare('UPDATE users SET email = ?, name = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([strtolower(trim($email)), $name !== '' ? $name : null, $id]);
    }

    public function close(string $id): void
    {
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID);
        $stmt = Db::pdo()->prepare('UPDATE users SET password_hash = ?, deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    public function reopen(string $id, string $password, ?string $name): void
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = Db::pdo()->prepare('UPDATE users SET password_hash = ?, name = ?, deleted_at = NULL WHERE id = ?');
        $stmt->execute([$hash, $name !== '' ? $name : null, $id]);
    }

    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}

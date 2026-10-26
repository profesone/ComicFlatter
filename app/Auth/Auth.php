<?php

declare(strict_types=1);

namespace App\Auth;

use App\Domain\UserRepository;
use App\Security\Session;
use App\Support\Db;

final class Auth
{
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_string($id) || $id === '') {
            return null;
        }
        $user = (new UserRepository())->findById($id);
        if (!$user || !empty($user['deleted_at'])) {
            return null;
        }
        return $user;
    }

    public static function requireUser(): array
    {
        $user = self::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }
        return $user;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = $user['id'];
        unset($_SESSION['user']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public static function tooManyAttempts(string $bucket): bool
    {
        $stmt = Db::pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE bucket = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
        );
        $stmt->execute([$bucket]);
        return (int) $stmt->fetchColumn() >= 8;
    }

    public static function recordAttempt(string $bucket): void
    {
        $stmt = Db::pdo()->prepare('INSERT INTO login_attempts (bucket) VALUES (?)');
        $stmt->execute([$bucket]);
    }

    public static function clearAttempts(string $bucket): void
    {
        $stmt = Db::pdo()->prepare('DELETE FROM login_attempts WHERE bucket = ?');
        $stmt->execute([$bucket]);
    }
}

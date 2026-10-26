<?php

declare(strict_types=1);

namespace App\Domain;

use App\Support\Db;
use PDO;

final class JobRepository
{
    public function createPending(string $userId, string $sessionId): string
    {
        $id = UserRepository::uuid();
        $stmt = Db::pdo()->prepare(
            'INSERT INTO jobs (id, user_id, stripe_session_id, status, output_paths, expires_at)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 48 HOUR))'
        );
        $stmt->execute([$id, $userId, $sessionId, 'pending_payment', '[]']);
        return $id;
    }

    public function createFree(string $userId): string
    {
        $id = UserRepository::uuid();
        $session = 'free_' . bin2hex(random_bytes(8));
        $stmt = Db::pdo()->prepare(
            'INSERT INTO jobs (id, user_id, stripe_session_id, status, output_paths, expires_at)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 48 HOUR))'
        );
        $stmt->execute([$id, $userId, $session, 'paid', '[]']);
        return $id;
    }

    /** @return list<array<string, mixed>> */
    public function purchasesFor(string $userId): array
    {
        $stmt = Db::pdo()->prepare(
            "SELECT id, status, stripe_payment_intent_id, attempts_used, max_attempts, created_at
             FROM jobs
             WHERE user_id = ? AND stripe_session_id NOT LIKE 'free_%' AND status <> 'pending_payment'
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function latestUsable(string $userId): ?array
    {
        $rows = $this->usableFor($userId);
        return $rows[0] ?? null;
    }

    /** @return list<array<string, mixed>> */
    public function usableFor(string $userId): array
    {
        $stmt = Db::pdo()->prepare(
            "SELECT * FROM jobs
             WHERE user_id = ? AND status IN ('paid','processing','completed','failed')
               AND expires_at > NOW() AND attempts_used < max_attempts
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findForUser(string $id, string $userId): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM jobs WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markPaid(string $stripeSessionId, ?string $paymentIntent): void
    {
        $stmt = Db::pdo()->prepare(
            "UPDATE jobs SET status = 'paid', stripe_payment_intent_id = COALESCE(?, stripe_payment_intent_id)
             WHERE stripe_session_id = ? AND status = 'pending_payment'"
        );
        $stmt->execute([$paymentIntent, $stripeSessionId]);
    }

    public function update(string $id, array $fields): void
    {
        $allowed = ['status', 'attempts_used', 'input_path', 'line_path', 'output_paths', 'style_notes', 'region_hint', 'seed', 'error_message'];
        $sets = [];
        $values = [];
        foreach ($fields as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $sets[] = $key . ' = ?';
            $values[] = $value;
        }
        if ($sets === []) {
            return;
        }
        $values[] = $id;
        $sql = 'UPDATE jobs SET ' . implode(', ', $sets) . ' WHERE id = ?';
        Db::pdo()->prepare($sql)->execute($values);
    }
}

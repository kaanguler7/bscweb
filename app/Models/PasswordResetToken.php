<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use PDO;
use Throwable;

final class PasswordResetToken
{
    private static bool $schemaEnsured = false;

    private static function pdo(): PDO
    {
        self::ensureSchema();
        return Database::connection(App::config());
    }

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }

        $pdo = Database::connection(App::config());
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL UNIQUE,
                expires_at_ts BIGINT NOT NULL,
                used_at_ts BIGINT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_password_reset_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                KEY idx_password_reset_tokens_user_active (user_id, used_at_ts),
                KEY idx_password_reset_tokens_expires (expires_at_ts)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        self::$schemaEnsured = true;
    }

    public static function createForUser(int $userId, int $ttlSeconds = 3600): string
    {
        self::purgeExpired();

        $pdo = self::pdo();
        $now = time();
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $invalidateStmt = $pdo->prepare(
            'UPDATE password_reset_tokens
             SET used_at_ts = :used_at_ts
             WHERE user_id = :user_id AND used_at_ts IS NULL'
        );
        $invalidateStmt->execute([
            'used_at_ts' => $now,
            'user_id' => $userId,
        ]);

        $insertStmt = $pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at_ts, used_at_ts)
             VALUES (:user_id, :token_hash, :expires_at_ts, NULL)'
        );
        $insertStmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at_ts' => $now + $ttlSeconds,
        ]);

        return $token;
    }

    public static function findActiveByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        self::purgeExpired();

        $tokenHash = hash('sha256', $token);
        $now = time();
        $stmt = self::pdo()->prepare(
            'SELECT id, user_id, expires_at_ts
             FROM password_reset_tokens
             WHERE token_hash = :token_hash
               AND used_at_ts IS NULL
               AND expires_at_ts >= :now_ts
             LIMIT 1'
        );
        $stmt->execute([
            'token_hash' => $tokenHash,
            'now_ts' => $now,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function consumeAndResetPassword(string $token, string $newPassword): bool
    {
        $activeToken = self::findActiveByToken($token);
        if ($activeToken === null) {
            return false;
        }

        $pdo = self::pdo();
        $now = time();
        $userId = (int) $activeToken['user_id'];

        try {
            $pdo->beginTransaction();

            User::updatePassword($userId, $newPassword);

            $useStmt = $pdo->prepare(
                'UPDATE password_reset_tokens
                 SET used_at_ts = :used_at_ts
                 WHERE user_id = :user_id AND used_at_ts IS NULL'
            );
            $useStmt->execute([
                'used_at_ts' => $now,
                'user_id' => $userId,
            ]);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function purgeExpired(): void
    {
        $now = time();
        $oneWeekAgo = $now - (7 * 24 * 60 * 60);

        $stmt = self::pdo()->prepare(
            'DELETE FROM password_reset_tokens
             WHERE expires_at_ts < :now_ts
                OR (used_at_ts IS NOT NULL AND used_at_ts < :week_ago)'
        );
        $stmt->execute([
            'now_ts' => $now,
            'week_ago' => $oneWeekAgo,
        ]);
    }
}

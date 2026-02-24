<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use PDO;

final class User
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
            'CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                full_name VARCHAR(160) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS wishlists (
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, product_id),
                CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_wishlists_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        self::$schemaEnsured = true;
    }

    public static function create(string $fullName, string $email, string $password): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)'
        );
        $stmt->execute([
            'full_name' => $fullName,
            'email' => mb_strtolower($email, 'UTF-8'),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return (int) self::pdo()->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $stmt = self::pdo()->prepare('SELECT id, full_name, email, password_hash, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $email = mb_strtolower(trim($email), 'UTF-8');
        $stmt = self::pdo()->prepare('SELECT id, full_name, email, password_hash, created_at FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function emailExists(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }

    public static function verifyCredentials(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if ($user === null) {
            return null;
        }

        $hash = (string) $user['password_hash'];
        if (!password_verify($password, $hash)) {
            return null;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $stmt = self::pdo()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $stmt->execute([
                'id' => (int) $user['id'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        return $user;
    }

    public static function updatePassword(int $userId, string $password): void
    {
        $stmt = self::pdo()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            'id' => $userId,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }
}

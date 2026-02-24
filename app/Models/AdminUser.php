<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;

final class AdminUser
{
    public static function findById(int $id): ?array
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function verifyCredentials(string $username, string $password): ?array
    {
        $user = self::findByUsername($username);
        if ($user === null) {
            return null;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            self::updatePassword((int) $user['id'], $password);
            $user = self::findById((int) $user['id']) ?? $user;
        }

        return $user;
    }

    public static function updatePassword(int $id, string $password): void
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }
}

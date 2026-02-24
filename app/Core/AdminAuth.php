<?php

namespace App\Core;

final class AdminAuth
{
    public static function check(): bool
    {
        return isset($_SESSION['admin_user_id']) && (int) $_SESSION['admin_user_id'] > 0;
    }

    public static function login(int $userId, string $username): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['admin_user_id'] = $userId;
        $_SESSION['admin_username'] = $username;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_user_id'], $_SESSION['admin_username']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}

<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use PDO;

final class Wishlist
{
    private static function pdo(): PDO
    {
        User::ensureSchema();
        return Database::connection(App::config());
    }

    public static function toggle(int $userId, int $productId): bool
    {
        if (self::exists($userId, $productId)) {
            self::remove($userId, $productId);
            return false;
        }

        self::add($userId, $productId);
        return true;
    }

    public static function add(int $userId, int $productId): void
    {
        $stmt = self::pdo()->prepare(
            'INSERT IGNORE INTO wishlists (user_id, product_id, created_at) VALUES (:user_id, :product_id, CURRENT_TIMESTAMP)'
        );
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    }

    public static function remove(int $userId, int $productId): void
    {
        $stmt = self::pdo()->prepare('DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id');
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    }

    public static function exists(int $userId, int $productId): bool
    {
        $stmt = self::pdo()->prepare('SELECT product_id FROM wishlists WHERE user_id = :user_id AND product_id = :product_id LIMIT 1');
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function productIdsForUser(int $userId): array
    {
        $stmt = self::pdo()->prepare('SELECT product_id FROM wishlists WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return array_map(static fn($row): int => (int) $row['product_id'], $stmt->fetchAll());
    }

    public static function countForUser(int $userId): int
    {
        $stmt = self::pdo()->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public static function productsForUser(int $userId): array
    {
        $sql = 'SELECT p.*, c.name AS category_name, b.name AS brand, m.name AS model, pc.code AS part_code
                FROM wishlists w
                JOIN products p ON p.id = w.product_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN product_models m ON m.id = p.model_id
                LEFT JOIN part_codes pc ON pc.id = p.part_code_id
                WHERE w.user_id = :user_id
                ORDER BY w.created_at DESC';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }
}

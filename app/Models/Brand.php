<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use PDO;

final class Brand
{
    private static function pdo(): PDO
    {
        return Database::connection(App::config());
    }

    public static function all(): array
    {
        return self::pdo()->query('SELECT id, name FROM brands ORDER BY name ASC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::pdo()->prepare('SELECT id, name FROM brands WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM brands WHERE LOWER(name) = LOWER(:name)';
        $params = ['name' => $name];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(string $name): int
    {
        $stmt = self::pdo()->prepare('INSERT INTO brands (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name): void
    {
        $stmt = self::pdo()->prepare('UPDATE brands SET name = :name WHERE id = :id');
        $stmt->execute(['id' => $id, 'name' => $name]);
    }

    public static function hasProducts(int $id): bool
    {
        $stmt = self::pdo()->prepare('SELECT id FROM products WHERE brand_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public static function productCount(int $id): int
    {
        $stmt = self::pdo()->prepare('SELECT COUNT(*) FROM products WHERE brand_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function delete(int $id): void
    {
        $stmt = self::pdo()->prepare('DELETE FROM brands WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

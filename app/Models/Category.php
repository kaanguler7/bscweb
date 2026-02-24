<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;

final class Category
{
    public static function all(): array
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->query('SELECT id, name, parent_id FROM categories ORDER BY COALESCE(parent_id, id), parent_id, name');

        return $stmt->fetchAll();
    }

    public static function rootCategories(): array
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->query('SELECT id, name, parent_id FROM categories WHERE parent_id IS NULL ORDER BY name');

        return $stmt->fetchAll();
    }

    public static function tree(): array
    {
        $all = self::all();
        $childrenByParent = [];

        foreach ($all as $category) {
            $parentId = $category['parent_id'] === null ? null : (int) $category['parent_id'];
            $childrenByParent[$parentId][] = $category;
        }

        return self::buildTree($childrenByParent, null);
    }

    public static function flatForSelect(): array
    {
        $flat = [];
        self::flattenTree(self::tree(), $flat);

        return $flat;
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('SELECT id, name, parent_id FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function map(): array
    {
        $map = [];
        foreach (self::flatForSelect() as $category) {
            $map[(int) $category['id']] = $category['display_name'];
        }

        return $map;
    }

    public static function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $pdo = Database::connection(App::config());
        $sql = 'SELECT id FROM categories WHERE LOWER(name) = LOWER(:name)';
        $params = ['name' => $name];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(string $name, ?int $parentId = null): int
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('INSERT INTO categories (name, parent_id) VALUES (:name, :parent_id)');
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':parent_id', $parentId, $parentId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name, ?int $parentId = null): void
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('UPDATE categories SET name = :name, parent_id = :parent_id WHERE id = :id');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':parent_id', $parentId, $parentId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function hasProducts(int $id): bool
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('SELECT id FROM products WHERE category_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public static function hasChildren(int $id): bool
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE parent_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public static function descendantIds(int $id): array
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE parent_id = :id');
        $stmt->execute(['id' => $id]);

        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $childId = (int) $row['id'];
            $ids[] = $childId;
            foreach (self::descendantIds($childId) as $descId) {
                $ids[] = $descId;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function isDescendant(int $candidateId, int $categoryId): bool
    {
        return in_array($candidateId, self::descendantIds($categoryId), true);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function breadcrumb(int $id): array
    {
        $chain = [];
        $current = self::find($id);

        while ($current !== null) {
            array_unshift($chain, $current);
            $parentId = $current['parent_id'] === null ? null : (int) $current['parent_id'];
            $current = $parentId === null ? null : self::find($parentId);
        }

        return $chain;
    }

    private static function buildTree(array $childrenByParent, ?int $parentId): array
    {
        $tree = [];
        foreach ($childrenByParent[$parentId] ?? [] as $category) {
            $id = (int) $category['id'];
            $tree[] = [
                'id' => $id,
                'name' => $category['name'],
                'parent_id' => $category['parent_id'] === null ? null : (int) $category['parent_id'],
                'children' => self::buildTree($childrenByParent, $id),
            ];
        }

        return $tree;
    }

    private static function flattenTree(array $tree, array &$flat, int $depth = 0): void
    {
        foreach ($tree as $node) {
            $prefix = $depth > 0 ? str_repeat('-- ', $depth) : '';
            $flat[] = [
                'id' => (int) $node['id'],
                'name' => $node['name'],
                'display_name' => $prefix . $node['name'],
                'parent_id' => $node['parent_id'] === null ? null : (int) $node['parent_id'],
            ];
            self::flattenTree($node['children'], $flat, $depth + 1);
        }
    }
}

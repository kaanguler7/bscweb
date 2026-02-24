<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use PDO;

final class Product
{
    public const KDV_RATE = 0.20;

    private static function pdo(): PDO
    {
        return Database::connection(App::config());
    }

    private static function isSqlSrv(): bool
    {
        return Database::isSqlSrv();
    }

    private static function baseSelect(): string
    {
        return 'SELECT p.*, c.name AS category_name, b.name AS brand, m.name AS model, pc.code AS part_code
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN product_models m ON m.id = p.model_id
                LEFT JOIN part_codes pc ON pc.id = p.part_code_id';
    }

    public static function all(): array
    {
        $sql = self::baseSelect() . ' ORDER BY p.id DESC';
        return self::pdo()->query($sql)->fetchAll();
    }

    public static function sitemapEntries(int $limit = 50000): array
    {
        $limit = max(1, min(50000, $limit));

        if (self::isSqlSrv()) {
            $sql = 'SELECT TOP(:limit) id, created_at, updated_at FROM products ORDER BY id DESC';
        } else {
            $sql = 'SELECT id, created_at, updated_at FROM products ORDER BY id DESC LIMIT :limit';
        }

        $stmt = self::pdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function latest(int $limit = 4): array
    {
        if (self::isSqlSrv()) {
            $sql = self::baseSelect() . ' ORDER BY p.id DESC OFFSET 0 ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = self::baseSelect() . ' ORDER BY p.id DESC LIMIT :limit';
        }
        $stmt = self::pdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        if (self::isSqlSrv()) {
            $sql = 'SELECT TOP 1 p.*, c.name AS category_name, b.name AS brand, m.name AS model, pc.code AS part_code
                    FROM products p
                    LEFT JOIN categories c ON c.id = p.category_id
                    LEFT JOIN brands b ON b.id = p.brand_id
                    LEFT JOIN product_models m ON m.id = p.model_id
                    LEFT JOIN part_codes pc ON pc.id = p.part_code_id
                    WHERE p.id = :id';
        } else {
            $sql = self::baseSelect() . ' WHERE p.id = :id LIMIT 1';
        }
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        if ($product === false) {
            return null;
        }

        $technicalRows = self::technicalRows((int) $product['id']);
        $technicalMap = [];
        foreach ($technicalRows as $row) {
            $technicalMap[(string) $row['code']] = (string) $row['value'];
        }

        $product['technical'] = $technicalMap;
        $product['technical_rows'] = $technicalRows;

        return $product;
    }

    public static function filter(?string $query, ?int $categoryId, array $brands = [], bool $inStockOnly = false): array
    {
        $sql = self::baseSelect() . ' WHERE 1=1';
        $params = [];

        if ($categoryId !== null) {
            [$clause, $categoryParams] = self::categoryInClause($categoryId, 'cat');
            $sql .= $clause;
            $params = array_merge($params, $categoryParams);
        }

        if ($query !== null && trim($query) !== '') {
            $like = '%' . trim($query) . '%';
            $sql .= ' AND (
                p.name LIKE :q1
                OR COALESCE(b.name, \'\') LIKE :q2
                OR COALESCE(m.name, \'\') LIKE :q3
                OR COALESCE(pc.code, \'\') LIKE :q4
                OR EXISTS (
                    SELECT 1
                    FROM product_technical_specs pts
                    WHERE pts.product_id = p.id AND pts.value LIKE :q5
                )
            )';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
            $params['q5'] = $like;
        }

        $brands = array_values(array_filter(array_map('trim', $brands), static fn(string $b): bool => $b !== ''));
        if ($brands !== []) {
            $brandPlaceholders = [];
            foreach ($brands as $idx => $brand) {
                $key = ':brand' . $idx;
                $brandPlaceholders[] = $key;
                $params[ltrim($key, ':')] = $brand;
            }
            $sql .= ' AND b.name IN (' . implode(',', $brandPlaceholders) . ')';
        }

        if ($inStockOnly) {
            $sql .= ' AND p.stock > 0';
        }

        $sql .= ' ORDER BY p.id DESC';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Ürün filtreleme + sayfalama (public sayfa için, MSSQL uyumlu)
     * @return array{items: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function filterPaginated(?string $query, ?int $categoryId, array $brands = [], bool $inStockOnly = false, int $page = 1, int $perPage = 15): array
    {
        $where = ' WHERE 1=1';
        $params = [];

        if ($categoryId !== null) {
            [$clause, $categoryParams] = self::categoryInClause($categoryId, 'cat');
            $where .= $clause;
            $params = array_merge($params, $categoryParams);
        }

        if ($query !== null && trim($query) !== '') {
            $like = '%' . trim($query) . '%';
            $where .= ' AND (
                p.name LIKE :q1
                OR COALESCE(b.name, \'\') LIKE :q2
                OR COALESCE(m.name, \'\') LIKE :q3
                OR COALESCE(pc.code, \'\') LIKE :q4
                OR EXISTS (
                    SELECT 1
                    FROM product_technical_specs pts
                    WHERE pts.product_id = p.id AND pts.value LIKE :q5
                )
            )';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
            $params['q5'] = $like;
        }

        $brands = array_values(array_filter(array_map('trim', $brands), static fn(string $b): bool => $b !== ''));
        if ($brands !== []) {
            $brandPlaceholders = [];
            foreach ($brands as $idx => $brand) {
                $key = ':brand' . $idx;
                $brandPlaceholders[] = $key;
                $params[ltrim($key, ':')] = $brand;
            }
            $where .= ' AND b.name IN (' . implode(',', $brandPlaceholders) . ')';
        }

        if ($inStockOnly) {
            $where .= ' AND p.stock > 0';
        }

        // Toplam sayı
        $countSql = 'SELECT COUNT(*) FROM products p
                     LEFT JOIN categories c ON c.id = p.category_id
                     LEFT JOIN brands b ON b.id = p.brand_id
                     LEFT JOIN product_models m ON m.id = p.model_id
                     LEFT JOIN part_codes pc ON pc.id = p.part_code_id' . $where;
        $countStmt = self::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page < 1) $page = 1;
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        if (self::isSqlSrv()) {
            $sql = self::baseSelect() . $where . ' ORDER BY p.id DESC OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = self::baseSelect() . $where . ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';
        }

        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    public static function brandFacets(?int $categoryId, ?string $query = null): array
    {
        $sql = 'SELECT b.name AS brand, COUNT(*) AS total
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN product_models m ON m.id = p.model_id
                LEFT JOIN part_codes pc ON pc.id = p.part_code_id
                WHERE b.name IS NOT NULL';
        $params = [];

        if ($categoryId !== null) {
            [$clause, $categoryParams] = self::categoryInClause($categoryId, 'facet_cat');
            $sql .= $clause;
            $params = array_merge($params, $categoryParams);
        }

        if ($query !== null && trim($query) !== '') {
            $like = '%' . trim($query) . '%';
            $sql .= ' AND (
                p.name LIKE :fq1
                OR b.name LIKE :fq2
                OR COALESCE(m.name, \'\') LIKE :fq3
                OR COALESCE(pc.code, \'\') LIKE :fq4
                OR EXISTS (
                    SELECT 1
                    FROM product_technical_specs pts
                    WHERE pts.product_id = p.id AND pts.value LIKE :fq5
                )
            )';
            $params['fq1'] = $like;
            $params['fq2'] = $like;
            $params['fq3'] = $like;
            $params['fq4'] = $like;
            $params['fq5'] = $like;
        }

        $sql .= ' GROUP BY b.name ORDER BY b.name ASC';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        $facets = [];
        foreach ($stmt->fetchAll() as $row) {
            $facets[] = [
                'brand' => (string) $row['brand'],
                'count' => (int) $row['total'],
            ];
        }

        return $facets;
    }

    public static function create(array $data): int
    {
        $brandId = self::ensureBrandId(self::cleanText($data['brand'] ?? null));
        $modelId = self::ensureModelId(self::cleanText($data['model'] ?? null), $brandId);
        $partCodeId = self::ensurePartCodeId(self::cleanText($data['part_code'] ?? null));

        if (self::isSqlSrv()) {
            $sql = 'INSERT INTO products (name, category_id, brand_id, model_id, part_code_id, price, stock, image_path, created_at, updated_at)
                    VALUES (:name, :category_id, :brand_id, :model_id, :part_code_id, :price, :stock, :image_path, GETDATE(), GETDATE())';
        } else {
            $sql = 'INSERT INTO products (name, category_id, brand_id, model_id, part_code_id, price, stock, image_path, created_at, updated_at)
                    VALUES (:name, :category_id, :brand_id, :model_id, :part_code_id, :price, :stock, :image_path, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
        }
        $stmt = self::pdo()->prepare($sql);
        $stmt->bindValue(':name', (string) $data['name']);
        $stmt->bindValue(':category_id', $data['category_id'], $data['category_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':brand_id', $brandId, $brandId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':model_id', $modelId, $modelId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':part_code_id', $partCodeId, $partCodeId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':price', (float) ($data['price'] ?? 0));
        $stmt->bindValue(':stock', (int) ($data['stock'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':image_path', $data['image_path']);
        $stmt->execute();

        $id = (int) self::pdo()->lastInsertId();
        self::saveTechnicalValues($id, $data['technical'] ?? []);

        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $brandId = self::ensureBrandId(self::cleanText($data['brand'] ?? null));
        $modelId = self::ensureModelId(self::cleanText($data['model'] ?? null), $brandId);
        $partCodeId = self::ensurePartCodeId(self::cleanText($data['part_code'] ?? null));

        if (self::isSqlSrv()) {
            $sql = 'UPDATE products SET
                        name = :name,
                        category_id = :category_id,
                        brand_id = :brand_id,
                        model_id = :model_id,
                        part_code_id = :part_code_id,
                        price = :price,
                        stock = :stock,
                        image_path = :image_path,
                        updated_at = GETDATE()
                    WHERE id = :id';
        } else {
            $sql = 'UPDATE products SET
                        name = :name,
                        category_id = :category_id,
                        brand_id = :brand_id,
                        model_id = :model_id,
                        part_code_id = :part_code_id,
                        price = :price,
                        stock = :stock,
                        image_path = :image_path,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id';
        }
        $stmt = self::pdo()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', (string) $data['name']);
        $stmt->bindValue(':category_id', $data['category_id'], $data['category_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':brand_id', $brandId, $brandId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':model_id', $modelId, $modelId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':part_code_id', $partCodeId, $partCodeId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':price', (float) ($data['price'] ?? 0));
        $stmt->bindValue(':stock', (int) ($data['stock'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':image_path', $data['image_path']);
        $stmt->execute();

        self::saveTechnicalValues($id, $data['technical'] ?? []);
    }

    public static function delete(int $id): void
    {
        $stmt = self::pdo()->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function partCodeExists(?string $partCode, ?int $ignoreId = null): bool
    {
        $partCode = self::cleanText($partCode);
        if ($partCode === null) {
            return false;
        }

        $sql = 'SELECT p.id
                FROM products p
                JOIN part_codes pc ON pc.id = p.part_code_id
                WHERE LOWER(pc.code) = LOWER(:part_code)';
        $params = ['part_code' => $partCode];

        if ($ignoreId !== null) {
            $sql .= ' AND p.id != :id';
            $params['id'] = $ignoreId;
        }

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public static function technicalDefinitions(): array
    {
        $stmt = self::pdo()->query('SELECT id, code, label FROM technical_spec_definitions ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    // --- KDV Hesaplama Metodlari ---

    public static function priceFormatted(float $price): string
    {
        return number_format($price, 2, ',', '.') . ' TL';
    }

    public static function priceWithKdv(float $price): float
    {
        return round($price * (1 + self::KDV_RATE), 2);
    }

    public static function kdvAmount(float $price): float
    {
        return round($price * self::KDV_RATE, 2);
    }

    public static function priceWithKdvFormatted(float $price): string
    {
        return number_format(self::priceWithKdv($price), 2, ',', '.') . ' TL';
    }

    public static function kdvFormatted(float $price): string
    {
        return number_format(self::kdvAmount($price), 2, ',', '.') . ' TL';
    }

    // --- Admin Sayfalama + Arama ---

    /**
     * Admin ürün listesi: sayfalama + arama desteği (MSSQL uyumlu)
     * @return array{items: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function adminPaginated(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $where = '';
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where = ' WHERE (
                p.name LIKE :s1
                OR COALESCE(b.name, \'\') LIKE :s2
                OR COALESCE(m.name, \'\') LIKE :s3
                OR COALESCE(pc.code, \'\') LIKE :s4
            )';
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
            $params['s4'] = $like;
        }

        // Toplam sayı
        $countSql = 'SELECT COUNT(*) FROM products p
                     LEFT JOIN brands b ON b.id = p.brand_id
                     LEFT JOIN product_models m ON m.id = p.model_id
                     LEFT JOIN part_codes pc ON pc.id = p.part_code_id' . $where;
        $countStmt = self::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page < 1) $page = 1;
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        // Ürünleri çek
        if (self::isSqlSrv()) {
            $sql = self::baseSelect() . $where . ' ORDER BY p.id DESC OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = self::baseSelect() . $where . ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';
        }

        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    // --- Envanter Arama ---

    public static function searchInventory(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return self::all();
        }

        $like = '%' . $term . '%';
        $sql = self::baseSelect() . ' WHERE (
            p.name LIKE :q1
            OR COALESCE(pc.code, \'\') LIKE :q2
            OR COALESCE(b.name, \'\') LIKE :q3
            OR COALESCE(m.name, \'\') LIKE :q4
        ) ORDER BY p.name ASC';

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like]);
        return $stmt->fetchAll();
    }

    /**
     * Envanter arama + sayfalama (MSSQL uyumlu)
     * @return array{items: array, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function searchInventoryPaginated(string $term = '', int $page = 1, int $perPage = 20): array
    {
        $where = '';
        $params = [];

        $term = trim($term);
        if ($term !== '') {
            $like = '%' . $term . '%';
            $where = ' WHERE (
                p.name LIKE :q1
                OR COALESCE(pc.code, \'\') LIKE :q2
                OR COALESCE(b.name, \'\') LIKE :q3
                OR COALESCE(m.name, \'\') LIKE :q4
            )';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        $countSql = 'SELECT COUNT(*) FROM products p
                     LEFT JOIN brands b ON b.id = p.brand_id
                     LEFT JOIN product_models m ON m.id = p.model_id
                     LEFT JOIN part_codes pc ON pc.id = p.part_code_id' . $where;
        $countStmt = self::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page < 1) $page = 1;
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        if (self::isSqlSrv()) {
            $sql = self::baseSelect() . $where . ' ORDER BY p.name ASC OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = self::baseSelect() . $where . ' ORDER BY p.name ASC LIMIT :limit OFFSET :offset';
        }

        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    // --- Oneri (Suggestion) ---

    public static function suggestions(string $term, int $limit = 12): array
    {
        $term = trim($term);
        if (mb_strlen($term, 'UTF-8') < 2) {
            return [];
        }

        $pdo = self::pdo();
        $prefix = $term . '%';
        $results = [];
        $seen = [];
        $isSqlSrv = self::isSqlSrv();

        $productLimit = max(1, (int) floor($limit / 3));
        $partCodeLimit = max(1, (int) floor($limit / 4));
        $modelLimit = max(1, (int) floor($limit / 4));
        $brandLimit = max(1, (int) floor(($limit - $productLimit - $partCodeLimit - $modelLimit) / 2));
        $categoryLimit = max(1, $limit - $productLimit - $partCodeLimit - $modelLimit - $brandLimit);

        // Urun aramasi
        if ($isSqlSrv) {
            $productSql = 'SELECT TOP(:limit) p.id, p.name, b.name AS brand
                 FROM products p
                 LEFT JOIN brands b ON b.id = p.brand_id
                 WHERE LOWER(p.name) LIKE LOWER(:prefix)
                 ORDER BY p.name ASC';
        } else {
            $productSql = 'SELECT p.id, p.name, b.name AS brand
                 FROM products p
                 LEFT JOIN brands b ON b.id = p.brand_id
                 WHERE p.name LIKE :prefix
                 ORDER BY p.name ASC
                 LIMIT :limit';
        }
        $productStmt = $pdo->prepare($productSql);
        $productStmt->bindValue(':prefix', $prefix);
        $productStmt->bindValue(':limit', $productLimit, PDO::PARAM_INT);
        $productStmt->execute();
        foreach ($productStmt->fetchAll() as $row) {
            $key = 'product:' . (int) $row['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $results[] = [
                'type' => 'urun',
                'title' => $row['name'],
                'meta' => $row['brand'] ?: 'Marka yok',
                'url' => 'index.php?route=product&id=' . (int) $row['id'],
            ];
        }

        // Parca kodu aramasi
        if ($isSqlSrv) {
            $partCodeSql = 'SELECT TOP(:limit) p.id, p.name, b.name AS brand, pc.code AS part_code
                 FROM products p
                 JOIN part_codes pc ON pc.id = p.part_code_id
                 LEFT JOIN brands b ON b.id = p.brand_id
                 WHERE LOWER(pc.code) LIKE LOWER(:prefix)
                 ORDER BY pc.code ASC';
        } else {
            $partCodeSql = 'SELECT p.id, p.name, b.name AS brand, pc.code AS part_code
                 FROM products p
                 JOIN part_codes pc ON pc.id = p.part_code_id
                 LEFT JOIN brands b ON b.id = p.brand_id
                 WHERE pc.code LIKE :prefix
                 ORDER BY pc.code ASC
                 LIMIT :limit';
        }
        $partCodeStmt = $pdo->prepare($partCodeSql);
        $partCodeStmt->bindValue(':prefix', $prefix);
        $partCodeStmt->bindValue(':limit', $partCodeLimit, PDO::PARAM_INT);
        $partCodeStmt->execute();
        foreach ($partCodeStmt->fetchAll() as $row) {
            $partCode = (string) ($row['part_code'] ?? '');
            if ($partCode === '') {
                continue;
            }

            $key = 'part_code:' . mb_strtolower($partCode, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $meta = trim((string) ($row['name'] ?? ''));
            $brand = trim((string) ($row['brand'] ?? ''));
            if ($brand !== '') {
                $meta .= ' - ' . $brand;
            }

            $results[] = [
                'type' => 'parça kodu',
                'title' => $partCode,
                'meta' => $meta !== '' ? $meta : 'Ürün',
                'url' => 'index.php?route=product&id=' . (int) $row['id'],
            ];
        }

        // Model aramasi
        if ($isSqlSrv) {
            $modelSql = 'SELECT TOP(:limit) m.name, b.name AS brand
                 FROM product_models m
                 LEFT JOIN brands b ON b.id = m.brand_id
                 WHERE LOWER(m.name) LIKE LOWER(:prefix)
                 ORDER BY m.name ASC';
        } else {
            $modelSql = 'SELECT m.name, b.name AS brand
                 FROM product_models m
                 LEFT JOIN brands b ON b.id = m.brand_id
                 WHERE m.name LIKE :prefix
                 ORDER BY m.name ASC
                 LIMIT :limit';
        }
        $modelStmt = $pdo->prepare($modelSql);
        $modelStmt->bindValue(':prefix', $prefix);
        $modelStmt->bindValue(':limit', $modelLimit, PDO::PARAM_INT);
        $modelStmt->execute();
        foreach ($modelStmt->fetchAll() as $row) {
            $model = trim((string) ($row['name'] ?? ''));
            if ($model === '') {
                continue;
            }

            $key = 'model:' . mb_strtolower($model, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $brand = trim((string) ($row['brand'] ?? ''));
            $results[] = [
                'type' => 'model',
                'title' => $model,
                'meta' => $brand !== '' ? $brand : 'Model',
                'url' => 'index.php?route=products&q=' . rawurlencode($model),
            ];
        }

        // Marka aramasi
        if ($isSqlSrv) {
            $brandSql = 'SELECT TOP(:limit) b.name FROM brands b WHERE LOWER(b.name) LIKE LOWER(:prefix) ORDER BY b.name ASC';
        } else {
            $brandSql = 'SELECT b.name FROM brands b WHERE b.name LIKE :prefix ORDER BY b.name ASC LIMIT :limit';
        }
        $brandStmt = $pdo->prepare($brandSql);
        $brandStmt->bindValue(':prefix', $prefix);
        $brandStmt->bindValue(':limit', $brandLimit, PDO::PARAM_INT);
        $brandStmt->execute();
        foreach ($brandStmt->fetchAll() as $row) {
            $brand = (string) $row['name'];
            $key = 'brand:' . mb_strtolower($brand, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $results[] = [
                'type' => 'marka',
                'title' => $brand,
                'meta' => 'Marka',
                'url' => 'index.php?route=products&q=' . rawurlencode($brand),
            ];
        }

        // Kategori aramasi
        if ($isSqlSrv) {
            $categorySql = 'SELECT TOP(:limit) c.id, c.name FROM categories c WHERE LOWER(c.name) LIKE LOWER(:prefix) ORDER BY c.name ASC';
        } else {
            $categorySql = 'SELECT c.id, c.name FROM categories c WHERE c.name LIKE :prefix ORDER BY c.name ASC LIMIT :limit';
        }
        $categoryStmt = $pdo->prepare($categorySql);
        $categoryStmt->bindValue(':prefix', $prefix);
        $categoryStmt->bindValue(':limit', $categoryLimit, PDO::PARAM_INT);
        $categoryStmt->execute();
        foreach ($categoryStmt->fetchAll() as $row) {
            $key = 'category:' . (int) $row['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $results[] = [
                'type' => 'kategori',
                'title' => $row['name'],
                'meta' => 'Kategori',
                'url' => 'index.php?route=products&category=' . (int) $row['id'],
            ];
        }

        return array_slice($results, 0, $limit);
    }

    private static function technicalRows(int $productId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT d.code, d.label, pts.value
             FROM product_technical_specs pts
             JOIN technical_spec_definitions d ON d.id = pts.spec_id
             WHERE pts.product_id = :product_id
             ORDER BY d.id ASC'
        );
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    private static function saveTechnicalValues(int $productId, array $values): void
    {
        $definitions = self::technicalDefinitions();
        $codeToId = [];
        foreach ($definitions as $definition) {
            $codeToId[(string) $definition['code']] = (int) $definition['id'];
        }

        $deleteStmt = self::pdo()->prepare(
            'DELETE FROM product_technical_specs WHERE product_id = :product_id AND spec_id = :spec_id'
        );
        $insertStmt = self::pdo()->prepare(
            'INSERT INTO product_technical_specs (product_id, spec_id, value) VALUES (:product_id, :spec_id, :value)'
        );

        foreach ($codeToId as $code => $specId) {
            $value = self::cleanText($values[$code] ?? null);

            $deleteStmt->execute([
                'product_id' => $productId,
                'spec_id' => $specId,
            ]);

            if ($value !== null) {
                $insertStmt->execute([
                    'product_id' => $productId,
                    'spec_id' => $specId,
                    'value' => $value,
                ]);
            }
        }
    }

    private static function ensureBrandId(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        if (self::isSqlSrv()) {
            $select = self::pdo()->prepare('SELECT TOP 1 id FROM brands WHERE LOWER(name) = LOWER(:name)');
        } else {
            $select = self::pdo()->prepare('SELECT id FROM brands WHERE LOWER(name) = LOWER(:name) LIMIT 1');
        }
        $select->execute(['name' => $name]);
        $existing = $select->fetchColumn();
        if ($existing !== false) {
            return (int) $existing;
        }

        $insert = self::pdo()->prepare('INSERT INTO brands (name) VALUES (:name)');
        $insert->execute(['name' => $name]);

        return (int) self::pdo()->lastInsertId();
    }

    private static function ensureModelId(?string $name, ?int $brandId): ?int
    {
        if ($name === null) {
            return null;
        }

        if ($brandId === null) {
            if (self::isSqlSrv()) {
                $select = self::pdo()->prepare('SELECT TOP 1 id FROM product_models WHERE brand_id IS NULL AND LOWER(name) = LOWER(:name)');
            } else {
                $select = self::pdo()->prepare('SELECT id FROM product_models WHERE brand_id IS NULL AND LOWER(name) = LOWER(:name) LIMIT 1');
            }
            $select->execute(['name' => $name]);
        } else {
            if (self::isSqlSrv()) {
                $select = self::pdo()->prepare('SELECT TOP 1 id FROM product_models WHERE brand_id = :brand_id AND LOWER(name) = LOWER(:name)');
            } else {
                $select = self::pdo()->prepare('SELECT id FROM product_models WHERE brand_id = :brand_id AND LOWER(name) = LOWER(:name) LIMIT 1');
            }
            $select->execute(['brand_id' => $brandId, 'name' => $name]);
        }

        $existing = $select->fetchColumn();
        if ($existing !== false) {
            return (int) $existing;
        }

        $insert = self::pdo()->prepare('INSERT INTO product_models (brand_id, name) VALUES (:brand_id, :name)');
        $insert->bindValue(':brand_id', $brandId, $brandId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $insert->bindValue(':name', $name);
        $insert->execute();

        return (int) self::pdo()->lastInsertId();
    }

    private static function ensurePartCodeId(?string $code): ?int
    {
        if ($code === null) {
            return null;
        }

        if (self::isSqlSrv()) {
            $select = self::pdo()->prepare('SELECT TOP 1 id FROM part_codes WHERE LOWER(code) = LOWER(:code)');
        } else {
            $select = self::pdo()->prepare('SELECT id FROM part_codes WHERE LOWER(code) = LOWER(:code) LIMIT 1');
        }
        $select->execute(['code' => $code]);
        $existing = $select->fetchColumn();
        if ($existing !== false) {
            return (int) $existing;
        }

        $insert = self::pdo()->prepare('INSERT INTO part_codes (code) VALUES (:code)');
        $insert->execute(['code' => $code]);

        return (int) self::pdo()->lastInsertId();
    }

    private static function cleanText(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private static function categoryInClause(int $categoryId, string $prefix): array
    {
        $categoryIds = [$categoryId, ...Category::descendantIds($categoryId)];
        $placeholders = [];
        $params = [];
        foreach ($categoryIds as $idx => $id) {
            $key = ':' . $prefix . $idx;
            $placeholders[] = $key;
            $params[ltrim($key, ':')] = $id;
        }

        return [' AND p.category_id IN (' . implode(',', $placeholders) . ')', $params];
    }
}


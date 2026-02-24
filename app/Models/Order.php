<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class Order
{
    private static bool $columnsEnsured = false;

    public static function create(string $customerName, ?string $customerEmail, ?int $userId, array $items, float $total): int
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO orders (customer_name, customer_email, user_id, total, status, created_at)
                 VALUES (:customer_name, :customer_email, :user_id, :total, :status, CURRENT_TIMESTAMP)'
            );
            $stmt->bindValue(':customer_name', $customerName);
            $stmt->bindValue(':customer_email', $customerEmail, $customerEmail === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':total', $total);
            $stmt->bindValue(':status', 'pending_payment');
            $stmt->execute();

            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, part_code, quantity, unit_price, line_total, created_at)
                                       VALUES (:order_id, :product_id, :product_name, :part_code, :quantity, :unit_price, :line_total, CURRENT_TIMESTAMP)');

            foreach ($items as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => (int) $item['product']['id'],
                    'product_name' => $item['product']['name'],
                    'part_code' => $item['product']['part_code'] ?? null,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['product']['price'],
                    'line_total' => (float) $item['lineTotal'],
                ]);
            }

            $pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function findWithItems(int $id): ?array
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();

        if ($order === false) {
            return null;
        }

        $itemsStmt = $pdo->prepare(
            'SELECT order_id, product_id, product_name, part_code, quantity, unit_price, line_total
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC'
        );
        $itemsStmt->execute(['order_id' => $id]);
        $order['items'] = $itemsStmt->fetchAll();

        return $order;
    }

    public static function markPaymentNotified(int $orderId, string $customerName, ?string $proofPath, ?string $note): void
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        $order = self::findWithItems($orderId);
        if ($order === null) {
            throw new RuntimeException('Siparis bulunamadi.');
        }

        if ((string) $order['status'] === 'paid') {
            throw new RuntimeException('Bu siparis zaten odenmis olarak onayli.');
        }

        $note = trim((string) $note);
        if ($note === '') {
            $note = null;
        }

        $sql = 'UPDATE orders
                SET customer_name = :customer_name,
                    payment_proof_path = :payment_proof_path,
                    payment_note = :payment_note,
                    payment_notified_at = ' . self::nowExpression() . ',
                    status = :status
                WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':customer_name', $customerName);
        $stmt->bindValue(':payment_proof_path', $proofPath, $proofPath === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':payment_note', $note, $note === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', 'payment_notified');
        $stmt->execute();
    }

    public static function approvePayment(int $orderId): void
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        $pdo->beginTransaction();

        try {
            $order = self::findWithItems($orderId);
            if ($order === null) {
                throw new RuntimeException('Siparis bulunamadi.');
            }

            $status = (string) ($order['status'] ?? '');
            if ($status === 'paid') {
                throw new RuntimeException('Bu siparis zaten odeme onayi almis.');
            }
            if (!in_array($status, ['pending_payment', 'payment_notified', 'payment_rejected'], true)) {
                throw new RuntimeException('Bu siparisin odeme durumu onaylanamaz.');
            }

            $stockStmt = $pdo->prepare(
                'UPDATE products
                 SET stock = stock - :quantity,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND stock >= :quantity'
            );

            foreach ($order['items'] as $item) {
                $quantity = (int) $item['quantity'];
                $productId = (int) $item['product_id'];
                $stockStmt->execute([
                    'id' => $productId,
                    'quantity' => $quantity,
                ]);

                if ($stockStmt->rowCount() !== 1) {
                    throw new RuntimeException('Stok yetersiz oldugu icin odeme onaylanamadi.');
                }
            }

            $update = $pdo->prepare(
                'UPDATE orders
                 SET status = :status, paid_at = ' . self::nowExpression() . '
                 WHERE id = :id'
            );
            $update->execute([
                'status' => 'paid',
                'id' => $orderId,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function rejectPayment(int $orderId, ?string $note = null): void
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        $order = self::findWithItems($orderId);
        if ($order === null) {
            throw new RuntimeException('Siparis bulunamadi.');
        }
        if ((string) $order['status'] === 'paid') {
            throw new RuntimeException('Onayli siparis red edilemez.');
        }

        $note = trim((string) $note);
        if ($note === '') {
            $note = null;
        }

        $stmt = $pdo->prepare(
            'UPDATE orders
             SET status = :status, payment_note = :payment_note
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'payment_rejected');
        $stmt->bindValue(':payment_note', $note, $note === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function pendingPaymentOrders(int $limit = 20): array
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare(
            "SELECT id, customer_name, total, status, payment_notified_at, payment_proof_path, payment_note, created_at
             FROM orders
             WHERE status IN ('pending_payment', 'payment_notified', 'payment_rejected')
             ORDER BY id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        if ($orders === []) {
            return [];
        }

        $ids = array_map(static fn(array $o): int => (int) $o['id'], $orders);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $itemsStmt = $pdo->prepare(
            'SELECT order_id, product_name, quantity
             FROM order_items
             WHERE order_id IN (' . $placeholders . ')
             ORDER BY id ASC'
        );
        $itemsStmt->execute($ids);
        $items = $itemsStmt->fetchAll();

        $groupedItems = [];
        foreach ($items as $item) {
            $groupedItems[(int) $item['order_id']][] = $item;
        }

        foreach ($orders as &$order) {
            $order['items'] = $groupedItems[(int) $order['id']] ?? [];
        }
        unset($order);

        return $orders;
    }

    public static function recentWithItems(int $limit = 10): array
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());

        $stmt = $pdo->prepare('SELECT id, customer_name, total, status, created_at FROM orders ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        if ($orders === []) {
            return [];
        }

        $ids = array_map(static fn(array $o): int => (int) $o['id'], $orders);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $itemsStmt = $pdo->prepare('SELECT order_id, product_name, part_code, quantity, unit_price, line_total FROM order_items WHERE order_id IN (' . $placeholders . ') ORDER BY id ASC');
        $itemsStmt->execute($ids);
        $items = $itemsStmt->fetchAll();

        $groupedItems = [];
        foreach ($items as $item) {
            $groupedItems[(int) $item['order_id']][] = $item;
        }

        foreach ($orders as &$order) {
            $order['items'] = $groupedItems[(int) $order['id']] ?? [];
        }
        unset($order);

        return $orders;
    }

    public static function countToday(): int
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        return (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
    }

    private static function nowExpression(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    public static function forUser(int $userId, int $limit = 100): array
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare(
            'SELECT id, customer_name, customer_email, total, status, payment_notified_at, paid_at, created_at
             FROM orders
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        if ($orders === []) {
            return [];
        }

        $ids = array_map(static fn(array $o): int => (int) $o['id'], $orders);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $itemsStmt = $pdo->prepare(
            'SELECT order_id, product_name, part_code, quantity, unit_price, line_total
             FROM order_items
             WHERE order_id IN (' . $placeholders . ')
             ORDER BY id ASC'
        );
        $itemsStmt->execute($ids);
        $items = $itemsStmt->fetchAll();

        $groupedItems = [];
        foreach ($items as $item) {
            $groupedItems[(int) $item['order_id']][] = $item;
        }
        foreach ($orders as &$order) {
            $order['items'] = $groupedItems[(int) $order['id']] ?? [];
        }
        unset($order);

        return $orders;
    }

    public static function adminRowsPaginated(array $filters, int $page = 1, int $perPage = 50): array
    {
        self::ensureColumns();

        $pdo = Database::connection(App::config());
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $search = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        $allowedStatuses = ['pending_payment', 'payment_notified', 'payment_rejected', 'paid'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }
        if ($dateFrom !== '' && $dateTo !== '' && strcmp($dateFrom, $dateTo) > 0) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $dateExpr = self::dateOnlyExpression('o.created_at');
        $whereParts = [];
        $params = [];

        if ($search !== '') {
            $searchLike = '%' . $search . '%';
            $searchParts = [
                'o.customer_name LIKE :search_customer_name',
                'o.customer_email LIKE :search_customer_email',
                'oi.product_name LIKE :search_product_name',
                'oi.part_code LIKE :search_part_code',
            ];
            $params['search_customer_name'] = $searchLike;
            $params['search_customer_email'] = $searchLike;
            $params['search_product_name'] = $searchLike;
            $params['search_part_code'] = $searchLike;

            if (ctype_digit($search)) {
                $searchParts[] = 'o.id = :search_order_id';
                $params['search_order_id'] = (int) $search;
            }

            $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        if ($status !== '') {
            $whereParts[] = 'o.status = :status';
            $params['status'] = $status;
        }

        if ($dateFrom !== '') {
            $whereParts[] = $dateExpr . ' >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== '') {
            $whereParts[] = $dateExpr . ' <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $whereSql = $whereParts === [] ? '' : 'WHERE ' . implode(' AND ', $whereParts);
        $baseFrom = ' FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id ';

        $countSql = 'SELECT COUNT(*)' . $baseFrom . $whereSql;
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $countStmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $countStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $selectSql = 'SELECT
                o.id AS order_id,
                o.customer_name,
                o.customer_email,
                o.status,
                o.total AS order_total,
                o.created_at AS order_created_at,
                o.payment_notified_at,
                o.paid_at,
                o.payment_note,
                o.payment_proof_path,
                oi.product_id,
                oi.product_name,
                oi.part_code,
                oi.quantity,
                oi.unit_price,
                oi.line_total'
            . $baseFrom
            . $whereSql
            . ' ORDER BY o.id DESC, oi.id ASC ';

        $selectSql .= 'LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($selectSql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ];
    }

    private static function dateOnlyExpression(string $column): string
    {
        return 'DATE(' . $column . ')';
    }

    private static function ensureColumns(): void
    {
        if (self::$columnsEnsured) {
            return;
        }

        $pdo = Database::connection(App::config());
        $exists = static function (string $column) use ($pdo): bool {
            $stmt = $pdo->prepare('SHOW COLUMNS FROM orders LIKE :column');
            $stmt->execute(['column' => $column]);
            return (bool) $stmt->fetch();
        };

        $adds = [];
        if (!$exists('customer_email')) {
            $adds[] = 'ADD COLUMN customer_email VARCHAR(190) NULL';
        }
        if (!$exists('user_id')) {
            $adds[] = 'ADD COLUMN user_id INT NULL';
        }
        if (!$exists('status')) {
            $adds[] = "ADD COLUMN status VARCHAR(40) NOT NULL DEFAULT 'pending_payment'";
        }
        if (!$exists('payment_notified_at')) {
            $adds[] = 'ADD COLUMN payment_notified_at DATETIME NULL';
        }
        if (!$exists('payment_note')) {
            $adds[] = 'ADD COLUMN payment_note TEXT NULL';
        }
        if (!$exists('payment_proof_path')) {
            $adds[] = 'ADD COLUMN payment_proof_path VARCHAR(255) NULL';
        }
        if (!$exists('paid_at')) {
            $adds[] = 'ADD COLUMN paid_at DATETIME NULL';
        }

        if ($adds !== []) {
            $pdo->exec('ALTER TABLE orders ' . implode(', ', $adds));
        }

        self::$columnsEnsured = true;
    }
}

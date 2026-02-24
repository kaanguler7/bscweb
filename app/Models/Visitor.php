<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;

final class Visitor
{
    public static function logDailyVisit(string $sessionId): void
    {
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('INSERT IGNORE INTO daily_visitors (visit_date, session_id, created_at) VALUES (CURDATE(), :session_id, CURRENT_TIMESTAMP)');
        $stmt->execute(['session_id' => $sessionId]);
    }

    public static function countToday(): int
    {
        $pdo = Database::connection(App::config());
        return (int) $pdo->query('SELECT COUNT(*) FROM daily_visitors WHERE visit_date = CURDATE()')->fetchColumn();
    }
}

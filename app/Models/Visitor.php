<?php

namespace App\Models;

use App\Core\App;
use App\Core\Database;

final class Visitor
{
    public static function shouldCountPageView(string $route, string $method, string $userAgent, string $accept, bool $isAdmin): bool
    {
        if ($isAdmin || strtoupper($method) !== 'GET') {
            return false;
        }

        if (!in_array($route, ['home', 'products', 'product', 'about', 'contact'], true)) {
            return false;
        }

        if ($userAgent === '' || stripos($accept, 'text/html') === false) {
            return false;
        }

        return preg_match('/bot|crawl|spider|slurp|scrap|preview|monitor|lighthouse|headless|curl|wget|python|postman|http-client|axios|facebookexternalhit|telegram|whatsapp|uptime|pingdom/i', $userAgent) !== 1;
    }

    public static function logDailyVisit(string $visitorId): void
    {
        $visitDate = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Istanbul')))->format('Y-m-d');
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('INSERT IGNORE INTO daily_public_visitors (visit_date, visitor_hash, created_at) VALUES (:visit_date, :visitor_hash, CURRENT_TIMESTAMP)');
        $stmt->execute([
            'visit_date' => $visitDate,
            'visitor_hash' => hash('sha256', $visitorId),
        ]);
    }

    public static function countToday(): int
    {
        $visitDate = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Istanbul')))->format('Y-m-d');
        $pdo = Database::connection(App::config());
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM daily_public_visitors WHERE visit_date = :visit_date');
        $stmt->execute(['visit_date' => $visitDate]);

        return (int) $stmt->fetchColumn();
    }
}

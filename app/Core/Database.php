<?php

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(array $config): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $db = $config['db'];
        $connection = strtolower((string) ($db['connection'] ?? 'mysql'));

        if ($connection !== 'mysql') {
            throw new RuntimeException(
                'Bu proje yalnizca MySQL ile calisir. .env dosyanizda DB_CONNECTION=mysql olmalidir.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) ($db['host'] ?? '127.0.0.1'),
            (int) ($db['port'] ?? 3306),
            (string) ($db['database'] ?? 'bsc_elektronik')
        );

        self::$pdo = new PDO($dsn, (string) ($db['username'] ?? 'root'), (string) ($db['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$pdo;
    }

    public static function driverName(): string
    {
        $pdo = self::connection(App::config());
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function isSqlSrv(): bool
    {
        return false;
    }
}

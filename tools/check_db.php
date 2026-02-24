<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (is_file(dirname(__DIR__) . '/.env')) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

$host = (string) ($_ENV['DB_HOST'] ?? '127.0.0.1');
$port = (int) ($_ENV['DB_PORT'] ?? '3306');
$database = (string) ($_ENV['DB_DATABASE'] ?? 'bsc_elektronik');
$username = (string) ($_ENV['DB_USERNAME'] ?? 'root');
$password = (string) ($_ENV['DB_PASSWORD'] ?? '');

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo 'products=' . (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() . PHP_EOL;

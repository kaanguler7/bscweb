<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @return array<string, string>
 */
function parseCliOptions(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (!str_starts_with($arg, '--')) {
            continue;
        }

        $raw = substr($arg, 2);
        if ($raw === '') {
            continue;
        }

        $parts = explode('=', $raw, 2);
        $key = trim((string) ($parts[0] ?? ''));
        if ($key === '') {
            continue;
        }

        $options[$key] = isset($parts[1]) ? (string) $parts[1] : '1';
    }

    return $options;
}

function indexExists(PDO $pdo, string $schema, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM information_schema.statistics
         WHERE table_schema = :schema
           AND table_name = :table_name
           AND index_name = :index_name
         LIMIT 1'
    );
    $stmt->execute([
        'schema' => $schema,
        'table_name' => $table,
        'index_name' => $index,
    ]);

    return (bool) $stmt->fetchColumn();
}

$basePath = dirname(__DIR__);
if (is_file($basePath . '/.env')) {
    Dotenv::createImmutable($basePath)->safeLoad();
}

$options = parseCliOptions($argv);
if (isset($options['help'])) {
    echo "MySQL performans indekslerini ekler.\n";
    echo "Kullanim:\n";
    echo "  php database/add_mysql_indexes.php\n";
    echo "  php database/add_mysql_indexes.php --host=127.0.0.1 --port=3306 --database=bsc_elektronik --username=root --password=\n";
    exit(0);
}

$host = (string) ($options['host'] ?? ($_ENV['DB_HOST'] ?? '127.0.0.1'));
$port = (int) ($options['port'] ?? ($_ENV['DB_PORT'] ?? '3306'));
$database = (string) ($options['database'] ?? ($_ENV['DB_DATABASE'] ?? 'bsc_elektronik'));
$username = (string) ($options['username'] ?? ($_ENV['DB_USERNAME'] ?? 'root'));
$password = (string) ($options['password'] ?? ($_ENV['DB_PASSWORD'] ?? ''));
$connection = strtolower((string) ($_ENV['DB_CONNECTION'] ?? ''));

if ($connection !== '' && $connection !== 'mysql') {
    fwrite(STDERR, "Hata: DB_CONNECTION=mysql olmali. Mevcut deger: {$connection}\n");
    exit(1);
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('SET NAMES utf8mb4');

    $indexes = [
        [
            'table' => 'products',
            'name' => 'idx_products_name',
            'sql' => 'CREATE INDEX idx_products_name ON products (name)',
        ],
        [
            'table' => 'product_models',
            'name' => 'idx_product_models_name',
            'sql' => 'CREATE INDEX idx_product_models_name ON product_models (name)',
        ],
    ];

    $createdCount = 0;
    $skippedCount = 0;

    foreach ($indexes as $index) {
        $table = (string) $index['table'];
        $name = (string) $index['name'];
        $sql = (string) $index['sql'];

        if (indexExists($pdo, $database, $table, $name)) {
            echo "[SKIP] {$name} zaten var.\n";
            $skippedCount++;
            continue;
        }

        $pdo->exec($sql);
        echo "[OK] {$name} olusturuldu.\n";
        $createdCount++;
    }

    echo "Tamamlandi. Olusturulan: {$createdCount}, Atlanan: {$skippedCount}\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Hata: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

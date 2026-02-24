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
        $value = isset($parts[1]) ? (string) $parts[1] : '1';
        $options[$key] = $value;
    }

    return $options;
}

/**
 * @return array<int, string>
 */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;
    $prev = '';

    for ($i = 0; $i < $length; $i++) {
        $ch = $sql[$i];
        $next = ($i + 1 < $length) ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($ch === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($ch === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick) {
            if ($ch === '-' && $next === '-') {
                $inLineComment = true;
                $i++;
                continue;
            }
            if ($ch === '#') {
                $inLineComment = true;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
        }

        if ($ch === "'" && !$inDouble && !$inBacktick && $prev !== '\\') {
            $inSingle = !$inSingle;
        } elseif ($ch === '"' && !$inSingle && !$inBacktick && $prev !== '\\') {
            $inDouble = !$inDouble;
        } elseif ($ch === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
        }

        if ($ch === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            $prev = '';
            continue;
        }

        $buffer .= $ch;
        $prev = $ch;
    }

    $last = trim($buffer);
    if ($last !== '') {
        $statements[] = $last;
    }

    return $statements;
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function stripUtf8Bom(string $text): string
{
    if (str_starts_with($text, "\xEF\xBB\xBF")) {
        return substr($text, 3);
    }

    return $text;
}

$basePath = dirname(__DIR__);
if (is_file($basePath . '/.env')) {
    Dotenv::createImmutable($basePath)->safeLoad();
}

$options = parseCliOptions($argv);
if (isset($options['help'])) {
    echo "MySQL veritabanini hazirlama scripti\n";
    echo "Kullanim:\n";
    echo "  php database/init_mysql.php [--with-seed=1]\n";
    echo "  php database/init_mysql.php --host=127.0.0.1 --port=3306 --database=bsc_elektronik --username=root --password=\n";
    exit(0);
}

$host = (string) ($options['host'] ?? ($_ENV['DB_HOST'] ?? '127.0.0.1'));
$port = (int) ($options['port'] ?? ($_ENV['DB_PORT'] ?? '3306'));
$database = (string) ($options['database'] ?? ($_ENV['DB_DATABASE'] ?? 'bsc_elektronik'));
$username = (string) ($options['username'] ?? ($_ENV['DB_USERNAME'] ?? 'root'));
$password = (string) ($options['password'] ?? ($_ENV['DB_PASSWORD'] ?? ''));
$withSeed = (($options['with-seed'] ?? '0') === '1');

try {
    $serverDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
    $serverPdo = new PDO($serverDsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $serverPdo->exec(
        'CREATE DATABASE IF NOT EXISTS ' . quoteIdentifier($database) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $dbDsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);
    $pdo = new PDO($dbDsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('SET NAMES utf8mb4');

    $schemaSql = (string) file_get_contents($basePath . '/database/schema.sql');
    $schemaSql = stripUtf8Bom($schemaSql);
    $schemaStatements = splitSqlStatements($schemaSql);
    foreach ($schemaStatements as $statement) {
        $pdo->exec($statement);
    }

    if ($withSeed) {
        $seedSql = (string) file_get_contents($basePath . '/database/seed.sql');
        $seedSql = stripUtf8Bom($seedSql);
        $seedStatements = splitSqlStatements($seedSql);
        foreach ($seedStatements as $statement) {
            $pdo->exec($statement);
        }
    }

    $adminCheck = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = :username');
    $adminCheck->execute(['username' => 'admin']);
    $adminExists = (int) $adminCheck->fetchColumn() > 0;
    if (!$adminExists) {
        $adminInsert = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:username, :password_hash)');
        $adminInsert->execute([
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        ]);
    }

    echo "MySQL veritabani hazirlandi.\n";
    echo "Host: {$host}:{$port}\n";
    echo "Veritabani: {$database}\n";
    echo 'Demo seed: ' . ($withSeed ? 'evet' : 'hayir') . "\n";
    echo "Admin kullanici: admin / admin123\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Hata: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

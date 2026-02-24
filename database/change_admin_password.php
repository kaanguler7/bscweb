<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Database;
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

$basePath = dirname(__DIR__);
if (is_file($basePath . '/.env')) {
    Dotenv::createImmutable($basePath)->safeLoad();
}

$options = parseCliOptions($argv);
if (isset($options['help'])) {
    echo "Admin sifresi guncelleme araci\n";
    echo "Kullanim:\n";
    echo "  php database/change_admin_password.php --username=admin --password=YeniSifre\n";
    exit(0);
}

$username = trim((string) ($options['username'] ?? 'admin'));
$password = (string) ($options['password'] ?? '');

if ($username === '' || $password === '') {
    fwrite(STDERR, "Hata: username ve password zorunludur.\n");
    fwrite(STDERR, "Ornek: php database/change_admin_password.php --username=admin --password=YeniSifre\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Hata: sifre en az 8 karakter olmalidir.\n");
    exit(1);
}

try {
    $config = require $basePath . '/config.php';
    App::setConfig($config);
    $pdo = Database::connection($config);

    $checkStmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = :username LIMIT 1');
    $checkStmt->execute(['username' => $username]);
    $row = $checkStmt->fetch(\PDO::FETCH_ASSOC);

    if ($row === false) {
        fwrite(STDERR, "Hata: '{$username}' kullanicisi bulunamadi.\n");
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE username = :username');
    $updateStmt->execute([
        'password_hash' => $hash,
        'username' => $username,
    ]);

    echo "Admin sifresi guncellendi. Kullanici: {$username}\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Hata: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

<?php

declare(strict_types=1);

$php = dirname(__DIR__) . '/tools/php/php.exe';
$runner = dirname(__DIR__) . '/tests/route_runner.php';
$routes = ['home', 'products', 'admin/login'];

$initCommand = escapeshellarg($php) . ' ' . escapeshellarg(dirname(__DIR__) . '/database/init_mysql.php');
exec($initCommand, $initOutput, $initCode);
if ($initCode !== 0) {
    fwrite(STDERR, "Veritabani init basarisiz\n");
    exit(1);
}

foreach ($routes as $route) {
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($runner) . ' ' . escapeshellarg($route);
    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "Route test basarisiz: {$route}\n");
        exit(1);
    }

    $length = isset($output[0]) ? (int) $output[0] : 0;
    if ($length < 200) {
        fwrite(STDERR, "Route icerigi cok kisa: {$route}\n");
        exit(1);
    }

    $output = [];
}

echo "ROUTE_TEST_OK\n";

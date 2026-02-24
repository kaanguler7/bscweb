<?php

declare(strict_types=1);

$route = $argv[1] ?? 'home';
$_GET['route'] = $route;
$_SERVER['REQUEST_METHOD'] = 'GET';
putenv('DB_CONNECTION=mysql');
$_ENV['DB_CONNECTION'] = 'mysql';

ob_start();
include dirname(__DIR__) . '/index.php';
$out = ob_get_clean();

echo strlen($out);

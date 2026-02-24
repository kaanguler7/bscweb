<?php

declare(strict_types=1);

use App\Core\App;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$config = require __DIR__ . '/../config.php';
App::setConfig($config);

$phpPath = dirname(__DIR__) . '/tools/php/php.exe';
$initCommand = escapeshellarg($phpPath) . ' ' . escapeshellarg(dirname(__DIR__) . '/database/init_mysql.php');
exec($initCommand, $initOutput, $initCode);
if ($initCode !== 0) {
    fwrite(STDERR, "init_mysql calismadi\n");
    exit(1);
}

$email = 'test.user.' . random_int(100000, 999999) . '@example.com';
$userId = User::create('Test Kullanici', $email, 'Test1234!');
$user = User::verifyCredentials($email, 'Test1234!');
if ($user === null || (int) $user['id'] !== $userId) {
    fwrite(STDERR, "Kullanici dogrulama basarisiz\n");
    exit(1);
}

$product = Product::all()[0] ?? null;
if ($product === null) {
    $productId = Product::create([
        'name' => 'User Flow Test Kart',
        'part_code' => null,
        'brand' => null,
        'model' => null,
        'category_id' => null,
        'price' => 1200.00,
        'stock' => 10,
        'image_path' => null,
        'technical' => [],
    ]);
    $product = Product::find($productId);
}
if ($product === null) {
    fwrite(STDERR, "Urun kaydi bulunamadi\n");
    exit(1);
}

Wishlist::add($userId, (int) $product['id']);
if (!Wishlist::exists($userId, (int) $product['id'])) {
    fwrite(STDERR, "Favori ekleme basarisiz\n");
    exit(1);
}

$orderId = Order::create('Test Kullanici', $email, $userId, [[
    'product' => $product,
    'quantity' => 1,
    'lineTotal' => (float) $product['price'],
]], (float) $product['price']);

$orders = Order::forUser($userId);
if ($orders === [] || (int) $orders[0]['id'] !== $orderId) {
    fwrite(STDERR, "Kullanici siparisleri listelenemedi\n");
    exit(1);
}

echo "USER_FLOW_TEST_OK\n";

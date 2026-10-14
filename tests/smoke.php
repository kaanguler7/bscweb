<?php

declare(strict_types=1);

use App\Core\App;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Visitor;
use App\Services\ProductImageService;
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

$newCategoryName = 'KategoriTest' . random_int(100, 999);
$newCategoryId = Category::create($newCategoryName);
if (Category::find($newCategoryId) === null) {
    fwrite(STDERR, "Kategori create basarisiz\n");
    exit(1);
}
Category::update($newCategoryId, $newCategoryName . '-Guncel');
if (Category::hasProducts($newCategoryId)) {
    fwrite(STDERR, "Bos kategori urun iceriyor gorundu\n");
    exit(1);
}

$imageService = new ProductImageService();
$tmpImage = __DIR__ . '/tmp-upload.jpg';
copy(dirname(__DIR__) . '/assets/img/product/home-one/product-10.jpg', $tmpImage);

$storedPath = $imageService->storeUploadedImage([
    'error' => UPLOAD_ERR_OK,
    'tmp_name' => $tmpImage,
]);

if (!is_file(dirname(__DIR__) . '/' . $storedPath)) {
    fwrite(STDERR, "Yuklenen fotograf olusmadi\n");
    exit(1);
}

$newId = Product::create([
    'name' => 'CLI Test Kart',
    'part_code' => null,
    'brand' => null,
    'model' => null,
    'category_id' => null,
    'price' => 0,
    'stock' => 0,
    'image_path' => $storedPath,
    'technical' => [
        'part_type' => 'Main Board',
        'supplier_code' => 'CLI-' . random_int(100, 999),
    ],
]);

$created = Product::find($newId);
if ($created === null) {
    fwrite(STDERR, "Urun create basarisiz\n");
    exit(1);
}

Product::update($newId, [
    'name' => 'CLI Test Kart Guncel',
    'part_code' => 'CLI-TEST-' . random_int(1000, 9999),
    'brand' => 'CLI',
    'model' => 'T2',
    'category_id' => 1,
    'price' => 1299.99,
    'stock' => 4,
    'image_path' => $storedPath,
    'technical' => [
        'part_type' => 'Power Board',
        'part_number' => 'CLI-PN-1',
    ],
]);

$updated = Product::find($newId);
if ($updated === null || (string) $updated['name'] !== 'CLI Test Kart Guncel' || (int) $updated['stock'] !== 4) {
    fwrite(STDERR, "Urun update basarisiz\n");
    exit(1);
}

$suggestions = Product::suggestions('CLI', 10);
if ($suggestions === []) {
    fwrite(STDERR, "Arama onerisi bos dondu\n");
    exit(1);
}

$orderId = Order::create('Smoke Tester', 'smoke@example.com', null, [[
    'product' => $updated,
    'quantity' => 1,
    'lineTotal' => (float) $updated['price'],
]], (float) $updated['price']);

if ($orderId <= 0 || Order::countToday() < 1) {
    fwrite(STDERR, "Siparis kaydi basarisiz\n");
    exit(1);
}

Visitor::logDailyVisit('smoke-session-' . random_int(1000, 9999));
if (Visitor::countToday() < 1) {
    fwrite(STDERR, "Ziyaretci sayaci basarisiz\n");
    exit(1);
}

$imageService->deleteImage($storedPath);
if (is_file(dirname(__DIR__) . '/' . $storedPath)) {
    fwrite(STDERR, "Fotograf delete basarisiz\n");
    exit(1);
}

Category::delete($newCategoryId);
@unlink($tmpImage);

echo "SMOKE_TEST_OK\n";

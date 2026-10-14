<?php

declare(strict_types=1);

use App\Controllers\ProductController;
use App\Support\ProductCode;
use App\Support\ProductSeo;

require __DIR__ . '/../vendor/autoload.php';

$product = [
    'id' => 999,
    'name' => 'Vestel 42PF7014 Power Board',
    'part_code' => "17PW26\u{2011}4",
    'brand' => 'Vestel',
    'model' => '42PF7014',
    'category_name' => 'Power Board',
    'price' => 800,
    'stock' => 1,
    'image_path' => 'storage/uploads/example.webp',
    'technical' => [
        'supplier_code' => '20453122',
        'part_number' => '17PW26-4',
    ],
];

seoAssertSame('17PW26-4', ProductCode::requireValid(" 17PW26\u{2013}4 "), 'Unicode dash normalization');
seoAssertThrows(static fn() => ProductCode::requireValid(''), 'Empty part code rejected');
seoAssertThrows(static fn() => ProductCode::requireValid('17PW26-4, 20453122'), 'Multiple primary codes rejected');

$displayName = ProductSeo::displayName($product);
$pageTitle = ProductSeo::pageTitle($product);
$description = ProductSeo::metaDescription($product);
$codes = ProductSeo::codes($product);

seoAssertSame('17PW26-4, Vestel 42PF7014 Power Board', $displayName, 'Display name starts with primary code');
seoAssertTrue(str_starts_with($pageTitle, '17PW26-4 '), 'Page title starts with primary code');
seoAssertTrue(mb_strlen($pageTitle, 'UTF-8') <= 70, 'Page title length');
seoAssertTrue(str_starts_with($description, '17PW26-4,'), 'Meta description starts with primary code');
seoAssertTrue(mb_strlen($description, 'UTF-8') <= 160, 'Meta description length');
$describedProduct = $product;
$describedProduct['description'] = "Orijinal Vestel güç kartı.\nTest edilmiş ürün.";
seoAssertSame('Orijinal Vestel güç kartı. Test edilmiş ürün.', ProductSeo::metaDescription($describedProduct), 'Verified product description is used for meta description');
seoAssertSame('17PW26-4', $codes[0] ?? null, 'Primary code is first SEO code');

$controller = new ProductController(['app_url' => 'https://www.bscelektronik.com']);
$method = (new ReflectionClass($controller))->getMethod('buildProductJsonLd');
$method->setAccessible(true);
$json = (string) $method->invoke(
    $controller,
    $product,
    $codes,
    'https://www.bscelektronik.com/index.php?route=product&id=999',
    'https://www.bscelektronik.com/storage/uploads/example.webp',
    $description,
    $displayName
);
$schema = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

seoAssertSame('Product', $schema['@type'] ?? null, 'Product schema type');
seoAssertSame('17PW26-4', $schema['sku'] ?? null, 'Product schema SKU');
seoAssertSame($displayName, $schema['name'] ?? null, 'Product schema name');
seoAssertSame('960.00', $schema['offers']['price'] ?? null, 'KDV dahil Product offer price');

$zeroPriceProduct = $product;
$zeroPriceProduct['price'] = 0;
$zeroPriceJson = (string) $method->invoke(
    $controller,
    $zeroPriceProduct,
    $codes,
    'https://www.bscelektronik.com/index.php?route=product&id=999',
    'https://www.bscelektronik.com/storage/uploads/example.webp',
    $description,
    $displayName
);
$zeroPriceSchema = json_decode($zeroPriceJson, true, 512, JSON_THROW_ON_ERROR);
seoAssertTrue(!isset($zeroPriceSchema['offers']), 'Zero price does not publish a false offer');

echo "PRODUCT_SEO_TEST_OK\n";

function seoAssertSame(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $label . " failed\n");
        exit(1);
    }
}

function seoAssertTrue(bool $condition, string $label): void
{
    if (!$condition) {
        fwrite(STDERR, $label . " failed\n");
        exit(1);
    }
}

function seoAssertThrows(callable $callback, string $label): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }

    fwrite(STDERR, $label . " failed\n");
    exit(1);
}

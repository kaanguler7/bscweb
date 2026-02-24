<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UserAuth;
use App\Models\Category;
use App\Models\Product;
use App\Models\Wishlist;

final class ProductController extends Controller
{
    public function index(): void
    {
        $query = $_GET['q'] ?? null;
        $category = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
        $selectedBrands = $_GET['brands'] ?? [];
        if (!is_array($selectedBrands)) {
            $selectedBrands = [$selectedBrands];
        }
        $selectedBrands = array_values(array_filter(array_map(static fn($v): string => trim((string) $v), $selectedBrands), static fn(string $v): bool => $v !== ''));

        $inStockOnly = isset($_GET['in_stock']) && (string) $_GET['in_stock'] === '1';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $brandFacets = Product::brandFacets($category, $query);
        $categoryBreadcrumb = $category !== null ? Category::breadcrumb($category) : [];
        $paginated = Product::filterPaginated($query, $category, $selectedBrands, $inStockOnly, $page, 15);
        $userId = UserAuth::id();
        $pageTitle = 'Urunler | BSC Elektronik';
        $metaDescription = 'TV yedek parca urunleri: anakart, power board, led bar, t-con ve panel parcasi arama.';

        $qTrimmed = trim((string) $query);
        if ($qTrimmed !== '') {
            $pageTitle = $qTrimmed . ' | BSC Elektronik Urunler';
            $metaDescription = $qTrimmed . ' aramaniza uygun TV yedek parcalarini BSC Elektronik urun sayfasinda inceleyin.';
        }

        $canonicalParams = [];
        if ($qTrimmed !== '') {
            $canonicalParams['q'] = $qTrimmed;
        }
        if ($category !== null) {
            $canonicalParams['category'] = $category;
        }
        if ($page > 1) {
            $canonicalParams['page'] = $page;
        }
        $canonicalUrl = $this->buildAbsoluteUrl('products', $canonicalParams);

        $this->view('product/index', [
            'pageTitle' => $pageTitle,
            'products' => $paginated['items'],
            'pagination' => $paginated,
            'categories' => Category::flatForSelect(),
            'query' => $query,
            'selectedCategory' => $category,
            'selectedBrands' => $selectedBrands,
            'brandFacets' => $brandFacets,
            'inStockOnly' => $inStockOnly,
            'categoryBreadcrumb' => $categoryBreadcrumb,
            'favoriteIds' => $userId !== null ? Wishlist::productIdsForUser($userId) : [],
            'metaDescription' => $metaDescription,
            'canonicalUrl' => $canonicalUrl,
        ]);
    }

    public function show(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $product = Product::find($id);

        if ($product === null) {
            http_response_code(404);
            echo 'Urun bulunamadi.';
            return;
        }

        $userId = UserAuth::id();
        $seoCodes = $this->extractSeoCodes($product);
        $canonicalUrl = $this->buildAbsoluteUrl('product', ['id' => (int) $product['id']]);
        $imagePath = trim((string) ($product['image_path'] ?? ''));
        if ($imagePath === '') {
            $imagePath = 'assets/img/product/home-one/product-1.jpg';
        }
        $ogImage = $this->buildAbsoluteAssetUrl($imagePath);
        $metaDescription = $this->buildProductMetaDescription($product, $seoCodes);
        $metaKeywords = implode(', ', array_slice($seoCodes, 0, 14));
        $jsonLd = $this->buildProductJsonLd($product, $seoCodes, $canonicalUrl, $ogImage);

        $this->view('product/show', [
            'pageTitle' => $product['name'] . ' | BSC Elektronik',
            'product' => $product,
            'categories' => Category::flatForSelect(),
            'isFavorite' => $userId !== null ? Wishlist::exists($userId, (int) $product['id']) : false,
            'seoCodes' => $seoCodes,
            'metaDescription' => $metaDescription,
            'metaKeywords' => $metaKeywords,
            'canonicalUrl' => $canonicalUrl,
            'ogImage' => $ogImage,
            'ogType' => 'product',
            'jsonLd' => $jsonLd,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function extractSeoCodes(array $product): array
    {
        $candidates = [];
        $name = trim((string) ($product['name'] ?? ''));
        $partCode = trim((string) ($product['part_code'] ?? ''));
        $model = trim((string) ($product['model'] ?? ''));
        $brand = trim((string) ($product['brand'] ?? ''));
        $technical = isset($product['technical']) && is_array($product['technical']) ? $product['technical'] : [];

        if ($name !== '') {
            $candidates[] = $name;
            foreach (preg_split('/\s*[,;|]+\s*/u', $name) ?: [] as $segment) {
                $segment = trim((string) $segment);
                if ($segment !== '') {
                    $candidates[] = $segment;
                }
            }
        }
        if ($partCode !== '') {
            $candidates[] = $partCode;
        }
        if ($model !== '') {
            $candidates[] = $model;
        }
        if ($brand !== '') {
            $candidates[] = $brand;
        }

        foreach (['part_number', 'supplier_code', 'tv_panel_model', 'known_brand_model'] as $codeKey) {
            $value = trim((string) ($technical[$codeKey] ?? ''));
            if ($value !== '') {
                $candidates[] = $value;
            }
        }

        $unique = [];
        $seen = [];
        foreach ($candidates as $candidate) {
            $candidate = preg_replace('/\s+/u', ' ', trim((string) $candidate));
            if ($candidate === null || $candidate === '' || mb_strlen($candidate, 'UTF-8') < 2) {
                continue;
            }

            $normalized = mb_strtolower($candidate, 'UTF-8');
            if (isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $unique[] = $candidate;

            if (count($unique) >= 24) {
                break;
            }
        }

        return $unique;
    }

    /**
     * @param array<int, string> $seoCodes
     */
    private function buildProductMetaDescription(array $product, array $seoCodes): string
    {
        $name = trim((string) ($product['name'] ?? ''));
        $brand = trim((string) ($product['brand'] ?? ''));
        $model = trim((string) ($product['model'] ?? ''));
        $codesText = implode(', ', array_slice($seoCodes, 0, 4));

        $parts = [];
        if ($name !== '') {
            $parts[] = $name;
        }
        if ($brand !== '') {
            $parts[] = $brand;
        }
        if ($model !== '') {
            $parts[] = $model;
        }

        $description = trim(implode(' - ', $parts));
        if ($codesText !== '') {
            $description .= '. Uyumlu kodlar: ' . $codesText . '.';
        }
        $description .= ' BSC Elektronik urun detaylari ve siparis secenekleri.';

        if (mb_strlen($description, 'UTF-8') > 300) {
            $description = mb_substr($description, 0, 297, 'UTF-8') . '...';
        }

        return trim($description);
    }

    /**
     * @param array<int, string> $seoCodes
     */
    private function buildProductJsonLd(array $product, array $seoCodes, string $canonicalUrl, string $ogImage): string
    {
        $technical = isset($product['technical']) && is_array($product['technical']) ? $product['technical'] : [];
        $partNumber = trim((string) ($technical['part_number'] ?? ''));
        $supplierCode = trim((string) ($technical['supplier_code'] ?? ''));
        $partCode = trim((string) ($product['part_code'] ?? ''));
        $sku = $partCode !== '' ? $partCode : ($supplierCode !== '' ? $supplierCode : null);
        $mpn = $partNumber !== '' ? $partNumber : ($partCode !== '' ? $partCode : null);
        $availability = ((int) ($product['stock'] ?? 0) > 0)
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';
        $price = number_format((float) ($product['price'] ?? 0), 2, '.', '');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => (string) ($product['name'] ?? ''),
            'url' => $canonicalUrl,
            'image' => $ogImage,
            'brand' => [
                '@type' => 'Brand',
                'name' => (string) ($product['brand'] ?? 'BSC Elektronik'),
            ],
            'sku' => $sku,
            'mpn' => $mpn,
            'category' => (string) ($product['category_name'] ?? ''),
            'keywords' => implode(', ', array_slice($seoCodes, 0, 18)),
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'TRY',
                'price' => $price,
                'availability' => $availability,
                'url' => $canonicalUrl,
            ],
        ];

        return (string) json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );
    }

    /**
     * @param array<string, scalar> $params
     */
    private function buildAbsoluteUrl(string $route, array $params = []): string
    {
        $query = ['route' => $route];
        foreach ($params as $key => $value) {
            $query[$key] = (string) $value;
        }

        return $this->baseAppUrl() . '/index.php?' . http_build_query($query);
    }

    private function buildAbsoluteAssetUrl(string $assetPath): string
    {
        if (preg_match('/^https?:\/\//i', $assetPath) === 1) {
            return $assetPath;
        }

        return $this->baseAppUrl() . '/' . ltrim($assetPath, '/');
    }

    private function baseAppUrl(): string
    {
        $configured = trim((string) ($this->config['app_url'] ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $basePath = trim(dirname($scriptName), '/');
        if ($basePath === '.' || $basePath === '/') {
            $basePath = '';
        }

        return $scheme . '://' . $host . ($basePath !== '' ? '/' . $basePath : '');
    }
}

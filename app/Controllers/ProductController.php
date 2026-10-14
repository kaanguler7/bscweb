<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UserAuth;
use App\Models\Category;
use App\Models\Product;
use App\Models\Wishlist;
use App\Support\ProductCode;
use App\Support\ProductSeo;

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
        $categoryName = $categoryBreadcrumb !== []
            ? trim((string) $categoryBreadcrumb[count($categoryBreadcrumb) - 1]['name'])
            : '';
        $listingHeading = $categoryName !== '' ? $categoryName : 'TV Yedek Parçaları';
        $categoryIntro = $categoryName !== ''
            ? $categoryName . ' kategorisindeki TV yedek parçalarını marka, model ve parça koduna göre inceleyin.'
            : 'TV yedek parçalarını marka, model ve parça koduna göre inceleyin.';
        $pageTitle = $categoryName !== ''
            ? $categoryName . ' TV Yedek Parçaları | BSC Elektronik'
            : 'TV Yedek Parçaları | BSC Elektronik';
        $metaDescription = $categoryIntro;

        $qTrimmed = trim((string) $query);
        if ($qTrimmed !== '') {
            $pageTitle = $qTrimmed . ' Arama Sonuçları | BSC Elektronik';
            $metaDescription = $qTrimmed . ' için bulunan TV yedek parçalarını inceleyin.';
            $listingHeading = 'Arama sonuçları: ' . $qTrimmed;
        }

        $canonicalParams = [];
        if ($category !== null) {
            $canonicalParams['category'] = $category;
        }
        if ($page > 1) {
            $canonicalParams['page'] = $page;
        }
        $canonicalUrl = $this->buildAbsoluteUrl('products', $canonicalParams);

        $this->view('product/index', [
            'pageTitle' => $pageTitle,
            'listingHeading' => $listingHeading,
            'categoryIntro' => $categoryIntro,
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
            'metaRobots' => ($qTrimmed !== '' || $selectedBrands !== [] || $inStockOnly)
                ? 'noindex,follow'
                : 'index,follow',
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
        $seoCodes = ProductSeo::codes($product);
        $displayName = ProductSeo::displayName($product);
        $canonicalUrl = $this->buildAbsoluteUrl('product', ['id' => (int) $product['id']]);
        $imagePath = trim((string) ($product['image_path'] ?? ''));
        if ($imagePath === '') {
            $imagePath = 'assets/img/product/home-one/product-1.jpg';
        }
        $ogImage = $this->buildAbsoluteAssetUrl($imagePath);
        $metaDescription = ProductSeo::metaDescription($product);
        $metaKeywords = implode(', ', array_slice($seoCodes, 0, 14));
        $jsonLd = $this->buildProductJsonLd(
            $product,
            $seoCodes,
            $canonicalUrl,
            $ogImage,
            $metaDescription,
            $displayName
        );

        $this->view('product/show', [
            'pageTitle' => ProductSeo::pageTitle($product),
            'product' => $product,
            'displayName' => $displayName,
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
     * @param array<int, string> $seoCodes
     */
    private function buildProductJsonLd(
        array $product,
        array $seoCodes,
        string $canonicalUrl,
        string $ogImage,
        string $description,
        string $displayName
    ): string
    {
        $technical = isset($product['technical']) && is_array($product['technical']) ? $product['technical'] : [];
        $partNumber = trim((string) ($technical['part_number'] ?? ''));
        $supplierCode = trim((string) ($technical['supplier_code'] ?? ''));
        $partCode = trim((string) ($product['part_code'] ?? ''));
        $sku = ProductCode::firstIdentifier($partCode, $supplierCode);
        $mpn = ProductCode::firstIdentifier($partNumber, $partCode);
        $availability = ((int) ($product['stock'] ?? 0) > 0)
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';
        $price = number_format(Product::priceWithKdv((float) ($product['price'] ?? 0)), 2, '.', '');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $displayName,
            'description' => $description,
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
        ];

        if ((float) ($product['price'] ?? 0) > 0) {
            $data['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'TRY',
                'price' => $price,
                'availability' => $availability,
                'url' => $canonicalUrl,
            ];
        }

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

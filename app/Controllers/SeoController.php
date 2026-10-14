<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Product;

final class SeoController extends Controller
{
    public function sitemap(): void
    {
        $baseUrl = $this->baseAppUrl();
        $productEntries = Product::sitemapEntries();
        $latestProductModification = $this->latestModification($productEntries);

        $urls = [
            [
                'loc' => $baseUrl . '/',
                'lastmod' => $latestProductModification,
            ],
            [
                'loc' => $baseUrl . '/index.php?route=products',
                'lastmod' => $latestProductModification,
            ],
            [
                'loc' => $baseUrl . '/index.php?route=about',
                'lastmod' => null,
            ],
            [
                'loc' => $baseUrl . '/index.php?route=contact',
                'lastmod' => null,
            ],
        ];

        foreach (Category::all() as $category) {
            $categoryId = (int) $category['id'];
            if (!Category::hasProducts($categoryId) && !Category::hasChildren($categoryId)) {
                continue;
            }
            $urls[] = [
                'loc' => $baseUrl . '/index.php?route=products&category=' . $categoryId,
                'lastmod' => $latestProductModification,
            ];
        }

        foreach ($productEntries as $entry) {
            $urls[] = [
                'loc' => $baseUrl . '/index.php?route=product&id=' . (int) $entry['id'],
                'lastmod' => $this->entryModification($entry),
            ];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars((string) $url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>' . "\n";
            if (!empty($url['lastmod'])) {
                echo '    <lastmod>' . htmlspecialchars((string) $url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</lastmod>' . "\n";
            }
            echo '  </url>' . "\n";
        }
        echo '</urlset>';
        exit;
    }

    public function robots(): void
    {
        $baseUrl = $this->baseAppUrl();
        header('Content-Type: text/plain; charset=utf-8');

        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /index.php?route=admin/\n";
        echo "Disallow: /index.php?route=search/suggest\n";
        echo 'Sitemap: ' . $baseUrl . "/sitemap.xml\n";
        exit;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function latestModification(array $entries): ?string
    {
        $latestTimestamp = null;
        foreach ($entries as $entry) {
            $lastmod = $this->entryModification($entry);
            if ($lastmod === null) {
                continue;
            }

            $timestamp = strtotime($lastmod);
            if ($timestamp !== false && ($latestTimestamp === null || $timestamp > $latestTimestamp)) {
                $latestTimestamp = $timestamp;
            }
        }

        return $latestTimestamp !== null ? gmdate('c', $latestTimestamp) : null;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function entryModification(array $entry): ?string
    {
        $updatedAt = trim((string) ($entry['updated_at'] ?? ''));
        $createdAt = trim((string) ($entry['created_at'] ?? ''));
        $value = $updatedAt !== '' ? $updatedAt : $createdAt;
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? gmdate('c', $timestamp) : null;
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

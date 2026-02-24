<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

final class SeoController extends Controller
{
    public function sitemap(): void
    {
        $baseUrl = $this->baseAppUrl();
        $now = gmdate('c');

        $urls = [
            [
                'loc' => $baseUrl . '/index.php?route=home',
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => $baseUrl . '/index.php?route=products',
                'lastmod' => $now,
                'changefreq' => 'hourly',
                'priority' => '0.9',
            ],
            [
                'loc' => $baseUrl . '/index.php?route=about',
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
            [
                'loc' => $baseUrl . '/index.php?route=contact',
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ],
        ];

        foreach (Product::sitemapEntries() as $entry) {
            $updatedAt = trim((string) ($entry['updated_at'] ?? ''));
            $createdAt = trim((string) ($entry['created_at'] ?? ''));
            $lastmod = $updatedAt !== '' ? $updatedAt : $createdAt;
            if ($lastmod === '') {
                $lastmod = $now;
            } else {
                $timestamp = strtotime($lastmod);
                $lastmod = $timestamp !== false ? gmdate('c', $timestamp) : $now;
            }

            $urls[] = [
                'loc' => $baseUrl . '/index.php?route=product&id=' . (int) $entry['id'],
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars((string) $url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>' . "\n";
            echo '    <lastmod>' . htmlspecialchars((string) $url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</lastmod>' . "\n";
            echo '    <changefreq>' . htmlspecialchars((string) $url['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</changefreq>' . "\n";
            echo '    <priority>' . htmlspecialchars((string) $url['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</priority>' . "\n";
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
        echo "Disallow: /index.php?route=auth/\n";
        echo "Disallow: /index.php?route=cart\n";
        echo "Disallow: /index.php?route=account/\n";
        echo 'Sitemap: ' . $baseUrl . "/index.php?route=sitemap.xml\n";
        exit;
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

<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UserAuth;
use App\Models\Category;
use App\Models\Product;
use App\Models\Wishlist;

final class HomeController extends Controller
{
    public function index(): void
    {
        $products = Product::latest(9);
        $userId = UserAuth::id();
        $organizationJsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'BSC Elektronik',
            'url' => $this->applicationBaseUrl() . '/',
            'logo' => $this->applicationBaseUrl() . '/assets/img/logo/busebsc.png',
            'email' => 'bscelektronik@outlook.com',
            'telephone' => '+90 216 573 93 02',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Fındıklı Mahallesi Evren Caddesi No:3/a',
                'addressLocality' => 'Maltepe',
                'addressRegion' => 'İstanbul',
                'addressCountry' => 'TR',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $this->view('home/index', [
            'pageTitle' => 'BSC Elektronik | TV Yedek Parçaları',
            'products' => $products,
            'categories' => Category::tree(),
            'favoriteIds' => $userId !== null ? Wishlist::productIdsForUser($userId) : [],
            'metaDescription' => 'TV anakartı, power board, T-Con, inverter, LED bar, panel ve diğer televizyon yedek parçalarını parça koduyla BSC Elektronik\'te bulun.',
            'canonicalUrl' => $this->applicationBaseUrl() . '/',
            'jsonLd' => $organizationJsonLd ?: '',
        ]);
    }
}

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

        $this->view('home/index', [
            'pageTitle' => 'BSC Elektronik | TV Yedek Parçaları',
            'products' => $products,
            'categories' => Category::tree(),
            'favoriteIds' => $userId !== null ? Wishlist::productIdsForUser($userId) : [],
            'metaDescription' => 'TV anakartı, power board, T-Con, inverter, LED bar, panel ve diğer televizyon yedek parçalarını parça koduyla BSC Elektronik\'te bulun.',
            'canonicalUrl' => $this->applicationBaseUrl() . '/',
        ]);
    }
}

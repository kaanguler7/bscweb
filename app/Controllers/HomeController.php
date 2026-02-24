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
            'pageTitle' => 'BSC Elektronik | TV Yedek Parcalari',
            'products' => $products,
            'categories' => Category::tree(),
            'favoriteIds' => $userId !== null ? Wishlist::productIdsForUser($userId) : [],
        ]);
    }
}

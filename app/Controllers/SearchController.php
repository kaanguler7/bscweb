<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

final class SearchController extends Controller
{
    public function suggest(): void
    {
        $query = (string) ($_GET['q'] ?? '');
        $suggestions = Product::suggestions($query, 12);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'query' => $query,
            'items' => $suggestions,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UserAuth;
use App\Models\Product;
use App\Models\Wishlist;
use RuntimeException;
use Throwable;

final class WishlistController extends Controller
{
    public function index(): void
    {
        $user = UserAuth::user();
        if ($user === null) {
            $this->flash('danger', 'Favorileri görmek için giriş yapmalısınız.');
            $this->redirect('auth/login');
        }

        $products = Wishlist::productsForUser((int) $user['id']);
        $this->view('wishlist/index', [
            'pageTitle' => 'Favorilerim | BSC Elektronik',
            'products' => $products,
        ]);
    }

    public function toggle(): void
    {
        $userId = UserAuth::id();
        if ($userId === null) {
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => 'Favorilere eklemek için giriş yapmalısınız.',
                    'redirect' => 'index.php?route=auth/login',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $this->flash('danger', 'Favorilere eklemek için giriş yapmalısınız.');
            $this->redirect('auth/login');
        }

        $returnTo = (string) ($_POST['return_to'] ?? 'index.php?route=products');
        if (!str_starts_with($returnTo, 'index.php?')) {
            $returnTo = 'index.php?route=products';
        }

        try {
            $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
            if ($productId <= 0 || Product::find($productId) === null) {
                throw new RuntimeException('Geçersiz ürün seçildi.');
            }

            $added = Wishlist::toggle($userId, $productId);
            $wishlistCount = Wishlist::countForUser($userId);

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => true,
                    'added' => $added,
                    'productId' => $productId,
                    'wishlistCount' => $wishlistCount,
                    'message' => $added ? 'Ürün favorilere eklendi.' : 'Ürün favorilerden çıkarıldı.',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $this->flash('success', $added ? 'Ürün favorilere eklendi.' : 'Ürün favorilerden çıkarıldı.');
        } catch (Throwable $e) {
            if ($this->isAjaxRequest()) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $this->flash('danger', $e->getMessage());
        }

        header('Location: ' . $returnTo);
        exit;
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if (strtolower($requestedWith) === 'xmlhttprequest') {
            return true;
        }

        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        return str_contains(strtolower($accept), 'application/json');
    }
}

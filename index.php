<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Csrf;
use App\Core\Router;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\Visitor;
use Dotenv\Dotenv;

session_start();

require __DIR__ . '/vendor/autoload.php';

if (is_file(__DIR__ . '/.env')) {
    Dotenv::createImmutable(__DIR__)->safeLoad();
}

$config = require __DIR__ . '/config.php';
App::setConfig($config);
User::ensureSchema();
PasswordResetToken::ensureSchema();

$route = $_GET['route'] ?? 'home';
$eligibleVisitorId = null;
if (Visitor::shouldCountPageView(
    (string) $route,
    (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
    (string) ($_SERVER['HTTP_ACCEPT'] ?? ''),
    !empty($_SESSION['admin_user_id'])
)) {
    $visitorId = (string) ($_COOKIE['bsc_visitor_id'] ?? '');
    if (preg_match('/^[a-f0-9]{32}$/', $visitorId) !== 1) {
        $visitorId = bin2hex(random_bytes(16));
        setcookie('bsc_visitor_id', $visitorId, [
            'expires' => time() + 365 * 24 * 60 * 60,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $eligibleVisitorId = $visitorId;
}

$router = new Router($config);
$router->get('home', [\App\Controllers\HomeController::class, 'index']);
$router->get('products', [\App\Controllers\ProductController::class, 'index']);
$router->get('product', [\App\Controllers\ProductController::class, 'show']);
$router->get('sitemap.xml', [\App\Controllers\SeoController::class, 'sitemap']);
$router->get('robots.txt', [\App\Controllers\SeoController::class, 'robots']);
$router->get('search/suggest', [\App\Controllers\SearchController::class, 'suggest']);
$router->get('about', [\App\Controllers\PageController::class, 'about']);
$router->get('contact', [\App\Controllers\PageController::class, 'contact']);
$router->get('cart', [\App\Controllers\CartController::class, 'index']);
$router->post('cart/add', [\App\Controllers\CartController::class, 'add']);
$router->post('cart/update', [\App\Controllers\CartController::class, 'update']);
$router->post('cart/remove', [\App\Controllers\CartController::class, 'remove']);
$router->post('cart/checkout', [\App\Controllers\CartController::class, 'checkout']);
$router->post('cart/payment-notify', [\App\Controllers\CartController::class, 'paymentNotify']);
$router->get('auth/login', [\App\Controllers\AuthController::class, 'loginForm']);
$router->post('auth/login', [\App\Controllers\AuthController::class, 'login']);
$router->get('auth/register', [\App\Controllers\AuthController::class, 'registerForm']);
$router->post('auth/register', [\App\Controllers\AuthController::class, 'register']);
$router->get('auth/forgot-password', [\App\Controllers\AuthController::class, 'forgotPasswordForm']);
$router->post('auth/forgot-password', [\App\Controllers\AuthController::class, 'forgotPasswordSend']);
$router->get('auth/reset-password', [\App\Controllers\AuthController::class, 'resetPasswordForm']);
$router->post('auth/reset-password', [\App\Controllers\AuthController::class, 'resetPassword']);
$router->post('auth/logout', [\App\Controllers\AuthController::class, 'logout']);
$router->get('account/orders', [\App\Controllers\AccountController::class, 'orders']);
$router->get('wishlist', [\App\Controllers\WishlistController::class, 'index']);
$router->post('wishlist/toggle', [\App\Controllers\WishlistController::class, 'toggle']);

$router->get('admin/login', [\App\Controllers\AdminController::class, 'login']);
$router->post('admin/login', [\App\Controllers\AdminController::class, 'authenticate']);
$router->get('admin/password', [\App\Controllers\AdminController::class, 'password']);
$router->post('admin/password/update', [\App\Controllers\AdminController::class, 'updatePassword']);
$router->post('admin/logout', [\App\Controllers\AdminController::class, 'logout']);
$router->get('admin/products', [\App\Controllers\AdminController::class, 'products']);
$router->get('admin/orders', [\App\Controllers\AdminController::class, 'orders']);
$router->get('admin/categories', [\App\Controllers\AdminController::class, 'categories']);
$router->post('admin/products/store', [\App\Controllers\AdminController::class, 'store']);
$router->post('admin/products/update', [\App\Controllers\AdminController::class, 'update']);
$router->post('admin/products/delete', [\App\Controllers\AdminController::class, 'delete']);

$router->post('admin/categories/store', [\App\Controllers\AdminController::class, 'storeCategory']);
$router->post('admin/categories/update', [\App\Controllers\AdminController::class, 'updateCategory']);
$router->post('admin/categories/delete', [\App\Controllers\AdminController::class, 'deleteCategory']);

$router->get('admin/brands', [\App\Controllers\AdminController::class, 'brands']);
$router->post('admin/brands/store', [\App\Controllers\AdminController::class, 'storeBrand']);
$router->post('admin/brands/update', [\App\Controllers\AdminController::class, 'updateBrand']);
$router->post('admin/brands/delete', [\App\Controllers\AdminController::class, 'deleteBrand']);

$router->get('admin/inventory', [\App\Controllers\AdminController::class, 'inventory']);
$router->get('admin/inventory/search', [\App\Controllers\AdminController::class, 'inventorySearch']);
$router->post('admin/orders/approve', [\App\Controllers\AdminController::class, 'approveOrderPayment']);
$router->post('admin/orders/reject', [\App\Controllers\AdminController::class, 'rejectOrderPayment']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!Csrf::validate(is_string($csrfToken) ? $csrfToken : null)) {
        http_response_code(419);
        echo 'Geçersiz oturum doğrulaması. Lütfen sayfayı yenileyip tekrar deneyin.';
        exit;
    }
}

$router->dispatch($route, $_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($eligibleVisitorId !== null && http_response_code() === 200) {
    Visitor::logDailyVisit($eligibleVisitorId);
}

<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UserAuth;
use App\Models\PasswordResetToken;
use App\Models\Product;
use App\Models\User;
use App\Services\Mailer;
use RuntimeException;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        $nextRoute = $this->requestNextRoute();
        $addProductId = $this->requestAddProductId();
        $addQuantity = $this->requestAddQuantity();
        $pendingProduct = $addProductId > 0 ? Product::find($addProductId) : null;

        if (UserAuth::check()) {
            $this->addProductToCartIfNeeded($addProductId, $addQuantity);
            $this->redirect($nextRoute);
        }

        $this->view('auth/login', [
            'pageTitle' => 'Üye Girişi | BSC Elektronik',
            'nextRoute' => $nextRoute,
            'addProductId' => $addProductId,
            'addQuantity' => $addQuantity,
            'pendingProduct' => $pendingProduct,
        ]);
    }

    public function registerForm(): void
    {
        $nextRoute = $this->requestNextRoute();
        $addProductId = $this->requestAddProductId();
        $addQuantity = $this->requestAddQuantity();
        $pendingProduct = $addProductId > 0 ? Product::find($addProductId) : null;

        if (UserAuth::check()) {
            $this->addProductToCartIfNeeded($addProductId, $addQuantity);
            $this->redirect($nextRoute);
        }

        $this->view('auth/register', [
            'pageTitle' => 'Üye Kaydı | BSC Elektronik',
            'nextRoute' => $nextRoute,
            'addProductId' => $addProductId,
            'addQuantity' => $addQuantity,
            'pendingProduct' => $pendingProduct,
        ]);
    }

    public function login(): void
    {
        $nextRoute = $this->requestNextRoute();
        $addProductId = $this->requestAddProductId();
        $addQuantity = $this->requestAddQuantity();
        $loginRouteWithParams = $this->authRouteWithReturnParams('auth/login', $nextRoute, $addProductId, $addQuantity);

        if (UserAuth::check()) {
            $this->addProductToCartIfNeeded($addProductId, $addQuantity);
            $this->redirect($nextRoute);
        }

        $lockUntil = (int) ($_SESSION['auth_lock_until'] ?? 0);
        if ($lockUntil > time()) {
            $seconds = $lockUntil - time();
            $this->flash('danger', 'Çok fazla hatalı deneme yaptınız. Lütfen ' . $seconds . ' saniye sonra tekrar deneyin.');
            $this->redirect($loginRouteWithParams);
        }

        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
        $password = (string) ($_POST['password'] ?? '');

        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçerli bir e-posta girin.');
            }
            if ($password === '') {
                throw new RuntimeException('Şifre alanı zorunludur.');
            }

            $user = User::verifyCredentials($email, $password);
            if ($user === null) {
                $attempts = (int) ($_SESSION['auth_attempts'] ?? 0) + 1;
                $_SESSION['auth_attempts'] = $attempts;
                if ($attempts >= 5) {
                    $_SESSION['auth_lock_until'] = time() + 300;
                }
                throw new RuntimeException('E-posta veya şifre hatalı.');
            }

            unset($_SESSION['auth_attempts'], $_SESSION['auth_lock_until']);
            UserAuth::login((int) $user['id']);
            $this->addProductToCartIfNeeded($addProductId, $addQuantity);
            $this->flash('success', 'Giriş başarılı.');
            $this->redirect($nextRoute);
        } catch (RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect($loginRouteWithParams);
        }
    }

    public function register(): void
    {
        $nextRoute = $this->requestNextRoute();
        $addProductId = $this->requestAddProductId();
        $addQuantity = $this->requestAddQuantity();
        $registerRouteWithParams = $this->authRouteWithReturnParams('auth/register', $nextRoute, $addProductId, $addQuantity);

        if (UserAuth::check()) {
            $this->addProductToCartIfNeeded($addProductId, $addQuantity);
            $this->redirect($nextRoute);
        }

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        try {
            if (mb_strlen($fullName, 'UTF-8') < 2) {
                throw new RuntimeException('Ad Soyad en az 2 karakter olmalıdır.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçerli bir e-posta girin.');
            }
            if (mb_strlen($password, 'UTF-8') < 8) {
                throw new RuntimeException('Şifre en az 8 karakter olmalıdır.');
            }
            if ($password !== $passwordConfirm) {
                throw new RuntimeException('Şifre tekrar alanı eşleşmiyor.');
            }
            if (User::emailExists($email)) {
                throw new RuntimeException('Bu e-posta adresi zaten kayıtlı.');
            }

            $userId = User::create($fullName, $email, $password);
            UserAuth::login($userId);
            $this->addProductToCartIfNeeded($addProductId, $addQuantity);
            $this->flash('success', 'Hesabınız oluşturuldu.');
            $this->redirect($nextRoute);
        } catch (RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect($registerRouteWithParams);
        }
    }

    public function forgotPasswordForm(): void
    {
        $this->view('auth/forgot_password', [
            'pageTitle' => 'Şifremi Unuttum | BSC Elektronik',
        ]);
    }

    public function forgotPasswordSend(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');

        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçerli bir e-posta girin.');
            }

            $this->enforceForgotPasswordRateLimit();

            $user = User::findByEmail($email);
            if ($user !== null) {
                $token = PasswordResetToken::createForUser((int) $user['id']);
                $resetUrl = $this->buildResetPasswordUrl($token);
                $fullName = trim((string) ($user['full_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = 'Müşterimiz';
                }

                $subject = 'Şifre Sıfırlama Talebi';
                $body = "Merhaba {$fullName},\n\n"
                    . "Şifre yenileme talebiniz alındı.\n"
                    . "Aşağıdaki bağlantıya tıklayarak yeni şifre belirleyebilirsiniz:\n\n"
                    . $resetUrl . "\n\n"
                    . "Bu bağlantı 60 dakika geçerlidir.\n"
                    . "Eğer bu talebi siz oluşturmadıysanız bu e-postayı dikkate almayın.\n\n"
                    . "BSC Elektronik";

                Mailer::send((string) $user['email'], $subject, $body);
            }

            $this->flash('success', 'Eğer e-posta adresiniz sistemde kayıtlıysa şifre sıfırlama bağlantısı gönderildi.');
            $this->redirect('auth/forgot-password');
        } catch (RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect('auth/forgot-password');
        }
    }

    public function resetPasswordForm(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            $this->flash('danger', 'Şifre yenileme bağlantısı eksik.');
            $this->redirect('auth/forgot-password');
        }

        if (PasswordResetToken::findActiveByToken($token) === null) {
            $this->flash('danger', 'Şifre yenileme bağlantısı geçersiz veya süresi dolmuş.');
            $this->redirect('auth/forgot-password');
        }

        $this->view('auth/reset_password', [
            'pageTitle' => 'Şifre Yenile | BSC Elektronik',
            'token' => $token,
        ]);
    }

    public function resetPassword(): void
    {
        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        try {
            if ($token === '') {
                throw new RuntimeException('Şifre yenileme bağlantısı eksik.');
            }
            if (mb_strlen($password, 'UTF-8') < 8) {
                throw new RuntimeException('Şifre en az 8 karakter olmalıdır.');
            }
            if ($password !== $passwordConfirm) {
                throw new RuntimeException('Şifre tekrar alanı eşleşmiyor.');
            }

            $updated = PasswordResetToken::consumeAndResetPassword($token, $password);
            if (!$updated) {
                throw new RuntimeException('Şifre yenileme bağlantısı geçersiz veya süresi dolmuş.');
            }

            $this->flash('success', 'Şifreniz güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
            $this->redirect('auth/login');
        } catch (RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect('auth/reset-password&token=' . rawurlencode($token));
        }
    }

    public function logout(): void
    {
        UserAuth::logout();
        $this->flash('success', 'Oturum kapatıldı.');
        $this->redirect('home');
    }

    private function requestNextRoute(): string
    {
        $next = trim((string) ($_POST['next'] ?? $_GET['next'] ?? 'home'));
        if ($next === '') {
            return 'home';
        }

        $allowedRoutes = [
            'home',
            'products',
            'cart',
            'wishlist',
            'account/orders',
            'about',
            'contact',
        ];

        return in_array($next, $allowedRoutes, true) ? $next : 'home';
    }

    private function requestAddProductId(): int
    {
        $rawProductId = $_POST['add_product'] ?? $_GET['add_product'] ?? null;
        $productId = is_numeric($rawProductId) ? (int) $rawProductId : 0;
        if ($productId <= 0) {
            return 0;
        }

        return Product::find($productId) !== null ? $productId : 0;
    }

    private function requestAddQuantity(): int
    {
        $rawQuantity = $_POST['quantity'] ?? $_GET['quantity'] ?? 1;
        $quantity = is_numeric($rawQuantity) ? (int) $rawQuantity : 1;
        if ($quantity < 1) {
            $quantity = 1;
        }

        return min($quantity, 99);
    }

    private function addProductToCartIfNeeded(int $productId, int $quantity): void
    {
        if ($productId <= 0 || $quantity <= 0) {
            return;
        }

        $product = Product::find($productId);
        if ($product === null) {
            return;
        }

        $stock = (int) ($product['stock'] ?? 0);
        if ($stock <= 0) {
            return;
        }

        $quantity = min($quantity, $stock);
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 0;
        }

        $_SESSION['cart'][$productId] += $quantity;
        if ((int) $_SESSION['cart'][$productId] > $stock) {
            $_SESSION['cart'][$productId] = $stock;
        }
    }

    private function authRouteWithReturnParams(string $route, string $nextRoute, int $addProductId, int $addQuantity): string
    {
        $queryParts = [];

        if ($nextRoute !== 'home') {
            $queryParts[] = 'next=' . rawurlencode($nextRoute);
        }
        if ($addProductId > 0) {
            $queryParts[] = 'add_product=' . $addProductId;
            $queryParts[] = 'quantity=' . max(1, $addQuantity);
        }

        if ($queryParts === []) {
            return $route;
        }

        return $route . '&' . implode('&', $queryParts);
    }

    private function enforceForgotPasswordRateLimit(): void
    {
        $now = time();
        $windowSeconds = 15 * 60;
        $maxAttempts = 5;

        $attempts = $_SESSION['forgot_password_attempts'] ?? [];
        if (!is_array($attempts)) {
            $attempts = [];
        }

        $attempts = array_values(array_filter(
            $attempts,
            static fn (mixed $ts): bool => is_int($ts) && $ts > ($now - $windowSeconds)
        ));

        if (count($attempts) >= $maxAttempts) {
            throw new RuntimeException('Kısa sürede çok fazla şifre sıfırlama talebi gönderdiniz. Lütfen daha sonra tekrar deneyin.');
        }

        $attempts[] = $now;
        $_SESSION['forgot_password_attempts'] = $attempts;
    }

    private function buildResetPasswordUrl(string $token): string
    {
        $appUrl = trim((string) ($this->config['app_url'] ?? ''));
        if ($appUrl !== '') {
            $base = rtrim($appUrl, '/');
            if (!str_ends_with(strtolower($base), 'index.php')) {
                $base .= '/index.php';
            }

            return $base . '?route=auth/reset-password&token=' . rawurlencode($token);
        }

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $basePath = dirname(str_replace('\\', '/', $scriptName));
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }

        return $scheme . '://' . $host . $basePath . '/index.php?route=auth/reset-password&token=' . rawurlencode($token);
    }
}


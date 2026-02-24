<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UserAuth;
use App\Models\Order;
use App\Models\Product;
use App\Services\PaymentProofService;
use RuntimeException;
use Throwable;

final class CartController extends Controller
{
    public function index(): void
    {
        [$items, $total] = $this->cartItemsAndTotal();
        $orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
        $paymentOrder = null;
        if ($orderId > 0) {
            $candidate = Order::findWithItems($orderId);
            $currentUserId = UserAuth::id();
            if ($candidate !== null && $currentUserId !== null && (int) ($candidate['user_id'] ?? 0) === $currentUserId) {
                $paymentOrder = $candidate;
            }
        }

        $this->view('cart/index', [
            'pageTitle' => 'Sepetim | BSC Elektronik',
            'items' => $items,
            'total' => $total,
            'paymentOrder' => $paymentOrder,
            'paymentConfig' => $this->config['payment'] ?? [],
            'loggedUser' => UserAuth::user(),
        ]);
    }

    public function add(): void
    {
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

        if ($productId > 0 && Product::find($productId) !== null) {
            if (!isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] = 0;
            }

            $_SESSION['cart'][$productId] += $quantity;
        }

        $this->redirect('cart');
    }

    public function update(): void
    {
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

        if ($productId > 0 && isset($_SESSION['cart'][$productId])) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
        }

        $this->redirect('cart');
    }

    public function remove(): void
    {
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

        if ($productId > 0) {
            unset($_SESSION['cart'][$productId]);
        }

        $this->redirect('cart');
    }

    public function checkout(): void
    {
        try {
            $currentUser = UserAuth::user();
            if ($currentUser === null) {
                throw new RuntimeException('Sipariş verebilmek için giriş yapmalısınız.');
            }

            $customerName = trim((string) ($_POST['customer_name'] ?? ''));
            $customerEmail = mb_strtolower(trim((string) ($_POST['customer_email'] ?? '')), 'UTF-8');

            $customerName = (string) ($currentUser['full_name'] ?? $customerName);
            $customerEmail = (string) ($currentUser['email'] ?? $customerEmail);

            if ($customerName === '') {
                throw new RuntimeException('Lütfen sipariş veren kişinin adını girin.');
            }
            if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Lütfen geçerli bir e-posta adresi girin.');
            }

            [$items, $total] = $this->cartItemsAndTotal();
            if ($items === []) {
                throw new RuntimeException('Sepet boş olduğu için sipariş oluşturulamadı.');
            }

            $orderId = Order::create(
                $customerName,
                $customerEmail,
                (int) $currentUser['id'],
                $items,
                $total
            );
            unset($_SESSION['cart']);

            $this->flash('success', 'Sipariş oluşturuldu. Ödeme yapıp dekont bildirimi gönderebilirsiniz. Sipariş No: #' . $orderId);
            $this->redirect('cart&order_id=' . $orderId);
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect('cart');
        }
    }

    public function paymentNotify(): void
    {
        try {
            $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
            $customerName = trim((string) ($_POST['customer_name'] ?? ''));
            $note = trim((string) ($_POST['payment_note'] ?? ''));

            if ($orderId <= 0) {
                throw new RuntimeException('Geçerli sipariş numarası gereklidir.');
            }
            if ($customerName === '') {
                throw new RuntimeException('Ad Soyad alanı zorunludur.');
            }

            $order = Order::findWithItems($orderId);
            if ($order === null) {
                throw new RuntimeException('Sipariş bulunamadı.');
            }

            $currentUserId = UserAuth::id();
            if ($currentUserId === null) {
                throw new RuntimeException('Ödeme bildirimi için giriş yapmalısınız.');
            }
            $orderUserId = isset($order['user_id']) ? (int) $order['user_id'] : 0;
            if ($orderUserId > 0 && $currentUserId !== $orderUserId) {
                throw new RuntimeException('Bu sipariş için işlem yetkiniz yok.');
            }

            $proofPath = null;
            if (isset($_FILES['payment_proof']) && (int) ($_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $proofPath = (new PaymentProofService())->storeUploadedProof($_FILES['payment_proof']);
            }

            Order::markPaymentNotified($orderId, $customerName, $proofPath, $note);
            $this->flash('success', 'Ödeme bildiriminiz alındı. Kısa sürede kontrol edilip onaylanacaktır.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('cart&order_id=' . (int) ($_POST['order_id'] ?? 0));
    }

    private function cartItemsAndTotal(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $total = 0.0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::find((int) $productId);
            if ($product === null) {
                continue;
            }

            $lineTotal = (float) $product['price'] * (int) $quantity;
            $total += $lineTotal;

            $items[] = [
                'product' => $product,
                'quantity' => (int) $quantity,
                'lineTotal' => $lineTotal,
            ];
        }

        return [$items, $total];
    }
}

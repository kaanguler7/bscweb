<?php

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Controller;
use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Visitor;
use App\Services\ProductImageService;
use App\Support\ProductCode;
use RuntimeException;
use Throwable;

final class AdminController extends Controller
{
    private ProductImageService $imageService;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->imageService = new ProductImageService();
    }

    // -- Ürün Yönetimi ------------------------------------------

    public function products(): void
    {
        $this->requireAdmin();

        $categories = Category::flatForSelect();
        $categoryMap = Category::map();
        $brands = Brand::all();

        // Sayfalama ve arama parametreleri
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim((string) ($_GET['q'] ?? ''));
        $paginated = Product::adminPaginated($page, 20, $search);

        $editingProduct = null;
        if (isset($_GET['edit']) && $_GET['edit'] !== '') {
            $editingProduct = Product::find((int) $_GET['edit']);
        }

        $this->view('admin/products', [
            'pageTitle' => 'Admin | Ürünler',
            'products' => $paginated['items'],
            'pagination' => $paginated,
            'searchTerm' => $search,
            'categories' => $categories,
            'brands' => $brands,
            'technicalDefinitions' => Product::technicalDefinitions(),
            'categoryMap' => $categoryMap,
            'editingProduct' => $editingProduct,
            'recentOrders' => Order::recentWithItems(10),
            'pendingPayments' => Order::pendingPaymentOrders(12),
            'todayVisitors' => Visitor::countToday(),
            'todayOrders' => Order::countToday(),
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();

        try {
            $data = $this->validatedPayload();

            if (Product::partCodeExists($data['part_code'])) {
                throw new RuntimeException('Bu parça kodu zaten kayıtlı.');
            }

            if (!isset($_FILES['image']) || (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('Yeni ürün için ürün fotoğrafı zorunludur.');
            }
            $data['image_path'] = $this->imageService->storeUploadedImage($_FILES['image']);
            Product::create($data);

            $this->flash('success', 'Ürün başarıyla eklendi.');
        } catch (Throwable $exception) {
            $this->flash('danger', $exception->getMessage());
        }

        $this->redirect('admin/products');
    }

    public function update(): void
    {
        $this->requireAdmin();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $product = Product::find($id);

        if ($product === null) {
            $this->flash('danger', 'Güncellenecek ürün bulunamadı.');
            $this->redirect('admin/products');
        }

        try {
            $data = $this->validatedPayload();

            if (Product::partCodeExists($data['part_code'], $id)) {
                throw new RuntimeException('Bu parça kodu başka bir ürüne ait.');
            }

            $data['image_path'] = $product['image_path'];
            if (isset($_FILES['image']) && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $data['image_path'] = $this->imageService->storeUploadedImage($_FILES['image'], $product['image_path']);
            }

            Product::update($id, $data);
            $this->flash('success', 'Ürün başarıyla güncellendi.');
        } catch (Throwable $exception) {
            $this->flash('danger', $exception->getMessage());
            $this->redirect('admin/products&edit=' . $id);
        }

        $this->redirect('admin/products');
    }

    public function delete(): void
    {
        $this->requireAdmin();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $product = Product::find($id);

        if ($product === null) {
            $this->flash('danger', 'Silinecek ürün bulunamadı.');
            $this->redirect('admin/products');
        }

        Product::delete($id);
        $this->imageService->deleteImage($product['image_path'] ?? null);

        $this->flash('success', 'Ürün silindi.');
        $this->redirect('admin/products');
    }

    // -- Kategori Yönetimi ---------------------------------------

    public function categories(): void
    {
        $this->requireAdmin();

        $editingCategory = null;
        if (isset($_GET['cat_edit']) && $_GET['cat_edit'] !== '') {
            $editingCategory = Category::find((int) $_GET['cat_edit']);
        }

        $categoryTree = Category::tree();
        $parentOptions = Category::flatForSelect();
        if ($editingCategory !== null) {
            $blockedParentIds = array_merge([(int) $editingCategory['id']], Category::descendantIds((int) $editingCategory['id']));
            $parentOptions = array_values(array_filter(
                $parentOptions,
                static fn (array $category): bool => !in_array((int) $category['id'], $blockedParentIds, true)
            ));
        }

        $this->view('admin/categories', [
            'pageTitle' => 'Admin | Kategoriler',
            'categoryTree' => $categoryTree,
            'parentOptions' => $parentOptions,
            'editingCategory' => $editingCategory,
            'todayVisitors' => Visitor::countToday(),
            'todayOrders' => Order::countToday(),
        ]);
    }

    public function storeCategory(): void
    {
        $this->requireAdmin();

        try {
            $name = trim((string) ($_POST['name'] ?? ''));
            $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null;

            if ($name === '') {
                throw new RuntimeException('Kategori adı boş olamaz.');
            }

            if (Category::nameExists($name)) {
                throw new RuntimeException('Bu kategori zaten var.');
            }

            if ($parentId !== null) {
                $parent = Category::find($parentId);
                if ($parent === null) {
                    throw new RuntimeException('Seçilen ana kategori bulunamadı.');
                }
            }

            Category::create($name, $parentId);
            $this->flash('success', 'Kategori eklendi.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect($this->categoryReturnRoute());
    }

    public function updateCategory(): void
    {
        $this->requireAdmin();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        try {
            if ($id <= 0 || Category::find($id) === null) {
                throw new RuntimeException('Güncellenecek kategori bulunamadı.');
            }

            $name = trim((string) ($_POST['name'] ?? ''));
            $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null;

            if ($name === '') {
                throw new RuntimeException('Kategori adı boş olamaz.');
            }

            if (Category::nameExists($name, $id)) {
                throw new RuntimeException('Bu kategori adı başka kayıtta var.');
            }

            if ($parentId !== null) {
                $parent = Category::find($parentId);
                if ($parent === null) {
                    throw new RuntimeException('Seçilen ana kategori bulunamadı.');
                }
                if ($parentId === $id) {
                    throw new RuntimeException('Kategori kendisinin altinda olamaz.');
                }
                if (Category::isDescendant($parentId, $id)) {
                    throw new RuntimeException('Kategori kendi alt kategorisine bağlanamaz.');
                }
            }

            Category::update($id, $name, $parentId);
            $this->flash('success', 'Kategori güncellendi.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect($this->categoryReturnRoute());
    }

    public function deleteCategory(): void
    {
        $this->requireAdmin();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        try {
            if ($id <= 0 || Category::find($id) === null) {
                throw new RuntimeException('Silinecek kategori bulunamadı.');
            }

            if (Category::hasChildren($id)) {
                throw new RuntimeException('Bu kategoriye bağlı alt kategoriler var.');
            }

            if (Category::hasProducts($id)) {
                throw new RuntimeException('Bu kategoriye bağlı ürünler var, önce ürünleri taşıyın/silin.');
            }

            Category::delete($id);
            $this->flash('success', 'Kategori silindi.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect($this->categoryReturnRoute());
    }

    // -- Marka Yönetimi ------------------------------------------

    public function brands(): void
    {
        $this->requireAdmin();

        $editingBrand = null;
        if (isset($_GET['brand_edit']) && $_GET['brand_edit'] !== '') {
            $editingBrand = Brand::find((int) $_GET['brand_edit']);
        }

        $brands = Brand::all();

        $this->view('admin/brands', [
            'pageTitle' => 'Admin | Markalar',
            'brands' => $brands,
            'editingBrand' => $editingBrand,
            'todayVisitors' => Visitor::countToday(),
            'todayOrders' => Order::countToday(),
        ]);
    }

    public function storeBrand(): void
    {
        $this->requireAdmin();

        try {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Marka adı boş olamaz.');
            }
            if (Brand::nameExists($name)) {
                throw new RuntimeException('Bu marka zaten var.');
            }
            Brand::create($name);
            $this->flash('success', 'Marka eklendi.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('admin/brands');
    }

    public function updateBrand(): void
    {
        $this->requireAdmin();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        try {
            if ($id <= 0 || Brand::find($id) === null) {
                throw new RuntimeException('Güncellenecek marka bulunamadı.');
            }
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Marka adı boş olamaz.');
            }
            if (Brand::nameExists($name, $id)) {
                throw new RuntimeException('Bu marka adı başka kayıtta var.');
            }
            Brand::update($id, $name);
            $this->flash('success', 'Marka güncellendi.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('admin/brands');
    }

    public function deleteBrand(): void
    {
        $this->requireAdmin();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        try {
            if ($id <= 0 || Brand::find($id) === null) {
                throw new RuntimeException('Silinecek marka bulunamadı.');
            }
            if (Brand::hasProducts($id)) {
                throw new RuntimeException('Bu markaya bağlı ürünler var, önce ürünlerdeki markayı değiştirin.');
            }
            Brand::delete($id);
            $this->flash('success', 'Marka silindi.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('admin/brands');
    }

    // -- Envanter Kontrol ----------------------------------------

    public function inventory(): void
    {
        $this->requireAdmin();

        $searchTerm = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $paginated = Product::searchInventoryPaginated($searchTerm, $page, 20);

        $this->view('admin/inventory', [
            'pageTitle' => 'Admin | Envanter Kontrol',
            'products' => $paginated['items'],
            'pagination' => $paginated,
            'searchTerm' => $searchTerm,
            'todayVisitors' => Visitor::countToday(),
            'todayOrders' => Order::countToday(),
        ]);
    }

    public function inventorySearch(): void
    {
        $this->requireAdmin();

        $searchTerm = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $paginated = Product::searchInventoryPaginated($searchTerm, $page, 20);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($paginated, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function orders(): void
    {
        $this->requireAdmin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];
        $paginated = Order::adminRowsPaginated($filters, $page, 50);

        $this->view('admin/orders', [
            'pageTitle' => 'Admin | Siparişler',
            'rows' => $paginated['items'],
            'pagination' => $paginated,
            'filters' => $paginated['filters'],
            'todayVisitors' => Visitor::countToday(),
            'todayOrders' => Order::countToday(),
        ]);
    }

    public function approveOrderPayment(): void
    {
        $this->requireAdmin();

        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        try {
            if ($orderId <= 0) {
                throw new RuntimeException('Geçerli sipariş seçilmedi.');
            }

            Order::approvePayment($orderId);
            $this->flash('success', 'Ödeme onaylandı. Stok düşümü tamamlandı.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('admin/products');
    }

    public function rejectOrderPayment(): void
    {
        $this->requireAdmin();

        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $note = trim((string) ($_POST['note'] ?? ''));
        try {
            if ($orderId <= 0) {
                throw new RuntimeException('Geçerli sipariş seçilmedi.');
            }

            Order::rejectPayment($orderId, $note);
            $this->flash('success', 'Ödeme bildirimi reddedildi.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('admin/products');
    }

    // -- Giriş / Çıkış -------------------------------------------

    public function login(): void
    {
        if (AdminAuth::check()) {
            $this->redirect('admin/products');
        }

        $this->view('admin/login', [
            'pageTitle' => 'Admin Giriş',
        ]);
    }

    public function authenticate(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->flash('danger', 'Kullanıcı adı ve şifre zorunludur.');
            $this->redirect('admin/login');
        }

        $user = AdminUser::verifyCredentials($username, $password);
        if ($user === null) {
            $this->flash('danger', 'Giriş bilgileri hatalı.');
            $this->redirect('admin/login');
        }

        AdminAuth::login((int) $user['id'], (string) $user['username']);
        $this->redirect('admin/products');
    }

    public function logout(): void
    {
        AdminAuth::logout();
        $this->flash('success', 'Admin oturumu kapatıldı.');
        $this->redirect('admin/login');
    }

    // -- Şifre Değiştirme ---------------------------------------

    public function password(): void
    {
        $this->requireAdmin();

        $this->view('admin/password', [
            'pageTitle' => 'Admin | Şifre Değiştir',
        ]);
    }

    public function updatePassword(): void
    {
        $this->requireAdmin();

        $adminId = (int) ($_SESSION['admin_user_id'] ?? 0);
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

        try {
            if ($adminId <= 0) {
                throw new RuntimeException('Oturum bilgisi bulunamadı. Lütfen tekrar giriş yapın.');
            }

            if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirm === '') {
                throw new RuntimeException('Tüm alanları doldurmanız zorunludur.');
            }

            if (mb_strlen($newPassword, 'UTF-8') < 8) {
                throw new RuntimeException('Yeni şifre en az 8 karakter olmalıdır.');
            }

            if ($newPassword !== $newPasswordConfirm) {
                throw new RuntimeException('Yeni şifre tekrarı eşleşmiyor.');
            }

            if ($currentPassword === $newPassword) {
                throw new RuntimeException('Yeni şifre mevcut şifre ile aynı olamaz.');
            }

            $admin = AdminUser::findById($adminId);
            if ($admin === null) {
                throw new RuntimeException('Admin hesabı bulunamadı.');
            }

            if (!password_verify($currentPassword, (string) $admin['password_hash'])) {
                throw new RuntimeException('Mevcut şifreyi yanlış girdiniz.');
            }

            AdminUser::updatePassword($adminId, $newPassword);
            $this->flash('success', 'Şifreniz başarıyla güncellendi.');
        } catch (Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('admin/password');
    }

    // -- Yardimci Metodlar ---------------------------------------

    private function validatedPayload(): array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $partCode = ProductCode::requireValid($_POST['part_code'] ?? '');
        $brand = trim((string) ($_POST['brand'] ?? ''));
        $model = trim((string) ($_POST['model'] ?? ''));
        $categoryIdRaw = trim((string) ($_POST['category_id'] ?? ''));
        $priceRaw = trim((string) ($_POST['price'] ?? ''));
        $stockRaw = trim((string) ($_POST['stock'] ?? ''));
        $technical = $_POST['technical'] ?? [];
        if (!is_array($technical)) {
            $technical = [];
        }

        if ($name === '') {
            throw new RuntimeException('Ürün adı zorunludur.');
        }
        if (mb_strlen($name, 'UTF-8') > 180) {
            throw new RuntimeException('Ürün adı en fazla 180 karakter olabilir.');
        }
        if (mb_strlen($description, 'UTF-8') > 5000) {
            throw new RuntimeException('Ürün açıklaması en fazla 5000 karakter olabilir.');
        }
        if ($brand === '') {
            throw new RuntimeException('Marka zorunludur.');
        }
        if (mb_strlen($brand, 'UTF-8') > 120) {
            throw new RuntimeException('Marka en fazla 120 karakter olabilir.');
        }
        if (mb_strlen($model, 'UTF-8') > 160) {
            throw new RuntimeException('Model en fazla 160 karakter olabilir.');
        }

        if ($categoryIdRaw === '') {
            throw new RuntimeException('Kategori zorunludur.');
        }
        $categoryId = (int) $categoryIdRaw;
        if ($categoryId <= 0 || Category::find($categoryId) === null) {
            throw new RuntimeException('Seçilen kategori geçersiz.');
        }

        $normalizedPrice = str_replace(',', '.', $priceRaw);
        if ($normalizedPrice === '' || !is_numeric($normalizedPrice)) {
            throw new RuntimeException('Geçerli bir fiyat girin.');
        }
        $price = (float) $normalizedPrice;
        if ($price <= 0) {
            throw new RuntimeException('Yayınlanan ürünün fiyatı 0 TL olamaz.');
        }

        $validatedStock = filter_var($stockRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($validatedStock === false) {
            throw new RuntimeException('Stok 0 veya daha büyük bir tam sayı olmalıdır.');
        }
        $stock = (int) $validatedStock;

        return [
            'name' => $name,
            'description' => $description === '' ? null : $description,
            'part_code' => $partCode,
            'brand' => $brand,
            'model' => $model === '' ? null : $model,
            'category_id' => $categoryId,
            'price' => $price,
            'stock' => $stock,
            'technical' => $technical,
        ];
    }

    private function categoryReturnRoute(): string
    {
        $allowed = ['admin/categories', 'admin/products'];
        $candidate = trim((string) ($_POST['return_to'] ?? 'admin/categories'));

        return in_array($candidate, $allowed, true) ? $candidate : 'admin/categories';
    }

    private function requireAdmin(): void
    {
        if (!AdminAuth::check()) {
            $this->flash('danger', 'Admin alanı için giriş yapmalısınız.');
            $this->redirect('admin/login');
        }
    }
}


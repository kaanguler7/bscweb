<?php
$nextRoute = isset($nextRoute) && is_string($nextRoute) ? $nextRoute : 'home';
$addProductId = isset($addProductId) ? (int) $addProductId : 0;
$addQuantity = isset($addQuantity) ? max(1, (int) $addQuantity) : 1;
$pendingProduct = isset($pendingProduct) && is_array($pendingProduct) ? $pendingProduct : null;

$queryParts = [];
if ($nextRoute !== 'home') {
    $queryParts[] = 'next=' . rawurlencode($nextRoute);
}
if ($addProductId > 0) {
    $queryParts[] = 'add_product=' . $addProductId;
    $queryParts[] = 'quantity=' . $addQuantity;
}
$loginHref = 'index.php?route=auth/login' . ($queryParts === [] ? '' : '&' . implode('&', $queryParts));
?>
<section class="pt-80 pb-80">
   <div class="container">
      <div class="row justify-content-center">
         <div class="col-lg-6 col-md-8">
            <div class="card">
               <div class="card-body p-4">
                  <h3 class="mb-3">Üye Kayıt</h3>
                  <p class="text-muted mb-4">Kayıt sonrası siparişlerinizi takip edebilir ve ürünleri favorilere ekleyebilirsiniz.</p>

                  <?php if ($pendingProduct !== null): ?>
                     <div class="alert alert-info" role="alert" style="font-size:14px;">
                        <strong>Devam eden ürün:</strong>
                        <?= htmlspecialchars((string) ($pendingProduct['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        (Adet: <?= (int) $addQuantity ?>)
                     </div>
                  <?php endif; ?>

                  <form method="post" action="index.php?route=auth/register">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <input type="hidden" name="next" value="<?= htmlspecialchars($nextRoute, ENT_QUOTES, 'UTF-8') ?>">
                     <?php if ($addProductId > 0): ?>
                        <input type="hidden" name="add_product" value="<?= $addProductId ?>">
                        <input type="hidden" name="quantity" value="<?= $addQuantity ?>">
                     <?php endif; ?>
                     <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input class="form-control" type="text" name="full_name" required autocomplete="name">
                     </div>
                     <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input class="form-control" type="email" name="email" required autocomplete="email">
                     </div>
                     <div class="mb-3">
                        <label class="form-label">Şifre (en az 8 karakter)</label>
                        <input class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password">
                     </div>
                     <div class="mb-4">
                        <label class="form-label">Şifre Tekrar</label>
                        <input class="form-control" type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                     </div>
                     <button class="btn btn-dark w-100" type="submit">Kayıt Ol</button>
                  </form>
                  <div class="mt-3 text-center">
                     <a href="<?= htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8') ?>">Zaten hesabın var mı? Giriş yap.</a>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

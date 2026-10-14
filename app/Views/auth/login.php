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
$registerHref = 'index.php?route=auth/register' . ($queryParts === [] ? '' : '&' . implode('&', $queryParts));
?>
<section class="pt-80 pb-80">
   <div class="container">
      <div class="row justify-content-center">
         <div class="col-lg-5 col-md-7">
            <div class="card">
               <div class="card-body p-4">
                  <h3 class="mb-3">Üye Girişi</h3>
                  <p class="text-muted mb-4">Sipariş geçmişinizi ve favorilerinizi görmek için giriş yapın.</p>

                  <?php if ($pendingProduct !== null): ?>
                     <div class="alert alert-info" role="alert" style="font-size:14px;">
                        <strong>Devam eden ürün:</strong>
                        <?= htmlspecialchars((string) ($pendingProduct['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        (Adet: <?= (int) $addQuantity ?>)
                     </div>
                  <?php endif; ?>

                  <form method="post" action="index.php?route=auth/login">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <input type="hidden" name="next" value="<?= htmlspecialchars($nextRoute, ENT_QUOTES, 'UTF-8') ?>">
                     <?php if ($addProductId > 0): ?>
                        <input type="hidden" name="add_product" value="<?= $addProductId ?>">
                        <input type="hidden" name="quantity" value="<?= $addQuantity ?>">
                     <?php endif; ?>
                     <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input class="form-control" type="email" name="email" required autocomplete="email">
                     </div>
                     <div class="mb-2">
                        <label class="form-label">Şifre</label>
                        <input class="form-control" type="password" name="password" required autocomplete="current-password">
                     </div>
                     <div class="mb-4 text-end">
                        <a href="index.php?route=auth/forgot-password" style="font-size:14px;">Şifremi Unuttum</a>
                     </div>
                     <button class="btn btn-dark w-100" type="submit">Giriş Yap</button>
                  </form>
                  <div class="mt-3 text-center">
                     <a href="<?= htmlspecialchars($registerHref, ENT_QUOTES, 'UTF-8') ?>">Hesabın yok mu? Kayıt ol.</a>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

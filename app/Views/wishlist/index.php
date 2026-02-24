<?php use App\Models\Product; ?>
<section class="pt-60 pb-80">
   <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-25">
         <h2 class="mb-0">Favorilerim</h2>
         <a href="index.php?route=products" class="btn btn-outline-secondary">Ürünlere Dön</a>
      </div>

      <?php if ($products === []): ?>
         <div class="card">
            <div class="card-body">
               <p class="mb-0">Favori listeniz boş.</p>
            </div>
         </div>
      <?php else: ?>
         <div class="row g-4">
            <?php foreach ($products as $product): ?>
               <?php $image = $product['image_path'] ?: 'assets/img/product/home-one/product-1.jpg'; ?>
               <div class="col-xl-3 col-md-4 col-sm-6" data-wishlist-item="<?= (int) $product['id'] ?>">
                  <div class="card h-100">
                     <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" style="height:200px;object-fit:cover;" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
                     <div class="card-body d-flex flex-column">
                        <h6 class="card-title"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></h6>
                        <p class="text-muted mb-2"><?= htmlspecialchars((string) ($product['brand'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mb-3"><strong><?= Product::priceWithKdvFormatted((float) $product['price']) ?></strong></p>
                        <div class="mt-auto d-flex gap-2">
                           <a class="btn btn-sm btn-primary" href="index.php?route=product&id=<?= (int) $product['id'] ?>">Ürüne Git</a>
                           <form method="post" action="index.php?route=wishlist/toggle" class="js-wishlist-toggle" data-product-id="<?= (int) $product['id'] ?>" data-remove-card="1">
                              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                              <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                              <input type="hidden" name="return_to" value="index.php?route=wishlist">
                              <button class="btn btn-sm btn-outline-danger" type="submit">Çıkar</button>
                           </form>
                        </div>
                     </div>
                  </div>
               </div>
            <?php endforeach; ?>
         </div>
      <?php endif; ?>
   </div>
</section>

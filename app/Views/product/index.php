<?php use App\Models\Product; ?>
<section class="breadcrumb__area include-bg pt-25 pb-25" style="background-image:url('assets/img/banner/breadcrumb-01.jpg')">
   <div class="container"><h2 class="text-white mb-0">Ürünler</h2></div>
</section>

<section class="shop-area pt-35 pb-70">
   <div class="container">
      <div class="shop-breadcrumb-line">
         <a href="index.php?route=home">Ana Sayfa</a>
         <?php foreach ($categoryBreadcrumb as $crumb): ?>
            &nbsp;>&nbsp;
            <a href="index.php?route=products&category=<?= (int) $crumb['id'] ?>"><?= htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') ?></a>
         <?php endforeach; ?>
      </div>

      <div class="row g-4">
         <div class="col-xl-3 col-lg-4">
            <form id="shop-filter-form" method="get" action="index.php" class="shop-filter-box">
               <input type="hidden" name="route" value="products">
               <?php if ($selectedCategory !== null): ?>
                  <input type="hidden" name="category" value="<?= (int) $selectedCategory ?>">
               <?php endif; ?>
               <?php if (!empty($query)): ?>
                  <input type="hidden" name="q" value="<?= htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8') ?>">
               <?php endif; ?>

               <?php if (count($brandFacets) > 0): ?>
                  <div class="shop-filter-section">
                     <div class="shop-filter-title">
                        <span>Marka</span>
                        <i class="fal fa-angle-up"></i>
                     </div>
                     <input id="brand-filter-input" class="brand-search-input" type="text" placeholder="Arama Yapabilirsiniz">
                     <div id="brand-filter-list" class="brand-list">
                        <?php foreach ($brandFacets as $brandFacet): ?>
                           <?php $brand = (string) $brandFacet['brand']; ?>
                           <label class="brand-item" data-brand="<?= htmlspecialchars(mb_strtolower($brand, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                              <input type="checkbox" name="brands[]" value="<?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($brand, $selectedBrands, true) ? 'checked' : '' ?>>
                              <span><?= htmlspecialchars(mb_strtoupper($brand, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?> (<?= (int) $brandFacet['count'] ?>)</span>
                           </label>
                        <?php endforeach; ?>
                     </div>
                  </div>
               <?php endif; ?>

               <div class="shop-filter-section">
                  <div class="shop-filter-title">
                     <span>Ürün Durumu</span>
                     <i class="fal fa-angle-up"></i>
                  </div>
                  <label class="brand-item">
                     <input type="checkbox" name="in_stock" value="1" <?= !empty($inStockOnly) ? 'checked' : '' ?>>
                     <span>Stoktakiler</span>
                  </label>
               </div>

               <div class="shop-filter-section">
                  <button class="tp-btn filter-submit" type="submit">Filtrele</button>
               </div>
            </form>
         </div>

         <div class="col-xl-9 col-lg-8">
            <form method="get" action="index.php" class="row g-2 mb-25">
               <input type="hidden" name="route" value="products">
               <div class="col-md-6">
                  <input class="form-control" type="text" name="q" value="<?= htmlspecialchars((string) ($query ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Marka, model veya parça kodu ara">
               </div>
               <div class="col-md-4">
                  <select class="form-select" name="category">
                     <option value="">Tüm kategoriler</option>
                     <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= ((int) ($selectedCategory ?? 0) === (int) $category['id']) ? 'selected' : '' ?>>
                           <?= htmlspecialchars($category['display_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                     <?php endforeach; ?>
                  </select>
               </div>
               <div class="col-md-2">
                  <button class="tp-btn w-100" type="submit">Ara</button>
               </div>
            </form>

            <div class="row">
               <?php if (count($products) === 0): ?>
                  <div class="col-12"><p>Filtreye uygun ürün bulunamadı.</p></div>
               <?php endif; ?>

               <?php foreach ($products as $product): ?>
                  <?php $image = $product['image_path'] ?: 'assets/img/product/home-one/product-1.jpg'; ?>
                  <div class="col-xl-4 col-md-6">
                     <div class="shop-product-card">
                        <div class="shop-product-thumb">
                           <?php if ((int) $product['stock'] <= 0): ?>
                              <span class="out-of-stock-ribbon">Stokta Yok</span>
                           <?php endif; ?>
                           <a href="index.php?route=product&id=<?= (int) $product['id'] ?>">
                              <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                           </a>
                        </div>
                        <div class="shop-product-info">
                           <div class="shop-product-name">
                              <a href="index.php?route=product&id=<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a>
                           </div>
                           <div class="shop-product-meta"><span class="shop-product-brand"><?= htmlspecialchars((string) ($product['brand'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span> &middot; <?= htmlspecialchars((string) ($product['part_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                           <div class="shop-product-price"><?= Product::priceWithKdvFormatted((float) $product['price']) ?></div>
                           <div class="shop-product-kdv"><?= Product::priceFormatted((float) $product['price']) ?> + KDV</div>
                           <?php $userId = \App\Core\UserAuth::id(); ?>
                           <?php if ($userId !== null): ?>
                              <?php $isFavorite = in_array((int) $product['id'], $favoriteIds ?? [], true); ?>
                              <form method="post" action="index.php?route=wishlist/toggle" class="shop-fav-form js-wishlist-toggle" data-product-id="<?= (int) $product['id'] ?>">
                                 <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                 <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                 <input type="hidden" name="return_to" value="index.php?route=products">
                                 <button class="shop-fav-btn" type="submit" aria-label="Favori">
                                    <i class="js-wishlist-icon <?= $isFavorite ? 'fas' : 'far' ?> fa-heart"></i>
                                 </button>
                              </form>
                           <?php else: ?>
                              <a class="shop-fav-btn shop-fav-form" href="index.php?route=auth/login" aria-label="Favori">
                                 <i class="far fa-heart"></i>
                              </a>
                           <?php endif; ?>
                        </div>
                     </div>
                  </div>
               <?php endforeach; ?>
            </div>

            <?php if ((int) $pagination['totalPages'] > 1): ?>
            <?php
               $pPage = (int) $pagination['page'];
               $pTotalPages = (int) $pagination['totalPages'];

               // Sayfalama URL: mevcut filtreleri koru
               $shopPageUrl = function(int $pg) use ($query, $selectedCategory, $selectedBrands, $inStockOnly): string {
                  $params = ['route' => 'products', 'page' => $pg];
                  if ($query !== null && trim((string) $query) !== '') $params['q'] = $query;
                  if ($selectedCategory !== null) $params['category'] = $selectedCategory;
                  foreach ($selectedBrands as $b) $params['brands[]'] = $b;
                  if ($inStockOnly) $params['in_stock'] = '1';
                  // brands[] özel işlem
                  $base = 'index.php?route=products&page=' . $pg;
                  if ($query !== null && trim((string) $query) !== '') $base .= '&q=' . urlencode((string) $query);
                  if ($selectedCategory !== null) $base .= '&category=' . (int) $selectedCategory;
                  foreach ($selectedBrands as $b) $base .= '&brands[]=' . urlencode($b);
                  if ($inStockOnly) $base .= '&in_stock=1';
                  return $base;
               };
            ?>
            <nav class="mt-4 mb-3">
               <ul class="pagination justify-content-center">
                  <li class="page-item <?= $pPage <= 1 ? 'disabled' : '' ?>">
                     <a class="page-link" href="<?= $shopPageUrl($pPage - 1) ?>">&laquo;</a>
                  </li>
                  <?php
                     $startPg = max(1, $pPage - 3);
                     $endPg = min($pTotalPages, $startPg + 6);
                     if ($endPg - $startPg < 6) $startPg = max(1, $endPg - 6);
                  ?>
                  <?php for ($i = $startPg; $i <= $endPg; $i++): ?>
                     <li class="page-item <?= $i === $pPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $shopPageUrl($i) ?>"><?= $i ?></a>
                     </li>
                  <?php endfor; ?>
                  <li class="page-item <?= $pPage >= $pTotalPages ? 'disabled' : '' ?>">
                     <a class="page-link" href="<?= $shopPageUrl($pPage + 1) ?>">&raquo;</a>
                  </li>
               </ul>
            </nav>
            <?php endif; ?>
         </div>
      </div>
   </div>
</section>


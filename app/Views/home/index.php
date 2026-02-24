<?php use App\Models\Product; ?>
<!-- Hero -->
<section class="home-hero-area">
   <div class="container">
      <div class="row align-items-center">
         <div class="col-lg-5">
            <h1 class="home-hero-title">TV Yedek Parçalarında<br>Uzman Satış</h1>
            <p class="home-hero-desc">Anakart, LED bar, Power Board, T-Con ve Panel Kablolarını<br class="d-none d-lg-block"> model ve parça koduna göre kolayca bulun.</p>
            <a class="tp-btn" href="index.php?route=products" style="padding: 14px 36px; font-size: 15px;">Ürünleri İncele</a>
         </div>
         <div class="col-lg-7 d-none d-md-block">
            <?php
               $heroDir = __DIR__ . '/../../../assets/img/hero-slides/';
               $heroImages = [];
               if (is_dir($heroDir)) {
                  foreach (glob($heroDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $file) {
                     $heroImages[] = 'assets/img/hero-slides/' . basename($file);
                  }
               }
               if (empty($heroImages)) {
                  $heroImages = ['assets/img/slider/slider-01.png', 'assets/img/slider/slider-02.png'];
               }
            ?>
            <div class="home-hero-slider swiper">
               <div class="swiper-wrapper">
                  <?php foreach ($heroImages as $img): ?>
                     <div class="swiper-slide">
                        <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="BSC Elektronik">
                     </div>
                  <?php endforeach; ?>
               </div>
               <div class="swiper-button-prev"></div>
               <div class="swiper-button-next"></div>
               <div class="swiper-pagination"></div>
            </div>
         </div>
      </div>
   </div>
</section>

<!-- Kategoriler + Urunler -->
<section class="product-area pb-60 pt-30">
   <div class="container">
      <div class="row g-4">
         <!-- Kategoriler Sidebar -->
         <div class="col-xl-3 col-lg-4">
            <div class="home-category-box">
               <h4>Kategoriler</h4>
               <ul class="home-category-list">
                  <?php foreach ($categories as $category): ?>
                     <li class="home-category-item">
                        <?php if (!empty($category['children'])): ?>
                           <details>
                              <summary class="home-category-toggle">
                                 <span><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                 <i class="fal fa-angle-down" style="font-size:13px;color:#94a3b8;"></i>
                              </summary>
                              <ul class="home-subcategory-list">
                                 <?php foreach ($category['children'] as $child): ?>
                                    <li>
                                       <a href="index.php?route=products&category=<?= (int) $child['id'] ?>">
                                          <?= htmlspecialchars($child['name'], ENT_QUOTES, 'UTF-8') ?>
                                       </a>
                                    </li>
                                 <?php endforeach; ?>
                                 <li>
                                    <a href="index.php?route=products&category=<?= (int) $category['id'] ?>" style="font-weight:600;color:#2563eb;">
                                       Tümünü Gör &rarr;
                                    </a>
                                 </li>
                              </ul>
                           </details>
                        <?php else: ?>
                           <a href="index.php?route=products&category=<?= (int) $category['id'] ?>">
                              <span><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></span>
                           </a>
                        <?php endif; ?>
                     </li>
                  <?php endforeach; ?>
               </ul>
            </div>
         </div>

         <!-- Urunler Grid -->
         <div class="col-xl-9 col-lg-8">
            <h2 class="home-section-title">Öne Çıkan TV Yedek Parçaları</h2>
            <div class="row">
               <?php if (empty($products)): ?>
                  <div class="col-12">
                     <p class="text-muted" style="font-size:15px;">Henüz ürün eklenmemiş.</p>
                  </div>
               <?php endif; ?>
               <?php foreach ($products as $product): ?>
                  <?php $image = $product['image_path'] ?: 'assets/img/product/home-one/product-1.jpg'; ?>
                  <div class="col-xl-4 col-md-6">
                     <div class="home-product-card">
                        <div class="home-product-thumb">
                           <?php if ((int) $product['stock'] <= 0): ?>
                              <span class="out-of-stock-ribbon">Stokta Yok</span>
                           <?php endif; ?>
                           <a href="index.php?route=product&id=<?= (int) $product['id'] ?>">
                              <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                           </a>
                        </div>
                        <div class="home-product-info">
                           <div class="home-product-name">
                              <a href="index.php?route=product&id=<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></a>
                           </div>
                           <div class="home-product-brand"><?= htmlspecialchars((string) ($product['brand'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                           <div class="home-product-price"><?= Product::priceWithKdvFormatted((float) $product['price']) ?></div>
                           <div class="home-product-kdv"><?= Product::priceFormatted((float) $product['price']) ?> + KDV</div>
                           <?php $userId = \App\Core\UserAuth::id(); ?>
                           <?php if ($userId !== null): ?>
                              <?php $isFavorite = in_array((int) $product['id'], $favoriteIds ?? [], true); ?>
                              <form method="post" action="index.php?route=wishlist/toggle" class="home-fav-form js-wishlist-toggle" data-product-id="<?= (int) $product['id'] ?>">
                                 <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                 <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                 <input type="hidden" name="return_to" value="index.php?route=home">
                                 <button class="home-fav-btn" type="submit" aria-label="Favori">
                                    <i class="js-wishlist-icon <?= $isFavorite ? 'fas' : 'far' ?> fa-heart"></i>
                                 </button>
                              </form>
                           <?php else: ?>
                              <a class="home-fav-btn home-fav-form" href="index.php?route=auth/login" aria-label="Favori">
                                 <i class="far fa-heart"></i>
                              </a>
                           <?php endif; ?>
                        </div>
                     </div>
                  </div>
               <?php endforeach; ?>
            </div>
         </div>
      </div>
   </div>
</section>


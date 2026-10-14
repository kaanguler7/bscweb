<?php use App\Models\Product; ?>
<?php
$image = $product['image_path'] ?: 'assets/img/product/home-one/product-1.jpg';
$userId = \App\Core\UserAuth::id();
$partCodeValue = trim((string) ($product['part_code'] ?? '-'));
$displayNameValue = trim((string) ($displayName ?? $product['name'] ?? ''));
$whatsappPhone = '905359714587';
$seoCodes = isset($seoCodes) && is_array($seoCodes) ? $seoCodes : [];
$isPurchasable = (int) ($product['stock'] ?? 0) > 0 && (float) ($product['price'] ?? 0) > 0;
$technicalRows = array_values(array_filter(
   Product::technicalDefinitions(),
   static fn(array $definition): bool => trim((string) ($product['technical'][(string) $definition['code']] ?? '')) !== ''
));
?>
<section class="shop-details-area pt-50 pb-50">
   <div class="container">
      <div class="row mb-4 g-4">
         <div class="col-lg-5">
            <div class="detail-image-wrap">
               <?php if ((int) $product['stock'] <= 0): ?>
                  <span class="out-of-stock-ribbon">Stokta Yok</span>
               <?php endif; ?>
               <img class="detail-image-zoom-target" src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($displayNameValue, ENT_QUOTES, 'UTF-8') ?>">
            </div>
         </div>

         <div class="col-lg-7">
            <h1 class="detail-title"><?= htmlspecialchars($displayNameValue, ENT_QUOTES, 'UTF-8') ?></h1>

            <div class="detail-info">
               <p><strong>Marka:</strong> <?= htmlspecialchars((string) ($product['brand'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
               <p><strong>Model:</strong> <?= htmlspecialchars((string) ($product['model'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
               <p><strong>Parça Kodu:</strong> <code style="font-size:14px;background:#f1f5f9;padding:2px 8px;border-radius:4px;"><?= htmlspecialchars($partCodeValue, ENT_QUOTES, 'UTF-8') ?></code></p>
               <p><strong>Kategori:</strong> <?= htmlspecialchars((string) ($product['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
               <p><strong>Stok:</strong>
                  <?php if ((int) $product['stock'] > 0): ?>
                     <span class="badge bg-success"><?= (int) $product['stock'] ?> adet</span>
                  <?php else: ?>
                     <span class="badge bg-danger">Stokta yok</span>
                  <?php endif; ?>
               </p>
               <?php if ($seoCodes !== []): ?>
                  <div class="detail-seo-codes-wrap">
                     <strong>Parça ve Model Kodları:</strong>
                     <div class="detail-seo-codes">
                        <?php foreach (array_slice($seoCodes, 0, 12) as $code): ?>
                           <span class="detail-seo-code"><?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                     </div>
                  </div>
               <?php endif; ?>
            </div>

            <hr class="detail-divider">

            <?php if ((float) ($product['price'] ?? 0) > 0): ?>
               <div class="detail-price"><?= Product::priceWithKdvFormatted((float) $product['price']) ?></div>
               <div class="detail-kdv-note">
                  <?= Product::priceFormatted((float) $product['price']) ?> + KDV (<?= Product::kdvFormatted((float) $product['price']) ?>)
               </div>
            <?php else: ?>
               <div class="detail-price">Fiyat için iletişime geçin</div>
               <div class="detail-kdv-note">Bu ürün, geçerli fiyat girilene kadar internetten siparişe kapalıdır.</div>
            <?php endif; ?>

            <?php if ($isPurchasable): ?>
               <?php if ($userId !== null): ?>
                  <form method="post" action="index.php?route=cart/add">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                     <div class="d-flex align-items-center gap-3">
                        <input type="number" class="form-control" min="1" max="<?= (int) $product['stock'] ?>" name="quantity" value="1" style="width:90px;">
                        <button class="tp-btn" type="submit" style="padding:12px 32px;font-size:15px;">Sepete Ekle</button>
                     </div>
                  </form>
               <?php else: ?>
                  <form
                     method="get"
                     action="index.php"
                     class="js-guest-cart-choice-form"
                     data-whatsapp-phone="<?= htmlspecialchars($whatsappPhone, ENT_QUOTES, 'UTF-8') ?>"
                     data-product-name="<?= htmlspecialchars($displayNameValue, ENT_QUOTES, 'UTF-8') ?>"
                     data-part-code="<?= htmlspecialchars($partCodeValue, ENT_QUOTES, 'UTF-8') ?>"
                     data-product-url="index.php?route=product&id=<?= (int) $product['id'] ?>"
                  >
                     <input type="hidden" name="route" value="auth/login">
                     <input type="hidden" name="next" value="cart">
                     <input type="hidden" name="add_product" value="<?= (int) $product['id'] ?>">
                     <div class="d-flex align-items-center gap-3">
                        <input type="number" class="form-control" min="1" max="<?= (int) $product['stock'] ?>" name="quantity" value="1" style="width:90px;">
                        <button class="tp-btn" type="submit" style="padding:12px 32px;font-size:15px;">Sepete Ekle</button>
                     </div>
                  </form>

                  <div id="guest-cart-choice-modal" class="guest-cart-choice-modal" hidden>
                     <div class="guest-cart-choice-card" role="dialog" aria-modal="true" aria-labelledby="guest-cart-choice-title">
                        <button type="button" class="guest-cart-choice-close" data-guest-action="close" aria-label="Kapat">×</button>
                        <h5 id="guest-cart-choice-title" class="guest-cart-choice-title">Nasıl devam etmek istersiniz?</h5>
                        <p class="guest-cart-choice-text">Bu ürünü almak için üye girişi yapabilir veya WhatsApp üzerinden ürün bilgisiyle talep gönderebilirsiniz.</p>
                        <div class="guest-cart-choice-actions">
                           <a href="index.php?route=auth/login&next=cart&add_product=<?= (int) $product['id'] ?>&quantity=1" class="btn btn-dark" data-guest-action="login">Üye Girişi Yap ve Satın Al</a>
                           <a href="https://wa.me/<?= htmlspecialchars($whatsappPhone, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success" data-guest-action="whatsapp">WhatsApp'tan Yaz</a>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" data-guest-action="close">Vazgeç</button>
                     </div>
                  </div>
               <?php endif; ?>
            <?php elseif ((int) ($product['stock'] ?? 0) > 0): ?>
               <a class="btn btn-success mt-3" href="https://wa.me/<?= htmlspecialchars($whatsappPhone, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Fiyat bilgisi için WhatsApp</a>
            <?php endif; ?>

            <div class="mt-3">
               <?php if ($userId !== null): ?>
                  <?php $isFavorite = \App\Models\Wishlist::exists($userId, (int) $product['id']); ?>
                  <form method="post" action="index.php?route=wishlist/toggle" class="js-wishlist-toggle" data-product-id="<?= (int) $product['id'] ?>">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                     <input type="hidden" name="return_to" value="index.php?route=product&id=<?= (int) $product['id'] ?>">
                     <button class="detail-fav-btn" type="submit" aria-label="Favori">
                        <i class="js-wishlist-icon <?= !empty($isFavorite) ? 'fas' : 'far' ?> fa-heart"></i>
                     </button>
                  </form>
               <?php else: ?>
                  <a class="detail-fav-btn" href="index.php?route=auth/login" aria-label="Favori">
                     <i class="far fa-heart"></i>
                  </a>
               <?php endif; ?>
            </div>
         </div>
      </div>

      <div class="spec-wrap">
         <?php if (trim((string) ($product['description'] ?? '')) !== ''): ?>
            <section class="mb-4" aria-labelledby="product-description-heading">
               <h2 id="product-description-heading">Ürün Açıklaması</h2>
               <p><?= nl2br(htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8')) ?></p>
            </section>
         <?php endif; ?>
         <?php if ($technicalRows !== []): ?>
         <div class="row g-3">
            <div class="col-lg-8">
               <table class="spec-table">
                  <thead>
                     <tr>
                        <th colspan="2">Ürün Bilgisi</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($technicalRows as $definition): ?>
                        <?php $code = (string) $definition['code']; ?>
                        <?php $value = trim((string) ($product['technical'][$code] ?? '')); ?>
                        <?php if ($value === '') { continue; } ?>
                        <tr>
                           <th><?= htmlspecialchars((string) $definition['label'], ENT_QUOTES, 'UTF-8') ?></th>
                           <td><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <?php if (trim((string) ($product['technical']['supplier_code'] ?? '')) !== ''): ?>
            <div class="col-lg-4">
               <div class="spec-side">
                  <h5 class="mb-3">Ürün Filtreleri</h5>
                  <p style="font-size:14px;"><strong>Tedarikçi Ürün Kodu:</strong><br>
                     <?= htmlspecialchars((string) ($product['technical']['supplier_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                  </p>
               </div>
            </div>
            <?php endif; ?>
         </div>
         <?php endif; ?>
      </div>
   </div>
</section>

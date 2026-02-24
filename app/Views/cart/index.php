<?php use App\Models\Product; ?>
<section class="cart-area pt-60 pb-80">
   <div class="container">
      <h2 class="mb-30">Sepetim</h2>
      <?php if (count($items) === 0): ?>
         <p>Sepetiniz boş. <a href="index.php?route=products">Ürünlere dön</a></p>
      <?php else: ?>
         <div class="table-content table-responsive mb-30">
            <table class="table">
               <thead>
                  <tr>
                     <th>Ürün</th>
                     <th>Parça Kodu</th>
                     <th>Birim Fiyat</th>
                     <th>Adet</th>
                     <th>Tutar</th>
                     <th></th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($items as $item): ?>
                     <tr>
                        <td><?= htmlspecialchars($item['product']['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($item['product']['part_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                           <?= Product::priceWithKdvFormatted((float) $item['product']['price']) ?>
                           <br><small class="text-muted"><?= Product::priceFormatted((float) $item['product']['price']) ?> + KDV</small>
                        </td>
                        <td>
                           <form method="post" action="index.php?route=cart/update" class="d-flex gap-2">
                              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                              <input type="hidden" name="product_id" value="<?= (int) $item['product']['id'] ?>">
                              <input type="number" min="0" class="form-control" style="max-width:90px" name="quantity" value="<?= (int) $item['quantity'] ?>">
                              <button class="btn btn-sm btn-outline-primary" type="submit">Güncelle</button>
                           </form>
                        </td>
                        <td><?= Product::priceWithKdvFormatted((float) $item['lineTotal']) ?></td>
                        <td>
                           <form method="post" action="index.php?route=cart/remove">
                              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                              <input type="hidden" name="product_id" value="<?= (int) $item['product']['id'] ?>">
                              <button class="btn btn-sm btn-outline-danger" type="submit">Sil</button>
                           </form>
                        </td>
                     </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         </div>

         <div class="row g-3 align-items-end">
            <div class="col-lg-6">
               <?php if (empty($loggedUser)): ?>
                  <label class="form-label">Sipariş İçin Üyelik Gerekli</label>
                  <div class="d-flex gap-2">
                     <a class="btn btn-dark" href="index.php?route=auth/login">Giriş Yap</a>
                     <a class="btn btn-outline-secondary" href="index.php?route=auth/register">Kayıt Ol</a>
                  </div>
                  <p class="text-muted mt-2 mb-0" style="font-size:14px;">Siparişlerinizi sonradan görebilmek için üye girişi zorunludur.</p>
               <?php else: ?>
                  <label class="form-label">Sipariş Veren Kişi</label>
                  <form method="post" action="index.php?route=cart/checkout" class="d-flex gap-2">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <input class="form-control" type="text" name="customer_name" value="<?= htmlspecialchars((string) $loggedUser['full_name'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                     <button class="tp-btn" type="submit">Siparişi Oluştur</button>
                  </form>
               <?php endif; ?>
            </div>
            <div class="col-lg-6 text-lg-end">
               <h4>Genel Toplam: <?= Product::priceWithKdvFormatted((float) $total) ?></h4>
               <p class="text-muted" style="font-size:14px;">Ara Toplam: <?= Product::priceFormatted((float) $total) ?> + KDV: <?= Product::kdvFormatted((float) $total) ?></p>
            </div>
         </div>
      <?php endif; ?>

      <?php if (!empty($paymentOrder)): ?>
         <?php
            $orderStatus = (string) ($paymentOrder['status'] ?? '');
            $statusClass = 'secondary';
            $statusText = 'Bilinmiyor';
            if ($orderStatus === 'pending_payment') { $statusClass = 'warning'; $statusText = 'Ödeme Bekleniyor'; }
            if ($orderStatus === 'payment_notified') { $statusClass = 'info'; $statusText = 'Ödeme Bildirildi'; }
            if ($orderStatus === 'payment_rejected') { $statusClass = 'danger'; $statusText = 'Ödeme Reddedildi'; }
            if ($orderStatus === 'paid') { $statusClass = 'success'; $statusText = 'Ödeme Onaylandı'; }
         ?>
         <div class="card mt-40">
            <div class="card-body">
               <h4 class="mb-15">Havale / EFT Ödeme Bilgisi</h4>
               <p class="mb-2">
                  Sipariş No:
                  <strong>#<?= (int) $paymentOrder['id'] ?></strong>
                  <span class="badge bg-<?= $statusClass ?> ms-2"><?= $statusText ?></span>
               </p>
               <p class="mb-2">Tutar: <strong><?= Product::priceFormatted((float) $paymentOrder['total']) ?> + KDV</strong></p>
               <p class="mb-2">KDV Dahil: <strong><?= Product::priceWithKdvFormatted((float) $paymentOrder['total']) ?></strong></p>
               <hr>
               <p class="mb-1"><strong>Banka:</strong> <?= htmlspecialchars((string) ($paymentConfig['bank_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
               <p class="mb-1"><strong>Hesap Adı:</strong> <?= htmlspecialchars((string) ($paymentConfig['account_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
               <p class="mb-1">
                  <strong>IBAN:</strong>
                  <code style="color:#b91c1c;font-family:'OCR A Std','Consolas','Courier New',monospace;font-weight:900;font-size:1.08rem;letter-spacing:0.06em;background:#fff5f5;padding:3px 8px;border-radius:6px;">
                     <?= htmlspecialchars((string) ($paymentConfig['iban'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                  </code>
               </p>
               <div class="alert alert-warning mt-2 mb-0" role="alert" style="border-left:4px solid #f59e0b;">
                  <strong>Önemli:</strong> IBAN'a para gönderirken <strong>açıklama</strong> alanına mutlaka
                  <code style="font-size:1rem;font-weight:800;"><?= (int) $paymentOrder['id'] ?></code>
                  sipariş numarasını yazın (`#` işareti yazmanız gerekmez).
               </div>
            </div>
         </div>

         <?php if ($orderStatus !== 'paid' && !empty($loggedUser)): ?>
         <div class="card mt-20">
            <div class="card-body">
               <h5 class="mb-15">Ödeme Yaptım Bildirimi</h5>
               <form method="post" action="index.php?route=cart/payment-notify" enctype="multipart/form-data">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="order_id" value="<?= (int) $paymentOrder['id'] ?>">
                  <div class="row g-3">
                     <div class="col-md-6">
                        <label class="form-label">Ad Soyad</label>
                        <input class="form-control" type="text" name="customer_name" value="<?= htmlspecialchars((string) ($paymentOrder['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">Dekont (opsiyonel)</label>
                        <input class="form-control" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf">
                     </div>
                     <div class="col-12">
                        <label class="form-label">Not (opsiyonel)</label>
                        <textarea class="form-control" name="payment_note" rows="3" placeholder="Gönderen adı, banka bilgisi vb."><?= htmlspecialchars((string) ($paymentOrder['payment_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                     </div>
                  </div>
                  <div class="mt-15">
                     <button class="tp-btn" type="submit">Ödeme Yaptım</button>
                  </div>
               </form>
            </div>
         </div>
         <?php endif; ?>
         <?php if ($orderStatus !== 'paid' && empty($loggedUser)): ?>
         <div class="alert alert-warning mt-20 mb-0">
            Ödeme bildirimi göndermek için giriş yapmalısınız.
         </div>
         <?php endif; ?>
      <?php endif; ?>
   </div>
</section>

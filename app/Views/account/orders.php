<?php use App\Models\Product; ?>
<section class="pt-60 pb-80">
   <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-25">
         <h2 class="mb-0">Siparişlerim</h2>
         <div class="text-muted">Hoş geldiniz, <?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>

      <?php if ($orders === []): ?>
         <div class="card">
            <div class="card-body">
               <p class="mb-0">Henüz siparişiniz bulunmuyor.</p>
            </div>
         </div>
      <?php else: ?>
         <?php foreach ($orders as $order): ?>
            <?php
               $status = (string) ($order['status'] ?? '');
               $badgeClass = 'bg-secondary';
               $badgeText = 'Bilinmiyor';
               if ($status === 'pending_payment') { $badgeClass = 'bg-warning text-dark'; $badgeText = 'Ödeme Bekleniyor'; }
               if ($status === 'payment_notified') { $badgeClass = 'bg-info text-dark'; $badgeText = 'Ödeme Bildirildi'; }
               if ($status === 'payment_rejected') { $badgeClass = 'bg-danger'; $badgeText = 'Ödeme Reddedildi'; }
               if ($status === 'paid') { $badgeClass = 'bg-success'; $badgeText = 'Ödendi'; }
            ?>
            <div class="card mb-20">
               <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                     <strong>Sipariş #<?= (int) $order['id'] ?></strong>
                     <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                  </div>
                  <div class="text-muted mb-2" style="font-size:14px;">
                     Tarih: <?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?>
                     &middot;
                     E-posta: <?= htmlspecialchars((string) ($order['customer_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div class="table-responsive">
                     <table class="table">
                        <thead>
                           <tr>
                              <th>Ürün</th>
                              <th>Parça Kodu</th>
                              <th>Adet</th>
                              <th>Birim Fiyat</th>
                              <th>Tutar</th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php foreach ($order['items'] as $item): ?>
                              <tr>
                                 <td><?= htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                 <td><?= htmlspecialchars((string) ($item['part_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                 <td><?= (int) $item['quantity'] ?></td>
                                 <td><?= Product::priceFormatted((float) $item['unit_price']) ?></td>
                                 <td><?= Product::priceFormatted((float) $item['line_total']) ?></td>
                              </tr>
                           <?php endforeach; ?>
                        </tbody>
                     </table>
                  </div>
                  <div class="text-end"><strong>Toplam: <?= Product::priceFormatted((float) $order['total']) ?></strong></div>
               </div>
            </div>
         <?php endforeach; ?>
      <?php endif; ?>
   </div>
</section>

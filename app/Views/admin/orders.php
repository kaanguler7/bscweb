<?php use App\Models\Product; ?>
<?php
   $page = (int) ($pagination['page'] ?? 1);
   $totalPages = (int) ($pagination['totalPages'] ?? 1);
   $total = (int) ($pagination['total'] ?? 0);
   $q = (string) ($filters['q'] ?? '');
   $status = (string) ($filters['status'] ?? '');
   $dateFrom = (string) ($filters['date_from'] ?? '');
   $dateTo = (string) ($filters['date_to'] ?? '');
   $queryBase = [
      'route' => 'admin/orders',
      'q' => $q,
      'status' => $status,
      'date_from' => $dateFrom,
      'date_to' => $dateTo,
   ];
   $pageUrl = static function (int $targetPage) use ($queryBase): string {
      $params = $queryBase;
      $params['page'] = $targetPage;
      return 'index.php?' . http_build_query($params);
   };

   $statusBadge = static function (string $value): array {
      if ($value === 'pending_payment') return ['bg-warning text-dark', 'Ödeme bekleniyor'];
      if ($value === 'payment_notified') return ['bg-info text-dark', 'Ödeme bildirildi'];
      if ($value === 'payment_rejected') return ['bg-danger', 'Ödeme reddedildi'];
      if ($value === 'paid') return ['bg-success', 'Ödeme onaylandı'];
      return ['bg-secondary', 'Bilinmiyor'];
   };
?>
<section class="pt-60 pb-80">
   <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-20">
         <h2 class="mb-0">Siparişler</h2>
         <a class="btn btn-outline-secondary" href="index.php?route=admin/products">Ürün Yönetimi</a>
      </div>

      <div class="row g-4">
         <div class="col-xl-9">
            <div class="card mb-20">
               <div class="card-body">
                  <form method="get" action="index.php" class="row g-3 align-items-end">
                     <input type="hidden" name="route" value="admin/orders">
                     <div class="col-md-4">
                        <label class="form-label">Arama</label>
                        <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Sipariş no, müşteri, e-posta, ürün, parça kodu">
                     </div>
                     <div class="col-md-3">
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="status">
                           <option value="">Tüm durumlar</option>
                           <option value="pending_payment" <?= $status === 'pending_payment' ? 'selected' : '' ?>>Ödeme bekleniyor</option>
                           <option value="payment_notified" <?= $status === 'payment_notified' ? 'selected' : '' ?>>Ödeme bildirildi</option>
                           <option value="payment_rejected" <?= $status === 'payment_rejected' ? 'selected' : '' ?>>Ödeme reddedildi</option>
                           <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Ödeme onaylandı</option>
                        </select>
                     </div>
                     <div class="col-md-2">
                        <label class="form-label">Tarih (Başlangıç)</label>
                        <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
                     </div>
                     <div class="col-md-2">
                        <label class="form-label">Tarih (Bitiş)</label>
                        <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
                     </div>
                     <div class="col-md-1 d-grid">
                        <button class="btn btn-primary" type="submit">Filtrele</button>
                     </div>
                  </form>
                  <div class="d-flex justify-content-between align-items-center mt-3">
                     <div class="text-muted" style="font-size:13px;">
                        Toplam <strong><?= $total ?></strong> satır
                        <?php if ($totalPages > 1): ?>
                           &middot; Sayfa <?= $page ?>/<?= $totalPages ?>
                        <?php endif; ?>
                     </div>
                     <a class="btn btn-sm btn-outline-secondary" href="index.php?route=admin/orders">Temizle</a>
                  </div>
               </div>
            </div>

            <div class="card">
               <div class="card-body p-0">
                  <div class="table-responsive">
                     <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                           <tr>
                              <th style="min-width:88px;">Sipariş No</th>
                              <th style="min-width:150px;">Tarih</th>
                              <th style="min-width:145px;">Durum</th>
                              <th style="min-width:160px;">Müşteri</th>
                              <th style="min-width:190px;">E-posta</th>
                              <th style="min-width:220px;">Ürün</th>
                              <th style="min-width:140px;">Parça Kodu</th>
                              <th style="min-width:72px;">Adet</th>
                              <th style="min-width:130px;">Birim Fiyat (KDV Dahil)</th>
                              <th style="min-width:130px;">Satır Toplam (KDV Dahil)</th>
                              <th style="min-width:130px;">Sipariş Toplam (KDV Dahil)</th>
                              <th style="min-width:150px;">Ödeme Bildirimi</th>
                              <th style="min-width:150px;">Onay Tarihi</th>
                              <th style="min-width:120px;">Dekont</th>
                              <th style="min-width:240px;">Not</th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php if ($rows === []): ?>
                              <tr>
                                 <td colspan="15" class="text-center py-4 text-muted">Filtreye uygun sipariş satırı bulunamadı.</td>
                              </tr>
                           <?php else: ?>
                              <?php foreach ($rows as $row): ?>
                                 <?php [$badgeClass, $statusText] = $statusBadge((string) ($row['status'] ?? '')); ?>
                                 <tr>
                                    <td>#<?= (int) ($row['order_id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['order_created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars((string) ($row['customer_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($row['customer_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($row['product_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><code><?= htmlspecialchars((string) ($row['part_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td><?= (int) ($row['quantity'] ?? 0) ?></td>
                                    <td><?= Product::priceWithKdvFormatted((float) ($row['unit_price'] ?? 0)) ?></td>
                                    <td><?= Product::priceWithKdvFormatted((float) ($row['line_total'] ?? 0)) ?></td>
                                    <td><?= Product::priceWithKdvFormatted((float) ($row['order_total'] ?? 0)) ?></td>
                                    <td><?= htmlspecialchars((string) ($row['payment_notified_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($row['paid_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                       <?php if (!empty($row['payment_proof_path'])): ?>
                                          <a href="<?= htmlspecialchars((string) $row['payment_proof_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Aç</a>
                                       <?php else: ?>
                                          -
                                       <?php endif; ?>
                                    </td>
                                    <?php $noteText = trim((string) ($row['payment_note'] ?? '')); ?>
                                    <td class="orders-note-cell">
                                       <?php if ($noteText === ''): ?>
                                          -
                                       <?php else: ?>
                                          <div class="orders-note-preview" title="<?= htmlspecialchars($noteText, ENT_QUOTES, 'UTF-8') ?>">
                                             <?= htmlspecialchars($noteText, ENT_QUOTES, 'UTF-8') ?>
                                          </div>
                                          <div class="orders-note-actions">
                                             <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary js-orders-note-open"
                                                data-note="<?= htmlspecialchars($noteText, ENT_QUOTES, 'UTF-8') ?>"
                                             >Notu Gör</button>
                                          </div>
                                       <?php endif; ?>
                                    </td>
                                 </tr>
                              <?php endforeach; ?>
                           <?php endif; ?>
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>

            <?php if ($totalPages > 1): ?>
               <?php
                  $startPg = max(1, $page - 3);
                  $endPg = min($totalPages, $startPg + 6);
                  if ($endPg - $startPg < 6) {
                     $startPg = max(1, $endPg - 6);
                  }
               ?>
               <nav class="mt-3">
                  <ul class="pagination justify-content-center">
                     <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $pageUrl($page - 1) ?>">&laquo;</a>
                     </li>
                     <?php for ($i = $startPg; $i <= $endPg; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                           <a class="page-link" href="<?= $pageUrl($i) ?>"><?= $i ?></a>
                        </li>
                     <?php endfor; ?>
                     <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $pageUrl($page + 1) ?>">&raquo;</a>
                     </li>
                  </ul>
               </nav>
            <?php endif; ?>
         </div>

         <div class="col-xl-3">
            <div class="card mb-20">
               <div class="card-body">
                  <h5 class="mb-10">Günlük Giriş</h5>
                  <h3><?= (int) $todayVisitors ?> kişi</h3>
               </div>
            </div>
            <div class="card mb-20">
               <div class="card-body">
                  <h5 class="mb-10">Bugünkü Sipariş</h5>
                  <h3><?= (int) $todayOrders ?> sipariş</h3>
               </div>
            </div>
            <div class="card">
               <div class="card-body">
                  <h5 class="mb-10">Kullanım</h5>
                  <p class="text-muted mb-0" style="font-size:13px;">
                     Arama kutusuna sipariş no, müşteri, e-posta, ürün veya parça kodu yazabilirsin.
                     Tarih aralığı ve durum seçimi ile listeyi daraltabilirsin.
                  </p>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

<div class="orders-note-modal" id="orders-note-modal" aria-hidden="true">
   <div class="orders-note-modal-card" role="dialog" aria-modal="true" aria-labelledby="orders-note-modal-title">
      <div class="orders-note-modal-head">
         <strong id="orders-note-modal-title">Sipariş Notu</strong>
         <button type="button" class="orders-note-close js-orders-note-close">Kapat</button>
      </div>
      <div class="orders-note-modal-body" id="orders-note-modal-content"></div>
   </div>
</div>

<?php use App\Models\Product; ?>
<?php
$isEditing = $editingProduct !== null;
?>
<section class="pt-60 pb-80">
   <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-20">
         <h2 class="mb-0">Ürün Yönetimi</h2>
         <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="index.php?route=admin/brands">Marka Yönetimi</a>
            <a class="btn btn-outline-primary" href="index.php?route=admin/categories">Kategori Yönetimi</a>
         </div>
      </div>

      <div class="row g-4">
         <div class="col-xl-8">
            <div class="card mb-30">
               <div class="card-body">
                  <h4 class="mb-20"><?= $isEditing ? 'Ürün Güncelle' : 'Yeni Ürün Ekle' ?></h4>
                  <form method="post" enctype="multipart/form-data" action="index.php?route=<?= $isEditing ? 'admin/products/update' : 'admin/products/store' ?>">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <?php if ($isEditing): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingProduct['id'] ?>">
                     <?php endif; ?>

                     <div class="row g-3">
                        <div class="col-md-6">
                           <label class="form-label">Ürün Adı</label>
                           <input class="form-control" type="text" name="name" required maxlength="180" value="<?= htmlspecialchars((string) ($editingProduct['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                           <label class="form-label" for="product-description">Ürün Açıklaması</label>
                           <textarea class="form-control" id="product-description" name="description" rows="4" maxlength="5000" placeholder="Doğrulanmış parça kodları, uyumlu modeller, kondisyon ve test bilgilerini yazın."><?= htmlspecialchars((string) ($editingProduct['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                           <div class="form-text">Yalnızca doğruladığınız teknik ve uyumluluk bilgilerini yazın. Açıklama ürün sayfasında görünür.</div>
                        </div>
                        <div class="col-md-3">
                           <label class="form-label">Marka</label>
                           <select class="form-select" name="brand" required>
                              <option value="">Seçmeyin</option>
                              <?php foreach ($brands as $brandOption): ?>
                                 <option value="<?= htmlspecialchars($brandOption['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ((string) ($editingProduct['brand'] ?? '') === $brandOption['name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($brandOption['name'], ENT_QUOTES, 'UTF-8') ?>
                                 </option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="col-md-3">
                           <label class="form-label">Model</label>
                           <input class="form-control" type="text" name="model" maxlength="160" value="<?= htmlspecialchars((string) ($editingProduct['model'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3">
                           <label class="form-label">Parça Kodu</label>
                           <input class="form-control" type="text" name="part_code" required maxlength="120" autocomplete="off" placeholder="Örn. 17PW26-4" value="<?= htmlspecialchars((string) ($editingProduct['part_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                           <div class="form-text">Tek ana parça kodu girin. Diğer uyumlu kodları ürün adı veya teknik bilgi alanlarına yazın.</div>
                        </div>
                        <div class="col-md-3">
                           <label class="form-label">Kategori</label>
                           <select class="form-select" name="category_id" required>
                              <option value="">Seçmeyin</option>
                              <?php foreach ($categories as $category): ?>
                                 <option value="<?= (int) $category['id'] ?>" <?= ((int) ($editingProduct['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['display_name'], ENT_QUOTES, 'UTF-8') ?>
                                 </option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="col-md-3">
                           <label class="form-label">Fiyat (TL)</label>
                           <input class="form-control" type="number" step="0.01" min="0.01" name="price" id="price-input" required value="<?= htmlspecialchars((string) ($editingProduct['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3">
                           <label class="form-label">Stok</label>
                           <input class="form-control" type="number" min="0" name="stock" required value="<?= htmlspecialchars((string) ($editingProduct['stock'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="col-md-6" id="kdv-preview-wrap" style="display:none;">
                           <label class="form-label">KDV Onizleme (%20)</label>
                           <div class="p-2 bg-light border rounded" style="font-size:14px;">
                              <span id="kdv-base">0,00 TL</span> +
                              <span id="kdv-amount" class="text-primary">0,00 TL</span> KDV =
                              <strong id="kdv-total" class="text-success">0,00 TL</strong>
                           </div>
                        </div>

                        <div class="col-md-6">
                           <label class="form-label">Ürün Fotoğrafı</label>
                           <input class="form-control" type="file" name="image" accept="image/png,image/jpeg,image/webp" <?= $isEditing ? '' : 'required' ?>>
                           <?php if (!$isEditing): ?>
                              <div class="form-text">Google ürün sonuçları için yeni üründe gerçek ürün fotoğrafı zorunludur.</div>
                           <?php endif; ?>
                        </div>
                        <?php if ($isEditing && !empty($editingProduct['image_path'])): ?>
                           <div class="col-md-6">
                              <label class="form-label">Mevcut Fotograf</label>
                              <div><img src="<?= htmlspecialchars($editingProduct['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="mevcut" style="max-height:120px"></div>
                           </div>
                        <?php endif; ?>

                        <div class="col-12">
                           <hr>
                           <h5 class="mb-2">Teknik Bilgi</h5>
                        </div>
                        <?php foreach ($technicalDefinitions as $spec): ?>
                           <?php $code = (string) $spec['code']; ?>
                           <div class="col-md-6">
                              <label class="form-label"><?= htmlspecialchars((string) $spec['label'], ENT_QUOTES, 'UTF-8') ?></label>
                              <input
                                 class="form-control"
                                 type="text"
                                 maxlength="255"
                                 name="technical[<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>]"
                                 value="<?= htmlspecialchars((string) (($editingProduct['technical'][$code] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                              >
                           </div>
                        <?php endforeach; ?>
                     </div>

                     <div class="mt-20 d-flex gap-2">
                        <button class="tp-btn" type="submit"><?= $isEditing ? 'Güncelle' : 'Ekle' ?></button>
                        <?php if ($isEditing): ?>
                           <a href="index.php?route=admin/products" class="btn btn-outline-secondary">Vazgeç</a>
                        <?php endif; ?>
                     </div>
                  </form>
               </div>
            </div>

            <?php
               $pPage = (int) $pagination['page'];
               $pTotal = (int) $pagination['total'];
               $pTotalPages = (int) $pagination['totalPages'];
               $pageUrl = function (int $pg) use ($searchTerm): string {
                  $params = ['route' => 'admin/products', 'page' => $pg];
                  if ($searchTerm !== '') {
                     $params['q'] = $searchTerm;
                  }
                  return 'index.php?' . http_build_query($params) . '#product-list';
               };
            ?>

            <div id="product-list" class="card mb-20">
               <div class="card-body pb-2">
                  <form method="get" action="index.php#product-list" class="d-flex align-items-center gap-3 mb-2">
                     <input type="hidden" name="route" value="admin/products">
                     <div class="flex-grow-1">
                        <div class="input-group input-group-lg">
                           <span class="input-group-text bg-white"><i class="fal fa-search text-muted"></i></span>
                           <input class="form-control border-start-0" type="text" name="q"
                                  placeholder="Ürün adı, parça kodu, marka veya model ile ara..."
                                  value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>"
                                  style="font-size:15px;">
                        </div>
                     </div>
                     <button type="submit" class="btn btn-primary btn-lg" style="white-space:nowrap;">Ara</button>
                     <?php if ($searchTerm !== ''): ?>
                        <a href="index.php?route=admin/products#product-list" class="btn btn-outline-secondary btn-lg" style="white-space:nowrap;">Temizle</a>
                     <?php endif; ?>
                  </form>
                  <div class="text-muted" style="font-size:13px;">
                     <?php if ($searchTerm !== ''): ?>
                        <strong><?= $pTotal ?></strong> ürün bulundu
                     <?php else: ?>
                        Toplam <strong><?= $pTotal ?></strong> ürün
                     <?php endif; ?>
                     <?php if ($pTotalPages > 1): ?>
                        &middot; Sayfa <?= $pPage ?>/<?= $pTotalPages ?>
                     <?php endif; ?>
                  </div>
               </div>
            </div>

            <div class="table-content table-responsive" style="min-height:600px;">
               <table class="table table-hover">
                  <thead class="table-light">
                     <tr>
                        <th>ID</th>
                        <th>Fotograf</th>
                        <th>Ürün</th>
                        <th>Kategori</th>
                        <th>Parça Kodu</th>
                        <th>Stok</th>
                        <th>Fiyat</th>
                        <th>İşlemler</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($products)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">
                           <?= $searchTerm !== '' ? 'Aramanızla eşleşen ürün bulunamadı.' : 'Henüz ürün eklenmemiş.' ?>
                        </td></tr>
                     <?php endif; ?>
                     <?php foreach ($products as $product): ?>
                        <?php $image = $product['image_path'] ?: 'assets/img/product/home-one/product-1.jpg'; ?>
                        <tr>
                           <td><?= (int) $product['id'] ?></td>
                           <td><img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="ürün" style="width:60px;height:60px;object-fit:cover;border-radius:4px"></td>
                           <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                           <td><?= htmlspecialchars($categoryMap[(int) $product['category_id']] ?? 'Belirsiz', ENT_QUOTES, 'UTF-8') ?></td>
                           <td><code><?= htmlspecialchars((string) ($product['part_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                           <td>
                              <span class="badge <?= (int) $product['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                 <?= (int) $product['stock'] ?>
                              </span>
                           </td>
                           <td>
                              <?= Product::priceFormatted((float) $product['price']) ?>
                              <br><small class="text-muted">KDV dahil: <?= Product::priceWithKdvFormatted((float) $product['price']) ?></small>
                           </td>
                           <td>
                              <div class="d-flex gap-2">
                                 <a class="btn btn-sm btn-outline-primary" href="index.php?route=admin/products&edit=<?= (int) $product['id'] ?>">Düzenle</a>
                                 <form method="post" action="index.php?route=admin/products/delete" onsubmit="return confirm('Bu ürünü silmek istiyor musunuz?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Sil</button>
                                 </form>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>

            <?php if ($pTotalPages > 1): ?>
            <nav class="mt-3">
               <ul class="pagination justify-content-center">
                  <li class="page-item <?= $pPage <= 1 ? 'disabled' : '' ?>">
                     <a class="page-link" href="<?= $pageUrl($pPage - 1) ?>">&laquo;</a>
                  </li>
                  <?php
                     $startPg = max(1, $pPage - 3);
                     $endPg = min($pTotalPages, $startPg + 6);
                     if ($endPg - $startPg < 6) {
                        $startPg = max(1, $endPg - 6);
                     }
                  ?>
                  <?php for ($i = $startPg; $i <= $endPg; $i++): ?>
                     <li class="page-item <?= $i === $pPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $pageUrl($i) ?>"><?= $i ?></a>
                     </li>
                  <?php endfor; ?>
                  <li class="page-item <?= $pPage >= $pTotalPages ? 'disabled' : '' ?>">
                     <a class="page-link" href="<?= $pageUrl($pPage + 1) ?>">&raquo;</a>
                  </li>
               </ul>
            </nav>
            <?php endif; ?>
         </div>

         <div class="col-xl-4">
            <div class="card mb-20">
               <div class="card-body">
                  <h5 class="mb-10">Bugünkü tahmini tekil ziyaretçi</h5>
                  <h3><?= (int) $todayVisitors ?> kişi</h3>
                  <small>Türkiye saatine göre; bilinen botlar ve yönetici ziyaretleri hariç.</small>
               </div>
            </div>
            <div class="card mb-30">
               <div class="card-body">
                  <h5 class="mb-10">Bugünkü Sipariş</h5>
                  <h3><?= (int) $todayOrders ?> sipariş</h3>
               </div>
            </div>

            <div class="card mb-30">
               <div class="card-body">
                  <h5 class="mb-20">Ödeme Onay Bekleyenler</h5>
                  <?php if ($pendingPayments === []): ?>
                     <p class="mb-0">Bekleyen ödeme yok.</p>
                  <?php else: ?>
                     <?php foreach ($pendingPayments as $paymentOrder): ?>
                        <div class="border-bottom pb-10 mb-10">
                           <div class="d-flex justify-content-between align-items-center">
                              <strong>#<?= (int) $paymentOrder['id'] ?> - <?= htmlspecialchars($paymentOrder['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                              <?php
                                 $pStatus = (string) ($paymentOrder['status'] ?? '');
                                 $pStatusClass = 'bg-secondary';
                                 $pStatusText = 'Bilinmiyor';
                                 if ($pStatus === 'pending_payment') { $pStatusClass = 'bg-warning text-dark'; $pStatusText = 'Ödeme Bekleniyor'; }
                                 if ($pStatus === 'payment_notified') { $pStatusClass = 'bg-info text-dark'; $pStatusText = 'Bildirim Geldi'; }
                                 if ($pStatus === 'payment_rejected') { $pStatusClass = 'bg-danger'; $pStatusText = 'Reddedildi'; }
                              ?>
                              <span class="badge <?= $pStatusClass ?>"><?= $pStatusText ?></span>
                           </div>
                           <div class="text-muted" style="font-size:13px;">
                              Tutar (KDV Dahil): <?= Product::priceWithKdvFormatted((float) $paymentOrder['total']) ?>
                              <?php if (!empty($paymentOrder['payment_notified_at'])): ?>
                                 &middot; Bildirim: <?= htmlspecialchars((string) $paymentOrder['payment_notified_at'], ENT_QUOTES, 'UTF-8') ?>
                              <?php endif; ?>
                           </div>
                           <?php if (!empty($paymentOrder['items'])): ?>
                              <div style="font-size:13px;">
                                 <?php foreach ($paymentOrder['items'] as $item): ?>
                                    <div><?= (int) $item['quantity'] ?>x <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                 <?php endforeach; ?>
                              </div>
                           <?php endif; ?>
                           <?php if (!empty($paymentOrder['payment_note'])): ?>
                              <div style="font-size:12px;" class="text-muted mt-1">
                                 Not: <?= htmlspecialchars((string) $paymentOrder['payment_note'], ENT_QUOTES, 'UTF-8') ?>
                              </div>
                           <?php endif; ?>
                           <?php if (!empty($paymentOrder['payment_proof_path'])): ?>
                              <div class="mt-1">
                                 <a href="<?= htmlspecialchars((string) $paymentOrder['payment_proof_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Dekontu Aç</a>
                              </div>
                           <?php endif; ?>
                           <div class="d-flex gap-2 mt-2">
                              <form method="post" action="index.php?route=admin/orders/approve">
                                 <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                 <input type="hidden" name="order_id" value="<?= (int) $paymentOrder['id'] ?>">
                                 <button class="btn btn-sm btn-success" type="submit">Onayla</button>
                              </form>
                              <form method="post" action="index.php?route=admin/orders/reject" class="d-flex gap-2">
                                 <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                 <input type="hidden" name="order_id" value="<?= (int) $paymentOrder['id'] ?>">
                                 <input type="text" class="form-control form-control-sm" name="note" placeholder="Red notu (opsiyonel)">
                                 <button class="btn btn-sm btn-outline-danger" type="submit">Red</button>
                              </form>
                           </div>
                        </div>
                     <?php endforeach; ?>
                  <?php endif; ?>
               </div>
            </div>

            <div class="card">
               <div class="card-body">
                  <h5 class="mb-20">Son Satın Alanlar</h5>
                  <?php if ($recentOrders === []): ?>
                     <p>Henüz sipariş yok.</p>
                  <?php else: ?>
                     <?php foreach ($recentOrders as $order): ?>
                        <div class="border-bottom pb-10 mb-10">
                           <div class="d-flex justify-content-between align-items-center">
                              <strong><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                              <?php
                                 $status = (string) ($order['status'] ?? '');
                                 $statusClass = 'bg-secondary';
                                 $statusText = 'Bilinmiyor';
                                 if ($status === 'pending_payment') { $statusClass = 'bg-warning text-dark'; $statusText = 'Ödeme Bekleniyor'; }
                                 if ($status === 'payment_notified') { $statusClass = 'bg-info text-dark'; $statusText = 'Bildirim Geldi'; }
                                 if ($status === 'payment_rejected') { $statusClass = 'bg-danger'; $statusText = 'Red'; }
                                 if ($status === 'paid') { $statusClass = 'bg-success'; $statusText = 'Ödendi'; }
                              ?>
                              <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                           </div>
                           <div class="text-muted" style="font-size:13px;"><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                           <?php foreach ($order['items'] as $item): ?>
                              <div style="font-size:13px;">
                                 <?= (int) $item['quantity'] ?>x <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?>
                              </div>
                           <?php endforeach; ?>
                           <div class="mt-1"><strong><?= Product::priceFormatted((float) $order['total']) ?></strong></div>
                        </div>
                     <?php endforeach; ?>
                  <?php endif; ?>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

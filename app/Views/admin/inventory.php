<?php use App\Models\Product; ?>
<section class="pt-60 pb-80">
   <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-20">
         <h2 class="mb-0">Envanter Kontrol</h2>
         <a class="btn btn-outline-secondary" href="index.php?route=admin/products">Ürün Yönetimi</a>
      </div>

      <div class="row g-4">
         <div class="col-xl-8">
            <div class="card mb-30">
               <div class="card-body">
                  <div class="mb-20">
                     <label class="form-label fw-bold" style="font-size:16px;">Ürün / Parça Kodu Ara</label>
                     <input id="inventory-search" class="form-control form-control-lg" type="text"
                            placeholder="Ürün adı, parça kodu, marka veya model girin..."
                            value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                     <small class="text-muted">Yazdığınızda sonuçlar otomatik filtrelenir. Parça koduna veya ürün adına göre arama yapabilirsiniz.</small>
                  </div>

                  <div id="inventory-results">
                     <div class="table-responsive">
                        <table class="table table-hover" id="inventory-table">
                           <thead class="table-light">
                              <tr>
                                 <th>Ürün Adı</th>
                                 <th>Parça Kodu</th>
                                 <th>Marka</th>
                                 <th>Model</th>
                                 <th style="width:80px">Stok</th>
                                 <th style="width:120px">Fiyat</th>
                              </tr>
                           </thead>
                           <tbody id="inventory-tbody">
                              <?php if (empty($products)): ?>
                                 <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sonuç bulunamadı.</td>
                                 </tr>
                              <?php else: ?>
                                 <?php foreach ($products as $product): ?>
                                    <tr>
                                       <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                       <td><code><?= htmlspecialchars((string) ($product['part_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                                       <td><?= htmlspecialchars((string) ($product['brand'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                       <td><?= htmlspecialchars((string) ($product['model'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                       <td>
                                          <span class="badge <?= (int) $product['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                             <?= (int) $product['stock'] ?>
                                          </span>
                                       </td>
                                       <td><?= Product::priceFormatted((float) $product['price']) ?></td>
                                    </tr>
                                 <?php endforeach; ?>
                              <?php endif; ?>
                           </tbody>
                        </table>
                     </div>
                     <div id="inventory-info" class="d-flex justify-content-between align-items-center mt-2">
                        <p id="inventory-count" class="text-muted mb-0">
                           <?php if ($searchTerm !== ''): ?>
                              <strong><?= (int) $pagination['total'] ?></strong> ürün bulundu
                           <?php else: ?>
                              Toplam <strong><?= (int) $pagination['total'] ?></strong> ürün
                           <?php endif; ?>
                           <?php if ((int) $pagination['totalPages'] > 1): ?>
                              &middot; Sayfa <?= (int) $pagination['page'] ?>/<?= (int) $pagination['totalPages'] ?>
                           <?php endif; ?>
                        </p>
                     </div>
                     <nav id="inventory-pagination" class="mt-3">
                        <?php if ((int) $pagination['totalPages'] > 1): ?>
                        <ul class="pagination justify-content-center">
                           <li class="page-item <?= (int) $pagination['page'] <= 1 ? 'disabled' : '' ?>">
                              <a class="page-link" href="#" data-page="<?= (int) $pagination['page'] - 1 ?>">&laquo;</a>
                           </li>
                           <?php
                              $startPg = max(1, (int) $pagination['page'] - 3);
                              $endPg = min((int) $pagination['totalPages'], $startPg + 6);
                              if ($endPg - $startPg < 6) $startPg = max(1, $endPg - 6);
                           ?>
                           <?php for ($i = $startPg; $i <= $endPg; $i++): ?>
                              <li class="page-item <?= $i === (int) $pagination['page'] ? 'active' : '' ?>">
                                 <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                              </li>
                           <?php endfor; ?>
                           <li class="page-item <?= (int) $pagination['page'] >= (int) $pagination['totalPages'] ? 'disabled' : '' ?>">
                              <a class="page-link" href="#" data-page="<?= (int) $pagination['page'] + 1 ?>">&raquo;</a>
                           </li>
                        </ul>
                        <?php endif; ?>
                     </nav>
                  </div>
               </div>
            </div>
         </div>

         <div class="col-xl-4">
            <div class="card mb-20">
               <div class="card-body">
                  <h5 class="mb-10">Bugünkü tahmini tekil ziyaretçi</h5>
                  <h3><?= (int) $todayVisitors ?> kişi</h3>
                  <small>Türkiye saatine göre; bilinen botlar ve yönetici ziyaretleri hariç.</small>
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
                  <h5 class="mb-10">Envanter Bilgisi</h5>
                  <p class="text-muted" style="font-size:13px;">Bu sayfayı kullanarak sistemdeki ürünleri parça koduna veya ada göre arayabilirsiniz. Fiziksel stoğunuzu sistem kayıtlarıyla karşılaştırmak için kullanın.</p>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

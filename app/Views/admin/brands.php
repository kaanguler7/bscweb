<?php
$isEditing = $editingBrand !== null;
?>
<section class="pt-60 pb-80">
   <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-20">
         <h2 class="mb-0">Marka Yönetimi</h2>
         <a class="btn btn-outline-secondary" href="index.php?route=admin/products">Ürün Yönetimi</a>
      </div>

      <div class="row g-4">
         <div class="col-xl-8">
            <div class="card mb-30">
               <div class="card-body">
                  <h4 class="mb-20"><?= $isEditing ? 'Marka Güncelle' : 'Yeni Marka Ekle' ?></h4>
                  <form method="post" action="index.php?route=<?= $isEditing ? 'admin/brands/update' : 'admin/brands/store' ?>" class="row g-3 align-items-end mb-20">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <?php if ($isEditing): ?>
                        <input type="hidden" name="id" value="<?= (int) $editingBrand['id'] ?>">
                     <?php endif; ?>

                     <div class="col-md-8">
                        <label class="form-label">Marka Adı</label>
                        <input class="form-control" type="text" name="name" required value="<?= htmlspecialchars((string) ($editingBrand['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ornek: Samsung, LG, Vestel...">
                     </div>
                     <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit"><?= $isEditing ? 'Güncelle' : 'Ekle' ?></button>
                        <?php if ($isEditing): ?>
                           <a href="index.php?route=admin/brands" class="btn btn-outline-secondary">İptal</a>
                        <?php endif; ?>
                     </div>
                  </form>

                  <div class="table-responsive">
                     <table class="table table-hover">
                        <thead>
                           <tr>
                              <th style="width:60px">ID</th>
                              <th>Marka Adı</th>
                              <th style="width:180px">Islem</th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php if (empty($brands)): ?>
                              <tr>
                                 <td colspan="3" class="text-center text-muted py-4">Henüz marka eklenmemiş.</td>
                              </tr>
                           <?php else: ?>
                              <?php foreach ($brands as $brand): ?>
                                 <tr>
                                    <td><?= (int) $brand['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($brand['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td class="d-flex gap-2">
                                       <a class="btn btn-sm btn-outline-primary" href="index.php?route=admin/brands&brand_edit=<?= (int) $brand['id'] ?>">Düzenle</a>
                                       <form method="post" action="index.php?route=admin/brands/delete" onsubmit="return confirm('Bu markayı silmek istiyor musunuz?');">
                                          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                          <input type="hidden" name="id" value="<?= (int) $brand['id'] ?>">
                                          <button class="btn btn-sm btn-outline-danger" type="submit">Sil</button>
                                       </form>
                                    </td>
                                 </tr>
                              <?php endforeach; ?>
                           <?php endif; ?>
                        </tbody>
                     </table>
                  </div>
                  <p class="text-muted mt-2"><?= count($brands) ?> marka kayıtlı.</p>
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
            <div class="card">
               <div class="card-body">
                  <h5 class="mb-10">Bugünkü Sipariş</h5>
                  <h3><?= (int) $todayOrders ?> sipariş</h3>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>


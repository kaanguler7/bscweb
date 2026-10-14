<section class="pt-80 pb-80">
   <div class="container">
      <div class="row justify-content-center">
         <div class="col-lg-4 col-md-6">
            <div class="card">
               <div class="card-body p-4">
                  <h3 class="mb-3">Admin Giriş</h3>
                  <p class="text-muted mb-4">Bu alana sadece yetkili kullanıcılar erişebilir.</p>
                  <form method="post" action="index.php?route=admin/login">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input class="form-control" type="text" name="username" required>
                     </div>
                     <div class="mb-4">
                        <label class="form-label">Sifre</label>
                        <input class="form-control" type="password" name="password" required>
                     </div>
                     <button class="btn btn-dark w-100" type="submit">Giriş Yap</button>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>


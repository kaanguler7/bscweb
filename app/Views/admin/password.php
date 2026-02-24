<section class="pt-60 pb-80">
   <div class="container">
      <div class="row justify-content-center">
         <div class="col-xl-6 col-lg-7">
            <div class="card mb-20">
               <div class="card-body p-4 p-lg-5">
                  <h3 class="mb-3">Şifre Değiştir</h3>
                  <p class="text-muted mb-4">
                     Güvenliğiniz için mevcut şifrenizi doğrulayarak yeni şifre belirleyin.
                  </p>

                  <form method="post" action="index.php?route=admin/password/update">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                     <div class="mb-3">
                        <label class="form-label">Mevcut Şifre</label>
                        <input class="form-control" type="password" name="current_password" required autocomplete="current-password">
                     </div>

                     <div class="mb-3">
                        <label class="form-label">Yeni Şifre</label>
                        <input class="form-control" type="password" name="new_password" required minlength="8" autocomplete="new-password">
                        <div class="form-text">Yeni şifre en az 8 karakter olmalıdır.</div>
                     </div>

                     <div class="mb-4">
                        <label class="form-label">Yeni Şifre (Tekrar)</label>
                        <input class="form-control" type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password">
                     </div>

                     <div class="d-flex gap-2">
                        <button class="btn btn-dark" type="submit">Şifreyi Güncelle</button>
                        <a class="btn btn-outline-secondary" href="index.php?route=admin/products">İptal</a>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="pt-80 pb-80">
   <div class="container">
      <div class="row justify-content-center">
         <div class="col-lg-5 col-md-7">
            <div class="card">
               <div class="card-body p-4">
                  <h3 class="mb-3">Yeni Şifre Belirle</h3>
                  <p class="text-muted mb-4">Lütfen hesabınız için yeni bir şifre oluşturun.</p>
                  <form method="post" action="index.php?route=auth/reset-password">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <input type="hidden" name="token" value="<?= htmlspecialchars((string) ($token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                     <div class="mb-3">
                        <label class="form-label">Yeni Şifre (en az 8 karakter)</label>
                        <input class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password">
                     </div>
                     <div class="mb-4">
                        <label class="form-label">Yeni Şifre Tekrar</label>
                        <input class="form-control" type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                     </div>
                     <button class="btn btn-dark w-100" type="submit">Şifreyi Güncelle</button>
                  </form>
                  <div class="mt-3 text-center">
                     <a href="index.php?route=auth/login">Giriş sayfasına dön</a>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

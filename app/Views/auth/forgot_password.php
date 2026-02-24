<section class="pt-80 pb-80">
   <div class="container">
      <div class="row justify-content-center">
         <div class="col-lg-5 col-md-7">
            <div class="card">
               <div class="card-body p-4">
                  <h3 class="mb-3">Şifremi Unuttum</h3>
                  <p class="text-muted mb-4">
                     E-posta adresinizi yazın. Hesabınız varsa şifre yenileme bağlantısı göndereceğiz.
                  </p>
                  <form method="post" action="index.php?route=auth/forgot-password">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <div class="mb-4">
                        <label class="form-label">E-posta</label>
                        <input class="form-control" type="email" name="email" required autocomplete="email">
                     </div>
                     <button class="btn btn-dark w-100" type="submit">Şifre Sıfırlama Linki Gönder</button>
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

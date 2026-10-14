</main>
<footer>
   <div class="footer-area black-bg pt-40 pb-20">
      <div class="container">
         <div class="row">
            <div class="col-12 text-center">
               <p class="text-white mb-0">BSC Elektronik - TV Yedek Parça E-Ticaret Altyapısı</p>
            </div>
         </div>
      </div>
   </div>
</footer>
<a
   class="whatsapp-support-float"
   href="https://wa.me/905359714587"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="WhatsApp Destek Hattı"
>
   <i class="fab fa-whatsapp" aria-hidden="true"></i>
   <span>WhatsApp Destek Hattı</span>
</a>
<script src="assets/js/jquery.js"></script>
<script src="assets/js/waypoints.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/meanmenu.js"></script>
<script src="assets/js/swiper-bundle.js"></script>
<script src="assets/js/slick.js"></script>
<script src="assets/js/magnific-popup.js"></script>
<script src="assets/js/counterup.js"></script>
<script src="assets/js/wow.js"></script>
<script src="assets/js/countdown.js"></script>
<script src="assets/js/nice-select.js"></script>
<script src="assets/js/jquery-ui.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/custom/layout-search.js"></script>
<script src="assets/js/custom/mobile-nav.js"></script>
<script src="assets/js/custom/wishlist-toggle.js"></script>
<?php
   if (!isset($currentRoute) || !is_string($currentRoute) || $currentRoute === '') {
      $currentRoute = (string) ($_GET['route'] ?? 'home');
      if ($currentRoute === '') {
         $currentRoute = 'home';
      }
   }

   $publicJsMap = [
      'home' => 'home-index.js',
      'products' => 'product-index.js',
      'product' => 'product-show.js',
   ];
?>
<?php if (isset($publicJsMap[$currentRoute])): ?>
<script src="assets/js/custom/<?= htmlspecialchars($publicJsMap[$currentRoute], ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>
</body>
</html>



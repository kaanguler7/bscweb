<?php
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int) $qty;
    }
}
$loggedUser = \App\Core\UserAuth::user();
$wishlistCount = $loggedUser !== null ? \App\Models\Wishlist::countForUser((int) $loggedUser['id']) : 0;
$currentRoute = (string) ($_GET['route'] ?? 'home');
if ($currentRoute === '') {
    $currentRoute = 'home';
}
$pageCssMap = [
    'home' => 'home-index.css',
    'products' => 'product-index.css',
    'product' => 'product-show.css',
    'about' => 'page-about.css',
    'contact' => 'page-contact.css',
];
$pageCssFile = $pageCssMap[$currentRoute] ?? null;
$metaDescription = isset($metaDescription) ? trim((string) $metaDescription) : '';
$metaKeywords = isset($metaKeywords) ? trim((string) $metaKeywords) : '';
$canonicalUrl = isset($canonicalUrl) ? trim((string) $canonicalUrl) : '';
$ogImage = isset($ogImage) ? trim((string) $ogImage) : '';
$ogType = isset($ogType) ? trim((string) $ogType) : 'website';
$privateRoutes = ['cart', 'wishlist'];
$isPrivateRoute = in_array($currentRoute, $privateRoutes, true)
    || str_starts_with($currentRoute, 'auth/')
    || str_starts_with($currentRoute, 'account/');
$metaRobots = isset($metaRobots)
    ? trim((string) $metaRobots)
    : ($isPrivateRoute ? 'noindex,nofollow' : 'index,follow');
$jsonLd = isset($jsonLd) ? trim((string) $jsonLd) : '';
?>
<!doctype html>
<html class="no-js" lang="tr">
<head>
   <meta charset="utf-8">
   <meta http-equiv="x-ua-compatible" content="ie=edge">
   <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <?php if ($metaDescription !== ''): ?>
      <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
      <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
   <?php endif; ?>
   <?php if ($metaKeywords !== ''): ?>
      <meta name="keywords" content="<?= htmlspecialchars($metaKeywords, ENT_QUOTES, 'UTF-8') ?>">
   <?php endif; ?>
   <meta name="robots" content="<?= htmlspecialchars($metaRobots, ENT_QUOTES, 'UTF-8') ?>">
   <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
   <meta property="og:type" content="<?= htmlspecialchars($ogType !== '' ? $ogType : 'website', ENT_QUOTES, 'UTF-8') ?>">
   <?php if ($canonicalUrl !== ''): ?>
      <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
      <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
   <?php endif; ?>
   <?php if ($ogImage !== ''): ?>
      <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
   <?php endif; ?>
   <?php if ($jsonLd !== ''): ?>
      <script type="application/ld+json"><?= $jsonLd ?></script>
   <?php endif; ?>
   <link rel="icon" type="image/png" href="assets/img/logo/BSC_elektronik.png?v=1">
   <link rel="stylesheet" href="assets/css/bootstrap.min.css">
   <link rel="stylesheet" href="assets/css/animate.css">
   <link rel="stylesheet" href="assets/css/swiper-bundle.css">
   <link rel="stylesheet" href="assets/css/slick.css">
   <link rel="stylesheet" href="assets/css/nice-select.css">
   <link rel="stylesheet" href="assets/css/fontawesome.min.css">
   <link rel="stylesheet" href="assets/css/magnific-popup.css">
   <link rel="stylesheet" href="assets/css/spacing.css">
   <link rel="stylesheet" href="assets/css/jquery-ui.css">
   <link rel="stylesheet" href="assets/css/meanmenu.css">
   <link rel="stylesheet" href="assets/css/main.css">
   <link rel="stylesheet" href="assets/css/custom/layout-header.css">
   <link rel="stylesheet" href="assets/css/custom/mobile-nav.css">
   <link rel="stylesheet" href="assets/css/custom/layout-footer.css">
   <?php if ($pageCssFile !== null): ?>
      <link rel="stylesheet" href="assets/css/custom/<?= htmlspecialchars($pageCssFile, ENT_QUOTES, 'UTF-8') ?>">
   <?php endif; ?>
</head>
<body>
<div id="preloader">
   <div class="preloader"><span></span><span></span></div>
</div>
<button class="scroll-top scroll-to-target" data-target="html"><i class="fas fa-angle-up"></i></button>
<header>
   <div class="header-top space-bg">
      <div class="container">
         <div class="row align-items-center">
            <div class="col-12">
               <div class="header-welcome-text text-center">
                  <span>TV yedek parçalarda güvenilir tedarik.</span>
               </div>
            </div>
         </div>
      </div>
   </div>

   <div class="bsc-mobile-nav">
      <a class="bsc-mobile-logo" href="index.php?route=home" aria-label="BSC Elektronik ana sayfa">
         <img src="assets/img/logo/busebsc.png" alt="BSC Elektronik">
      </a>
      <button class="bsc-mobile-toggle" type="button" aria-expanded="false" aria-controls="bsc-mobile-panel">
         Menü <span aria-hidden="true">☰</span>
      </button>
      <nav id="bsc-mobile-panel" class="bsc-mobile-panel" aria-label="Mobil ana menü" hidden>
         <ul>
            <li><a href="index.php?route=home">Ana Sayfa</a></li>
            <li><a href="index.php?route=products">Ürünler</a></li>
            <li><a href="index.php?route=about">Hakkımızda</a></li>
            <li><a href="index.php?route=contact">İletişim</a></li>
            <li><a href="index.php?route=cart">Sepet</a></li>
            <li><a href="index.php?route=wishlist">Favorilerim (<?= (int) $wishlistCount ?>)</a></li>
            <?php if ($loggedUser !== null): ?>
               <li><a href="index.php?route=account/orders">Siparişlerim</a></li>
            <?php endif; ?>
         </ul>
         <div class="bsc-mobile-account">
            <?php if ($loggedUser === null): ?>
               <a href="index.php?route=auth/login">Giriş</a>
               <a href="index.php?route=auth/register">Kayıt Ol</a>
            <?php else: ?>
               <span><?= htmlspecialchars((string) $loggedUser['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
               <form method="post" action="index.php?route=auth/logout">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit">Çıkış</button>
               </form>
            <?php endif; ?>
         </div>
      </nav>
   </div>

   <div class="global-search-area">
      <div class="container">
         <div class="global-search-wrap">
            <div class="global-search-input-wrap">
               <input id="global-search-input" class="global-search-input" type="text" placeholder="Ürün, marka, parça kodu veya kategori ara" autocomplete="off">
               <button id="global-search-btn" class="global-search-btn" type="button" aria-label="Ara">
                  <i class="fal fa-search"></i>
               </button>
            </div>
            <div id="global-search-results" class="global-search-results"></div>
         </div>
      </div>
   </div>

   <div class="mainmenuarea d-none d-xl-block">
      <div class="container">
         <div class="header-nav-row">
            <a class="header-logo-slot" href="index.php?route=home">
               <img src="assets/img/logo/busebsc.png" alt="BSC Elektronik Logo">
            </a>

            <div class="header-center-nav">
               <div class="main-menu">
                  <nav>
                     <ul>
                        <li><a href="index.php?route=home">Ana Sayfa</a></li>
                        <li><a href="index.php?route=products">Ürünler</a></li>
                        <li><a href="index.php?route=about">Hakkımızda</a></li>
                        <li><a href="index.php?route=contact">İletişim</a></li>
                        <li><a href="index.php?route=cart">Sepet</a></li>
                        <li><a id="wishlist-nav-link" href="index.php?route=wishlist">Favorilerim (<?= (int) $wishlistCount ?>)</a></li>
                        <?php if ($loggedUser !== null): ?>
                           <li><a href="index.php?route=account/orders">Siparişlerim</a></li>
                        <?php endif; ?>
                     </ul>
                  </nav>
               </div>
            </div>

            <div class="menu-contact">
               <?php if ($loggedUser === null): ?>
                  <a href="index.php?route=auth/login"><i class="fal fa-user"></i> Giriş</a>
                  <a href="index.php?route=auth/register">Kayıt Ol</a>
               <?php else: ?>
                  <span><?= htmlspecialchars((string) $loggedUser['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                  <form method="post" action="index.php?route=auth/logout">
                     <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                     <button type="submit" class="btn btn-sm btn-outline-secondary">Çıkış</button>
                  </form>
               <?php endif; ?>
            </div>
         </div>
      </div>
   </div>
</header>
<main>
<?php if (!empty($flash)): ?>
   <section class="pt-20">
      <div class="container">
         <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
         </div>
      </div>
   </section>
<?php endif; ?>



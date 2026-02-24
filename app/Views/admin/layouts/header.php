<!doctype html>
<html lang="tr">
<head>
   <meta charset="utf-8">
   <meta http-equiv="x-ua-compatible" content="ie=edge">
   <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="icon" type="image/png" href="assets/img/logo/BSC_elektronik.png?v=1">
   <link rel="stylesheet" href="assets/css/bootstrap.min.css">
   <link rel="stylesheet" href="assets/css/main.css">
   <?php $currentRoute = (string) ($_GET['route'] ?? ''); ?>
   <link rel="stylesheet" href="assets/css/custom/admin-layout.css">
   <?php if (str_starts_with($currentRoute, 'admin/categories')): ?>
      <link rel="stylesheet" href="assets/css/custom/admin-categories.css">
   <?php endif; ?>
   <?php if (str_starts_with($currentRoute, 'admin/orders')): ?>
      <link rel="stylesheet" href="assets/css/custom/admin-orders.css">
   <?php endif; ?>
</head>
<body>
<header class="admin-topbar">
   <div class="container d-flex justify-content-between align-items-center">
      <div class="admin-brand">BSC Admin</div>
      <?php if (!empty($_SESSION['admin_user_id'])): ?>
         <nav class="admin-nav">
            <a class="admin-nav-link <?= str_starts_with($currentRoute, 'admin/products') ? 'active' : '' ?>" href="index.php?route=admin/products">Ürünler</a>
            <a class="admin-nav-link <?= str_starts_with($currentRoute, 'admin/orders') ? 'active' : '' ?>" href="index.php?route=admin/orders">Siparişler</a>
            <a class="admin-nav-link <?= str_starts_with($currentRoute, 'admin/brands') ? 'active' : '' ?>" href="index.php?route=admin/brands">Markalar</a>
            <a class="admin-nav-link <?= str_starts_with($currentRoute, 'admin/categories') ? 'active' : '' ?>" href="index.php?route=admin/categories">Kategoriler</a>
            <a class="admin-nav-link <?= str_starts_with($currentRoute, 'admin/inventory') ? 'active' : '' ?>" href="index.php?route=admin/inventory">Envanter</a>
            <a class="admin-nav-link <?= str_starts_with($currentRoute, 'admin/password') ? 'active' : '' ?>" href="index.php?route=admin/password">Şifre Değiştir</a>
            <form method="post" action="index.php?route=admin/logout" style="margin:0;">
               <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
               <button class="admin-logout-btn" type="submit">Çıkış</button>
            </form>
         </nav>
      <?php endif; ?>
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

</main>
<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<?php
   $currentRoute = (string) ($_GET['route'] ?? '');
?>
<?php if (str_starts_with($currentRoute, 'admin/categories')): ?>
<script src="assets/js/custom/admin-categories.js"></script>
<?php endif; ?>
<?php if (str_starts_with($currentRoute, 'admin/inventory')): ?>
<script src="assets/js/custom/admin-inventory.js"></script>
<?php endif; ?>
<?php if (str_starts_with($currentRoute, 'admin/orders')): ?>
<script src="assets/js/custom/admin-orders.js"></script>
<?php endif; ?>
<?php if (str_starts_with($currentRoute, 'admin/products')): ?>
<script src="assets/js/custom/admin-products.js"></script>
<?php endif; ?>
</body>
</html>

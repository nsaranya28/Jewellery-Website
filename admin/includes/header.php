<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= $adminTitle ?? 'Admin Panel' ?> — Jewels Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= SITE_URL ?>/css/admin.css"/>
</head>
<body class="admin-body">

<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-logo">
    <span>💎</span>
    <div><div class="al-name">Jewels.com</div><div class="al-sub">Admin Panel</div></div>
  </div>
  <nav class="admin-nav">
    <a href="<?= SITE_URL ?>/admin/" class="<?= (basename($_SERVER['PHP_SELF'])==='index.php')?'active':'' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="<?= SITE_URL ?>/admin/categories.php" class="<?= (basename($_SERVER['PHP_SELF'])==='categories.php')?'active':'' ?>"><i class="fas fa-tags"></i> Categories</a>
    <a href="<?= SITE_URL ?>/admin/products.php" class="<?= (basename($_SERVER['PHP_SELF'])==='products.php'||basename($_SERVER['PHP_SELF'])==='product-form.php')?'active':'' ?>"><i class="fas fa-gem"></i> Products</a>
    <a href="<?= SITE_URL ?>/admin/orders.php" class="<?= (basename($_SERVER['PHP_SELF'])==='orders.php')?'active':'' ?>"><i class="fas fa-box"></i> Orders</a>
    <a href="<?= SITE_URL ?>/admin/coupons.php" class="<?= (basename($_SERVER['PHP_SELF'])==='coupons.php')?'active':'' ?>"><i class="fas fa-ticket"></i> Coupons</a>
    <a href="<?= SITE_URL ?>/admin/users.php" class="<?= (basename($_SERVER['PHP_SELF'])==='users.php')?'active':'' ?>"><i class="fas fa-users"></i> Users</a>
    <a href="<?= SITE_URL ?>/admin/stock.php" class="<?= (basename($_SERVER['PHP_SELF'])==='stock.php')?'active':'' ?>"><i class="fas fa-warehouse"></i> Stock</a>
    <a href="<?= SITE_URL ?>/admin/reports.php" class="<?= (basename($_SERVER['PHP_SELF'])==='reports.php')?'active':'' ?>"><i class="fas fa-chart-bar"></i> Reports</a>
    <hr style="border-color:rgba(255,255,255,0.1);margin:10px 0;"/>
    <a href="<?= SITE_URL ?>/" target="_blank"><i class="fas fa-external-link-alt"></i> View Store</a>
    <a href="<?= SITE_URL ?>/admin/logout.php" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</aside>

<div class="admin-main">
  <header class="admin-topbar">
    <button class="sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('collapsed')"><i class="fas fa-bars"></i></button>
    <h1 class="admin-page-title"><?= $adminTitle ?? 'Dashboard' ?></h1>
    <div class="admin-user">
      <i class="fas fa-user-shield"></i>
      <span><?= safeHtml($_SESSION['admin_name'] ?? 'Admin') ?></span>
    </div>
  </header>
  <div class="admin-content">
  <?php showFlash(); ?>

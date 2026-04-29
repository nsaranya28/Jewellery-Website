<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$_cartCount     = cartCount($pdo);
$_wishlistCount = isLoggedIn() ? wishlistCount($pdo) : 0;

// Categories for nav
$_navCategories = $pdo->query("SELECT name, slug FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?? SITE_NAME . ' — Pure Gold, Pure Love' ?></title>
  <meta name="description" content="<?= $pageDesc ?? 'Shop the finest gold, silver and diamond jewellery online at Jewels.com. Kolusu, Kammal, Chain, Bangle, Ring, Necklace and more.' ?>"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css"/>
</head>
<body>

<!-- ── TOP BAR ── -->
<div class="topbar">
  <div class="topbar-inner container">
    <span><i class="fas fa-truck"></i> Free shipping on orders above ₹5,000</span>
    <span><i class="fas fa-shield-halved"></i> Hallmark Certified Jewellery</span>
    <span><i class="fas fa-rotate-left"></i> 30-Day Easy Returns</span>
  </div>
</div>

<!-- ── HEADER ── -->
<header class="site-header" id="siteHeader">
  <div class="header-inner container">
    <!-- Logo -->
    <a href="<?= SITE_URL ?>/" class="logo">
      <span class="logo-gem">💎</span>
      <span class="logo-text">Jewels<span class="logo-dot">.com</span></span>
    </a>

    <!-- Search -->
    <form class="header-search" action="<?= SITE_URL ?>/shop.php" method="GET">
      <input type="text" name="q" placeholder="Search for gold chains, bangles, rings…" value="<?= safeHtml($_GET['q'] ?? '') ?>"/>
      <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <!-- Nav Icons -->
    <div class="header-icons">
      <?php if (isLoggedIn()): ?>
        <div class="hicon-dropdown">
          <a href="<?= SITE_URL ?>/profile.php" class="hicon" title="My Account">
            <i class="fas fa-user-circle"></i>
            <span class="hicon-label"><?= safeHtml(explode(' ', currentUser()['name'])[0]) ?> <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i></span>
          </a>
          <div class="dropdown-menu">
            <div class="dropdown-header">
              <strong>Hello, <?= safeHtml(currentUser()['name']) ?></strong>
              <span><?= safeHtml(currentUser()['email']) ?></span>
            </div>
            <div class="dropdown-divider"></div>
            <a href="<?= SITE_URL ?>/profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="<?= SITE_URL ?>/my-orders.php"><i class="fas fa-box"></i> My Orders</a>
            <a href="<?= SITE_URL ?>/wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a>
            <a href="<?= SITE_URL ?>/addresses.php"><i class="fas fa-location-dot"></i> My Addresses</a>
            <div class="dropdown-divider"></div>
            <a href="<?= SITE_URL ?>/logout.php" style="color:var(--red);"><i class="fas fa-sign-out-alt"></i> Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="hicon" title="Login">
          <i class="fas fa-user"></i>
          <span class="hicon-label">Login</span>
        </a>
      <?php endif; ?>

      <a href="<?= SITE_URL ?>/wishlist.php" class="hicon" title="Wishlist">
        <i class="fas fa-heart"></i>
        <?php if ($_wishlistCount > 0): ?><span class="badge"><?= $_wishlistCount ?></span><?php endif; ?>
        <span class="hicon-label">Wishlist</span>
      </a>

      <a href="<?= SITE_URL ?>/cart.php" class="hicon" title="Cart">
        <i class="fas fa-shopping-bag"></i>
        <?php if ($_cartCount > 0): ?><span class="badge"><?= $_cartCount ?></span><?php endif; ?>
        <span class="hicon-label">Cart</span>
      </a>

      <button class="hamburger" id="hamburger" aria-label="Menu"><i class="fas fa-bars"></i></button>
    </div>
  </div>

  <!-- ── NAV ── -->
  <nav class="site-nav" id="siteNav">
    <div class="nav-inner container">
      <a href="<?= SITE_URL ?>/" class="nav-link">Home</a>
      
      <!-- All Jewellery Tab -->
      <a href="<?= SITE_URL ?>/shop.php" class="nav-link">All Jewellery</a>

      <!-- Categories from DB -->
      <?php foreach ($_navCategories as $cat): ?>
        <?php if (in_array(strtolower($cat['name']), ['gold', 'diamond'])): ?>
          <a href="<?= SITE_URL ?>/category.php?slug=<?= $cat['slug'] ?>" class="nav-link"><?= safeHtml($cat['name']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>

      <!-- Wedding Mega Menu Tab -->
      <div class="nav-item-mega">
        <a href="<?= SITE_URL ?>/category.php?slug=wedding" class="nav-link active">Wedding <i class="fas fa-chevron-down mega-chevron"></i></a>
        <div class="mega-menu">
          <div class="mega-container container">
            <!-- Sidebar -->
            <div class="mega-sidebar">
              <div class="mega-side-item active">Category</div>
              <div class="mega-side-item">Community</div>
              <div class="mega-side-item">Metal</div>
            </div>
            
            <!-- Grid -->
            <div class="mega-grid-wrap">
              <div class="mega-grid">
                <a href="<?= SITE_URL ?>/category.php?slug=wedding" class="mega-card">
                  <div class="mega-card-img">
                    <img src="<?= SITE_URL ?>/images/rivaah_collection_1777472754593.png" alt="All Rivaah"/>
                  </div>
                  <span class="mega-card-label">All Rivaah</span>
                </a>
                <a href="<?= SITE_URL ?>/shop.php?q=choker" class="mega-card">
                  <div class="mega-card-img">
                    <img src="<?= SITE_URL ?>/images/wedding_choker_1777471841008.png" alt="Wedding Choker"/>
                  </div>
                  <span class="mega-card-label">Wedding Choker</span>
                </a>
                <a href="<?= SITE_URL ?>/shop.php?q=haram" class="mega-card">
                  <div class="mega-card-img">
                    <img src="<?= SITE_URL ?>/images/wedding_haram_1777472106601.png" alt="Wedding Haram"/>
                  </div>
                  <span class="mega-card-label">Wedding Haram</span>
                </a>
                <a href="<?= SITE_URL ?>/shop.php?q=bangles" class="mega-card">
                  <div class="mega-card-img">
                    <img src="<?= SITE_URL ?>/images/wedding_bangles_1777472425765.png" alt="Wedding Bangles"/>
                  </div>
                  <span class="mega-card-label">Wedding Bangles</span>
                </a>
                <a href="<?= SITE_URL ?>/shop.php?q=diamond" class="mega-card">
                  <div class="mega-card-img">
                    <img src="<?= SITE_URL ?>/images/wedding_diamond_necklace_1777472513071.png" alt="Wedding Diamond"/>
                  </div>
                  <span class="mega-card-label">Wedding Diamond</span>
                </a>
                <a href="<?= SITE_URL ?>/shop.php?q=mangalsutra" class="mega-card">
                  <div class="mega-card-img">
                    <img src="<?= SITE_URL ?>/images/wedding_mangalsutra_1777472655822.png" alt="Wedding Mangalsutra"/>
                  </div>
                  <span class="mega-card-label">Wedding Mangalsutra</span>
                </a>
                <div class="mega-card">
                  <div class="mega-card-img">
                    <div class="mega-placeholder"><i class="fas fa-plus"></i></div>
                  </div>
                  <span class="mega-card-label">Accessories</span>
                </div>
              </div>
            </div>

            <!-- Chat/Support integration like in screenshot -->
            <div class="mega-support">
              <div class="support-bubble">
                <span class="support-text">How can I help you?</span>
                <button class="support-close"><i class="fas fa-times"></i></button>
              </div>
              <div class="support-avatar">
                <img src="https://i.pravatar.cc/100?u=support" alt="Support"/>
              </div>
            </div>
          </div>
        </div>
      </div>

      <a href="<?= SITE_URL ?>/shop.php?filter=gifting" class="nav-link">Gifting</a>
      <a href="<?= SITE_URL ?>/shop.php?filter=new" class="nav-link">New Arrivals</a>
      <a href="<?= SITE_URL ?>/shop.php?filter=sale" class="nav-link nav-sale">Sale 🔥</a>
    </div>
  </nav>
</header>

<!-- React Toast Container -->
<div id="react-toast-root"></div>
<?php if (!empty($_SESSION['flash'])): ?>
<script>
  window.SERVER_FLASH = <?= json_encode($_SESSION['flash']) ?>;
  <?php unset($_SESSION['flash']); ?>
</script>
<?php endif; ?>

<main>

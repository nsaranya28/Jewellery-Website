<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$stmt = $pdo->prepare("SELECT o.* FROM orders o WHERE o.user_id=? ORDER BY o.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Order History — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <div class="reveal active">
      <div class="section-label">📜 Timeline</div>
      <h1>My Order History</h1>
      <div class="breadcrumb">
        <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i>
        <span>My Orders</span>
      </div>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="profile-layout">
      <!-- Sidebar -->
      <div class="profile-sidebar glass">
        <div class="profile-avatar-section">
          <div class="profile-avatar"><?= mb_substr(currentUser()['name'],0,1) ?></div>
          <div class="profile-uname"><?= safeHtml(currentUser()['name']) ?></div>
          <div class="profile-email"><?= safeHtml(currentUser()['email']) ?></div>
        </div>
        <nav class="sidebar-nav">
          <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
          <a href="my-orders.php" class="active"><i class="fas fa-box"></i> My Orders</a>
          <a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
          <a href="addresses.php"><i class="fas fa-location-dot"></i> Addresses</a>
          <a href="logout.php" style="color:var(--red);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
      </div>

      <!-- Content -->
      <div class="profile-content">
        <?php if (empty($orders)): ?>
          <div class="empty-state">
            <div class="es-icon">📦</div>
            <h3>No orders found</h3>
            <p>You haven't placed any orders yet. Start your journey with our premium collection!</p>
            <a href="shop.php" class="btn btn-gold mt-20">Start Shopping</a>
          </div>
        <?php else: ?>
          <?php foreach ($orders as $order): 
            $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
            $itemsStmt->execute([$order['id']]);
            $items = $itemsStmt->fetchAll();
          ?>
          <div class="order-card-new reveal active">
            <div class="order-header-new">
              <div class="order-id-tag">Order #JW<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
              <div class="order-meta-item">
                <span class="order-meta-label">Date Placed</span>
                <span class="order-meta-val"><?= date('d M, Y', strtotime($order['created_at'])) ?></span>
              </div>
              <div class="order-meta-item">
                <span class="order-meta-label">Total Amount</span>
                <span class="order-meta-val"><?= money($order['total']) ?></span>
              </div>
              <div class="order-meta-item">
                <span class="order-meta-label">Status</span>
                <span class="status-badge status-<?= $order['status'] ?>"><?= ucwords(str_replace('_',' ',$order['status'])) ?></span>
              </div>
            </div>
            
            <div class="order-items-new">
              <?php foreach ($items as $item): ?>
              <div class="order-item-row">
                <img src="<?= productImage($item['product_image']) ?>" alt="<?= safeHtml($item['product_name']) ?>" class="order-item-img">
                <div class="order-item-info">
                  <div class="order-item-name"><?= safeHtml($item['product_name']) ?></div>
                  <div class="order-item-price"><?= $item['quantity'] ?> × <?= money($item['price']) ?></div>
                </div>
                <a href="product.php?slug=<?= makeSlug($item['product_name']) ?>" class="btn btn-sm btn-outline">Buy Again</a>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="order-footer-new">
              <div style="font-size:12px;color:var(--gray);">
                <i class="fas fa-credit-card"></i> Paid via <?= safeHtml($order['payment_method']) ?>
              </div>
              <div style="display:flex;gap:10px;">
                <a href="invoice.php?order=<?= $order['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-file-invoice"></i> Invoice</a>
                <a href="order-tracking.php?order=<?= $order['id'] ?>" class="btn btn-sm btn-gold"><i class="fas fa-truck"></i> Track Order</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

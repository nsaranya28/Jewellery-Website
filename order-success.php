<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$orderId = (int)($_GET['order'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) { header('Location: my-orders.php'); exit; }

$pageTitle = 'Order Confirmed — ' . SITE_NAME;
include 'includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="success-card">
      <div class="success-icon">✅</div>
      <h2>Order Confirmed!</h2>
      <p>Thank you for shopping at Jewels.com. Your order has been placed successfully and will be processed shortly.</p>
      <div class="order-number">Order #JW<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
      <div style="background:var(--ivory-dark);border-radius:var(--radius-md);padding:16px 20px;margin-bottom:24px;text-align:left;">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;"><span style="color:var(--gray);">Payment Method</span><span style="font-weight:600;"><?= safeHtml($order['payment_method']) ?></span></div>
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;"><span style="color:var(--gray);">Amount Paid</span><span style="font-weight:700;color:var(--gold-dark);"><?= money($order['total']) ?></span></div>
        <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--gray);">Delivery To</span><span style="font-weight:500;font-size:12px;max-width:200px;text-align:right;"><?= safeHtml($order['address_snapshot']) ?></span></div>
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
        <a href="order-tracking.php?order=<?= $order['id'] ?>" class="btn btn-gold"><i class="fas fa-truck"></i> Track Order</a>
        <a href="my-orders.php" class="btn btn-outline" style="color:var(--dark);border-color:var(--gold-dark);"><i class="fas fa-list"></i> My Orders</a>
      </div>
      <a href="index.php" style="display:block;font-size:13px;color:var(--gray);margin-top:16px;">← Continue Shopping</a>
    </div>

    <!-- Confetti-like decoration -->
    <div style="text-align:center;font-size:28px;letter-spacing:8px;margin-top:10px;animation:float 3s ease-in-out infinite;">💎 💍 📿 ✨ 💛</div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

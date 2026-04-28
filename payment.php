<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

if (empty($_SESSION['checkout'])) { header('Location: cart.php'); exit; }
$co = $_SESSION['checkout'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $method = $_POST['payment_method'] ?? 'COD';

  // Fetch cart items
  $stmt = $pdo->prepare("SELECT c.*, COALESCE(p.discount_price, p.price) as unit_price, p.name, p.image1 FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
  $stmt->execute([$_SESSION['user_id']]);
  $items = $stmt->fetchAll();

  if (empty($items)) { header('Location: cart.php'); exit; }

  // Create order
  $pdo->beginTransaction();
  try {
    $ins = $pdo->prepare("INSERT INTO orders (user_id, address_id, address_snapshot, subtotal, discount, total, coupon_code, payment_method, payment_status, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $payStatus = ($method === 'COD') ? 'pending' : 'paid';
    $ins->execute([$_SESSION['user_id'], $co['address_id'], $co['address_snap'], $co['subtotal'], $co['discount'], $co['total'], $co['coupon_code'], $method, $payStatus, 'confirmed']);
    $orderId = $pdo->lastInsertId();

    foreach ($items as $item) {
      $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, price) VALUES (?,?,?,?,?,?)")
          ->execute([$orderId, $item['product_id'], $item['name'], $item['image1'], $item['quantity'], $item['unit_price']]);
      $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?")->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
    }

    // Update coupon usage
    if ($co['coupon_code'] && isset($_SESSION['coupon'])) {
      $pdo->prepare("UPDATE coupons SET used_count=used_count+1 WHERE code=?")->execute([$co['coupon_code']]);
      // Record per-user usage so coupon can't be reused
      $pdo->prepare("INSERT IGNORE INTO coupon_usage (user_id, coupon_code) VALUES (?,?)")->execute([$_SESSION['user_id'], $co['coupon_code']]);
    }

    // Clear cart & session
    $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$_SESSION['user_id']]);
    unset($_SESSION['checkout'], $_SESSION['coupon'], $_SESSION['coupon_discount'], $_SESSION['coupon_code_str']);

    $pdo->commit();
    header("Location: order-success.php?order=$orderId");
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    flashMessage('error', 'Order failed. Please try again.');
    header('Location: payment.php'); exit;
  }
}

$pageTitle = 'Payment — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>💳 Select Payment Method</h1>
    <div class="breadcrumb">
      <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i>
      <a href="cart.php">Cart</a> <i class="fas fa-chevron-right"></i>
      <a href="checkout.php">Checkout</a> <i class="fas fa-chevron-right"></i>
      <span>Payment</span>
    </div>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:680px;">
    <div style="background:var(--white);border-radius:var(--radius-lg);padding:36px;box-shadow:var(--shadow-md);border:1px solid var(--gray-light);">
      <div style="background:var(--gold-pale);border:1px solid var(--gold);border-radius:var(--radius-md);padding:14px 18px;margin-bottom:28px;display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size:14px;color:var(--dark);">
          <i class="fas fa-location-dot" style="color:var(--gold);"></i>
          <strong>Delivering to:</strong> <?= safeHtml($co['address_snap']) ?>
        </div>
        <a href="checkout.php" style="font-size:12px;color:var(--gold-dark);font-weight:600;">Change</a>
      </div>

      <h2 style="font-family:var(--font-serif);font-size:24px;color:var(--dark);margin-bottom:20px;">Choose Payment Method</h2>

      <form method="POST" id="payment-form">
        <input type="hidden" name="payment_method" id="paymentMethod" value="Card"/>

        <div class="payment-options">
          <div class="payment-option selected" data-method="Card" onclick="selectPay(this)">
            <div class="po-icon"><i class="fas fa-credit-card"></i></div>
            <div class="po-name">Credit / Debit Card</div>
            <div class="po-desc">Visa, Mastercard, RuPay</div>
          </div>
          <div class="payment-option" data-method="UPI" onclick="selectPay(this)">
            <div class="po-icon"><i class="fas fa-mobile-screen"></i></div>
            <div class="po-name">UPI Payment</div>
            <div class="po-desc">GPay, PhonePe, Paytm</div>
          </div>
          <div class="payment-option" data-method="COD" onclick="selectPay(this)">
            <div class="po-icon"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="po-name">Cash on Delivery</div>
            <div class="po-desc">Pay when delivered</div>
          </div>
        </div>

        <div id="payment-details-container" style="margin-top: 20px; display: none;">
          <!-- Card Details -->
          <div id="details-Card" class="pay-detail-group" style="display: none;">
            <h4 style="font-size: 14px; color: var(--dark); margin-bottom: 12px; font-weight: 600;">💳 Card Information</h4>
            <div class="form-group">
              <label>Card Number</label>
              <input type="text" id="card_number" name="card_number" placeholder="0000 0000 0000 0000" maxlength="19" class="form-control">
            </div>
            <div class="form-row">
              <div class="form-group" style="flex: 2;">
                <label>Card Holder Name</label>
                <input type="text" id="card_name" name="card_name" placeholder="Full Name" class="form-control">
              </div>
              <div class="form-group" style="flex: 1;">
                <label>Expiry</label>
                <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5" class="form-control">
              </div>
              <div class="form-group" style="flex: 0.8;">
                <label>CVV</label>
                <input type="password" id="card_cvv" name="card_cvv" placeholder="***" maxlength="3" class="form-control">
              </div>
            </div>
          </div>

          <!-- UPI Details -->
          <div id="details-UPI" class="pay-detail-group" style="display: none;">
            <h4 style="font-size: 14px; color: var(--dark); margin-bottom: 12px; font-weight: 600;">📱 UPI Details</h4>
            <div class="form-group">
              <label>UPI ID (VPA)</label>
              <input type="text" id="upi_id" name="upi_id" placeholder="username@bank" class="form-control">
            </div>
            <div class="form-group">
              <label>Account Holder Name</label>
              <input type="text" id="upi_name" name="upi_name" placeholder="Name linked to UPI" class="form-control">
            </div>
          </div>
        </div>

        <div style="border-top:2px solid var(--gold-pale);padding-top:20px;margin-top:20px;">
          <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;"><span>Subtotal</span><span><?= money($co['subtotal']) ?></span></div>
          <?php if ($co['discount']): ?><div style="display:flex;justify-content:space-between;font-size:14px;color:var(--green);margin-bottom:8px;"><span>Discount</span><span>−<?= money($co['discount']) ?></span></div><?php endif; ?>
          <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;"><span>Shipping</span><span><?= $co['shipping'] ? money($co['shipping']) : 'Free' ?></span></div>
          <div style="display:flex;justify-content:space-between;font-family:var(--font-serif);font-size:24px;font-weight:700;color:var(--gold-dark);margin-top:10px;padding-top:10px;border-top:2px solid var(--gold-pale);">
            <span>Total</span><span><?= money($co['total']) ?></span>
          </div>
        </div>

        <button type="submit" class="btn btn-gold btn-full" style="margin-top:20px;font-size:16px;padding:16px;">
          <i class="fas fa-check-circle"></i> Place Order — <?= money($co['total']) ?>
        </button>
        <p style="text-align:center;font-size:12px;color:var(--gray);margin-top:10px;"><i class="fas fa-shield-halved" style="color:var(--gold);"></i> 100% Secure & Encrypted Payment</p>
      </form>
    </div>
  </div>
</section>

<script>
function selectPay(el) {
  const method = el.dataset.method;
  document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('paymentMethod').value = method;

  // Show/Hide details
  const detailsContainer = document.getElementById('payment-details-container');
  const allDetails = document.querySelectorAll('.pay-detail-group');
  
  allDetails.forEach(d => d.style.display = 'none');
  
  if (method === 'Card' || method === 'UPI') {
    detailsContainer.style.display = 'block';
    document.getElementById('details-' + method).style.display = 'block';
  } else {
    detailsContainer.style.display = 'none';
  }
}

// Start first selected
document.querySelector('.payment-option.selected')?.click();

// Form Validation
document.getElementById('payment-form').addEventListener('submit', function(e) {
  const method = document.getElementById('paymentMethod').value;
  
  if (method === 'Card') {
    const num = document.getElementById('card_number').value.trim();
    const name = document.getElementById('card_name').value.trim();
    const exp = document.getElementById('card_expiry').value.trim();
    const cvv = document.getElementById('card_cvv').value.trim();
    
    if (!num || !name || !exp || !cvv) {
      e.preventDefault();
      showToast("Please fill in all card details.", "error");
      return;
    }
  } else if (method === 'UPI') {
    const upiId = document.getElementById('upi_id').value.trim();
    const upiName = document.getElementById('upi_name').value.trim();
    
    if (!upiId || !upiName) {
      e.preventDefault();
      showToast("Please fill in all UPI details.", "error");
      return;
    }
    if (!upiId.includes('@')) {
      e.preventDefault();
      showToast("Please enter a valid UPI ID.", "error");
      return;
    }
  }
});

// Input formatting for card
document.getElementById('card_number')?.addEventListener('input', function(e) {
  this.value = this.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim();
});
document.getElementById('card_expiry')?.addEventListener('input', function(e) {
  this.value = this.value.replace(/\D/g, '').replace(/(.{2})/, '$1/').substring(0, 5);
});
</script>

<?php include 'includes/footer.php'; ?>

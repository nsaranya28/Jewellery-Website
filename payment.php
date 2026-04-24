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
        <input type="hidden" name="payment_method" id="paymentMethod" value="COD"/>

        <div class="payment-options">
          <div class="payment-option selected" data-method="GPay" onclick="selectPay(this)">
            <div class="po-icon">📱</div>
            <div class="po-name">Google Pay</div>
            <div class="po-desc">Pay via UPI — GPay</div>
          </div>
          <div class="payment-option" data-method="PhonePe" onclick="selectPay(this)">
            <div class="po-icon">📲</div>
            <div class="po-name">PhonePe</div>
            <div class="po-desc">Pay via PhonePe UPI</div>
          </div>
          <div class="payment-option" data-method="UPI" onclick="selectPay(this)">
            <div class="po-icon">💸</div>
            <div class="po-name">Other UPI</div>
            <div class="po-desc">Any UPI app</div>
          </div>
          <div class="payment-option" data-method="COD" onclick="selectPay(this)">
            <div class="po-icon">💵</div>
            <div class="po-name">Cash on Delivery</div>
            <div class="po-desc">Pay when delivered</div>
          </div>
        </div>

        <div style="border-top:2px solid var(--gold-pale);padding-top:20px;margin-top:10px;">
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

<!-- Payment Authentication Modal -->
<div id="otpModalOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;backdrop-filter:blur(5px);align-items:center;justify-content:center;">
  <div style="background:var(--white);padding:30px;border-radius:16px;width:90%;max-width:400px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.3);position:relative;">
    <button type="button" onclick="closeOtpModal()" style="position:absolute;top:15px;right:15px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray);">&times;</button>
    <div id="providerIcon" style="width:60px;height:60px;background:var(--ivory);border:2px solid var(--gold-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;font-size:24px;">
    </div>
    <h3 style="font-family:var(--font-serif);font-size:22px;color:var(--dark);margin-bottom:8px;">Authenticate Payment</h3>
    <p style="font-size:14px;color:var(--gray);margin-bottom:20px;">We've sent a secure 6-digit OTP to your registered phone number to link your account.</p>
    
    <input type="text" id="verifyOtpInput" placeholder="Enter 6-digit OTP" maxlength="6" style="width:100%;padding:14px;border:2px solid var(--gray-light);border-radius:8px;font-size:18px;text-align:center;letter-spacing:4px;font-weight:600;margin-bottom:15px;outline:none;" onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--gray-light)'">
    
    <button type="button" id="btnVerifyOtp" class="btn btn-gold btn-full" style="padding:14px;font-size:16px;"><i class="fas fa-lock"></i> Verify & Pay securely</button>
  </div>
</div>

<script>
function selectPay(el) {
  document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('paymentMethod').value = el.dataset.method;
}
// Start first selected
document.querySelector('.payment-option.selected')?.click();

// OTP Flow
const pform = document.getElementById('payment-form');
const methodInput = document.getElementById('paymentMethod');
const pOverlay = document.getElementById('otpModalOverlay');
const pVerifyBtn = document.getElementById('btnVerifyOtp');
const pOtpInput = document.getElementById('verifyOtpInput');

pform.addEventListener('submit', function(e) {
  if (methodInput.value === 'GPay' || methodInput.value === 'PhonePe' || methodInput.value === 'UPI') {
    e.preventDefault();
    
    // Set dynamic icon based on method
    const iconWrapper = document.getElementById('providerIcon');
    if(methodInput.value === 'GPay') {
        iconWrapper.innerHTML = '<img src="https://upload.wikimedia.org/wikipedia/commons/f/f2/Google_Pay_Logo.svg" alt="GPay" style="width:30px;">';
    } else if(methodInput.value === 'PhonePe') {
        iconWrapper.innerHTML = '📲';
    } else {
        iconWrapper.innerHTML = '💸';
    }
    
    openOtpModal();
  }
});

function openOtpModal() {
  pOverlay.style.display = 'flex';
  
  fetch('ajax/send-payment-otp.php', { method: 'POST' })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast('📱 SMS: OTP is ' + data.dev_otp, 'success');
      } else {
        showToast(data.message, 'error');
        closeOtpModal();
      }
    });
}

function closeOtpModal() {
  pOverlay.style.display = 'none';
  pOtpInput.value = '';
}

pVerifyBtn.addEventListener('click', function() {
  const otpVal = pOtpInput.value.trim();
  if (otpVal.length !== 6) {
    showToast("Please enter a valid 6-digit OTP.", "error");
    return;
  }
  
  pVerifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Linking Bank...';
  pVerifyBtn.disabled = true;
  pVerifyBtn.style.background = 'var(--gray)';
  
  const fd = new FormData();
  fd.append('otp', otpVal);
  
  fetch('ajax/verify-payment-otp.php', { method: 'POST', body: fd })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      pVerifyBtn.innerHTML = '<i class="fas fa-check-circle"></i> Complete! Redirecting...';
      pVerifyBtn.style.background = 'var(--green)';
      setTimeout(() => {
        pform.submit();
      }, 1000);
    } else {
      showToast(data.message, "error");
      pVerifyBtn.innerHTML = '<i class="fas fa-lock"></i> Verify & Pay securely';
      pVerifyBtn.disabled = false;
      pVerifyBtn.style.background = 'var(--gold)';
    }
  });
});
</script>

<?php include 'includes/footer.php'; ?>

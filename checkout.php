<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

// Fetch cart
$stmt = $pdo->prepare("SELECT c.*, p.name, p.slug, p.image1, p.material, COALESCE(p.discount_price, p.price) as unit_price, p.stock FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();

if (empty($items)) { header('Location: cart.php'); exit; }

// Handle coupon apply
if (isset($_POST['apply_coupon'])) {
  $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
  $st = 0;
  foreach ($items as $i) $st += $i['unit_price'] * $i['quantity'];
  $result = applyCoupon($pdo, $code, $st);
  if (isset($result['error'])) {
    flashMessage('error', $result['error']);
  } else {
    // Check coupon_usage table
    $usageCheck = $pdo->prepare("SELECT id FROM coupon_usage WHERE user_id=? AND coupon_code=?");
    $usageCheck->execute([$_SESSION['user_id'], $code]);
    if ($usageCheck->fetch()) {
      flashMessage('error', 'Already Used — You have already used this coupon.');
    } else {
      $_SESSION['coupon'] = $result['coupon'];
      $_SESSION['coupon_code_str'] = $code;
      flashMessage('success', 'Coupon Applied');
    }
  }
  header('Location: checkout.php'); exit;
}
// Handle coupon remove
if (isset($_GET['remove_coupon'])) {
  unset($_SESSION['coupon'], $_SESSION['coupon_discount'], $_SESSION['coupon_code_str']);
  header('Location: checkout.php'); exit;
}

// Addresses
$addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC");
$addresses->execute([$_SESSION['user_id']]);
$addresses = $addresses->fetchAll();

// Pricing

// Pricing
$subtotal = 0;
foreach ($items as $i) $subtotal += $i['unit_price'] * $i['quantity'];
// Determine if coupons are enabled (max purchase 50,000)
$couponEnabled = $subtotal <= 50000;
$coupon   = $_SESSION['coupon'] ?? null;
$discount = 0;
if ($coupon && $couponEnabled && $subtotal >= $coupon['min_amount']) {
  $discount = ($coupon['type'] === 'percent') ? ($subtotal * $coupon['discount'] / 100) : $coupon['discount'];
  $discount = min($discount, $subtotal);
}
$shipping = $subtotal >= 5000 ? 0 : 150;
$total    = $subtotal - $discount + $shipping;

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $addrId  = (int)($_POST['address_id'] ?? 0);
  $newAddr = null;

  if ($addrId === -1) {
    // Save new address
    $stmt2 = $pdo->prepare("INSERT INTO addresses (user_id,label,full_name,phone,line1,city,state,pincode) VALUES (?,?,?,?,?,?,?,?)");
    $stmt2->execute([$_SESSION['user_id'],'Home',trim($_POST['full_name']),trim($_POST['phone']),trim($_POST['line1']),trim($_POST['city']),trim($_POST['state']),trim($_POST['pincode'])]);
    $addrId = $pdo->lastInsertId();
  }

  // Get address snapshot
  $addrStmt = $pdo->prepare("SELECT * FROM addresses WHERE id=? AND user_id=?");
  $addrStmt->execute([$addrId, $_SESSION['user_id']]);
  $addr = $addrStmt->fetch();
  if (!$addr) { flashMessage('error','Please select a delivery address.'); header('Location: checkout.php'); exit; }

  $addrSnap = $addr['full_name'] . ', ' . $addr['line1'] . ', ' . $addr['city'] . ', ' . $addr['state'] . ' - ' . $addr['pincode'] . ' | ' . $addr['phone'];

  // Store in session and go to payment
  $_SESSION['checkout'] = [
    'address_id'  => $addrId,
    'address_snap'=> $addrSnap,
    'subtotal'    => $subtotal,
    'discount'    => $discount,
    'shipping'    => $shipping,
    'total'       => $total,
    'coupon_code' => $_SESSION['coupon_code_str'] ?? null,
  ];
  header('Location: payment.php'); exit;
}

$pageTitle = 'Checkout — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>🔐 Checkout</h1>
    <div class="breadcrumb">
      <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i>
      <a href="cart.php">Cart</a> <i class="fas fa-chevron-right"></i>
      <span>Checkout</span>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <form method="POST">
    <div class="cart-layout">
      <!-- Address -->
      <div>
        <div style="background:var(--white);border-radius:var(--radius-md);padding:28px;box-shadow:var(--shadow-sm);border:1px solid var(--gray-light);">
          <h2 style="font-family:var(--font-serif);font-size:24px;color:var(--dark);margin-bottom:20px;">📍 Delivery Address</h2>

          <?php if (!empty($addresses)): ?>
          <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
            <?php foreach ($addresses as $addr): ?>
            <label style="display:flex;align-items:flex-start;gap:12px;padding:14px;border:2px solid var(--gray-light);border-radius:var(--radius-md);cursor:pointer;transition:all 0.3s;" class="addr-label">
              <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $addr['is_default'] ? 'checked' : '' ?> style="margin-top:4px;accent-color:var(--gold);"/>
              <div>
                <div style="font-weight:600;font-size:14px;"><?= safeHtml($addr['full_name']) ?> <span style="background:var(--gold-pale);color:var(--gold-dark);font-size:10px;padding:2px 8px;border-radius:50px;margin-left:6px;"><?= safeHtml($addr['label']) ?></span></div>
                <div style="font-size:13px;color:var(--gray);margin-top:4px;"><?= safeHtml($addr['line1']) ?>, <?= safeHtml($addr['city']) ?>, <?= safeHtml($addr['state']) ?> - <?= safeHtml($addr['pincode']) ?></div>
                <div style="font-size:12px;color:var(--gray);margin-top:2px;"><i class="fas fa-phone"></i> <?= safeHtml($addr['phone']) ?></div>
              </div>
            </label>
            <?php endforeach; ?>

            <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px dashed var(--gray-light);border-radius:var(--radius-md);cursor:pointer;font-size:14px;color:var(--gray);">
              <input type="radio" name="address_id" value="-1" id="newAddrToggle" style="accent-color:var(--gold);"/>
              <i class="fas fa-plus" style="color:var(--gold);"></i> Add a new address
            </label>
          </div>
          <?php else: ?>
          <input type="hidden" name="address_id" value="-1"/>
          <?php endif; ?>

          <!-- New address form -->
          <div id="newAddrForm" style="display:<?= empty($addresses)?'block':'none' ?>;">
            <div class="form-row">
              <div class="form-group"><label>Full Name</label><input type="text" name="full_name" placeholder="Your full name"/></div>
              <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="10-digit mobile number"/></div>
            </div>
            <div class="form-group"><label>Address Line 1</label><input type="text" name="line1" placeholder="House/Flat no, Street, Area"/></div>
            <div class="form-row">
              <div class="form-group"><label>City</label><input type="text" name="city" placeholder="City"/></div>
              <div class="form-group"><label>State</label><input type="text" name="state" placeholder="State"/></div>
            </div>
            <div class="form-group" style="max-width:200px;"><label>Pincode</label><input type="text" name="pincode" placeholder="6-digit pincode" maxlength="6"/></div>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div>
        <div class="order-summary">
          <h3>Order Summary</h3>
          <?php foreach ($items as $item): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--gray-light);">
            <img src="<?= productImage($item['image1']) ?>" alt="" style="width:44px;height:44px;border-radius:6px;object-fit:cover;"/>
            <div style="flex:1;font-size:13px;">
              <div style="font-weight:500;"><?= safeHtml(truncate($item['name'],30)) ?></div>
              <div style="color:var(--gray);font-size:11px;">Qty: <?= $item['quantity'] ?></div>
            </div>
            <div style="font-weight:600;font-size:13px;"><?= money($item['unit_price'] * $item['quantity']) ?></div>
          </div>
          <?php endforeach; ?>
          <div class="summary-row" style="margin-top:10px;"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <?php if ($discount): ?>
          <div class="summary-row" style="color:var(--green);">
            <span>Discount (<?= safeHtml($_SESSION['coupon_code_str'] ?? '') ?>)</span>
            <span>−<?= money($discount) ?></span>
          </div>
          <?php endif; ?>
          <div class="summary-row"><span>Shipping</span><span><?= $shipping ? money($shipping) : 'Free' ?></span></div>
          <div class="summary-row total"><span>Total</span><span><?= money($total) ?></span></div>

          <?php if ($coupon): ?>
            <p style="font-size:12px;color:var(--green);margin:10px 0 0;"><i class="fas fa-tag"></i> Coupon <strong><?= safeHtml($_SESSION['coupon_code_str'] ?? '') ?></strong> applied!
              <a href="checkout.php?remove_coupon=1" style="color:var(--red);font-size:11px;margin-left:4px;">Remove</a>
            </p>
          <?php else: ?>
          </div><!-- close main form's order-summary temporarily -->
          <div style="background:var(--white);border-radius:var(--radius-md);padding:20px;box-shadow:var(--shadow-sm);border:1px solid var(--gray-light);margin-top:16px;">
            <h4 style="font-size:14px;font-weight:600;color:var(--dark);margin-bottom:12px;"><i class="fas fa-ticket" style="color:var(--gold);"></i> Have a Coupon Code?</h4>
            <div style="display:flex;gap:8px;">
              <input type="text" name="coupon_code" id="couponCodeCheckout" placeholder="Enter coupon code" style="flex:1;padding:10px 14px;border:2px solid var(--gray-light);border-radius:var(--radius-md);font-size:14px;text-transform:uppercase;outline:none;transition:border 0.3s;" onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--gray-light)'"/>
              <button type="submit" name="apply_coupon" formnovalidate class="btn btn-gold" style="white-space:nowrap;">Apply</button>
            </div>
            <div style="font-size:11px;color:var(--gray);margin-top:6px;">Try: SAVE10 · FLAT500 · BRIDAL20</div>
          </div>
          <div class="order-summary" style="margin-top:16px;">
          <?php endif; ?>

          <button type="submit" class="btn btn-gold btn-full" style="margin-top:16px;"><i class="fas fa-arrow-right"></i> Continue to Payment</button>
        </div>
      </div>
    </div>
    </form>
  </div>
</section>

<script>
document.getElementById('newAddrToggle')?.addEventListener('change', function(){
  document.getElementById('newAddrForm').style.display = this.checked ? 'block' : 'none';
});
document.querySelectorAll('.addr-label input[type="radio"]:not(#newAddrToggle)').forEach(r => {
  r.addEventListener('change', () => {
    document.getElementById('newAddrForm').style.display = 'none';
  });
});
document.querySelectorAll('.addr-label').forEach(lbl => {
  lbl.addEventListener('click', function(){
    document.querySelectorAll('.addr-label').forEach(l => l.style.borderColor = 'var(--gray-light)');
    this.style.borderColor = 'var(--gold)';
  });
});
</script>

<?php include 'includes/footer.php'; ?>

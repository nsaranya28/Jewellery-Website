<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle remove
if (isset($_GET['remove'])) {
  $id = (int)$_GET['remove'];
  if (isLoggedIn()) {
    $pdo->prepare("DELETE FROM cart WHERE id=? AND user_id=?")->execute([$id, $_SESSION['user_id']]);
  } else {
    $pdo->prepare("DELETE FROM cart WHERE id=? AND session_id=?")->execute([$id, cartKey()]);
  }
  flashMessage('info', 'Item removed from cart.');
  header('Location: cart.php'); exit;
}

// Handle qty update
if (isset($_GET['update'])) {
  $id  = (int)$_GET['update'];
  $qty = max(1, (int)($_GET['qty'] ?? 1));
  if (isLoggedIn()) {
    $pdo->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?")->execute([$qty, $id, $_SESSION['user_id']]);
  } else {
    $pdo->prepare("UPDATE cart SET quantity=? WHERE id=? AND session_id=?")->execute([$qty, $id, cartKey()]);
  }
  header('Location: cart.php'); exit;
}

// Handle coupon
$appliedCoupon  = $_SESSION['coupon'] ?? null;
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
// Handle coupon
if (isset($_POST['apply_coupon'])) {
  $code   = strtoupper(trim($_POST['coupon_code'] ?? ''));
  
  // Calculate current subtotal to validate coupon
  $st = 0;
  if (isLoggedIn()) {
    $itemsStmt = $pdo->prepare("SELECT SUM(COALESCE(p.discount_price, p.price) * c.quantity) FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
    $itemsStmt->execute([$_SESSION['user_id']]);
  } else {
    $itemsStmt = $pdo->prepare("SELECT SUM(COALESCE(p.discount_price, p.price) * c.quantity) FROM cart c JOIN products p ON c.product_id=p.id WHERE c.session_id=?");
    $itemsStmt->execute([cartKey()]);
  }
  $st = (float)$itemsStmt->fetchColumn();

  $result = applyCoupon($pdo, $code, $st);
  if (isset($result['error'])) {
    flashMessage('error', $result['error']);
  } else {
    $_SESSION['coupon']          = $result['coupon'];
    $_SESSION['coupon_code_str'] = $code;
    flashMessage('success', "Coupon Applied");
  }
  header('Location: cart.php'); exit;
}
if (isset($_GET['remove_coupon'])) {
  unset($_SESSION['coupon'], $_SESSION['coupon_discount'], $_SESSION['coupon_code_str']);
  header('Location: cart.php'); exit;
}

// Fetch cart
if (isLoggedIn()) {
  $stmt = $pdo->prepare("SELECT c.*, p.name, p.slug, p.image1, p.material, p.purity, COALESCE(p.discount_price, p.price) as unit_price, p.stock FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
  $stmt->execute([$_SESSION['user_id']]);
} else {
  $stmt = $pdo->prepare("SELECT c.*, p.name, p.slug, p.image1, p.material, p.purity, COALESCE(p.discount_price, p.price) as unit_price, p.stock FROM cart c JOIN products p ON c.product_id=p.id WHERE c.session_id=?");
  $stmt->execute([cartKey()]);
}
$items = $stmt->fetchAll();

$subtotal = 0;
foreach ($items as $item) $subtotal += $item['unit_price'] * $item['quantity'];

// Recalc coupon discount
$coupon = $_SESSION['coupon'] ?? null;
$discount = 0;
if ($coupon && $subtotal >= $coupon['min_order']) {
  $discount = ($coupon['type'] === 'percent') ? ($subtotal * $coupon['discount'] / 100) : $coupon['discount'];
  $discount = min($discount, $subtotal);
}
$shipping = ($subtotal >= 5000 || empty($items)) ? 0 : 150;
$total    = $subtotal - $discount + $shipping;

$pageTitle = 'Shopping Cart — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>🛍️ Shopping Cart</h1>
    <div class="breadcrumb">
      <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i>
      <span>Cart</span>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($items)): ?>
    <div class="empty-state">
      <div class="es-icon">🛍️</div>
      <h3>Your cart is empty</h3>
      <p>Add some stunning jewellery to your cart!</p>
      <a href="shop.php" class="btn btn-gold mt-20"><i class="fas fa-gem"></i> Continue Shopping</a>
    </div>
    <?php else: ?>
    <div class="cart-layout">
      <!-- Cart Items -->
      <div>
        <div class="cart-table">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:14px;">
                    <a href="product.php?slug=<?= $item['slug'] ?>">
                      <img class="cart-item-img" src="<?= productImage($item['image1']) ?>" alt="<?= safeHtml($item['name']) ?>"/>
                    </a>
                    <div>
                      <div class="cart-item-name"><a href="product.php?slug=<?= $item['slug'] ?>"><?= safeHtml($item['name']) ?></a></div>
                      <div class="cart-item-meta"><?= safeHtml($item['material']) ?> · <?= safeHtml($item['purity']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= money($item['unit_price']) ?></td>
                <td>
                  <div class="qty-control">
                    <a href="cart.php?update=<?= $item['id'] ?>&qty=<?= max(1,$item['quantity']-1) ?>" class="qty-btn">−</a>
                    <span class="qty-val"><?= $item['quantity'] ?></span>
                    <a href="cart.php?update=<?= $item['id'] ?>&qty=<?= min($item['stock'],$item['quantity']+1) ?>" class="qty-btn">+</a>
                  </div>
                </td>
                <td><strong><?= money($item['unit_price'] * $item['quantity']) ?></strong></td>
                <td><a href="cart.php?remove=<?= $item['id'] ?>" class="remove-btn" title="Remove"><i class="fas fa-trash"></i></a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
          <a href="shop.php" class="btn btn-outline" style="color:var(--dark);border-color:var(--gold-dark);"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
        </div>
      </div>

      <!-- Order Summary -->
      <div>
        <div class="order-summary">
          <h3>Order Summary</h3>
          <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <?php if ($discount): ?>
          <div class="summary-row" style="color:var(--green);">
            <span>Coupon (<?= safeHtml($_SESSION['coupon_code_str'] ?? '') ?>)</span>
            <span>−<?= money($discount) ?></span>
          </div>
          <?php endif; ?>
          <div class="summary-row"><span>Shipping</span><span><?= $shipping > 0 ? money($shipping) : '<span style="color:var(--green);">Free</span>' ?></span></div>
          <div class="summary-row total"><span>Total</span><span><?= money($total) ?></span></div>

          <?php if ($coupon): ?>
            <p style="font-size:12px;color:var(--green);margin-bottom:10px;"><i class="fas fa-tag"></i> Coupon applied!
              <a href="cart.php?remove_coupon=1" style="color:var(--red);font-size:11px;"> Remove</a>
            </p>
          <?php else: ?>
          <form method="POST" id="couponForm" style="margin:14px 0;">
            <div class="coupon-form">
              <input type="text" name="coupon_code" id="couponCode" placeholder="Coupon code" value="">
              <button type="submit" name="apply_coupon">Apply</button>
            </div>
            <div style="font-size:11px;color:var(--gray);">Try: SAVE10 · FLAT500 · BRIDAL20</div>
          </form>
          <?php endif; ?>

          <a href="checkout.php" class="btn btn-gold btn-full" style="margin-top:10px;"><i class="fas fa-lock"></i> Proceed to Checkout</a>
          <div style="display:flex;justify-content:center;gap:10px;margin-top:14px;font-size:11px;color:var(--gray);">
            <span><i class="fas fa-shield-halved" style="color:var(--gold);"></i> Secure Checkout</span>
            <span><i class="fas fa-truck" style="color:var(--gold);"></i> Free on ₹5000+</span>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$orderId = (int)($_GET['order'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) { header('Location: my-orders.php'); exit; }

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating = (int)($_POST['rating'] ?? 0);
    $comments = trim($_POST['comments'] ?? '');

    if ($rating >= 1 && $rating <= 5) {
        $check = $pdo->prepare("SELECT id FROM feedbacks WHERE order_id=? AND user_id=?");
        $check->execute([$orderId, $_SESSION['user_id']]);
        if (!$check->fetch()) {
            $ins = $pdo->prepare("INSERT INTO feedbacks (user_id, order_id, rating, comments) VALUES (?, ?, ?, ?)");
            $ins->execute([$_SESSION['user_id'], $orderId, $rating, $comments]);
            flashMessage('success', 'Thank you for your feedback!');
        } else {
            flashMessage('error', 'Feedback already submitted for this order.');
        }
        header("Location: order-success.php?order=$orderId");
        exit;
    } else {
        flashMessage('error', 'Please select a rating.');
    }
}

// Check if feedback already given
$feedbackGiven = false;
$chk = $pdo->prepare("SELECT id FROM feedbacks WHERE order_id=? AND user_id=?");
$chk->execute([$orderId, $_SESSION['user_id']]);
if ($chk->fetch()) {
    $feedbackGiven = true;
}

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
        <a href="invoice.php?order=<?= $order['id'] ?>" class="btn btn-outline" style="color:var(--dark);border-color:var(--gold-dark);"><i class="fas fa-file-download"></i> Download Receipt</a>
        <a href="my-orders.php" class="btn btn-outline" style="color:var(--dark);border-color:var(--gold-dark);"><i class="fas fa-list"></i> My Orders</a>
      </div>

      <?php if (!$feedbackGiven): ?>
      <div class="feedback-section" style="margin-top: 30px; background: var(--white); padding: 24px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-light);">
        <h3 style="font-family: var(--font-serif); color: var(--dark); margin-bottom: 15px; font-size: 18px;">How was your checkout experience?</h3>
        <form method="POST">
            <div class="rating-stars" style="font-size: 32px; color: #e4e4e4; cursor: pointer; margin-bottom: 15px; display: inline-flex; flex-direction: row-reverse;">
                <input type="radio" name="rating" value="5" id="star5" style="display:none;"/><label for="star5" class="star" style="padding: 0 4px;">★</label>
                <input type="radio" name="rating" value="4" id="star4" style="display:none;"/><label for="star4" class="star" style="padding: 0 4px;">★</label>
                <input type="radio" name="rating" value="3" id="star3" style="display:none;"/><label for="star3" class="star" style="padding: 0 4px;">★</label>
                <input type="radio" name="rating" value="2" id="star2" style="display:none;"/><label for="star2" class="star" style="padding: 0 4px;">★</label>
                <input type="radio" name="rating" value="1" id="star1" style="display:none;"/><label for="star1" class="star" style="padding: 0 4px;">★</label>
            </div>
            <style>
                .rating-stars label { transition: color 0.2s; }
                .rating-stars label:hover, .rating-stars label:hover ~ label, .rating-stars input:checked ~ label { color: var(--gold); }
            </style>
            <div class="form-group" style="text-align: left;">
                <textarea name="comments" placeholder="Tell us more about your experience (optional)" class="form-control" style="width: 100%; height: 80px; resize: none; margin-bottom: 15px; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--gray-light); font-family: inherit; font-size: 14px;"></textarea>
            </div>
            <button type="submit" name="submit_feedback" class="btn btn-gold btn-full">Submit Feedback</button>
        </form>
      </div>
      <?php else: ?>
      <div style="margin-top: 30px; padding: 16px; background: rgba(39, 174, 96, 0.1); color: var(--green); border-radius: var(--radius-md); font-weight: 500; text-align: center; border: 1px solid rgba(39, 174, 96, 0.2);">
          <i class="fas fa-heart" style="color: var(--green); margin-right: 6px;"></i> Thank you for your feedback!
      </div>
      <?php endif; ?>

      <a href="index.php" style="display:block;font-size:13px;color:var(--gray);margin-top:20px;">← Continue Shopping</a>
    </div>

    <!-- Confetti-like decoration -->
    <div style="text-align:center;font-size:28px;letter-spacing:8px;margin-top:10px;animation:float 3s ease-in-out infinite;">💎 💍 📿 ✨ 💛</div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

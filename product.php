<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: shop.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id=c.id WHERE p.slug=? AND p.is_active=1");
$stmt->execute([$slug]);
$p = $stmt->fetch();
if (!$p) { header('Location: shop.php'); exit; }

// Rating
$ratingData = productRating($pdo, $p['id']);
$avgRating  = round($ratingData['avg_r'] ?? 5, 1);

// Reviews
$reviews = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.product_id=? ORDER BY r.created_at DESC LIMIT 10");
$reviews->execute([$p['id']]);
$reviews = $reviews->fetchAll();

// Related
$related = $pdo->prepare("SELECT p2.*, c.name as cat_name FROM products p2 JOIN categories c ON p2.category_id=c.id WHERE p2.category_id=? AND p2.id!=? AND p2.is_active=1 LIMIT 4");
$related->execute([$p['category_id'], $p['id']]);
$related = $related->fetchAll();

// Recently viewed
$rv = $pdo->prepare("INSERT INTO recently_viewed (user_id, session_id, product_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE viewed_at=NOW()");
$rv->execute([isLoggedIn() ? $_SESSION['user_id'] : null, cartKey(), $p['id']]);

// Handle review submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
  requireLogin();
  $rating  = min(5, max(1, (int)$_POST['rating']));
  $comment = trim($_POST['comment'] ?? '');
  $stmt2 = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)");
  $stmt2->execute([$_SESSION['user_id'], $p['id'], $rating, $comment]);
  flashMessage('success', 'Thank you for your review!');
  header("Location: product.php?slug=$slug");
  exit;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart'])) {
  $qty = max(1, (int)($_POST['quantity'] ?? 1));
  if (isLoggedIn()) {
    $chk = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
    $chk->execute([$_SESSION['user_id'], $p['id']]);
    $existing = $chk->fetch();
    if ($existing) {
      $pdo->prepare("UPDATE cart SET quantity=quantity+? WHERE id=?")->execute([$qty, $existing['id']]);
    } else {
      $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$_SESSION['user_id'], $p['id'], $qty]);
    }
  } else {
    $chk = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id=? AND product_id=?");
    $chk->execute([cartKey(), $p['id']]);
    $existing = $chk->fetch();
    if ($existing) {
      $pdo->prepare("UPDATE cart SET quantity=quantity+? WHERE id=?")->execute([$qty, $existing['id']]);
    } else {
      $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?,?,?)")->execute([cartKey(), $p['id'], $qty]);
    }
  }
  if (isset($_POST['buy_now'])) {
    header('Location: checkout.php'); exit;
  }
  flashMessage('success', safeHtml($p['name']) . ' added to cart!');
  header("Location: product.php?slug=$slug");
  exit;
}

$discount = $p['discount_price'] ? round((1 - $p['discount_price']/$p['price'])*100) : 0;
$displayPrice = $p['discount_price'] ?? $p['price'];

$pageTitle = safeHtml($p['name']) . ' — ' . SITE_NAME;
$pageDesc  = safeHtml(truncate($p['description'] ?? '', 150));

include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1 style="font-size:24px;"><?= safeHtml($p['name']) ?></h1>
    <div class="breadcrumb">
      <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i>
      <a href="shop.php">Shop</a> <i class="fas fa-chevron-right"></i>
      <a href="category.php?slug=<?= $p['cat_slug'] ?>"><?= safeHtml($p['cat_name']) ?></a> <i class="fas fa-chevron-right"></i>
      <span><?= safeHtml(truncate($p['name'],40)) ?></span>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="product-detail">

      <!-- Gallery -->
      <div class="product-gallery">
        <div class="main-img">
          <img src="<?= productImage($p['image1']) ?>" alt="<?= safeHtml($p['name']) ?>" id="mainProductImg"/>
        </div>
        <div class="product-thumbs">
          <?php
          $imgs = array_filter([$p['image1'],$p['image2'],$p['image3']]);
          if (count($imgs) <= 1) $imgs = [$p['image1'],$p['image1'],$p['image1']];
          foreach ($imgs as $idx => $img): ?>
          <div class="thumb-img <?= $idx===0?'active':'' ?>">
            <img src="<?= productImage($img) ?>" alt="View <?= $idx+1 ?>"/>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Info -->
      <div class="product-detail-info">
        <div class="product-category"><?= safeHtml($p['cat_name']) ?></div>
        <h1><?= safeHtml($p['name']) ?></h1>

        <div class="product-rating">
          <?= starRating($avgRating) ?>
          <span style="font-size:14px;font-weight:600;color:var(--gold-dark);margin-left:4px;"><?= $avgRating ?></span>
          <span class="rating-count">(<?= $ratingData['cnt'] ?> reviews)</span>
        </div>

        <div class="product-detail-price">
          <span class="price-current"><?= money($displayPrice) ?></span>
          <?php if ($discount): ?>
            <span class="price-original"><?= money($p['price']) ?></span>
            <span class="price-discount"><?= $discount ?>% off</span>
          <?php endif; ?>
        </div>

        <?php if ($p['description']): ?>
        <p style="color:var(--gray);font-size:14px;line-height:1.8;margin:16px 0;"><?= nl2br(safeHtml($p['description'])) ?></p>
        <?php endif; ?>

        <!-- Specs -->
        <div class="product-specs">
          <div class="spec-box"><div class="spec-key">Material</div><div class="spec-val"><?= safeHtml($p['material']) ?></div></div>
          <div class="spec-box"><div class="spec-key">Weight</div><div class="spec-val"><?= $p['weight'] ?>g</div></div>
          <div class="spec-box"><div class="spec-key">Purity</div><div class="spec-val"><?= safeHtml($p['purity']) ?></div></div>
          <div class="spec-box"><div class="spec-key">Category</div><div class="spec-val"><?= safeHtml($p['cat_name']) ?></div></div>
        </div>

        <!-- Stock -->
        <?php if ($p['stock'] > 5): ?>
          <div class="stock-info"><i class="fas fa-check-circle"></i> In Stock (<?= $p['stock'] ?> available)</div>
        <?php elseif ($p['stock'] > 0): ?>
          <div class="stock-info low"><i class="fas fa-exclamation-triangle"></i> Only <?= $p['stock'] ?> left — Order soon!</div>
        <?php else: ?>
          <div class="stock-info out"><i class="fas fa-times-circle"></i> Out of Stock</div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if ($p['stock'] > 0): ?>
        <form method="POST" style="margin-top:20px;">
          <div class="qty-control" style="margin-bottom:16px;">
            <span style="font-size:13px;font-weight:500;color:var(--gray);margin-right:8px;">Qty:</span>
            <button type="button" class="qty-btn" data-action="minus">−</button>
            <span class="qty-val">1</span>
            <button type="button" class="qty-btn" data-action="plus">+</button>
            <input type="hidden" name="quantity" value="1"/>
          </div>
          <div class="detail-actions">
            <button type="submit" name="add_cart" class="btn btn-cart btn-gold"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
            <button type="submit" name="add_cart" value="1" onclick="this.form.querySelector('[name=buy_now]').value='1'" class="btn btn-outline" style="color:var(--dark);border-color:var(--gold-dark);">
              <i class="fas fa-bolt"></i> Buy Now
            </button>
            <input type="hidden" name="buy_now" value=""/>
          </div>
        </form>
        <?php endif; ?>

        <button class="btn wish-toggle" data-id="<?= $p['id'] ?>" style="border:2px solid #e74c3c;color:#e74c3c;background:transparent;margin-top:8px;border-radius:50px;padding:8px 18px;font-size:13px;font-weight:600;">
          <i class="fas fa-heart"></i> Add to Wishlist
        </button>

        <!-- Trust -->
        <div style="display:flex;gap:16px;margin-top:20px;flex-wrap:wrap;">
          <span style="font-size:12px;color:var(--gray);"><i class="fas fa-shield-halved" style="color:var(--gold);"></i> Hallmark Certified</span>
          <span style="font-size:12px;color:var(--gray);"><i class="fas fa-truck" style="color:var(--gold);"></i> Free Shipping</span>
          <span style="font-size:12px;color:var(--gray);"><i class="fas fa-rotate-left" style="color:var(--gold);"></i> 30-Day Return</span>
        </div>
      </div>
    </div>

    <!-- ── REVIEWS ── -->
    <div style="margin-top:60px;">
      <h2 style="font-family:var(--font-serif);font-size:30px;font-weight:700;color:var(--dark);margin-bottom:30px;padding-bottom:14px;border-bottom:2px solid var(--gold-pale);">
        ⭐ Customer Reviews (<?= count($reviews) ?>)
      </h2>

      <?php if (isLoggedIn()): ?>
      <div style="background:var(--white);border-radius:var(--radius-md);padding:24px;box-shadow:var(--shadow-sm);border:1px solid var(--gray-light);margin-bottom:30px;">
        <h3 style="font-family:var(--font-serif);font-size:20px;color:var(--dark);margin-bottom:16px;">Write a Review</h3>
        <form method="POST">
          <div class="form-group">
            <label>Your Rating</label>
            <select name="rating" class="form-control" required>
              <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
              <option value="4">⭐⭐⭐⭐ Good</option>
              <option value="3">⭐⭐⭐ Average</option>
              <option value="2">⭐⭐ Poor</option>
              <option value="1">⭐ Terrible</option>
            </select>
          </div>
          <div class="form-group">
            <label>Your Review</label>
            <textarea name="comment" rows="4" placeholder="Share your experience with this product…" required></textarea>
          </div>
          <button type="submit" name="submit_review" class="btn btn-gold"><i class="fas fa-paper-plane"></i> Submit Review</button>
        </form>
      </div>
      <?php else: ?>
      <div style="background:var(--gold-pale);border:1px solid var(--gold);border-radius:var(--radius-md);padding:16px 20px;margin-bottom:24px;font-size:14px;color:var(--gold-dark);">
        <a href="login.php" style="color:var(--gold-dark);font-weight:700;"><i class="fas fa-sign-in-alt"></i> Login</a> to write a review
      </div>
      <?php endif; ?>

      <?php if (empty($reviews)): ?>
        <p style="color:var(--gray);font-size:14px;">No reviews yet. Be the first to review this product!</p>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ($reviews as $r): ?>
        <div style="background:var(--white);border-radius:var(--radius-md);padding:20px;box-shadow:var(--shadow-sm);border:1px solid var(--gray-light);">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:38px;height:38px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;color:var(--white);font-weight:700;font-family:var(--font-serif);font-size:16px;"><?= mb_substr($r['user_name'],0,1) ?></div>
              <div>
                <div style="font-weight:600;font-size:14px;color:var(--dark);"><?= safeHtml($r['user_name']) ?></div>
                <div style="font-size:11px;color:var(--gray);"><?= timeAgo($r['created_at']) ?></div>
              </div>
            </div>
            <div><?= starRating($r['rating']) ?></div>
          </div>
          <?php if ($r['comment']): ?>
          <p style="font-size:14px;color:var(--charcoal);line-height:1.7;font-style:italic;">"<?= safeHtml($r['comment']) ?>"</p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── RELATED ── -->
    <?php if (!empty($related)): ?>
    <div style="margin-top:60px;">
      <div class="section-header">
        <div class="section-label">💫 You May Also Like</div>
        <h2>Related Products</h2>
        <div class="gold-line"></div>
      </div>
      <div class="product-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
        <?php foreach ($related as $rp):
          $rdis = $rp['discount_price'] ? round((1-$rp['discount_price']/$rp['price'])*100) : 0;
        ?>
        <div class="product-card">
          <div class="product-img-wrap">
            <a href="product.php?slug=<?= $rp['slug'] ?>"><img src="<?= productImage($rp['image1']) ?>" alt="<?= safeHtml($rp['name']) ?>" loading="lazy"/></a>
          </div>
          <div class="product-info">
            <div class="product-name"><a href="product.php?slug=<?= $rp['slug'] ?>"><?= safeHtml($rp['name']) ?></a></div>
            <div class="product-price">
              <span class="price-current"><?= money($rp['discount_price'] ?? $rp['price']) ?></span>
              <?php if ($rdis): ?><span class="price-original"><?= money($rp['price']) ?></span><?php endif; ?>
            </div>
          </div>
          <button class="product-cart-btn quick-cart" data-id="<?= $rp['id'] ?>"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

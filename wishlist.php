<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

// Handle remove
if (isset($_GET['remove'])) {
  $pdo->prepare("DELETE FROM wishlist WHERE id=? AND user_id=?")->execute([(int)$_GET['remove'], $_SESSION['user_id']]);
  flashMessage('info', 'Removed from wishlist.');
  header('Location: wishlist.php'); exit;
}

// Handle move to cart
if (isset($_GET['move_cart'])) {
  $productId = (int)$_GET['move_cart'];
  $chk = $pdo->prepare("SELECT id FROM cart WHERE user_id=? AND product_id=?");
  $chk->execute([$_SESSION['user_id'], $productId]);
  if (!$chk->fetch()) {
    $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,1)")->execute([$_SESSION['user_id'], $productId]);
  }
  $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$_SESSION['user_id'], $productId]);
  flashMessage('success', 'Moved to cart!');
  header('Location: cart.php'); exit;
}

$stmt = $pdo->prepare("SELECT w.*, p.name, p.slug, p.image1, p.material, p.purity, p.price, p.discount_price, c.name as cat_name FROM wishlist w JOIN products p ON w.product_id=p.id JOIN categories c ON p.category_id=c.id WHERE w.user_id=? ORDER BY w.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();

$pageTitle = 'My Wishlist — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>💛 My Wishlist</h1>
    <div class="breadcrumb">
      <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i>
      <span>Wishlist</span>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($items)): ?>
    <div class="empty-state">
      <div class="es-icon">💛</div>
      <h3>Your wishlist is empty</h3>
      <p>Save your favourite jewellery for later!</p>
      <a href="shop.php" class="btn btn-gold mt-20"><i class="fas fa-gem"></i> Explore Jewellery</a>
    </div>
    <?php else: ?>
    <div class="product-grid">
      <?php foreach ($items as $item):
        $discount = $item['discount_price'] ? round((1 - $item['discount_price']/$item['price'])*100) : 0;
      ?>
      <div class="product-card">
        <div class="product-img-wrap">
          <a href="product.php?slug=<?= $item['slug'] ?>">
            <img src="<?= productImage($item['image1']) ?>" alt="<?= safeHtml($item['name']) ?>" loading="lazy"/>
          </a>
          <?php if ($discount): ?><div class="product-badge badge-sale"><?= $discount ?>% OFF</div><?php endif; ?>
          <div class="product-actions" style="opacity:1;transform:none;">
            <a href="wishlist.php?remove=<?= $item['id'] ?>" class="product-action-btn" title="Remove from Wishlist" style="color:var(--red);">
              <i class="fas fa-times"></i>
            </a>
          </div>
        </div>
        <div class="product-info">
          <div class="product-category"><?= safeHtml($item['cat_name']) ?></div>
          <div class="product-name"><a href="product.php?slug=<?= $item['slug'] ?>"><?= safeHtml($item['name']) ?></a></div>
          <div class="product-meta"><?= safeHtml($item['material']) ?></div>
          <div class="product-price">
            <span class="price-current"><?= money($item['discount_price'] ?? $item['price']) ?></span>
            <?php if ($discount): ?><span class="price-original"><?= money($item['price']) ?></span><?php endif; ?>
          </div>
        </div>
        <a href="wishlist.php?move_cart=<?= $item['product_id'] ?>" class="product-cart-btn" style="text-align:center;">
          <i class="fas fa-shopping-bag"></i> Move to Cart
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

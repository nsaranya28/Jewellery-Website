<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: shop.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug=? AND is_active=1");
$stmt->execute([$slug]);
$cat = $stmt->fetch();
if (!$cat) { header('Location: shop.php'); exit; }

$sort  = $_GET['sort'] ?? 'newest';
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$sortSQL = match($sort) {
  'price_asc'  => 'ORDER BY COALESCE(discount_price, price) ASC',
  'price_desc' => 'ORDER BY COALESCE(discount_price, price) DESC',
  default      => 'ORDER BY created_at DESC',
};

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=? AND is_active=1")->execute([$cat['id']]) ? $pdo->query("SELECT COUNT(*) FROM products WHERE category_id={$cat['id']} AND is_active=1")->fetchColumn() : 0;

$cnt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=? AND is_active=1");
$cnt->execute([$cat['id']]);
$total = (int)$cnt->fetchColumn();

$offset = ($page - 1) * $perPage;
$stmt2  = $pdo->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id=c.id WHERE p.category_id=? AND p.is_active=1 $sortSQL LIMIT $perPage OFFSET $offset");
$stmt2->execute([$cat['id']]);
$products = $stmt2->fetchAll();

$pageTitle = safeHtml($cat['name']) . ' — ' . SITE_NAME;
$baseUrl   = "category.php?slug=$slug&sort=$sort";

include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1><?= safeHtml($cat['name']) ?></h1>
    <div class="breadcrumb">
      <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i>
      <a href="shop.php">Shop</a> <i class="fas fa-chevron-right"></i>
      <span><?= safeHtml($cat['name']) ?></span>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (!empty($cat['description'])): ?>
    <p style="color:var(--gray);font-size:15px;max-width:700px;margin-bottom:32px;line-height:1.8;"><?= safeHtml($cat['description']) ?></p>
    <?php endif; ?>

    <div class="shop-header">
      <h2><?= $total ?> Products in <?= safeHtml($cat['name']) ?></h2>
      <div class="shop-sort">
        <form method="GET">
          <input type="hidden" name="slug" value="<?= $slug ?>"/>
          <select name="sort" onchange="this.form.submit()">
            <option value="newest"    <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
            <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low to High</option>
            <option value="price_desc"<?= $sort==='price_desc'?'selected':'' ?>>Price: High to Low</option>
          </select>
        </form>
      </div>
    </div>

    <?php if (empty($products)): ?>
    <div class="empty-state"><div class="es-icon">💎</div><h3>No products in this category yet</h3><p><a href="shop.php">Browse all jewellery</a></p></div>
    <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $p):
        $discount = $p['discount_price'] ? round((1 - $p['discount_price']/$p['price'])*100) : 0;
        $rating   = productRating($pdo, $p['id']);
      ?>
      <div class="product-card">
        <div class="product-img-wrap">
          <a href="product.php?slug=<?= $p['slug'] ?>"><img src="<?= productImage($p['image1']) ?>" alt="<?= safeHtml($p['name']) ?>" loading="lazy"/></a>
          <?php if ($discount): ?><div class="product-badge badge-sale"><?= $discount ?>% OFF</div><?php endif; ?>
          <div class="product-actions">
            <button class="product-action-btn wish-toggle" data-id="<?= $p['id'] ?>"><i class="fas fa-heart"></i></button>
          </div>
        </div>
        <div class="product-info">
          <div class="product-name"><a href="product.php?slug=<?= $p['slug'] ?>"><?= safeHtml($p['name']) ?></a></div>
          <div class="product-meta"><?= $p['weight'] ?>g · <?= safeHtml($p['purity']) ?></div>
          <div class="product-rating"><?= starRating($rating['avg_r'] ?? 5) ?><span class="rating-count">(<?= $rating['cnt'] ?? 0 ?>)</span></div>
          <div class="product-price">
            <span class="price-current"><?= money($p['discount_price'] ?? $p['price']) ?></span>
            <?php if ($discount): ?><span class="price-original"><?= money($p['price']) ?></span><?php endif; ?>
          </div>
        </div>
        <button class="product-cart-btn quick-cart" data-id="<?= $p['id'] ?>"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
      </div>
      <?php endforeach; ?>
    </div>
    <?= paginate($total, $page, $perPage, $baseUrl) ?>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

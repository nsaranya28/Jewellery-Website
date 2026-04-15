<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ── FILTERS ──────────────────────────────────────────────────
$q        = trim($_GET['q'] ?? '');
$catSlug  = $_GET['cat'] ?? '';
$sort     = $_GET['sort'] ?? 'newest';
$filter   = $_GET['filter'] ?? '';
$maxPrice = (int)($_GET['max_price'] ?? 200000);
$materials= $_GET['material'] ?? [];
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;

$where  = ["p.is_active = 1"];
$params = [];

if ($q) { $where[] = "p.name LIKE ?"; $params[] = "%$q%"; }
if ($catSlug) {
  $where[] = "c.slug = ?"; $params[] = $catSlug;
}
if ($filter === 'featured') { $where[] = "p.is_featured = 1"; }
if ($filter === 'sale')     { $where[] = "p.discount_price IS NOT NULL"; }
if ($filter === 'new')      { $where[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; }
if ($maxPrice < 200000)     { $where[] = "COALESCE(p.discount_price, p.price) <= ?"; $params[] = $maxPrice; }
if (!empty($materials)) {
  $phs = implode(',', array_fill(0, count($materials), '?'));
  $where[] = "p.material IN ($phs)";
  $params = array_merge($params, $materials);
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$sortSQL = match($sort) {
  'price_asc'  => 'ORDER BY COALESCE(p.discount_price, p.price) ASC',
  'price_desc' => 'ORDER BY COALESCE(p.discount_price, p.price) DESC',
  'name'       => 'ORDER BY p.name ASC',
  default      => 'ORDER BY p.created_at DESC',
};

// Count
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id=c.id $whereSQL");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

// Products
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id=c.id $whereSQL $sortSQL LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories for filter
$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();

// Current category info
$currentCat = null;
if ($catSlug) {
  $stmt2 = $pdo->prepare("SELECT * FROM categories WHERE slug=?");
  $stmt2->execute([$catSlug]);
  $currentCat = $stmt2->fetch();
}

$title = $currentCat ? safeHtml($currentCat['name']) : ($q ? "Search: $q" : 'All Jewellery');
$pageTitle = "$title — " . SITE_NAME;

// Build URL for pagination
$urlParams = array_filter(['q'=>$q,'cat'=>$catSlug,'sort'=>$sort,'filter'=>$filter,'max_price'=>($maxPrice<200000?$maxPrice:null)]);
$baseUrl = 'shop.php?' . http_build_query($urlParams);

include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1><?= $title ?></h1>
    <div class="breadcrumb">
      <a href="index.php">Home</a>
      <i class="fas fa-chevron-right"></i>
      <a href="shop.php">Shop</a>
      <?php if ($currentCat): ?>
        <i class="fas fa-chevron-right"></i>
        <span><?= safeHtml($currentCat['name']) ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="shop-layout">

      <!-- ── FILTER SIDEBAR ── -->
      <aside class="filter-sidebar">
        <h3>🔍 Filters</h3>
        <form method="GET" action="shop.php" id="filterForm">
          <?php if ($q): ?><input type="hidden" name="q" value="<?= safeHtml($q) ?>"/><?php endif; ?>

          <!-- Category -->
          <div class="filter-group">
            <h4>Category</h4>
            <?php foreach ($categories as $cat): ?>
            <label>
              <input type="radio" name="cat" value="<?= $cat['slug'] ?>" <?= $catSlug===$cat['slug'] ? 'checked' : '' ?>/>
              <?= safeHtml($cat['name']) ?>
            </label>
            <?php endforeach; ?>
            <label>
              <input type="radio" name="cat" value="" <?= !$catSlug ? 'checked' : '' ?>/>
              All Categories
            </label>
          </div>

          <!-- Price Range -->
          <div class="filter-group">
            <h4>Max Price</h4>
            <input type="range" id="priceRange" name="max_price" min="1000" max="200000" step="1000" value="<?= $maxPrice ?>"/>
            <div class="price-display" id="priceDisplay">₹0 — ₹<?= number_format($maxPrice) ?></div>
          </div>

          <!-- Material -->
          <div class="filter-group">
            <h4>Material</h4>
            <?php foreach (['Gold','Silver','White Gold + Diamond','Gold + Diamond','Gold + Ruby'] as $mat): ?>
            <label>
              <input type="checkbox" name="material[]" value="<?= $mat ?>" <?= in_array($mat,$materials)?'checked':'' ?>/>
              <?= $mat ?>
            </label>
            <?php endforeach; ?>
          </div>

          <!-- Sort -->
          <div class="filter-group">
            <h4>Sort By</h4>
            <select name="sort" onchange="this.form.submit()">
              <option value="newest"    <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
              <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low to High</option>
              <option value="price_desc"<?= $sort==='price_desc'?'selected':'' ?>>Price: High to Low</option>
              <option value="name"      <?= $sort==='name'?'selected':'' ?>>Name A–Z</option>
            </select>
          </div>

          <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Apply Filters</button>
          <a href="shop.php" style="display:block;text-align:center;font-size:13px;color:var(--gray);margin-top:10px;">Clear All</a>
        </form>
      </aside>

      <!-- ── PRODUCTS ── -->
      <div class="shop-main">
        <div class="shop-header">
          <h2><?= $total ?> Product<?= $total!=1?'s':'' ?> Found</h2>
        </div>

        <?php if (empty($products)): ?>
        <div class="empty-state">
          <div class="es-icon">💎</div>
          <h3>No products found</h3>
          <p>Try adjusting your filters or <a href="shop.php">browse all jewellery</a></p>
        </div>
        <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $p):
            $discount = $p['discount_price'] ? round((1 - $p['discount_price']/$p['price'])*100) : 0;
            $rating   = productRating($pdo, $p['id']);
          ?>
          <div class="product-card">
            <div class="product-img-wrap">
              <a href="product.php?slug=<?= $p['slug'] ?>">
                <img src="<?= productImage($p['image1']) ?>" alt="<?= safeHtml($p['name']) ?>" loading="lazy"/>
              </a>
              <?php if ($discount): ?><div class="product-badge badge-sale"><?= $discount ?>% OFF</div><?php endif; ?>
              <div class="product-actions">
                <button class="product-action-btn wish-toggle" data-id="<?= $p['id'] ?>" title="Wishlist"><i class="fas fa-heart"></i></button>
              </div>
            </div>
            <div class="product-info">
              <div class="product-category"><?= safeHtml($p['cat_name']) ?></div>
              <div class="product-name"><a href="product.php?slug=<?= $p['slug'] ?>"><?= safeHtml($p['name']) ?></a></div>
              <div class="product-meta"><?= $p['weight'] ?>g · <?= safeHtml($p['purity']) ?></div>
              <div class="product-rating"><?= starRating($rating['avg_r'] ?? 5) ?><span class="rating-count">(<?= $rating['cnt'] ?? 0 ?>)</span></div>
              <div class="product-price">
                <span class="price-current"><?= money($p['discount_price'] ?? $p['price']) ?></span>
                <?php if ($discount): ?><span class="price-original"><?= money($p['price']) ?></span><span class="price-discount"><?= $discount ?>% off</span><?php endif; ?>
              </div>
            </div>
            <button class="product-cart-btn quick-cart" data-id="<?= $p['id'] ?>">
              <i class="fas fa-shopping-bag"></i> Add to Cart
            </button>
          </div>
          <?php endforeach; ?>
        </div>
        <?= paginate($total, $page, $perPage, $baseUrl) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

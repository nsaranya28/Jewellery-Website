<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = SITE_NAME . ' — Pure Gold, Pure Love';
$pageDesc  = 'Shop the finest gold, silver and diamond jewellery online. Kolusu, Kammal, Chain, Bangle, Rings & more. Free shipping above ₹5000.';

// Featured products
$featured = $pdo->query("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id=c.id WHERE p.is_featured=1 AND p.is_active=1 LIMIT 8")->fetchAll();

// Categories
$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id=c.id AND is_active=1) as product_count FROM categories c WHERE c.is_active=1 ORDER BY c.name")->fetchAll();

// Testimonials
$testimonials = [
  ['name'=>'Ananya Krishnamurthy','loc'=>'Chennai','rating'=>5,'text'=>'The bridal necklace I ordered for my wedding was absolutely breathtaking! The craftsmanship is outstanding and delivery was on time.','img'=>'https://i.pravatar.cc/80?img=47'],
  ['name'=>'Meenakshi Sundaram','loc'=>'Coimbatore','rating'=>5,'text'=>'I bought the peacock jhumkas for my sister\'s wedding. She was in tears — they are so beautiful! Will definitely order again.','img'=>'https://i.pravatar.cc/80?img=48'],
  ['name'=>'Priya Devi','loc'=>'Madurai','rating'=>5,'text'=>'Excellent quality gold chain. The Singapore pattern is exactly what I wanted. Packaging was premium. 100% recommended!','img'=>'https://i.pravatar.cc/80?img=49'],
];

// Active Coupons
$today = date('Y-m-d');
$activeCoupons = $pdo->prepare("SELECT * FROM coupons WHERE is_active=1 AND used_count < max_uses AND (end_date >= ? OR expiry >= ? OR (end_date IS NULL AND expiry IS NULL)) ORDER BY created_at DESC LIMIT 3");
$activeCoupons->execute([$today, $today]);
$coupons = $activeCoupons->fetchAll();

include 'includes/header.php';
?>

<!-- ── HERO ────────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-gems">💍</div>
  <div class="container" style="width:100%;">
    <div class="hero-content">
      <div class="hero-badge">✨ New Collection 2026 — Hallmark Certified</div>
      <h1>Pure Gold,<br/><span class="gold">Pure Love.</span></h1>
      <p class="hero-desc">Discover handcrafted jewellery that tells your story. From traditional Kolusu to sparkling Diamond rings — crafted for every milestone.</p>
      <div class="hero-btns">
        <a href="shop.php" class="btn btn-gold"><i class="fas fa-gem"></i> Shop Now</a>
        <a href="shop.php?filter=featured" class="btn btn-outline"><i class="fas fa-star"></i> Featured Collection</a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat"><div class="num">10K+</div><div class="lbl">Happy Customers</div></div>
        <div class="hero-stat"><div class="num">500+</div><div class="lbl">Unique Designs</div></div>
        <div class="hero-stat"><div class="num">22K</div><div class="lbl">Hallmark Gold</div></div>
        <div class="hero-stat"><div class="num">30</div><div class="lbl">Days Easy Return</div></div>
      </div>
    </div>
  </div>
</section>

<!-- ── TRUST STRIP ─────────────────────────────────────────── -->
<div class="trust-strip">
  <div class="trust-grid container">
    <div class="trust-item"><div class="ti-icon">🚚</div><div class="ti-title">Free Shipping</div><div class="ti-sub">On orders above ₹5,000</div></div>
    <div class="trust-item"><div class="ti-icon">🏅</div><div class="ti-title">Hallmark Certified</div><div class="ti-sub">BIS certified 22K & 18K gold</div></div>
    <div class="trust-item"><div class="ti-icon">🔄</div><div class="ti-title">30-Day Returns</div><div class="ti-sub">Hassle-free returns & exchange</div></div>
    <div class="trust-item"><div class="ti-icon">🔒</div><div class="ti-title">Secure Payment</div><div class="ti-sub">GPay, PhonePe, UPI & COD</div></div>
    <div class="trust-item"><div class="ti-icon">💬</div><div class="ti-title">24/7 Support</div><div class="ti-sub">WhatsApp & phone support</div></div>
  </div>
</div>

<!-- ── CATEGORIES ──────────────────────────────────────────── -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="section-label">💎 Browse By Category</div>
      <h2>Shop Your Favourite<br/>Jewellery Style</h2>
      <p>Explore our curated collection of traditional and modern jewellery pieces</p>
      <div class="gold-line"></div>
    </div>
    <div class="category-grid">
      <?php foreach ($categories as $cat): ?>
      <a href="category.php?slug=<?= $cat['slug'] ?>" class="cat-card">
        <img src="<?= categoryImage($cat['slug']) ?>" alt="<?= safeHtml($cat['name']) ?>" class="cat-img" loading="lazy"/>
        <div class="cat-name"><?= safeHtml($cat['name']) ?></div>
        <div class="cat-count"><?= $cat['product_count'] ?> products</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── OFFERS & DEALS ──────────────────────────────────────── -->
<?php if (!empty($coupons)): ?>
<section class="section section-ivory" style="padding-top: 0;">
  <div class="container">
    <div class="section-header">
      <div class="section-label">🏷️ Special Savings</div>
      <h2>Exclusive Offers & Deals</h2>
      <p>Limited time discounts on your favourite jewellery collections</p>
      <div class="gold-line"></div>
    </div>
    
    <div class="offer-grid">
      <?php foreach ($coupons as $c): 
        $discountTxt = ($c['type'] === 'percent') ? round($c['discount']) . '% OFF' : money($c['discount']) . ' OFF';
        $title = $c['description'] ? 'Special Offer' : 'Save on your purchase';
        $desc = $c['description'] ?: 'Exclusive discount for our valued customers. Limited time only!';
      ?>
      <div class="offer-card">
        <div class="offer-cutout left"></div>
        <div class="offer-cutout right"></div>
        <div class="offer-badge"><?= $discountTxt ?></div>
        <h3 class="offer-title"><?= $title ?></h3>
        <p class="offer-desc"><?= safeHtml($desc) ?></p>
        <div style="font-size: 11px; color: var(--gray); margin-bottom: 15px;">
            <i class="fas fa-info-circle"></i> Min. order <?= money($c['min_amount']) ?>. Valid till <?= date('d M Y', strtotime($c['end_date'] ?: ($c['expiry'] ?: 'today'))) ?>.
        </div>
        <div class="offer-code-wrap">
          <span class="offer-code" id="code-<?= $c['id'] ?>"><?= safeHtml($c['code']) ?></span>
          <button class="btn-copy" onclick="copyCoupon('<?= safeHtml($c['code']) ?>', this)">
            <i class="fas fa-copy"></i> <span>Copy</span>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<script>
function copyCoupon(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        const span = btn.querySelector('span');
        const icon = btn.querySelector('i');
        const originalTxt = span.innerText;
        const originalIcon = icon.className;
        
        span.innerText = 'Copied!';
        icon.className = 'fas fa-check';
        btn.classList.add('copied');
        
        setTimeout(() => {
            span.innerText = originalTxt;
            icon.className = originalIcon;
            btn.classList.remove('copied');
        }, 2000);
        
        if (typeof showToast === 'function') {
            showToast('Coupon code copied to clipboard! 🎟️', 'success');
        }
    });
}
</script>
<?php endif; ?>

<!-- ── FEATURED PRODUCTS ───────────────────────────────────── -->
<section class="section section-ivory">
  <div class="container">
    <div class="section-header">
      <div class="section-label">⭐ Best Sellers</div>
      <h2>Featured Jewellery</h2>
      <p>Hand-picked collections loved by thousands of customers</p>
      <div class="gold-line"></div>
    </div>
    <div class="product-grid">
      <?php foreach ($featured as $p): 
        $discount = $p['discount_price'] ? round((1 - $p['discount_price']/$p['price'])*100) : 0;
        $rating   = productRating($pdo, $p['id']);
      ?>
      <div class="product-card">
        <div class="product-img-wrap">
          <a href="product.php?slug=<?= $p['slug'] ?>">
            <img src="<?= productImage($p['image1']) ?>" alt="<?= safeHtml($p['name']) ?>" loading="lazy"/>
          </a>
          <?php if ($discount): ?><div class="product-badge badge-sale"><?= $discount ?>% OFF</div><?php endif; ?>
          <div class="product-badge" style="top:auto;bottom:10px;left:10px;background:rgba(26,18,7,0.7);"><?= safeHtml($p['material']) ?></div>
          <div class="product-actions">
            <button class="product-action-btn wish-toggle" data-id="<?= $p['id'] ?>" title="Add to Wishlist"><i class="fas fa-heart"></i></button>
            <a href="product.php?slug=<?= $p['slug'] ?>" class="product-action-btn" title="Quick View"><i class="fas fa-eye"></i></a>
          </div>
        </div>
        <div class="product-info">
          <div class="product-category"><?= safeHtml($p['cat_name']) ?></div>
          <div class="product-name"><a href="product.php?slug=<?= $p['slug'] ?>"><?= safeHtml($p['name']) ?></a></div>
          <div class="product-meta"><?= $p['weight'] ?>g &nbsp;·&nbsp; <?= safeHtml($p['purity']) ?></div>
          <div class="product-rating">
            <?= starRating($rating['avg_r'] ?? 5) ?>
            <span class="rating-count">(<?= $rating['cnt'] ?? 0 ?>)</span>
          </div>
          <div class="product-price">
            <span class="price-current"><?= money($p['discount_price'] ?? $p['price']) ?></span>
            <?php if ($p['discount_price']): ?>
              <span class="price-original"><?= money($p['price']) ?></span>
              <span class="price-discount"><?= $discount ?>% off</span>
            <?php endif; ?>
          </div>
        </div>
        <button class="product-cart-btn quick-cart" data-id="<?= $p['id'] ?>">
          <i class="fas fa-shopping-bag"></i> Add to Cart
        </button>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-30">
      <a href="shop.php" class="btn btn-gold"><i class="fas fa-th-large"></i> View All Jewellery</a>
    </div>
  </div>
</section>

<!-- ── PROMO BANNER ────────────────────────────────────────── -->
<section class="section section-dark">
  <div class="container" style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;">
    <div>
      <div class="section-label" style="display:inline-block;">🏷️ Limited Time Offer</div>
      <h2 style="font-family:var(--font-serif);font-size:42px;font-weight:700;color:#fff;margin:16px 0;">Up to <span style="color:var(--gold-light);">20% Off</span><br/>on Bridal Sets</h2>
      <p style="color:#ccc;font-size:15px;margin-bottom:28px;">Use coupon <strong style="color:var(--gold-light);">BRIDAL20</strong> at checkout. Valid on orders above ₹50,000. Limited time only!</p>
      <a href="shop.php?filter=sale" class="btn btn-gold"><i class="fas fa-tag"></i> Shop the Sale</a>
    </div>
    <div style="text-align:center;font-size:120px;opacity:0.3;animation:float 3s ease-in-out infinite;">💍</div>
  </div>
</section>

<!-- ── NEW ARRIVALS ────────────────────────────────────────── -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="section-label">🆕 New Arrivals</div>
      <h2>Latest Additions</h2>
      <p>Fresh designs just added to our collection</p>
      <div class="gold-line"></div>
    </div>
    <?php
    $newArrivals = $pdo->query("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id=c.id WHERE p.is_active=1 ORDER BY p.created_at DESC LIMIT 4")->fetchAll();
    ?>
    <div class="product-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
      <?php foreach ($newArrivals as $p):
        $discount = $p['discount_price'] ? round((1 - $p['discount_price']/$p['price'])*100) : 0;
        $rating   = productRating($pdo, $p['id']);
      ?>
      <div class="product-card">
        <div class="product-img-wrap">
          <a href="product.php?slug=<?= $p['slug'] ?>">
            <img src="<?= productImage($p['image1']) ?>" alt="<?= safeHtml($p['name']) ?>" loading="lazy"/>
          </a>
          <div class="product-badge">New</div>
          <div class="product-actions">
            <button class="product-action-btn wish-toggle" data-id="<?= $p['id'] ?>" title="Add to Wishlist"><i class="fas fa-heart"></i></button>
          </div>
        </div>
        <div class="product-info">
          <div class="product-category"><?= safeHtml($p['cat_name']) ?></div>
          <div class="product-name"><a href="product.php?slug=<?= $p['slug'] ?>"><?= safeHtml($p['name']) ?></a></div>
          <div class="product-rating"><?= starRating($rating['avg_r'] ?? 5) ?><span class="rating-count">(<?= $rating['cnt'] ?? 0 ?>)</span></div>
          <div class="product-price">
            <span class="price-current"><?= money($p['discount_price'] ?? $p['price']) ?></span>
            <?php if ($p['discount_price']): ?><span class="price-original"><?= money($p['price']) ?></span><?php endif; ?>
          </div>
        </div>
        <button class="product-cart-btn quick-cart" data-id="<?= $p['id'] ?>">
          <i class="fas fa-shopping-bag"></i> Add to Cart
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── TESTIMONIALS ────────────────────────────────────────── -->
<section class="section section-dark">
  <div class="container">
    <div class="section-header">
      <div class="section-label" style="display:inline-block;">💬 Customer Stories</div>
      <h2 style="color:#fff;">What Our Customers Say</h2>
      <p style="color:#aaa;">Thousands of happy customers across Tamil Nadu & India</p>
      <div class="gold-line"></div>
    </div>
    <div class="testimonials-grid">
      <?php foreach ($testimonials as $t): ?>
      <div class="testi-card">
        <div class="testi-stars">⭐⭐⭐⭐⭐</div>
        <p class="testi-text">"<?= $t['text'] ?>"</p>
        <div class="testi-author">
          <div class="testi-avatar"><img src="<?= $t['img'] ?>" alt="<?= $t['name'] ?>"/></div>
          <div>
            <div class="testi-name"><?= $t['name'] ?></div>
            <div class="testi-loc">📍 <?= $t['loc'] ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── NEWSLETTER ──────────────────────────────────────────── -->
<div class="newsletter">
  <div class="container">
    <h2>✉️ Stay Updated with New Collections</h2>
    <p>Subscribe to get exclusive offers, new arrival alerts, and special discounts!</p>
    <form class="newsletter-form" onsubmit="showToast('Thank you for subscribing! 🎉','success');return false;">
      <input type="email" placeholder="Enter your email address" required/>
      <button type="submit">Subscribe</button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

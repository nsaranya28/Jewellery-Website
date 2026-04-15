<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
  $stmt->execute([$id]);
  $product = $stmt->fetch();
}

$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name']);
  $slug    = makeSlug($name);
  $catId   = (int)$_POST['category_id'];
  $desc    = trim($_POST['description'] ?? '');
  $price   = (float)$_POST['price'];
  $dPrice  = $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null;
  $mat     = trim($_POST['material'] ?? '');
  $weight  = (float)$_POST['weight'];
  $purity  = trim($_POST['purity'] ?? '');
  $stock   = (int)$_POST['stock'];
  $feat    = isset($_POST['is_featured']) ? 1 : 0;

  if ($product) {
    $pdo->prepare("UPDATE products SET category_id=?,name=?,slug=?,description=?,price=?,discount_price=?,material=?,weight=?,purity=?,stock=?,is_featured=? WHERE id=?")
        ->execute([$catId,$name,$slug,$desc,$price,$dPrice,$mat,$weight,$purity,$stock,$feat,$product['id']]);
    flashMessage('success', 'Product updated!');
  } else {
    // Check slug unique
    $chk = $pdo->prepare("SELECT id FROM products WHERE slug=?"); $chk->execute([$slug]);
    if ($chk->fetch()) $slug .= '-' . time();
    $pdo->prepare("INSERT INTO products (category_id,name,slug,description,price,discount_price,material,weight,purity,stock,is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$catId,$name,$slug,$desc,$price,$dPrice,$mat,$weight,$purity,$stock,$feat]);
    flashMessage('success', 'Product added!');
  }
  header('Location: products.php'); exit;
}

$adminTitle = ($product ? 'Edit' : 'Add') . ' Product';
include 'includes/header.php';
?>

<div style="max-width:700px;">
  <a href="products.php" style="font-size:13px;color:var(--gray);display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;"><i class="fas fa-arrow-left"></i> Back to Products</a>

  <div class="admin-form">
    <h2 style="font-size:20px;font-weight:700;color:var(--charcoal);margin-bottom:24px;"><?= $product ? '✏️ Edit' : '➕ Add New' ?> Product</h2>
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" required value="<?= safeHtml($product['name'] ?? '') ?>" placeholder="e.g. Traditional Lakshmi Kolusu"/>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select name="category_id" required>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? '')==$c['id']?'selected':'' ?>><?= safeHtml($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="4" placeholder="Describe this jewellery piece…"><?= safeHtml($product['description'] ?? '') ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Price (₹) *</label>
          <input type="number" name="price" step="0.01" required value="<?= $product['price'] ?? '' ?>" placeholder="0.00"/>
        </div>
        <div class="form-group">
          <label>Discount Price (₹)</label>
          <input type="number" name="discount_price" step="0.01" value="<?= $product['discount_price'] ?? '' ?>" placeholder="Leave blank if no discount"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Material</label>
          <input type="text" name="material" value="<?= safeHtml($product['material'] ?? '') ?>" placeholder="Gold, Silver, White Gold…"/>
        </div>
        <div class="form-group">
          <label>Purity</label>
          <input type="text" name="purity" value="<?= safeHtml($product['purity'] ?? '') ?>" placeholder="22K, 18K, 92.5%…"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Weight (grams)</label>
          <input type="number" name="weight" step="0.01" value="<?= $product['weight'] ?? '' ?>"/>
        </div>
        <div class="form-group">
          <label>Stock Quantity</label>
          <input type="number" name="stock" value="<?= $product['stock'] ?? 10 ?>"/>
        </div>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;">
          <input type="checkbox" name="is_featured" value="1" style="width:16px;height:16px;accent-color:var(--gold);" <?= ($product['is_featured'] ?? 0)?'checked':'' ?>/>
          Mark as Featured Product (shown on homepage)
        </label>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> <?= $product ? 'Update Product' : 'Add Product' ?></button>
        <a href="products.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

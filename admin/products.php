<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// Delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  try {
    // Check if product has orders
    $chk = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id=?");
    $chk->execute([$id]);
    if ($chk->fetchColumn() > 0) {
      // Product has orders, soft delete by deactivating
      $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([$id]);
      flashMessage('success', 'Product has existing orders, so it was deactivated instead of deleted.');
    } else {
      // Safe to delete physically
      $pdo->prepare("DELETE FROM products WHERE id=?");
      $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
      flashMessage('success', 'Product deleted.');
    }
  } catch (PDOException $e) {
    // Fallback for any other constraint failures
    $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([$id]);
    flashMessage('success', 'Product deactivated.');
  }
  header('Location: products.php'); exit;
}

$search  = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$where   = ["p.is_active=1"];
$params  = [];
if ($search) { $where[] = "p.name LIKE ?"; $params[] = "%$search%"; }
if ($catFilter) { $where[] = "p.category_id=?"; $params[] = $catFilter; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id $whereSQL ORDER BY p.created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();

$adminTitle = 'Products';
include 'includes/header.php';
?>

<div class="admin-table-head" style="margin-bottom:20px;padding:0;">
  <div class="table-toolbar">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
      <input type="text" name="q" placeholder="Search products…" value="<?= safeHtml($search) ?>" style="min-width:200px;"/>
      <select name="cat">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id']?'selected':'' ?>><?= safeHtml($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-search"></i> Filter</button>
      <a href="products.php" class="btn btn-outline btn-sm">Clear</a>
    </form>
  </div>
  <a href="product-form.php" class="btn btn-gold"><i class="fas fa-plus"></i> Add Product</a>
</div>

<div class="admin-table-wrap">
  <table>
    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Featured</th><th>Actions</th></tr></thead>
    <tbody>
      <?php if (empty($products)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray);">No products found</td></tr>
      <?php endif; ?>
      <?php foreach ($products as $p): ?>
      <tr>
        <td><img class="prod-img" src="<?= productImage($p['image1']) ?>" alt=""/></td>
        <td>
          <div style="font-weight:600;font-size:13px;"><?= safeHtml($p['name']) ?></div>
          <div style="font-size:11px;color:var(--gray);"><?= safeHtml($p['material']) ?> · <?= safeHtml($p['purity']) ?></div>
        </td>
        <td><?= safeHtml($p['cat_name']) ?></td>
        <td>
          <div style="font-weight:700;color:var(--gold-dark);">₹<?= number_format($p['discount_price'] ?? $p['price']) ?></div>
          <?php if ($p['discount_price']): ?><div style="font-size:11px;text-decoration:line-through;color:var(--gray);">₹<?= number_format($p['price']) ?></div><?php endif; ?>
        </td>
        <td><span class="badge <?= $p['stock']==0?'badge-red':($p['stock']<=5?'badge-gold':'badge-green') ?>"><?= $p['stock'] ?></span></td>
        <td><?= $p['is_featured'] ? '⭐' : '—' ?></td>
        <td>
          <div style="display:flex;gap:6px;">
            <a href="product-form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
            <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include 'includes/footer.php'; ?>

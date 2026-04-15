<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// Update stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($_POST['stock'] as $id => $qty) {
    $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([(int)$qty, (int)$id]);
  }
  flashMessage('success', 'Stock updated!');
  header('Location: stock.php'); exit;
}

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id WHERE p.is_active=1 ORDER BY p.stock ASC")->fetchAll();
$adminTitle = 'Stock Management';
include 'includes/header.php';
?>

<form method="POST">
<div class="admin-table-wrap">
  <div class="admin-table-head">
    <h3>📦 Stock Management</h3>
    <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Save All Changes</button>
  </div>
  <table>
    <thead><tr><th>Image</th><th>Product</th><th>Category</th><th>Material</th><th>Current Stock</th><th>Update Stock</th></tr></thead>
    <tbody>
      <?php foreach ($products as $p): ?>
      <tr style="background:<?= $p['stock']==0?'rgba(231,76,60,0.05)':($p['stock']<=5?'rgba(201,162,39,0.05)':'transparent') ?>;">
        <td><img class="prod-img" src="<?= productImage($p['image1']) ?>" alt=""/></td>
        <td>
          <div style="font-weight:600;font-size:13px;"><?= safeHtml($p['name']) ?></div>
          <div style="font-size:11px;color:var(--gray);"><?= safeHtml($p['purity']) ?></div>
        </td>
        <td><?= safeHtml($p['cat_name']) ?></td>
        <td><?= safeHtml($p['material']) ?></td>
        <td><span class="badge <?= $p['stock']==0?'badge-red':($p['stock']<=5?'badge-gold':'badge-green') ?>"><?= $p['stock'] ?> units</span></td>
        <td>
          <input type="number" name="stock[<?= $p['id'] ?>]" value="<?= $p['stock'] ?>" min="0" style="width:80px;padding:7px;border:1.5px solid #ededed;border-radius:8px;font-size:13px;outline:none;" onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='#ededed'"/>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</form>

<?php include 'includes/footer.php'; ?>

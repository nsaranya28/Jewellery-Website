<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// Delete
if (isset($_GET['delete'])) {
  $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([(int)$_GET['delete']]);
  flashMessage('success','Coupon deleted.'); header('Location: coupons.php'); exit;
}

// Add coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code     = strtoupper(trim($_POST['code']));
  $type     = $_POST['type'];
  $discount = (float)$_POST['discount'];
  $minOrder = (float)$_POST['min_order'];
  $maxUses  = (int)$_POST['max_uses'];
  $expiry   = $_POST['expiry'] ?: null;
  $pdo->prepare("INSERT INTO coupons (code,type,discount,min_order,max_uses,expiry) VALUES (?,?,?,?,?,?)")->execute([$code,$type,$discount,$minOrder,$maxUses,$expiry]);
  flashMessage('success','Coupon created!'); header('Location: coupons.php'); exit;
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$adminTitle = 'Coupons & Offers';
include 'includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">
  <div class="admin-table-wrap">
    <div class="admin-table-head"><h3>🏷️ Active Coupons</h3></div>
    <table>
      <thead><tr><th>Code</th><th>Type</th><th>Discount</th><th>Min Order</th><th>Uses</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($coupons as $c): ?>
        <tr>
          <td><strong style="color:var(--gold-dark);font-size:14px;letter-spacing:1px;"><?= safeHtml($c['code']) ?></strong></td>
          <td><?= ucfirst($c['type']) ?></td>
          <td><strong><?= $c['type']==='percent' ? $c['discount'].'%' : '₹'.number_format($c['discount']) ?></strong></td>
          <td>₹<?= number_format($c['min_order']) ?></td>
          <td><?= $c['used_count'] ?> / <?= $c['max_uses'] ?></td>
          <td style="font-size:12px;"><?= $c['expiry'] ? date('d M Y',strtotime($c['expiry'])) : 'No expiry' ?></td>
          <td><span class="badge <?= $c['is_active']?'badge-green':'badge-red' ?>"><?= $c['is_active']?'Active':'Inactive' ?></span></td>
          <td><a href="coupons.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-form">
    <h3 style="font-size:16px;font-weight:700;color:var(--charcoal);margin-bottom:18px;">➕ Create Coupon</h3>
    <form method="POST">
      <div class="form-group"><label>Coupon Code *</label><input type="text" name="code" required placeholder="e.g. SAVE10" style="text-transform:uppercase;"/></div>
      <div class="form-group">
        <label>Discount Type *</label>
        <select name="type">
          <option value="percent">Percentage (%)</option>
          <option value="flat">Flat Amount (₹)</option>
        </select>
      </div>
      <div class="form-group"><label>Discount Value *</label><input type="number" name="discount" step="0.01" required placeholder="e.g. 10 for 10%"/></div>
      <div class="form-group"><label>Minimum Order (₹)</label><input type="number" name="min_order" value="0" step="0.01"/></div>
      <div class="form-group"><label>Max Uses</label><input type="number" name="max_uses" value="100"/></div>
      <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry"/></div>
      <button type="submit" class="btn btn-gold" style="width:100%"><i class="fas fa-plus"></i> Create Coupon</button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

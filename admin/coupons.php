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

// Edit Mode
$editCoupon = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCoupon = $stmt->fetch();
}

// Add/Update coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code       = strtoupper(trim($_POST['code']));
  $type       = $_POST['type'];
  $discount   = (float)$_POST['discount'];
  $minAmount   = (float)$_POST['min_amount'];
  $maxUses    = (int)$_POST['max_uses'];
  $startDate  = $_POST['start_date'] ?: null;
  $endDate    = $_POST['end_date'] ?: null;
  $isActive   = isset($_POST['is_active']) ? 1 : 0;
  $id         = (int)($_POST['id'] ?? 0);

  if ($id > 0) {
      $pdo->prepare("UPDATE coupons SET code=?, type=?, discount=?, min_order=?, max_uses=?, start_date=?, end_date=?, is_active=? WHERE id=?")
          ->execute([$code, $type, $discount, $minAmount, $maxUses, $startDate, $endDate, $isActive, $id]);
      flashMessage('success','Coupon updated!');
  } else {
      $pdo->prepare("INSERT INTO coupons (code,type,discount,min_order,max_uses,start_date,end_date) VALUES (?,?,?,?,?,?,?)")
          ->execute([$code,$type,$discount,$minAmount,$maxUses,$startDate,$endDate]);
      flashMessage('success','Coupon created!');
  }
  header('Location: coupons.php'); exit;
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$adminTitle = 'Coupons & Offers';
include 'includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">
  <div class="admin-table-wrap">
    <div class="admin-table-head"><h3>🏷️ Active Coupons</h3></div>
    <table>
      <thead><tr><th>Code</th><th>Type</th><th>Discount</th><th>Min Order</th><th>Validity Period</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($coupons as $c): ?>
        <tr>
          <td><strong style="color:var(--gold-dark);font-size:14px;letter-spacing:1px;"><?= safeHtml($c['code']) ?></strong></td>
          <td><?= ucfirst($c['type']) ?></td>
          <td><strong><?= $c['type']==='percent' ? $c['discount'].'%' : '₹'.number_format($c['discount']) ?></strong></td>
          <td>₹<?= number_format($c['min_amount']) ?></td>
          <td style="font-size:11px; line-height:1.4;">
            <div style="color:var(--gray);">From: <?= $c['start_date'] ? date('d M Y',strtotime($c['start_date'])) : 'Anytime' ?></div>
            <div style="color:var(--gray);">To: <?= $c['end_date'] ? date('d M Y',strtotime($c['end_date'])) : 'No expiry' ?></div>
          </td>
          <td><span class="badge <?= $c['is_active']?'badge-green':'badge-red' ?>"><?= $c['is_active']?'Active':'Inactive' ?></span></td>
          <td style="white-space:nowrap;">
            <a href="coupons.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
            <a href="coupons.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-form">
    <h3 style="font-size:16px;font-weight:700;color:var(--charcoal);margin-bottom:18px;">
        <?= $editCoupon ? '📝 Edit Coupon' : '➕ Create Coupon' ?>
    </h3>
    <form method="POST">
      <?php if ($editCoupon): ?>
          <input type="hidden" name="id" value="<?= $editCoupon['id'] ?>"/>
      <?php endif; ?>

      <div class="form-group"><label>Coupon Code *</label><input type="text" name="code" value="<?= $editCoupon ? safeHtml($editCoupon['code']) : '' ?>" required placeholder="e.g. DIWALI20" style="text-transform:uppercase;"/></div>
      <div class="form-group">
        <label>Discount Type *</label>
        <select name="type">
          <option value="percent" <?= ($editCoupon && $editCoupon['type']==='percent') ? 'selected' : '' ?>>Percentage (%)</option>
          <option value="flat" <?= ($editCoupon && $editCoupon['type']==='flat') ? 'selected' : '' ?>>Flat Amount (₹)</option>
        </select>
      </div>
      <div class="form-group"><label>Discount Value *</label><input type="number" name="discount" value="<?= $editCoupon ? $editCoupon['discount'] : '' ?>" step="0.01" required placeholder="e.g. 10 for 10%"/></div>
      <div class="form-group"><label>Minimum Amount (₹)</label><input type="number" name="min_amount" value="<?= $editCoupon ? $editCoupon['min_amount'] : '0' ?>" step="0.01"/></div>
      <div class="form-group"><label>Max Uses</label><input type="number" name="max_uses" value="<?= $editCoupon ? $editCoupon['max_uses'] : '100' ?>"/></div>
      <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
          <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?= $editCoupon ? $editCoupon['start_date'] : '' ?>"/></div>
          <div class="form-group"><label>End Date</label><input type="date" name="end_date" value="<?= $editCoupon ? $editCoupon['end_date'] : '' ?>"/></div>
      </div>
      
      <?php if ($editCoupon): ?>
      <div class="form-group" style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="is_active" id="is_active" <?= $editCoupon['is_active'] ? 'checked' : '' ?> style="width:auto;"/>
          <label for="is_active" style="margin:0;">Is Active</label>
      </div>
      <?php endif; ?>

      <div style="display:flex; gap:10px;">
          <button type="submit" class="btn btn-gold" style="flex:1;"><i class="fas <?= $editCoupon ? 'fa-save' : 'fa-plus' ?>"></i> <?= $editCoupon ? 'Update' : 'Create' ?></button>
          <?php if ($editCoupon): ?>
              <a href="coupons.php" class="btn btn-outline">Cancel</a>
          <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

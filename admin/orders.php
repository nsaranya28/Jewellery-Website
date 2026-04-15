<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$_POST['status'], (int)$_POST['order_id']]);
  flashMessage('success', 'Order status updated!');
  header('Location: orders.php'); exit;
}

$status = $_GET['status'] ?? '';
$where  = $status ? "WHERE o.status='$status'" : '';
$orders = $pdo->query("SELECT o.*, u.name as user_name, u.email as user_email, (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) as item_count FROM orders o JOIN users u ON o.user_id=u.id $where ORDER BY o.created_at DESC")->fetchAll();

$statuses = ['confirmed','packed','shipped','out_for_delivery','delivered','cancelled'];
$adminTitle = 'Order Management';
include 'includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="orders.php" class="btn btn-sm <?= !$status?'btn-gold':'btn-outline' ?>">All</a>
    <?php foreach ($statuses as $s): ?>
    <a href="orders.php?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-gold':'btn-outline' ?>"><?= ucwords(str_replace('_',' ',$s)) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="admin-table-wrap">
  <div class="admin-table-head"><h3>📦 All Orders (<?= count($orders) ?>)</h3></div>
  <table>
    <thead><tr><th>Order ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><strong>#JW<?= str_pad($o['id'],6,'0',STR_PAD_LEFT) ?></strong></td>
        <td>
          <div style="font-size:13px;font-weight:500;"><?= safeHtml($o['user_name']) ?></div>
          <div style="font-size:11px;color:var(--gray);"><?= safeHtml($o['user_email']) ?></div>
        </td>
        <td><?= $o['item_count'] ?></td>
        <td><strong>₹<?= number_format($o['total']) ?></strong></td>
        <td><span class="badge badge-blue"><?= safeHtml($o['payment_method']) ?></span></td>
        <td>
          <form method="POST" style="display:flex;gap:6px;align-items:center;">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
            <select name="status" style="padding:5px 8px;border:1px solid #ededed;border-radius:8px;font-size:12px;outline:none;">
              <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="update_status" class="btn btn-sm btn-gold">✓</button>
          </form>
        </td>
        <td style="font-size:12px;color:var(--gray);"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
        <td>
          <a href="../order-tracking.php?order=<?= $o['id'] ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include 'includes/footer.php'; ?>

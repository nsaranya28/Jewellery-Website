<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

$feedbacks = $pdo->query("SELECT f.*, u.name, u.email FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC")->fetchAll();
$adminTitle = 'Customer Feedbacks';
include 'includes/header.php';
?>

<div class="admin-table-wrap">
  <div class="admin-table-head"><h3>💬 Customer Feedbacks (<?= count($feedbacks) ?>)</h3></div>
  <table>
    <thead><tr><th>User</th><th>Order ID</th><th>Rating</th><th>Comments</th><th>Date</th></tr></thead>
    <tbody>
      <?php if (empty($feedbacks)): ?>
      <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--gray);">No feedbacks received yet.</td></tr>
      <?php else: ?>
        <?php foreach ($feedbacks as $f): ?>
        <tr>
          <td>
            <div style="font-weight:500;"><?= safeHtml($f['name']) ?></div>
            <div style="font-size:12px;color:var(--gray);"><?= safeHtml($f['email']) ?></div>
          </td>
          <td>#JW<?= str_pad($f['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
          <td>
            <div style="color:var(--gold);font-size:14px;">
              <?php for($i=1; $i<=5; $i++) echo $i <= $f['rating'] ? '★' : '☆'; ?>
            </div>
            <div style="font-size:12px;color:var(--gray);"><?= $f['rating'] ?>/5</div>
          </td>
          <td style="max-width:300px;white-space:normal;">
            <div style="font-size:13px;line-height:1.5;"><?= empty($f['comments']) ? '<span style="color:var(--gray);font-style:italic;">No comments</span>' : nl2br(safeHtml($f['comments'])) ?></div>
          </td>
          <td style="font-size:12px;color:var(--gray);"><?= date('d M Y, h:i A', strtotime($f['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include 'includes/footer.php'; ?>

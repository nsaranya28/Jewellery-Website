<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// Toggle active
if (isset($_GET['toggle'])) {
  $user = $pdo->prepare("SELECT is_active FROM users WHERE id=?"); $user->execute([(int)$_GET['toggle']]);
  $u = $user->fetch();
  $pdo->prepare("UPDATE users SET is_active=? WHERE id=?")->execute([$u['is_active']?0:1,(int)$_GET['toggle']]);
  header('Location: users.php'); exit;
}

$users = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as order_count, (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=u.id AND status!='cancelled') as total_spent FROM users u ORDER BY u.created_at DESC")->fetchAll();
$adminTitle = 'User Management';
include 'includes/header.php';
?>

<div class="admin-table-wrap">
  <div class="admin-table-head"><h3>👥 Registered Users (<?= count($users) ?>)</h3></div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:34px;height:34px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;"><?= mb_substr($u['name'],0,1) ?></div>
            <span style="font-weight:500;"><?= safeHtml($u['name']) ?></span>
          </div>
        </td>
        <td><?= safeHtml($u['email']) ?></td>
        <td><?= safeHtml($u['phone'] ?? '—') ?></td>
        <td><?= $u['order_count'] ?></td>
        <td><strong>₹<?= number_format($u['total_spent']) ?></strong></td>
        <td style="font-size:12px;color:var(--gray);"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
        <td><span class="badge <?= $u['is_active']?'badge-green':'badge-red' ?>"><?= $u['is_active']?'Active':'Blocked' ?></span></td>
        <td>
          <a href="users.php?toggle=<?= $u['id'] ?>" class="btn btn-sm <?= $u['is_active']?'btn-red':'btn-outline' ?>">
            <?= $u['is_active'] ? '<i class="fas fa-ban"></i> Block' : '<i class="fas fa-check"></i> Unblock' ?>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include 'includes/footer.php'; ?>

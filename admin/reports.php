<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// Basic stats
$totalSales = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status='paid' OR payment_method='COD'")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Recent Orders for Report
$recentOrders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 15")->fetchAll();

$adminTitle = 'Reports';
include 'includes/header.php';
?>

<div class="admin-stats-grid" style="margin-bottom:30px;">
  <div class="stat-card">
    <div class="sc-icon" style="background:#e3fcef;color:#00875a;"><i class="fas fa-coins"></i></div>
    <div class="sc-info">
      <div class="sc-label">Total Revenue</div>
      <div class="sc-val">₹<?= number_format($totalSales, 2) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="sc-icon" style="background:#deebff;color:#0052cc;"><i class="fas fa-shopping-cart"></i></div>
    <div class="sc-info">
      <div class="sc-label">Total Orders</div>
      <div class="sc-val"><?= $totalOrders ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="sc-icon" style="background:#eae6ff;color:#403294;"><i class="fas fa-users"></i></div>
    <div class="sc-info">
      <div class="sc-label">Total Customers</div>
      <div class="sc-val"><?= $totalUsers ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="sc-icon" style="background:#fffae6;color:#ffab00;"><i class="fas fa-gem"></i></div>
    <div class="sc-info">
      <div class="sc-label">Products Active</div>
      <div class="sc-val"><?= $totalProducts ?></div>
    </div>
  </div>
</div>

<div class="admin-table-head">
  <h2 style="font-size:18px;font-weight:600;">Sales Report (Recent Activity)</h2>
  <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
</div>

<div class="admin-table-wrap">
  <table>
    <thead>
      <tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Date</th>
        <th>Amount</th>
        <th>Payment</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td>#ORD-<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
        <td><?= safeHtml($o['user_name']) ?></td>
        <td><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
        <td style="font-weight:600;">₹<?= number_format($o['total'], 2) ?></td>
        <td><span class="badge badge-outline"><?= strtoupper($o['payment_method']) ?></span></td>
        <td><span class="badge badge-green"><?= ucfirst($o['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<style>
.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}
.stat-card {
    background: var(--white);
    padding: 24px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid var(--gray-light);
}
.sc-icon {
    width: 50px; height: 50px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
}
.sc-label { font-size: 12px; color: var(--gray); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
.sc-val { font-size: 22px; font-weight: 700; color: var(--dark); margin-top: 2px; }
.badge-outline { background: transparent; border: 1px solid var(--gray-light); color: var(--gray); font-size: 10px; }
</style>

<?php include 'includes/footer.php'; ?>

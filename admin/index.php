<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';
requireAdmin();

// KPIs
$totalOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = (float)$pdo->query("SELECT SUM(total) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Recent orders
$recentOrders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();

// Monthly revenue data (last 6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
  $month = date('Y-m', strtotime("-$i months"));
  $label = date('M Y', strtotime("-$i months"));
  $revenue = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND status != 'cancelled'");
  $revenue->execute([$month]);
  $monthlyData[] = ['label'=>$label,'revenue'=>(float)$revenue->fetchColumn()];
}

// Low stock
$lowStock = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id WHERE p.stock <= 5 AND p.is_active=1 ORDER BY p.stock ASC LIMIT 5")->fetchAll();

$adminTitle = 'Dashboard';
include 'includes/header.php';
?>

<div class="admin-cards">
  <div class="admin-card">
    <div class="card-icon gold"><i class="fas fa-box" style="color:#c9a227;font-size:22px;"></i></div>
    <div><div class="card-num"><?= number_format($totalOrders) ?></div><div class="card-lbl">Total Orders</div></div>
  </div>
  <div class="admin-card green-border">
    <div class="card-icon green"><i class="fas fa-indian-rupee-sign" style="color:#27ae60;font-size:22px;"></i></div>
    <div><div class="card-num">₹<?= number_format($totalRevenue/1000,1) ?>K</div><div class="card-lbl">Total Revenue</div></div>
  </div>
  <div class="admin-card blue-border">
    <div class="card-icon blue"><i class="fas fa-gem" style="color:#2980b9;font-size:22px;"></i></div>
    <div><div class="card-num"><?= number_format($totalProducts) ?></div><div class="card-lbl">Active Products</div></div>
  </div>
  <div class="admin-card red-border">
    <div class="card-icon red"><i class="fas fa-users" style="color:#e74c3c;font-size:22px;"></i></div>
    <div><div class="card-num"><?= number_format($totalUsers) ?></div><div class="card-lbl">Registered Users</div></div>
  </div>
</div>

<!-- Revenue Chart -->
<div class="chart-wrap">
  <h3>📈 Monthly Revenue (Last 6 Months)</h3>
  <canvas id="revenueChart" height="100"></canvas>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">

  <!-- Recent Orders -->
  <div class="admin-table-wrap">
    <div class="admin-table-head">
      <h3>📦 Recent Orders</h3>
      <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <table>
      <thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($recentOrders as $order): ?>
        <tr>
          <td><strong>#JW<?= str_pad($order['id'],6,'0',STR_PAD_LEFT) ?></strong></td>
          <td><?= safeHtml($order['user_name']) ?></td>
          <td><strong>₹<?= number_format($order['total']) ?></strong></td>
          <td><span class="badge status-<?= $order['status'] ?>"><?= ucwords(str_replace('_',' ',$order['status'])) ?></span></td>
          <td><?= date('d M', strtotime($order['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Low Stock -->
  <div class="admin-table-wrap">
    <div class="admin-table-head">
      <h3>⚠️ Low Stock</h3>
      <a href="stock.php" class="btn btn-outline btn-sm">Manage</a>
    </div>
    <table>
      <thead><tr><th>Product</th><th>Stock</th></tr></thead>
      <tbody>
        <?php foreach ($lowStock as $p): ?>
        <tr>
          <td style="font-size:12px;"><?= safeHtml(truncate($p['name'],25)) ?></td>
          <td><span class="badge <?= $p['stock']==0?'badge-red':($p['stock']<=2?'badge-red':'badge-gold') ?>"><?= $p['stock'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($monthlyData,'label')) ?>,
    datasets: [{
      label: 'Revenue (₹)',
      data: <?= json_encode(array_column($monthlyData,'revenue')) ?>,
      backgroundColor: 'rgba(201,162,39,0.7)',
      borderColor: '#c9a227',
      borderWidth: 2,
      borderRadius: 8,
    }]
  },
  options: {
    responsive: true, plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { callback: v => '₹'+v.toLocaleString('en-IN') } } }
  }
});
</script>

<?php include 'includes/footer.php'; ?>

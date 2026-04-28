<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$orderId = (int)($_GET['order'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) { header('Location: my-orders.php'); exit; }

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
$items->execute([$order['id']]);
$items = $items->fetchAll();

$steps = [
  ['label'=>'Pending',   'icon'=>'<i class="fas fa-clock"></i>',   'statuses'=>['confirmed', 'packed']],
  ['label'=>'Shipped',   'icon'=>'<i class="fas fa-truck"></i>',   'statuses'=>['shipped', 'out_for_delivery']],
  ['label'=>'Delivered', 'icon'=>'<i class="fas fa-box-open"></i>', 'statuses'=>['delivered']],
];

$statusIdx = 0;
foreach ($steps as $idx => $step) {
    if (in_array($order['status'], $step['statuses'])) {
        $statusIdx = $idx;
        break;
    }
    // If the status is already past this step, we keep going
}
// Special case: if status is delivered, all steps should be marked as completed or active
if ($order['status'] === 'delivered') $statusIdx = 2;
elseif ($order['status'] === 'shipped' || $order['status'] === 'out_for_delivery') $statusIdx = 1;
else $statusIdx = 0;

$pageTitle = 'Order Tracking #JW' . str_pad($order['id'],6,'0',STR_PAD_LEFT) . ' — ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <div class="reveal active">
      <div class="section-label">📍 Real-time Tracking</div>
      <h1>Track Your Order</h1>
      <div class="breadcrumb">
        <a href="index.php">Home</a> <i class="fas fa-chevron-right"></i>
        <a href="my-orders.php">My Orders</a> <i class="fas fa-chevron-right"></i>
        <span>Order #JW<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
      </div>
    </div>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:900px;">
    
    <!-- Tracking Info Header -->
    <div class="glass" style="padding:32px; border-radius:var(--radius-lg); margin-bottom:30px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
      <div>
        <div style="font-size:12px; color:var(--gray); text-transform:uppercase; letter-spacing:1px; font-weight:600; margin-bottom:4px;">Estimated Delivery</div>
        <div style="font-family:var(--font-serif); font-size:24px; font-weight:700; color:var(--gold-dark);">
          <?= $order['status'] === 'delivered' ? 'Delivered' : date('d M, Y', strtotime($order['created_at'] . ' + 5 days')) ?>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:12px; color:var(--gray); text-transform:uppercase; letter-spacing:1px; font-weight:600; margin-bottom:4px;">Carrier</div>
        <div style="font-weight:600; color:var(--dark);">BlueDart Premium Service</div>
      </div>
    </div>

    <!-- The Timeline -->
    <div class="glass" style="padding:50px 30px; border-radius:var(--radius-lg); margin-bottom:30px; overflow-x:auto;">
      <div class="tracking-stepper">
        <?php foreach ($steps as $idx => $step): 
          $class = ($idx < $statusIdx) ? 'completed' : (($idx === $statusIdx) ? 'active' : '');
        ?>
        <div class="step-item <?= $class ?>">
          <div class="step-circle"><?= $step['icon'] ?></div>
          <div class="step-label"><?= $step['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
      <!-- Order Items -->
      <div class="glass" style="padding:24px; border-radius:var(--radius-lg);">
        <h3 style="font-family:var(--font-serif); font-size:20px; color:var(--dark); margin-bottom:16px; border-bottom:1px solid var(--ivory-dark); padding-bottom:12px;">Order Summary</h3>
        <?php foreach ($items as $item): ?>
        <div style="display:flex; align-items:center; gap:14px; padding:12px 0; border-bottom:1px dotted var(--gray-light);">
          <img src="<?= productImage($item['product_image']) ?>" alt="" style="width:50px; height:50px; border-radius:8px; object-fit:cover;">
          <div style="flex:1;">
            <div style="font-size:14px; font-weight:600;"><?= safeHtml($item['product_name']) ?></div>
            <div style="font-size:12px; color:var(--gray);">Qty: <?= $item['quantity'] ?></div>
          </div>
          <div style="font-weight:700; color:var(--gold-dark);"><?= money($item['price'] * $item['quantity']) ?></div>
        </div>
        <?php endforeach; ?>
        <div style="display:flex; justify-content:space-between; margin-top:16px; font-weight:700; font-family:var(--font-serif); font-size:18px;">
          <span>Total</span>
          <span><?= money($order['total']) ?></span>
        </div>
      </div>

      <!-- Shipping Address -->
      <div class="glass" style="padding:24px; border-radius:var(--radius-lg);">
        <h3 style="font-family:var(--font-serif); font-size:20px; color:var(--dark); margin-bottom:16px; border-bottom:1px solid var(--ivory-dark); padding-bottom:12px;">Delivery Details</h3>
        <p style="font-size:14px; color:var(--charcoal); line-height:1.7;">
          <i class="fas fa-location-dot" style="color:var(--gold); margin-right:8px;"></i>
          <?= nl2br(safeHtml($order['address_snapshot'])) ?>
        </p>
        <div style="margin-top:20px; padding:12px; background:var(--gold-pale); border-radius:var(--radius-sm); border-left:4px solid var(--gold);">
          <p style="font-size:12px; color:var(--gold-dark); font-weight:500; margin:0;">
            <i class="fas fa-info-circle"></i> Items will be delivered by contactless shipping. Please ensure someone is available at the address.
          </p>
        </div>
        <div style="margin-top:24px; display:flex; gap:10px;">
          <a href="invoice.php?order=<?= $order['id'] ?>" class="btn btn-sm btn-outline" style="flex:1; justify-content:center;"><i class="fas fa-file-pdf"></i> Receipt</a>
          <button onclick="window.print()" class="btn btn-sm btn-outline" style="flex:1; justify-content:center;"><i class="fas fa-print"></i> Print</button>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include 'includes/footer.php'; ?>

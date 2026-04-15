<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$orderId = (int)($_GET['order'] ?? 0);
$stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=? AND o.user_id=?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) { header('Location: my-orders.php'); exit; }

$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Invoice #JW<?= str_pad($order['id'],6,'0',STR_PAD_LEFT) ?></title>
  <style>
    body{font-family:'Poppins',Arial,sans-serif;background:#fdf8ee;margin:0;padding:20px;color:#2d2d2d;}
    .invoice{max-width:740px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.1);}
    .inv-header{background:linear-gradient(135deg,#1a1207,#3d2b00);color:#fff;padding:36px 40px;display:flex;justify-content:space-between;align-items:flex-start;}
    .inv-logo{font-size:26px;font-weight:700;color:#f5d16b;}
    .inv-logo span{font-size:13px;display:block;color:#ccc;font-weight:400;margin-top:4px;}
    .inv-title{font-size:13px;color:#aaa;text-align:right;}
    .inv-title strong{display:block;font-size:22px;color:#f5d16b;font-weight:700;}
    .inv-body{padding:36px 40px;}
    .inv-row{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-bottom:28px;}
    .inv-box h4{font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#c9a227;font-weight:600;margin-bottom:8px;}
    .inv-box p{font-size:13px;color:#555;line-height:1.7;margin:0;}
    .inv-box strong{color:#2d2d2d;}
    table{width:100%;border-collapse:collapse;margin:20px 0;}
    th{background:#fdf3d0;padding:12px 14px;font-size:11px;text-align:left;text-transform:uppercase;letter-spacing:1px;color:#8b6914;font-weight:600;}
    td{padding:12px 14px;border-bottom:1px solid #ededed;font-size:13px;}
    .totals{margin-top:10px;margin-left:auto;width:280px;}
    .tot-row{display:flex;justify-content:space-between;padding:8px 0;font-size:13px;border-bottom:1px solid #ededed;}
    .tot-row:last-child{border-color:#c9a227;font-size:16px;font-weight:700;color:#8b6914;padding-top:12px;}
    .inv-footer{background:#fdf3d0;padding:20px 40px;text-align:center;font-size:12px;color:#9a7a50;}
    .inv-footer strong{color:#8b6914;}
    .status-badge{display:inline-block;padding:4px 12px;border-radius:50px;font-size:11px;font-weight:700;}
    .no-print{text-align:center;padding:20px;}
    .btn-print{background:linear-gradient(135deg,#8b6914,#c9a227);color:#fff;border:none;padding:12px 30px;border-radius:50px;font-size:14px;font-weight:600;cursor:pointer;}
    @media print{.no-print{display:none;}body{background:#fff;}@page{margin:10mm;}}
  </style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> 🖨️ Print / Download PDF</button>
  <a href="order-tracking.php?order=<?= $order['id'] ?>" style="display:inline-block;margin-left:14px;font-size:14px;color:#8b6914;">← Back to Order</a>
</div>

<div class="invoice">
  <div class="inv-header">
    <div>
      <div class="inv-logo">💎 Jewels.com<span>Online Jewellery Shopping</span></div>
      <div style="font-size:12px;color:#aaa;margin-top:10px;">support@jewels.com &nbsp;|&nbsp; +91 98765 43210</div>
    </div>
    <div class="inv-title">
      <strong>INVOICE</strong>
      #JW<?= str_pad($order['id'],6,'0',STR_PAD_LEFT) ?><br/>
      <span style="font-size:12px;">Date: <?= date('d M Y', strtotime($order['created_at'])) ?></span>
    </div>
  </div>

  <div class="inv-body">
    <div class="inv-row">
      <div class="inv-box">
        <h4>Bill To</h4>
        <p>
          <strong><?= safeHtml($order['user_name']) ?></strong><br/>
          <?= safeHtml($order['user_email']) ?><br/>
          <?= $order['user_phone'] ? safeHtml($order['user_phone']) : '' ?>
        </p>
      </div>
      <div class="inv-box">
        <h4>Deliver To</h4>
        <p><?= safeHtml($order['address_snapshot']) ?></p>
      </div>
    </div>

    <div class="inv-row">
      <div class="inv-box">
        <h4>Payment Details</h4>
        <p>Method: <strong><?= safeHtml($order['payment_method']) ?></strong><br/>
        Status: <strong style="color:<?= $order['payment_status']==='paid'?'#27ae60':'#e67e22' ?>"><?= ucfirst($order['payment_status']) ?></strong></p>
      </div>
      <div class="inv-box">
        <h4>Order Status</h4>
        <p><strong><?= ucwords(str_replace('_',' ',$order['status'])) ?></strong></p>
        <?php if ($order['coupon_code']): ?>
        <p style="margin-top:6px;">Coupon: <strong style="color:#27ae60;"><?= safeHtml($order['coupon_code']) ?></strong></p>
        <?php endif; ?>
      </div>
    </div>

    <table>
      <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($items as $idx => $item): ?>
        <tr>
          <td><?= $idx+1 ?></td>
          <td><?= safeHtml($item['product_name']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td><?= money($item['price']) ?></td>
          <td><strong><?= money($item['price'] * $item['quantity']) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals">
      <div class="tot-row"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
      <?php if ($order['discount']): ?><div class="tot-row" style="color:#27ae60;"><span>Discount</span><span>−<?= money($order['discount']) ?></span></div><?php endif; ?>
      <div class="tot-row"><span>Shipping</span><span><?= $order['total'] - $order['subtotal'] + $order['discount'] > 0 ? money($order['total'] - $order['subtotal'] + $order['discount']) : 'Free' ?></span></div>
      <div class="tot-row"><span>Grand Total</span><span><?= money($order['total']) ?></span></div>
    </div>

    <div style="margin-top:30px;padding:16px;background:#fdf3d0;border:1px dashed #c9a227;border-radius:10px;font-size:12px;color:#8b6914;">
      <strong>Terms:</strong> All sales are final. Returns accepted within 30 days. Jewellery is BIS Hallmark certified. For queries: support@jewels.com
    </div>
  </div>

  <div class="inv-footer">
    <strong>Thank you for shopping at Jewels.com! 💎</strong><br/>
    Pure Gold, Pure Love — Handcrafted Jewellery for Every Milestone
  </div>
</div>

</body>
</html>

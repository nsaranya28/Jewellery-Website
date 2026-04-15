<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$productId = (int)($data['product_id'] ?? 0);
$quantity  = max(1, (int)($data['quantity'] ?? 1));

if (!$productId) { echo json_encode(['success'=>false,'error'=>'Invalid product.']); exit; }

$prod = $pdo->prepare("SELECT id, stock FROM products WHERE id=? AND is_active=1");
$prod->execute([$productId]);
$product = $prod->fetch();

if (!$product || $product['stock'] < 1) { echo json_encode(['success'=>false,'error'=>'Out of stock.']); exit; }

if (isLoggedIn()) {
  $chk = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
  $chk->execute([$_SESSION['user_id'], $productId]);
  $existing = $chk->fetch();
  if ($existing) {
    $pdo->prepare("UPDATE cart SET quantity=quantity+? WHERE id=?")->execute([$quantity, $existing['id']]);
  } else {
    $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$_SESSION['user_id'], $productId, $quantity]);
  }
  $countStmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id=?");
  $countStmt->execute([$_SESSION['user_id']]);
} else {
  $chk = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id=? AND product_id=?");
  $chk->execute([cartKey(), $productId]);
  $existing = $chk->fetch();
  if ($existing) {
    $pdo->prepare("UPDATE cart SET quantity=quantity+? WHERE id=?")->execute([$quantity, $existing['id']]);
  } else {
    $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?,?,?)")->execute([cartKey(), $productId, $quantity]);
  }
  $countStmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE session_id=?");
  $countStmt->execute([cartKey()]);
}

$count = (int)$countStmt->fetchColumn();
echo json_encode(['success'=>true,'count'=>$count]);

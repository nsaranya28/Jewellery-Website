<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$productId = (int)($data['product_id'] ?? 0);

if (!isLoggedIn()) { echo json_encode(['login'=>true]); exit; }
if (!$productId) { echo json_encode(['error'=>'Invalid.']); exit; }

$chk = $pdo->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
$chk->execute([$_SESSION['user_id'], $productId]);
$existing = $chk->fetch();

if ($existing) {
  $pdo->prepare("DELETE FROM wishlist WHERE id=?")->execute([$existing['id']]);
  $action = 'removed';
} else {
  $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?,?)")->execute([$_SESSION['user_id'], $productId]);
  $action = 'added';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?");
$countStmt->execute([$_SESSION['user_id']]);
$count = (int)$countStmt->fetchColumn();

echo json_encode(['action'=>$action,'count'=>$count]);

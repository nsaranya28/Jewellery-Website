<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE coupons");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($columns);

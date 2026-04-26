<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT email FROM users LIMIT 10");
while($row = $stmt->fetch()) {
    echo $row['email'] . "\n";
}

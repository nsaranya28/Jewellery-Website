<?php
require_once 'includes/db.php';

$results = [];

// 1. Create coupon_usage table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS coupon_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        coupon_code VARCHAR(50) NOT NULL,
        used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_usage (user_id, coupon_code),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    $results[] = "✅ coupon_usage table created (or already exists).";
} catch (Exception $e) {
    $results[] = "❌ coupon_usage: " . $e->getMessage();
}

// 2. Add start_date to coupons if missing
try {
    $pdo->exec("ALTER TABLE coupons ADD COLUMN start_date DATE AFTER code");
    $results[] = "✅ coupons.start_date column added.";
} catch (Exception $e) {
    $results[] = "ℹ️ coupons.start_date: " . $e->getMessage();
}

// 3. Add end_date to coupons if missing
try {
    $pdo->exec("ALTER TABLE coupons ADD COLUMN end_date DATE AFTER start_date");
    $results[] = "✅ coupons.end_date column added.";
} catch (Exception $e) {
    $results[] = "ℹ️ coupons.end_date: " . $e->getMessage();
}

// 4. Sync existing coupon usage from orders table into coupon_usage
try {
    $pdo->exec("INSERT IGNORE INTO coupon_usage (user_id, coupon_code)
        SELECT user_id, coupon_code FROM orders
        WHERE coupon_code IS NOT NULL AND coupon_code != '' AND status != 'cancelled'");
    $results[] = "✅ Synced existing coupon usage from orders table.";
} catch (Exception $e) {
    $results[] = "ℹ️ Sync: " . $e->getMessage();
}

foreach ($results as $r) {
    echo $r . "\n";
}
echo "\nSetup complete!";
?>
